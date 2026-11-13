<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', [
            // unread first (NULL read_at sorts first ascending), newest first
            'messages' => ContactMessage::query()->orderByRaw('read_at IS NOT NULL')->latest('id')->paginate(15),
            'unread' => ContactMessage::whereNull('read_at')->count(),
        ]);
    }

    public function markRead(ContactMessage $message): RedirectResponse
    {
        $message->forceFill(['read_at' => $message->read_at ? null : now()])->save();

        return back()->with('success', $message->read_at ? 'Marked as read.' : 'Marked as unread.');
    }
}
