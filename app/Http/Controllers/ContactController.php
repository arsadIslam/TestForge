<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('public.contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $thanks = redirect()->route('contact')->with('success', 'Thanks! Your message has been sent — we usually reply within two working days.');

        // A bot filled the hidden field: pretend it worked, store nothing.
        if ($request->isSpam()) {
            return $thanks;
        }

        $message = ContactMessage::create([...$request->safe()->except('website'), 'ip_address' => $request->ip()]);

        Notification::send(
            User::where('role', UserRole::Admin)->where('is_active', true)->get(),
            new ContactMessageReceived($message),
        );

        return $thanks;
    }
}
