<x-layouts.workspace title="Profile" description="Manage your QuickQuiz identity.">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,38rem)_minmax(16rem,1fr)] lg:items-start">
        <div class="flex flex-col gap-10">
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

        <section aria-labelledby="preferences-heading">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-accent-700">Experience settings</p>
            <h2 id="preferences-heading" class="mt-3 text-3xl font-black tracking-tight">Preferences</h2>
            <p class="mt-3 leading-7 text-muted">Choose how QuickQuiz looks and whether completion updates appear in your notification center.</p>

            <form method="POST" action="{{ route('profile.preferences.update') }}" class="mt-6 flex flex-col gap-5">
                @csrf
                @method('PATCH')

                <div class="flex flex-col gap-2">
                    <label for="theme_preference" class="text-sm font-bold text-ink">Color theme</label>
                    <select id="theme_preference" name="theme_preference" required class="min-h-12 rounded-xl border border-line bg-white px-4 py-3 text-ink shadow-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @foreach (\App\Enums\ThemePreference::cases() as $themePreference)
                            <option value="{{ $themePreference->value }}" @selected(old('theme_preference', $user->theme_preference->value) === $themePreference->value)>{{ $themePreference->label() }}</option>
                        @endforeach
                    </select>
                    @error('theme_preference')<p class="text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-white p-4 shadow-sm">
                    <input type="checkbox" name="notifications_enabled" value="1" class="mt-1 size-5 rounded border-line text-brand-600 focus:ring-brand-500" @checked(old('notifications_enabled', $user->notifications_enabled))>
                    <span><span class="block font-bold">In-app notifications</span><span class="mt-1 block text-sm leading-6 text-muted">Receive assessment completion and certificate updates inside QuickQuiz.</span></span>
                </label>

                <div><x-ui.button type="submit">Save preferences</x-ui.button></div>
            </form>
        </section>
        </div>

        <x-ui.panel as="article" class="p-6">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Workspace role</p>
            <p class="mt-3 text-2xl font-black">{{ $user->role->label() }}</p>
            <p class="mt-2 leading-7 text-muted">Roles are protected identity attributes and cannot be changed through profile input.</p>
            <div class="mt-6 flex flex-col items-start gap-2">
                <a href="{{ route('profile.security') }}" class="inline-flex min-h-11 items-center font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">View security activity</a>
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center font-bold text-muted underline decoration-line underline-offset-4 hover:text-ink">Return to workspace</a>
            </div>
        </x-ui.panel>
    </div>
</x-layouts.workspace>
