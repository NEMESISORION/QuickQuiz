<x-layouts.workspace title="Join live session" description="Join a secure QuickQuiz live session with a host code.">
    <section class="mx-auto max-w-xl">
        <x-ui.badge tone="accent">Live quiz</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Enter the room.</h1>
        <p class="mt-4 text-lg leading-8 text-muted">Ask your educator for the six-character code shown on their lobby screen.</p>

        <x-ui.panel class="mt-8 p-6 sm:p-8">
            <form method="POST" action="{{ route('learner.live-sessions.store') }}" class="flex flex-col gap-5">
                @csrf
                <div class="flex flex-col gap-2">
                    <label for="code" class="text-sm font-bold">Session code</label>
                    <input id="code" name="code" value="{{ old('code') }}" required maxlength="6" autocomplete="off" autocapitalize="characters" inputmode="text" class="min-h-16 rounded-2xl border border-line bg-white px-5 text-center font-mono text-2xl font-black uppercase tracking-[0.24em]" aria-describedby="code-help @error('code') code-error @enderror">
                    <p id="code-help" class="text-sm text-muted">Spaces and letter casing are handled automatically.</p>
                    @error('code')<p id="code-error" class="text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                </div>
                <x-ui.button type="submit" class="w-full">Join lobby</x-ui.button>
            </form>
        </x-ui.panel>
    </section>
</x-layouts.workspace>
