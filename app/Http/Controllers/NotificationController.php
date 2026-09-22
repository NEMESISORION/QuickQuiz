<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        assert($user instanceof User);

        return view('notifications.index', [
            'notifications' => $user->notifications()
                ->latest('created_at')
                ->latest('id')
                ->paginate(20),
        ]);
    }
}
