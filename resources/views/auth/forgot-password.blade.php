<x-layouts.auth
    title="Reset password"
    eyebrow="Account recovery"
    heading="Find your way back"
    intro="Enter your account email. If it matches an account, we’ll send a secure reset link."
>
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.input label="Email address" name="email" type="email" autocomplete="email" inputmode="email" autofocus required :value="old('email')" />

        <x-ui.button type="submit" class="w-full">Send reset link</x-ui.button>
    </form>

    @if (config('mail.default') === 'log')
        <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-warning" role="note">Email delivery is in local log mode. Reset links appear in <code>storage/logs/laravel.log</code>, not your inbox.</p>
    @endif

    <p class="mt-7 text-center text-sm">
        <a href="{{ route('login') }}" class="font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Back to sign in</a>
    </p>
</x-layouts.auth>
