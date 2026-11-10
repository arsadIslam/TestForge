<?php

use App\Models\Test;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->test = publishedTest();
    $this->board = app(LeaderboardService::class);
});

function names(array $entries): array
{
    return array_column($entries, 'name');
}

it('orders by score (highest first), then by time (fastest first)', function () {
    $slowTop = User::factory()->student()->create(['name' => 'Slow 9']);
    $fastTop = User::factory()->student()->create(['name' => 'Fast 9']);
    $low = User::factory()->student()->create(['name' => 'Low 2']);
    $mid = User::factory()->student()->create(['name' => 'Mid 5']);
    resultFor($this->test, $slowTop, 9, 500);
    resultFor($this->test, $fastTop, 9, 300);
    resultFor($this->test, $low, 2, 100);
    resultFor($this->test, $mid, 5, 200);

    $entries = $this->board->top($this->test);

    expect(names($entries))->toBe(['Fast 9', 'Slow 9', 'Mid 5', 'Low 2'])
        ->and(array_column($entries, 'rank_no'))->toBe([1, 2, 3, 4]);
});

it('ranks each student once, by their best attempt', function () {
    $ann = User::factory()->student()->create(['name' => 'Ann']);
    $bob = User::factory()->student()->create(['name' => 'Bob']);
    resultFor($this->test, $ann, 3, 100, attemptNo: 1);
    resultFor($this->test, $ann, 8, 400, attemptNo: 2); // her best
    resultFor($this->test, $bob, 6, 100);

    $entries = $this->board->top($this->test);

    expect(names($entries))->toBe(['Ann', 'Bob'])
        ->and($entries[0]['score'])->toBe('8.00')
        ->and($entries[0]['total_students'])->toBe(2);
});

it('gives equal results the same rank and then skips (RANK, not DENSE_RANK)', function () {
    foreach (['A' => 7, 'B' => 7, 'C' => 4] as $name => $score) {
        resultFor($this->test, User::factory()->student()->create(['name' => $name]), $score, 120);
    }

    expect(array_column($this->board->top($this->test), 'rank_no'))->toBe([1, 1, 3]);
});

it('calculates the percentile with PERCENT_RANK (share of students beaten)', function () {
    foreach ([10, 8, 6, 4, 2] as $i => $score) {
        resultFor($this->test, User::factory()->student()->create(['name' => "S{$i}"]), $score, 100);
    }

    expect(array_map('floatval', array_column($this->board->top($this->test), 'percentile')))
        ->toBe([100.0, 75.0, 50.0, 25.0, 0.0]);
});

it('shows the top 50 but can still find a student ranked lower', function () {
    foreach (range(1, 55) as $i) {
        resultFor($this->test, User::factory()->student()->create(), 100 - $i, 60);
    }
    $last = User::factory()->student()->create(['name' => 'Last']);
    resultFor($this->test, $last, 0, 60);

    expect($this->board->top($this->test))->toHaveCount(50);

    $mine = $this->board->rankOf($this->test, $last->id);
    expect($mine['rank_no'])->toBe(56)->and($mine['total_students'])->toBe(56);
});

it('caches the leaderboard and clears it when a new result is published', function () {
    resultFor($this->test, User::factory()->student()->create(), 5, 100);
    expect($this->board->top($this->test))->toHaveCount(1)
        ->and(Cache::has($this->board->cacheKey($this->test->id)))->toBeTrue();

    // A real submission: evaluate → ResultPublished → ClearLeaderboardCache.
    $service = app(AttemptService::class);
    $service->submit($service->start(User::factory()->student()->create(), $this->test));

    expect(Cache::has($this->board->cacheKey($this->test->id)))->toBeFalse()
        ->and($this->board->top($this->test))->toHaveCount(2);
});

it('shows the leaderboard page to students and 404s for drafts', function () {
    $me = User::factory()->student()->create(['name' => 'Me']);
    resultFor($this->test, $me, 5, 100);

    $this->actingAs($me)->get("/student/tests/{$this->test->slug}/leaderboard")
        ->assertOk()->assertSee('You are')->assertSee('#1', false);

    $draft = Test::factory()->create();
    $this->actingAs($me)->get("/student/tests/{$draft->slug}/leaderboard")->assertNotFound();
});
