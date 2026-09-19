<x-layouts.auth
    title="Create account"
    eyebrow="Start with QuickQuiz"
    heading="Create your workspace"
    intro="Set up one secure account. Your educator or learner experience will be shaped in the next step."
>
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.input label="Full name" name="name" autocomplete="name" autofocus required :value="old('name')" />
        <x-ui.input label="Email address" name="email" type="email" autocomplete="email" inputmode="email" required :value="old('email')" />
        <x-ui.input label="Password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-guidance" />
        <p id="password-guidance" class="-mt-3 text-sm leading-6 text-muted">Use at least 10 characters with uppercase, lowercase, and a number.</p>
        <x-ui.input label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" required />

        <x-ui.button type="submit" class="mt-1 w-full">Create account</x-ui.button>
    </form>

    <p class="mt-7 text-center text-sm text-muted">
        Already have an account?
        <a href="{{ route('login') }}" class="font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Sign in</a>
    </p>
</x-layouts.auth>
