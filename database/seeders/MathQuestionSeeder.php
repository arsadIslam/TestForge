<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\User;
use App\Services\QuestionService;
use Illuminate\Database\Seeder;

/**
 * A handful of real questions written with LaTeX (and one with chemistry notation), so the
 * demo shows maths rendering. Created through QuestionService, like the teacher form, so the
 * type (single/multiple) is derived from the correct options.
 */
class MathQuestionSeeder extends Seeder
{
    /**
     * topic slug => list of [body, explanation, difficulty, marks, options => [text => isCorrect]]
     */
    private const QUESTIONS = [
        'algebra' => [[
            'body' => 'Solve for \( x \): \( 2x + 3 = 11 \)',
            'explanation' => 'Subtract 3 from both sides: \( 2x = 8 \), so \( x = 4 \).',
            'difficulty' => 'easy', 'marks' => 1,
            'options' => ['\( x = 4 \)' => true, '\( x = 7 \)' => false, '\( x = 3 \)' => false, '\( x = 5.5 \)' => false],
        ], [
            'body' => 'What are the roots of \( x^2 - 5x + 6 = 0 \)? Select all that apply.',
            'explanation' => '\( x^2 - 5x + 6 = (x - 2)(x - 3) \), so \( x = 2 \) or \( x = 3 \).',
            'difficulty' => 'medium', 'marks' => 2,
            'options' => ['\( x = 2 \)' => true, '\( x = 3 \)' => true, '\( x = -2 \)' => false, '\( x = 6 \)' => false],
        ]],
        'trigonometry' => [[
            'body' => 'For any angle \( \theta \), what is \( \sin^2\theta + \cos^2\theta \)?',
            'explanation' => 'The Pythagorean identity: $$ \sin^2\theta + \cos^2\theta = 1 $$',
            'difficulty' => 'easy', 'marks' => 1,
            'options' => ['\( 1 \)' => true, '\( 0 \)' => false, '\( 2\sin\theta\cos\theta \)' => false, '\( \tan\theta \)' => false],
        ]],
        'probability' => [[
            'body' => 'Two fair dice are rolled. What is \( P(\text{sum} = 7) \)?',
            'explanation' => '6 of the 36 outcomes add up to 7: $$ P = \frac{6}{36} = \frac{1}{6} $$',
            'difficulty' => 'medium', 'marks' => 2,
            'options' => ['\( \frac{1}{6} \)' => true, '\( \frac{1}{12} \)' => false, '\( \frac{7}{36} \)' => false, '\( \frac{5}{36} \)' => false],
        ]],
        'geometry' => [[
            'body' => 'What is the area of a circle of radius \( r \)?',
            'explanation' => '\( A = \pi r^2 \). (\( 2\pi r \) is the circumference.)',
            'difficulty' => 'easy', 'marks' => 1,
            'options' => ['\( \pi r^2 \)' => true, '\( 2\pi r \)' => false, '\( \pi d \)' => false, '\( \frac{4}{3}\pi r^3 \)' => false],
        ]],
        'mechanics' => [[
            'body' => 'Which of these are equations of motion for constant acceleration? Select all that apply.',
            'explanation' => 'The SUVAT equations include \( v = u + at \) and \( s = ut + \tfrac{1}{2}at^2 \); the others are not valid.',
            'difficulty' => 'medium', 'marks' => 2,
            'options' => ['\( v = u + at \)' => true, '\( s = ut + \tfrac{1}{2}at^2 \)' => true, '\( F = ma^2 \)' => false, '\( v^2 = u - 2as \)' => false],
        ]],
        'acids-and-bases' => [[
            'body' => 'What is formed when \( \ce{HCl} \) reacts with \( \ce{NaOH} \)?',
            'explanation' => 'Neutralisation: \( \ce{HCl + NaOH -> NaCl + H2O} \)',
            'difficulty' => 'easy', 'marks' => 1,
            'options' => ['\( \ce{NaCl + H2O} \)' => true, '\( \ce{NaH + HClO} \)' => false, '\( \ce{Cl2 + NaOH} \)' => false, '\( \ce{Na2O + HCl} \)' => false],
        ]],
    ];

    public function run(QuestionService $questions): void
    {
        $teacher = User::where('email', 'teacher1@testforge.test')->firstOrFail();

        foreach (self::QUESTIONS as $topicSlug => $items) {
            $topic = Topic::where('slug', $topicSlug)->firstOrFail();

            foreach ($items as $item) {
                $questions->create($teacher, [
                    'topic_id' => $topic->id,
                    'body' => $item['body'],
                    'explanation' => $item['explanation'],
                    'difficulty' => $item['difficulty'],
                    'marks' => $item['marks'],
                    'negative_marks' => 0,
                    'is_active' => true,
                    'tag_names' => ['Formula Based'],
                    'options' => collect($item['options'])
                        ->map(fn (bool $correct, string $text) => ['text' => $text, 'is_correct' => $correct])
                        ->values()
                        ->all(),
                ]);
            }
        }
    }
}
