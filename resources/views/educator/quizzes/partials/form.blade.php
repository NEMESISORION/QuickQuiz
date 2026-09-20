@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4" role="alert" aria-labelledby="form-errors-title">
        <p id="form-errors-title" class="font-black text-danger">Review the highlighted fields.</p>
        <p class="mt-1 text-sm text-red-800">Your draft has not been saved yet.</p>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="mt-7 flex flex-col gap-7">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-ui.panel class="p-6 sm:p-8">
        <div class="max-w-2xl">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Assessment purpose</p>
            <h2 class="mt-2 text-2xl font-black">Name the learning goal</h2>
            <p class="mt-2 leading-7 text-muted">Use a clear title and description so learners know exactly what this quiz measures.</p>
        </div>
        <div class="mt-6 flex flex-col gap-5">
            <x-ui.input label="Quiz title" name="title" required maxlength="160" :value="old('title', $quiz->title)" placeholder="e.g. JavaScript fundamentals checkpoint" />
            <x-ui.textarea label="Description" name="description" maxlength="5000" :value="old('description', $quiz->description)" placeholder="What should learners understand before taking this quiz?" />
        </div>
    </x-ui.panel>

    <x-ui.panel class="p-6 sm:p-8">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Assessment rules</p>
        <h2 class="mt-2 text-2xl font-black">Set fair expectations</h2>
        <p class="mt-2 max-w-2xl leading-7 text-muted">These rules will be captured when learners begin an attempt, keeping delivery and scoring consistent.</p>

        <div class="mt-6 grid gap-5 sm:grid-cols-3">
            <x-ui.input label="Time limit (minutes)" name="duration_minutes" type="number" inputmode="numeric" min="1" max="480" :value="old('duration_minutes', $quiz->duration_minutes)" placeholder="No limit" />
            <x-ui.input label="Passing score (%)" name="pass_percentage" type="number" inputmode="numeric" required min="1" max="100" :value="old('pass_percentage', $quiz->pass_percentage)" />
            <x-ui.input label="Maximum attempts" name="max_attempts" type="number" inputmode="numeric" required min="1" max="100" :value="old('max_attempts', $quiz->max_attempts)" />
        </div>

        <div class="mt-5 flex flex-col gap-2">
            <label for="review_policy" class="text-sm font-bold text-ink">Answer review</label>
            <select id="review_policy" name="review_policy" required @class([
                'min-h-12 w-full rounded-xl border bg-white px-4 py-3 text-base text-ink shadow-sm transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100',
                'border-danger' => $errors->has('review_policy'),
                'border-line' => ! $errors->has('review_policy'),
            ]) @if ($errors->has('review_policy')) aria-invalid="true" aria-describedby="review_policy-error" @endif>
                @foreach ($reviewPolicies as $reviewPolicy)
                    <option value="{{ $reviewPolicy->value }}" @selected(old('review_policy', $quiz->review_policy?->value) === $reviewPolicy->value)>{{ $reviewPolicy->label() }}</option>
                @endforeach
            </select>
            @error('review_policy')<p id="review_policy-error" class="text-sm font-semibold text-danger">{{ $message }}</p>@enderror
        </div>
    </x-ui.panel>

    <x-ui.panel class="p-6 sm:p-8">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Delivery behavior</p>
        <h2 class="mt-2 text-2xl font-black">Choose how each attempt feels</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition hover:border-brand-200 hover:bg-brand-50/60">
                <input type="checkbox" name="shuffle_questions" value="1" class="mt-1 size-5 rounded border-line text-brand-600 focus:ring-brand-500" @checked(old('shuffle_questions', $quiz->shuffle_questions))>
                <span><span class="block font-bold">Shuffle questions</span><span class="mt-1 block text-sm leading-6 text-muted">Present the questions in a different order for each attempt.</span></span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition hover:border-brand-200 hover:bg-brand-50/60">
                <input type="checkbox" name="shuffle_answers" value="1" class="mt-1 size-5 rounded border-line text-brand-600 focus:ring-brand-500" @checked(old('shuffle_answers', $quiz->shuffle_answers))>
                <span><span class="block font-bold">Shuffle answer choices</span><span class="mt-1 block text-sm leading-6 text-muted">Reduce position bias while keeping each answer attached to its question.</span></span>
            </label>
        </div>
    </x-ui.panel>

    <div class="flex flex-wrap items-center gap-3">
        <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
        <x-ui.button href="{{ $cancelUrl }}" variant="secondary">Cancel</x-ui.button>
        <p class="w-full text-sm text-muted sm:ml-auto sm:w-auto">Saving keeps this quiz as a private draft.</p>
    </div>
</form>
