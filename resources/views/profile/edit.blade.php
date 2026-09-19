<x-layouts.workspace title="Profile" description="Manage your QuickQuiz identity.">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,38rem)_minmax(16rem,1fr)] lg:items-start">
        <section>
            <p class="text-sm font-black uppercase tracking-[0.18em] text-brand-700">Account settings</p>
            <h1 class="mt-3 text-4xl font-black tracking-tight">Your profile</h1>
            <p class="mt-3 leading-7 text-muted">Keep the identity attached to your assessments accurate and recognizable.</p>

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="mt-8 flex flex-col gap-5">
                @csrf
                @method('PATCH')

                <x-ui.input label="Full name" name="name" autocomplete="name" required :value="old('name', $user->name)" />
                <x-ui.input label="Email address" name="email" type="email" autocomplete="email" inputmode="email" required :value="old('email', $user->email)" />

                <div><x-ui.button type="submit">Save profile</x-ui.button></div>
            </form>
        </section>

        <x-ui.panel as="article" class="p-6">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Workspace role</p>
            <p class="mt-3 text-2xl font-black">{{ $user->role->label() }}</p>
            <p class="mt-2 leading-7 text-muted">Roles are protected identity attributes and cannot be changed through profile input.</p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex min-h-11 items-center font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Return to workspace</a>
        </x-ui.panel>
    </div>
</x-layouts.workspace>
