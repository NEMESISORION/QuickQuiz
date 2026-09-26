<x-layouts.auth
    title="Sign in"
    eyebrow="Welcome back"
    heading="Continue your work"
    intro="Return to your quizzes, attempts, and results in one focused workspace."
>
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.input label="Email address" name="email" type="email" autocomplete="email" inputmode="email" autofocus required :value="old('email')" />
        <x-ui.input label="Password" name="password" type="password" autocomplete="current-password" required revealable />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm font-semibold text-ink">
                <input name="remember" type="checkbox" value="1" class="size-4 rounded border-line accent-brand-600">
                Keep me signed in
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Forgot password?</a>
        </div>

        <x-ui.button type="submit" class="w-full">Sign in</x-ui.button>
    </form>

    <p class="mt-7 text-center text-sm text-muted">
        New to QuickQuiz?
        <a href="{{ route('register') }}" class="font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Create an account</a>
    </p>
</x-layouts.auth>
