<?php

use App\Models\Attempt;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestSeries;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
    // added with payments, import and the public site
    ['admin@testforge.test', '/admin/series', 4],
    ['admin@testforge.test', '/admin/plans', 4],
    ['admin@testforge.test', '/admin/payments', 7],
    ['admin@testforge.test', '/admin/messages', 4],
    ['student1@testforge.test', '/student/series', 5],
    ['student1@testforge.test', '/student/series/ssc', 8],
    ['student1@testforge.test', '/student/purchases', 7],
    ['teacher1@testforge.test', '/teacher/questions/import', 4],
]);

it('renders the public homepage in a handful of queries', function () {
    Cache::forget('home:stats');
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get('/')->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(6);
});

it('keeps the same query count when lists get longer', function () {
    $student = User::where('email', 'student1@testforge.test')->first();
    $teacher = User::where('email', 'teacher1@testforge.test')->first();

    $before = [queriesFor($this, $student, '/student/tests'), queriesFor($this, $teacher, '/teacher/questions')];

    Test::factory()->published()->count(5)->create(['created_by' => $teacher->id]);
    Question::factory()->count(30)->withOptions()->create(['created_by' => $teacher->id]);

    $after = [queriesFor($this, $student, '/student/tests'), queriesFor($this, $teacher, '/teacher/questions')];

    expect($after)->toBe($before);
});

it('keeps the payments report flat as payments of both kinds grow', function () {
    $admin = User::where('email', 'admin@testforge.test')->first();
    $plan = Plan::first();
    $series = TestSeries::first();

    // A polymorphic eager load runs ONE query per type on the page (series, plan) — never one per row.
    $pay = function (int $count) use ($plan, $series): void {
        foreach (range(1, $count) as $i) {
            $item = $i % 2 ? $plan : $series;
            (new Payment)->forceFill([
                'user_id' => User::factory()->student()->create()->id, 'payable_type' => $item->getMorphClass(), 'payable_id' => $item->id,
                'amount_paise' => $item->price_paise, 'gateway' => 'test', 'status' => 'paid', 'paid_at' => now(),
            ])->save();
        }
    };

    $pay(2); // both types on the page
    $before = queriesFor($this, $admin, '/admin/payments');

    $pay(30); // many more rows of both types

    expect(queriesFor($this, $admin, '/admin/payments'))->toBe($before);
});
