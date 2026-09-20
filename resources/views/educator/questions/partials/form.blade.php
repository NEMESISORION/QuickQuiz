@php
    $existingOptions = $question->relationLoaded('answerOptions') ? $question->answerOptions : collect();
    $correctOption = old('correct_option', $existingOptions->search(fn ($option) => $option->is_correct));
    $selectedType = old('type', $question->type?->value ?? \App\Enums\QuestionType::MultipleChoice->value);
@endphp

@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4" role="alert">
        <p class="font-black text-danger">Review the highlighted question fields.</p>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="mt-7 flex flex-col gap-7" x-data="{ questionType: @js($selectedType) }">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <x-ui.panel class="p-6 sm:p-8">
        <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_12rem]">
            <div class="flex flex-col gap-2">
                <label for="type" class="text-sm font-bold">Question type</label>
                <select id="type" name="type" x-model="questionType" class="min-h-12 rounded-xl border border-line bg-white px-4 py-3 focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @foreach ($questionTypes as $questionType)
                        <option value="{{ $questionType->value }}">{{ $questionType->label() }}</option>
                    @endforeach
                </select>
                @error('type')<p class="text-sm font-semibold text-danger">{{ $message }}</p>@enderror
            </div>
            <x-ui.input label="Points" name="points" type="number" min="1" max="100" required :value="old('points', $question->points)" />
        </div>

        <div class="mt-5 flex flex-col gap-5">
            <x-ui.textarea label="Question prompt" name="prompt" required maxlength="5000" :value="old('prompt', $question->prompt)" placeholder="Ask one clear, focused question." />
            <x-ui.textarea label="Answer explanation (optional)" name="explanation" maxlength="5000" :value="old('explanation', $question->explanation)" placeholder="Explain why the correct answer is right." />
        </div>
    </x-ui.panel>

    <x-ui.panel class="p-6 sm:p-8">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Answer key</p>
        <h2 class="mt-2 text-2xl font-black">Choose exactly one correct answer</h2>

        <div x-show="questionType === 'multiple_choice'" class="mt-6 flex flex-col gap-3">
            @for ($index = 0; $index < 4; $index++)
                <label class="grid cursor-pointer grid-cols-[auto_minmax(0,1fr)] items-center gap-3 rounded-xl border border-line p-3 hover:border-brand-200">
                    <input type="radio" name="correct_option" value="{{ $index }}" class="size-5 text-brand-600 focus:ring-brand-500" @checked((string) $correctOption === (string) $index)>
                    <span class="sr-only">Mark option {{ $index + 1 }} correct</span>
                    <input name="options[{{ $index }}][content]" value="{{ old('options.'.$index.'.content', $existingOptions->get($index)?->content) }}" maxlength="1000" class="min-h-11 rounded-lg border border-line px-3 focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="Option {{ $index + 1 }}{{ $index > 1 ? ' (optional)' : '' }}">
                </label>
            @endfor
        </div>

        <div x-cloak x-show="questionType === 'true_false'" class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach (['True', 'False'] as $index => $label)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line p-5 hover:border-brand-200">
                    <input type="radio" name="correct_option" value="{{ $index }}" class="size-5 text-brand-600 focus:ring-brand-500" @checked((string) $correctOption === (string) $index)>
                    <span class="font-black">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        @error('options')<p class="mt-3 text-sm font-semibold text-danger">{{ $message }}</p>@enderror
        @error('correct_option')<p class="mt-3 text-sm font-semibold text-danger">{{ $message }}</p>@enderror
    </x-ui.panel>

    <div class="flex flex-wrap gap-3">
        <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
        <x-ui.button href="{{ route('educator.quizzes.show', $quiz) }}" variant="secondary">Cancel</x-ui.button>
    </div>
</form>
