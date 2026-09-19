<x-layouts.auth
    title="Choose your workspace"
    eyebrow="One last step"
    heading="How will you use QuickQuiz?"
    intro="Choose the workspace that matches your role. This keeps every screen focused on the job you need to do."
>
    <form method="POST" action="{{ route('onboarding.store') }}" class="flex flex-col gap-5">
        @csrf

        <fieldset class="flex flex-col gap-3">
            <legend class="sr-only">Choose your QuickQuiz role</legend>

            @foreach ($roles as $role)
                <label class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-line bg-white p-5 shadow-sm transition hover:border-brand-300 hover:bg-brand-50/50 has-checked:border-brand-600 has-checked:bg-brand-50 has-checked:ring-4 has-checked:ring-brand-100">
                    <input class="mt-1 size-4 shrink-0 accent-brand-600" type="radio" name="role" value="{{ $role->value }}" @checked(old('role') === $role->value) required>
                    <span>
                        <span class="block font-black text-ink">{{ $role->label() }}</span>
                        <span class="mt-1 block text-sm leading-6 text-muted">{{ $role->description() }}</span>
                    </span>
                </label>
            @endforeach

            @error('role')
                <p class="text-sm font-semibold text-danger">{{ $message }}</p>
            @enderror
        </fieldset>

        <p class="text-sm leading-6 text-muted">Your role controls workspace access and cannot be changed from profile settings.</p>
        <x-ui.button type="submit" class="w-full">Continue to my workspace</x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" class="min-h-11 text-sm font-bold text-muted underline decoration-line underline-offset-4 hover:text-ink">Sign out and decide later</button>
    </form>
</x-layouts.auth>
