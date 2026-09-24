<?php

namespace App\Http\Controllers;

use App\Events\Identity\PasswordChanged;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ProfilePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordChanged($user));

        return redirect()->route('profile.edit')->with('status', 'Password changed. Other signed-in sessions will be asked to sign in again.');
    }
}
