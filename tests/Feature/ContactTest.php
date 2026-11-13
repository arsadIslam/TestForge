<?php

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function contactPayload(array $override = []): array
{
    return array_replace(['name' => 'Asha', 'email' => 'asha@example.com', 'subject' => 'Refund', 'message' => 'Please refund my SSC purchase.'], $override);
}

it('stores the message and notifies active admins', function () {
    $admin = User::factory()->admin()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();

    $this->post('/contact', contactPayload())->assertRedirect('/contact')->assertSessionHas('success');

    $message = ContactMessage::sole();
    expect($message->subject)->toBe('Refund')->and($message->ip_address)->toBe('127.0.0.1')->and($message->read_at)->toBeNull();
    Notification::assertSentTo($admin, ContactMessageReceived::class);
    Notification::assertNotSentTo($inactiveAdmin, ContactMessageReceived::class);
});

it('validates the form', function () {
    $this->post('/contact', contactPayload(['email' => 'nope', 'message' => 'short']))->assertSessionHasErrors(['email', 'message']);
    expect(ContactMessage::count())->toBe(0);
});

it('silently drops bot submissions caught by the honeypot', function () {
    $this->post('/contact', contactPayload(['website' => 'http://spam.example']))->assertRedirect('/contact')->assertSessionHas('success');

    expect(ContactMessage::count())->toBe(0);
    Notification::assertNothingSent();
});

it('rate-limits the form', function () {
    foreach (range(1, 3) as $i) {
        $this->post('/contact', contactPayload())->assertRedirect();
    }

    $this->post('/contact', contactPayload())->assertStatus(429);
    expect(ContactMessage::count())->toBe(3);
});

it('lets admins read and mark messages; nobody else', function () {
    $admin = User::factory()->admin()->create();
    $this->post('/contact', contactPayload());
    $message = ContactMessage::sole();

    $this->actingAs($admin)->get('/admin/messages')->assertOk()->assertSee('Please refund my SSC purchase.')->assertSee('1 unread');
    $this->actingAs($admin)->patch("/admin/messages/{$message->id}/read")->assertSessionHas('success', 'Marked as read.');
    expect($message->fresh()->read_at)->not->toBeNull();

    $this->actingAs(User::factory()->teacher()->create())->get('/admin/messages')->assertForbidden();
});
