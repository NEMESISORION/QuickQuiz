<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SelectRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        if ($user->role !== null) {
            return redirect()->route($user->role->dashboardRoute());
        }

        return view('onboarding.create', ['roles' => UserRole::cases()]);
    }

    public function store(SelectRoleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $role = $request->enum('role', UserRole::class);

        assert($user instanceof User);
        assert($role instanceof UserRole);

        $user->role = $role;
        $user->role_selected_at = now();
        $user->save();

        return redirect()->route($role->dashboardRoute());
    }
}
