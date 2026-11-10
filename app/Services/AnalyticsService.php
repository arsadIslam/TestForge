<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Read-only reporting queries. Key queries #2–#4 are written as raw SQL (with the
 * Query Builder version alongside) because they are the ones worth explaining
 * line by line; the simple summaries use the Query Builder directly.
 *
 * Only SCORED answers count (attempt_answers.is_correct IS NOT NULL), so an attempt
 * waiting in the queue never skews the numbers.
 */
class AnalyticsService
{
    /**
     * Key query #2 — subject-wise accuracy for one student, weakest subject first.
     *
     * Walks attempt_answers → attempts (whose answer?) → questions → topics → subjects
     * (which subject?) and groups by subject. AVG(is_correct) works because a boolean
     * is stored as 0/1, so the average IS the share of correct answers.
     *
     * Query Builder equivalent:
     *
     *   DB::table('attempt_answers as aa')
     *       ->join('attempts as a', 'a.id', '=', 'aa.attempt_id')
     *       ->join('questions as q', 'q.id', '=', 'aa.question_id')
     *       ->join('topics as t', 't.id', '=', 'q.topic_id')
     *       ->join('subjects as s', 's.id', '=', 't.subject_id')
     *       ->where('a.user_id', $userId)->whereNotNull('aa.is_correct')
     *       ->groupBy('s.id', 's.name')
     *       ->select('s.id as subject_id', 's.name as subject')
     *       ->selectRaw('COUNT(*) AS attempted, SUM(aa.is_correct) AS correct')
     *       ->selectRaw('ROUND(AVG(aa.is_correct) * 100, 2) AS accuracy')
     *       ->orderBy('accuracy')->get();
     *
     * @return list<array{subject_id: int, subject: string, attempted: int, correct: int, accuracy: string}>
     */
    public function subjectAccuracy(int $userId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT s.id AS subject_id,
                   s.name AS subject,
                   COUNT(*) AS attempted,
                   SUM(aa.is_correct) AS correct,
                   ROUND(AVG(aa.is_correct) * 100, 2) AS accuracy
            FROM attempt_answers aa
            JOIN attempts  a ON a.id = aa.attempt_id
            JOIN questions q ON q.id = aa.question_id
            JOIN topics    t ON t.id = q.topic_id
            JOIN subjects  s ON s.id = t.subject_id
            WHERE a.user_id = ?
              AND aa.is_correct IS NOT NULL
            GROUP BY s.id, s.name
            ORDER BY accuracy ASC, attempted DESC
            SQL, [$userId]);

        return array_map(fn (object $row): array => [
            'subject_id' => (int) $row->subject_id,
            'subject' => $row->subject,
            'attempted' => (int) $row->attempted,
            'correct' => (int) $row->correct,
            'accuracy' => $row->accuracy,
        ], $rows);
    }

    /**
     * Key query #3 — toughest questions in a test (lowest share of correct answers).
     *
     * HAVING (not WHERE) because it filters on an aggregate: a question answered by
     * only 2 students isn't "tough" just because both got it wrong, so it needs at
     * least $minAnswers answers before it is ranked.
     *
     * Aggregate FIRST, join LATER (Step 13): the first version grouped by
     * q.id, q.body, q.difficulty — body is a TEXT column, so MySQL built the temporary
     * table on disk and sorted every answer row by text: 1.6 s on 535k answers. Grouping
     * by the integer question_id in a derived table and joining questions only for the
     * 10 surviving rows takes it to a few hundred ms (see docs/performance.md).
     *
     * Query Builder equivalent:
     *
     *   $stats = DB::table('attempt_answers as aa')
     *       ->join('attempts as a', 'a.id', '=', 'aa.attempt_id')
     *       ->where('a.test_id', $testId)->whereNotNull('aa.is_correct')
     *       ->groupBy('aa.question_id')
     *       ->select('aa.question_id')
     *       ->selectRaw('COUNT(*) AS answered, ROUND(AVG(aa.is_correct) * 100, 2) AS correct_pct')
     *       ->havingRaw('COUNT(*) >= ?', [$minAnswers]);
     *
     *   DB::query()->fromSub($stats, 's')
     *       ->join('questions as q', 'q.id', '=', 's.question_id')
     *       ->select('q.id as question_id', 'q.body', 'q.difficulty', 's.answered', 's.correct_pct')
     *       ->orderBy('s.correct_pct')->orderByDesc('s.answered')->limit($limit)->get();
     *
     * @return list<array{question_id: int, body: string, difficulty: string, answered: int, correct_pct: string}>
     */
    public function toughestQuestions(int $testId, int $minAnswers = 10, int $limit = 10): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT q.id AS question_id,
                   q.body,
                   q.difficulty,
                   s.answered,
                   s.correct_pct
            FROM (
                SELECT aa.question_id,
                       COUNT(*) AS answered,
                       ROUND(AVG(aa.is_correct) * 100, 2) AS correct_pct
                FROM attempt_answers aa
                JOIN attempts a ON a.id = aa.attempt_id
                WHERE a.test_id = ?
                  AND aa.is_correct IS NOT NULL
                GROUP BY aa.question_id
                HAVING COUNT(*) >= ?
            ) s
            JOIN questions q ON q.id = s.question_id
            ORDER BY s.correct_pct ASC, s.answered DESC
            LIMIT ?
            SQL, [$testId, $minAnswers, $limit]);

        return array_map(fn (object $row): array => [
            'question_id' => (int) $row->question_id,
            'body' => $row->body,
            'difficulty' => $row->difficulty,
            'answered' => (int) $row->answered,
            'correct_pct' => $row->correct_pct,
        ], $rows);
    }

    /**
     * Key query #4 — published tests that nobody has attempted yet.
     *
     * LEFT JOIN keeps every test even when it has no attempt; for those tests the
     * attempt columns are NULL, so "a.id IS NULL" keeps exactly the unattempted ones.
     * (The same thing as NOT EXISTS (SELECT 1 FROM attempts WHERE test_id = t.id).)
     *
     * Query Builder equivalent:
     *
     *   DB::table('tests as t')
     *       ->join('users as u', 'u.id', '=', 't.created_by')
     *       ->leftJoin('attempts as a', 'a.test_id', '=', 't.id')
     *       ->where('t.status', 'published')->whereNull('t.deleted_at')->whereNull('a.id')
     *       ->when($teacherId, fn ($q) => $q->where('t.created_by', $teacherId))
     *       ->select('t.id', 't.title', 't.slug', 't.published_at', 'u.name as teacher')
     *       ->orderByDesc('t.published_at')->get();
     *
     *   // or with Eloquent: Test::published()->doesntHave('attempts')->get();
     *
     * @return list<array{id: int, title: string, slug: string, published_at: ?string, teacher: string}>
     */
    public function publishedTestsWithoutAttempts(?int $teacherId = null): array
    {
        $teacherFilter = $teacherId !== null ? 'AND t.created_by = ?' : '';

        $rows = DB::select(<<<SQL
            SELECT t.id, t.title, t.slug, t.published_at, u.name AS teacher
            FROM tests t
            JOIN users u ON u.id = t.created_by
            LEFT JOIN attempts a ON a.test_id = t.id
            WHERE t.status = 'published'
              AND t.deleted_at IS NULL
              AND a.id IS NULL
              {$teacherFilter}
            ORDER BY t.published_at DESC
            SQL, $teacherId !== null ? [$teacherId] : []);

        return array_map(fn (object $row): array => [
            'id' => (int) $row->id,
            'title' => $row->title,
            'slug' => $row->slug,
            'published_at' => $row->published_at,
            'teacher' => $row->teacher,
        ], $rows);
    }

    /**
     * Headline numbers for one test — a single aggregate query over results.
     *
     * @return array{attempts: int, students: int, avg_score: ?string, max_score: ?string, min_score: ?string, pass_rate: ?string, avg_time_seconds: ?int}
     */
    public function testSummary(int $testId): array
    {
        $row = DB::table('results')
            ->where('test_id', $testId)
            ->selectRaw('COUNT(*) AS attempts')
            ->selectRaw('COUNT(DISTINCT user_id) AS students')
            ->selectRaw('ROUND(AVG(score), 2) AS avg_score')
            ->selectRaw('MAX(score) AS max_score')
            ->selectRaw('MIN(score) AS min_score')
            ->selectRaw('ROUND(AVG(is_passed) * 100, 2) AS pass_rate')
            ->selectRaw('ROUND(AVG(time_taken_seconds)) AS avg_time_seconds')
            ->first();

        return [
            'attempts' => (int) $row->attempts,
            'students' => (int) $row->students,
            'avg_score' => $row->avg_score,
            'max_score' => $row->max_score,
            'min_score' => $row->min_score,
            'pass_rate' => $row->pass_rate,
            'avg_time_seconds' => $row->avg_time_seconds !== null ? (int) $row->avg_time_seconds : null,
        ];
    }

    /**
     * A student's overall numbers. Scores are turned into percentages of each test's
     * total, so tests with different total marks can be averaged fairly.
     *
     * @return array{results: int, tests: int, avg_percent: ?string, best_percent: ?string, pass_rate: ?string}
     */
    public function studentSummary(int $userId): array
    {
        $row = DB::table('results as r')
            ->join('tests as t', 't.id', '=', 'r.test_id')
            ->where('r.user_id', $userId)
            ->selectRaw('COUNT(*) AS results')
            ->selectRaw('COUNT(DISTINCT r.test_id) AS tests')
            ->selectRaw('ROUND(AVG(r.score / NULLIF(t.total_marks, 0) * 100), 2) AS avg_percent')
            ->selectRaw('ROUND(MAX(r.score / NULLIF(t.total_marks, 0) * 100), 2) AS best_percent')
            ->selectRaw('ROUND(AVG(r.is_passed) * 100, 2) AS pass_rate')
            ->first();

        return [
            'results' => (int) $row->results,
            'tests' => (int) $row->tests,
            'avg_percent' => $row->avg_percent,
            'best_percent' => $row->best_percent,
            'pass_rate' => $row->pass_rate,
        ];
    }
}
