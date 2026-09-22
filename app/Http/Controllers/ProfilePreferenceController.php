<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePreferencesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ProfilePreferenceController extends Controller
{
    public function update(UpdateProfilePreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        $user->update($request->safe()->only(['theme_preference', 'notifications_enabled']));

        return redirect()->route('profile.edit')->with('status', 'Preferences updated.');
    }
}
