<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SecurityActivityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        Gate::authorize('viewSecurityActivity', $user);

        $events = $user->identityAuditEvents()
            ->latest()
            ->limit(20)
            ->get();

        return view('profile.security-activity', [
            'events' => $events,
            'user' => $user,
        ]);
    }
}
