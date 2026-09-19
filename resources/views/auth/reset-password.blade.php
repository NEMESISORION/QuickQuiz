<x-layouts.auth
    title="Choose a new password"
    eyebrow="Account recovery"
    heading="Choose a new password"
    intro="Use a strong, unique password to secure your QuickQuiz account."
>
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <x-ui.input label="Email address" name="email" type="email" autocomplete="email" inputmode="email" required :value="old('email', $email)" />
        <x-ui.input label="New password" name="password" type="password" autocomplete="new-password" autofocus required aria-describedby="password-guidance" />
        <p id="password-guidance" class="-mt-3 text-sm leading-6 text-muted">Use at least 10 characters with uppercase, lowercase, and a number.</p>
        <x-ui.input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">Reset password</x-ui.button>
    </form>
</x-layouts.auth>
