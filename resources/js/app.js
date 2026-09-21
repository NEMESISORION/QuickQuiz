import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('quizAttempt', ({ answerUrls, initialAnswers, remainingSeconds, resultUrl }) => ({
    current: 0,
    answers: { ...initialAnswers },
    answerUrls,
    remainingSeconds,
    resultUrl,
    saveState: 'idle',
    message: '',
    expired: false,
    timer: null,
    saveQueue: Promise.resolve(),

    init() {
        if (this.remainingSeconds !== null) {
            this.timer = window.setInterval(() => this.tickTimer(), 1000);
        }
    },

    destroy() {
        if (this.timer) window.clearInterval(this.timer);
    },

    get answeredCount() {
        return Object.values(this.answers).filter(Boolean).length;
    },

    get progressPercent() {
        const total = Object.keys(this.answerUrls).length;
        return total === 0 ? 0 : Math.round((this.answeredCount / total) * 100);
    },

    get formattedTime() {
        if (this.remainingSeconds === null) return 'No time limit';
        const hours = Math.floor(this.remainingSeconds / 3600);
        const minutes = Math.floor((this.remainingSeconds % 3600) / 60);
        const seconds = this.remainingSeconds % 60;

        return hours > 0
            ? `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
            : `${minutes}:${String(seconds).padStart(2, '0')}`;
    },

    tickTimer() {
        this.remainingSeconds = Math.max(0, this.remainingSeconds - 1);
        if (this.remainingSeconds === 0) {
            this.expired = true;
            this.message = 'Time has expired. Finalizing your saved answers…';
            window.clearInterval(this.timer);
            window.location.assign(this.resultUrl);
        }
    },

    async submitAttempt(event) {
        event.preventDefault();
        if (!window.confirm('Submit this attempt? You cannot change answers afterward.')) return;

        this.message = 'Finishing your latest save…';
        await this.saveQueue.catch(() => {});
        if (this.saveState === 'error') {
            this.message = 'Resolve the save error before submitting.';
            return;
        }

        event.currentTarget.submit();
    },

    saveAnswer(questionId, optionId) {
        if (this.expired) return;
        this.answers[questionId] = String(optionId);
        this.saveState = 'saving';
        this.message = 'Saving answer…';
        this.saveQueue = this.saveQueue
            .catch(() => {})
            .then(() => this.persistAnswer(questionId, optionId));
    },

    async persistAnswer(questionId, optionId) {
        try {
            const response = await fetch(this.answerUrls[questionId], {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ option_id: optionId }),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.errors?.attempt?.[0] || payload.message || 'Your answer could not be saved.');
            }

            this.saveState = 'saved';
            this.message = `Saved ${new Date(payload.saved_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
        } catch (error) {
            this.saveState = 'error';
            this.message = error instanceof Error ? error.message : 'Your answer could not be saved.';
        }
    },
}));

Alpine.start();
