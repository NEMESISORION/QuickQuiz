document.querySelector('[data-print-page]')?.addEventListener('click', () => window.print());

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented || !(event.target instanceof HTMLFormElement) || !event.target.checkValidity()) return;

    event.target.setAttribute('aria-busy', 'true');
    window.requestAnimationFrame(() => {
        event.target.querySelectorAll('[type="submit"]').forEach((button) => {
            button.disabled = true;
        });
    });
});

const parseJson = (value, fallback) => {
    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
};

document.querySelectorAll('[data-question-form]').forEach((form) => {
    const type = form.querySelector('[data-question-type]');
    if (!(type instanceof HTMLSelectElement)) return;

    const update = () => form.querySelectorAll('[data-question-options]').forEach((options) => {
        options.hidden = options.dataset.questionOptions !== type.value;
    });

    type.addEventListener('change', update);
    update();
});

document.querySelectorAll('[data-live-session-sync]').forEach((container) => {
    const stateUrl = container.dataset.stateUrl;
    const initialVersion = container.dataset.initialVersion;
    let polling = false;

    if (!stateUrl || !initialVersion) return;

    const timer = window.setInterval(async () => {
        if (polling || document.hidden) return;
        polling = true;

        try {
            const response = await fetch(stateUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;

            const payload = await response.json();
            if (payload.version !== initialVersion) window.location.reload();
        } finally {
            polling = false;
        }
    }, 2000);

    window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
});

document.querySelectorAll('[data-quiz-attempt]').forEach((container) => {
    const answerUrls = parseJson(container.dataset.answerUrls, {});
    const answers = parseJson(container.dataset.initialAnswers, {});
    const resultUrl = container.dataset.resultUrl;
    const remainingValue = container.dataset.remainingSeconds;
    let remainingSeconds = remainingValue === '' ? null : Number(remainingValue);
    let current = 0;
    let expired = false;
    let saveState = 'idle';
    let saveQueue = Promise.resolve();
    let timer = null;
    const questions = [...container.querySelectorAll('[data-attempt-question]')];
    const total = questions.length;
    const time = container.querySelector('[data-attempt-time]');
    const answered = container.querySelector('[data-attempt-answered]');
    const progress = container.querySelector('[data-attempt-progress]');
    const progressBar = container.querySelector('[data-attempt-progress-bar]');
    const previous = container.querySelector('[data-attempt-previous]');
    const next = container.querySelector('[data-attempt-next]');
    const firstSpacer = container.querySelector('[data-attempt-first-spacer]');
    const submitForm = container.querySelector('[data-attempt-submit]');
    const message = container.querySelector('[data-attempt-message]');
    const unanswered = container.querySelector('[data-attempt-unanswered]');
    const jumpButtons = [...container.querySelectorAll('[data-attempt-jump]')];

    const answeredCount = () => Object.values(answers).filter(Boolean).length;
    const progressPercent = () => total === 0 ? 0 : Math.round((answeredCount() / total) * 100);
    const formattedTime = () => {
        if (remainingSeconds === null) return 'No time limit';
        const hours = Math.floor(remainingSeconds / 3600);
        const minutes = Math.floor((remainingSeconds % 3600) / 60);
        const seconds = remainingSeconds % 60;

        return hours > 0
            ? `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
            : `${minutes}:${String(seconds).padStart(2, '0')}`;
    };
    const setMessage = (text, isError = false) => {
        if (!(message instanceof HTMLElement)) return;
        message.textContent = text;
        message.classList.toggle('text-danger', isError);
        message.classList.toggle('text-muted', !isError);
    };
    const applyJumpState = (button) => {
        const index = Number(button.dataset.attemptJump);
        const hasAnswer = Boolean(answers[button.dataset.questionId]);
        button.classList.remove('border-brand-600', 'bg-brand-600', 'text-white', 'border-green-300', 'bg-green-50', 'text-success', 'border-line', 'bg-white', 'text-muted', 'hover:border-brand-300');

        if (index === current) {
            button.classList.add('border-brand-600', 'bg-brand-600', 'text-white');
        } else if (hasAnswer) {
            button.classList.add('border-green-300', 'bg-green-50', 'text-success');
        } else {
            button.classList.add('border-line', 'bg-white', 'text-muted', 'hover:border-brand-300');
        }
    };
    const render = () => {
        questions.forEach((question, index) => {
            question.hidden = index !== current;
        });
        container.querySelectorAll('[data-attempt-fieldset]').forEach((fieldset) => {
            fieldset.disabled = expired;
        });
        if (time instanceof HTMLElement) {
            time.textContent = formattedTime();
            const urgent = expired || (remainingSeconds !== null && remainingSeconds < 60);
            time.classList.toggle('text-danger', urgent);
            time.classList.toggle('text-ink', !urgent);
        }
        if (answered instanceof HTMLElement) answered.textContent = String(answeredCount());
        if (progress instanceof HTMLElement) progress.setAttribute('aria-valuenow', String(progressPercent()));
        if (progressBar instanceof HTMLElement) progressBar.style.width = `${progressPercent()}%`;
        if (previous instanceof HTMLElement) previous.hidden = current === 0;
        if (firstSpacer instanceof HTMLElement) firstSpacer.hidden = current !== 0;
        if (next instanceof HTMLElement) next.hidden = current >= total - 1;
        if (submitForm instanceof HTMLElement) submitForm.hidden = current !== total - 1;
        if (unanswered instanceof HTMLElement) {
            const count = total - answeredCount();
            unanswered.hidden = current !== total - 1 || count === 0;
            const value = unanswered.querySelector('span');
            if (value) value.textContent = String(count);
        }
        jumpButtons.forEach(applyJumpState);
    };
    const persistAnswer = async (questionId, optionId) => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(answerUrls[questionId], {
                method: 'PUT',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ option_id: optionId }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.errors?.attempt?.[0] || payload.message || 'Your answer could not be saved.');

            saveState = 'saved';
            setMessage(`Saved ${new Date(payload.saved_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`);
        } catch (error) {
            saveState = 'error';
            setMessage(error instanceof Error ? error.message : 'Your answer could not be saved.', true);
        }
    };

    container.querySelectorAll('[data-attempt-answer]').forEach((input) => {
        input.addEventListener('change', () => {
            if (expired || !(input instanceof HTMLInputElement)) return;
            const questionId = input.dataset.questionId;
            if (!questionId) return;

            answers[questionId] = input.value;
            saveState = 'saving';
            setMessage('Saving answer…');
            render();
            saveQueue = saveQueue.catch(() => {}).then(() => persistAnswer(questionId, Number(input.value)));
        });
    });
    previous?.addEventListener('click', () => {
        current = Math.max(0, current - 1);
        render();
    });
    next?.addEventListener('click', () => {
        current = Math.min(total - 1, current + 1);
        render();
    });
    jumpButtons.forEach((button) => button.addEventListener('click', () => {
        current = Number(button.dataset.attemptJump);
        render();
    }));
    submitForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!window.confirm('Submit this attempt? You cannot change answers afterward.')) return;

        setMessage('Finishing your latest save…');
        await saveQueue.catch(() => {});
        if (saveState === 'error') {
            setMessage('Resolve the save error before submitting.', true);
            return;
        }

        submitForm.submit();
    });

    if (remainingSeconds !== null) {
        timer = window.setInterval(() => {
            remainingSeconds = Math.max(0, remainingSeconds - 1);
            if (remainingSeconds === 0) {
                expired = true;
                setMessage('Time has expired. Finalizing your saved answers…', true);
                window.clearInterval(timer);
                if (resultUrl) window.location.assign(resultUrl);
            }
            render();
        }, 1000);
        window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
    }

    render();
});
