<?php

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
| N+1 guard. Two layers:
|  1. Model::preventLazyLoading() is on outside production, so any lazy-loaded relation
|     inside a list throws an exception — these page loads would fail.
|  2. Each list page must stay within a small query budget on the full demo data,
|     and the count must NOT grow when more rows are added.
*/

beforeEach(fn () => $this->seed());

function queriesFor(object $t, User $user, string $url): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $t->actingAs($user)->get($url)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('renders every list page within its query budget', function (string $email, string $url, int $budget) {
    $user = User::where('email', $email)->firstOrFail();
    // Ids are looked up, never hard-coded: InnoDB does not roll back AUTO_INCREMENT, so
    // after earlier tests the seeded rows have different ids every run.
    $url = strtr($url, [
        '{attempt}' => (string) Attempt::where('user_id', $user->id)->value('id'),
        '{maths}' => (string) Test::where('slug', 'mathematics-mock-test-1')->value('id'),
    ]);

    expect(queriesFor($this, $user, $url))->toBeLessThanOrEqual($budget);
})->with([
    ['admin@testforge.test', '/admin', 8],
    ['admin@testforge.test', '/admin/users', 4],
    ['admin@testforge.test', '/admin/subjects', 4],
    ['admin@testforge.test', '/admin/topics', 6],
    ['admin@testforge.test', '/admin/reports', 6],
    ['admin@testforge.test', '/admin/reports/tests/{maths}', 10],
    ['teacher1@testforge.test', '/teacher', 10],
    ['teacher1@testforge.test', '/teacher/questions', 10],
    ['teacher1@testforge.test', '/teacher/tests', 4],
    ['teacher1@testforge.test', '/teacher/tests/{maths}', 8],
    ['teacher1@testforge.test', '/teacher/tests/{maths}/analytics', 10],
    ['student1@testforge.test', '/student', 10],
    ['student1@testforge.test', '/student/tests', 6],
    ['student1@testforge.test', '/student/analytics', 5],
    ['student1@testforge.test', '/student/tests/mathematics-mock-test-1/leaderboard', 6],
    ['student1@testforge.test', '/student/attempts/{attempt}/result', 11],
]);

it('keeps the same query count when lists get longer', function () {
    $student = User::where('email', 'student1@testforge.test')->first();
    $teacher = User::where('email', 'teacher1@testforge.test')->first();

    $before = [queriesFor($this, $student, '/student/tests'), queriesFor($this, $teacher, '/teacher/questions')];

    Test::factory()->published()->count(5)->create(['created_by' => $teacher->id]);
    Question::factory()->count(30)->withOptions()->create(['created_by' => $teacher->id]);

    $after = [queriesFor($this, $student, '/student/tests'), queriesFor($this, $teacher, '/teacher/questions')];

    expect($after)->toBe($before);
});
