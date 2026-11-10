<?php

use App\Services\ScoringService;

/*
| The scoring rule on its own — no app, no database (ScoringService::scoreAnswer is pure).
| +3 for a correct answer, −1 for a wrong one.
*/

dataset('answers', [
    'single: correct option' => [[5], [5], true, 3.0],
    'single: wrong option' => [[6], [5], false, -1.0],
    'multi: exact set' => [[1, 2], [1, 2], true, 3.0],
    'multi: exact set, other order' => [[2, 1], [1, 2], true, 3.0],
    'multi: missing one (no partial marks)' => [[1], [1, 2], false, -1.0],
    'multi: one extra wrong option' => [[1, 2, 3], [1, 2], false, -1.0],
    'multi: right size, wrong option' => [[1, 3], [1, 2], false, -1.0],
    'string ids and duplicates are normalised' => [['1', '2', 2], [1, 2], true, 3.0],
    'empty selection is never correct' => [[], [1], false, -1.0],
]);

it('scores an answer', function (array $selected, array $correct, bool $isCorrect, float $marks) {
    $outcome = (new ScoringService)->scoreAnswer($selected, $correct, marks: 3, negativeMarks: 1);

    expect($outcome)->toBe(['is_correct' => $isCorrect, 'marks' => $marks]);
})->with('answers');

it('gives zero, not a negative, when there is no negative marking', function () {
    expect((new ScoringService)->scoreAnswer([9], [1], 2, 0)['marks'])->toEqual(0.0);
});
