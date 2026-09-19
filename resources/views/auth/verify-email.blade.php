<x-layouts.auth
    title="Verify your email"
    eyebrow="Secure your account"
    heading="Check your inbox"
    intro="We sent a verification link to your email address. Confirm it before choosing your workspace."
>
    @if (session('status') === 'verification-link-sent')
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">
            A fresh verification link has been sent.
        </div>
    @endif

    <div class="rounded-2xl border border-line bg-white p-5 shadow-sm">
        <p class="text-sm leading-6 text-muted">The link is time-limited. If it has expired or did not arrive, request another one below.</p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-5">
            @csrf
            <x-ui.button type="submit" class="w-full">Resend verification email</x-ui.button>
        </form>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" class="min-h-11 text-sm font-bold text-muted underline decoration-line underline-offset-4 hover:text-ink">Sign out and use another account</button>
    </form>
</x-layouts.auth>
