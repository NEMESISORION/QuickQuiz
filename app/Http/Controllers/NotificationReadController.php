<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('status', 'Notifications marked as read.');
    }
}
