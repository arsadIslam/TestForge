<?php

use App\Models\Question;
use App\Models\TestSeries;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('shows every public page to guests with the logo and the legal links', function (string $url, string $text) {
    $page = $this->get($url)->assertOk()->assertSee($text);

    $page->assertSee('tf-logo', false)                    // the animated logo
        ->assertSee(route('legal.terms'), false)         // footer links Razorpay looks for
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('legal.refunds'), false)
        ->assertSee(route('contact'), false);
})->with([
    'home' => ['/', 'Forge your score'],
    'features' => ['/features', 'How scoring works'],
    'contact' => ['/contact', 'Contact us'],
    'terms' => ['/terms', 'Terms of Service'],
    'privacy' => ['/privacy', 'Privacy Policy'],
    'refunds' => ['/refunds', 'Refund & Cancellation Policy'],
]);

it('marks the legal pages as templates to review', function (string $url) {
    $this->get($url)->assertSee('Template text — review before going live.');
})->with(['/terms', '/privacy', '/refunds']);

it('shows real numbers and only active series on the homepage', function () {
    Cache::forget('home:stats');
    Question::factory()->count(3)->withOptions()->create();
    User::factory()->student()->count(2)->create();
    TestSeries::factory()->create(['name' => 'SSC Mains']);
    TestSeries::factory()->inactive()->create(['name' => 'Retired Series']);

    $this->get('/')->assertOk()
        ->assertViewHas('stats', fn (array $s) => $s['questions'] === 3 && $s['students'] === 2)
        ->assertSee('SSC Mains')
        ->assertDontSee('Retired Series');
});

it('greets signed-in users with their dashboard', function () {
    $this->actingAs(User::factory()->student()->create())->get('/')->assertSee(route('dashboard'), false)->assertDontSee('Start practising free');
});

it('uses the new logo on the login page and in the panels', function () {
    $this->get('/login')->assertOk()->assertSee('tf-logo', false)->assertSee('Practise under real exam conditions');
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSee('tf-logo', false)->assertSee('Admin panel');
    $this->actingAs(User::factory()->student()->create())->get('/student')->assertSee('tf-logo', false);
});

it('renders branded error pages', function () {
    $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found')->assertSee('tf-logo', false);

    $this->actingAs(User::factory()->student()->create())->get('/admin')
        ->assertForbidden()->assertSee('Access denied')->assertSee('You do not have access to this area.');
});

it('ships a logo animation that respects reduced motion', function () {
    $css = collect(glob(public_path('build/assets/app-*.css')))->map(fn ($f) => file_get_contents($f))->implode('');

    expect($css)->toContain('tf-strike')->toContain('prefers-reduced-motion')
        ->and(file_get_contents(public_path('favicon.svg')))->toContain('<svg');
});
