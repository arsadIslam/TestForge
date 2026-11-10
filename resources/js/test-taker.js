/**
 * Alpine component for the test-taking screen. Registered in app.js as Alpine.data('testTaker').
 *
 * - One question at a time, with a navigator.
 * - Countdown to a DEADLINE (Date.now() + secondsLeft from the server), not a counter that
 *   is decremented: browsers throttle timers in background tabs, so a counter would drift.
 * - Every answer change is auto-saved with fetch (PUT, JSON). Saves for the same question are
 *   never sent in parallel: if a save is still in flight, the latest selection is sent when it
 *   returns, so an older request can never overwrite a newer answer.
 * - The server is the authority on time: after expires_at it rejects saves whatever this timer says.
 */
const csrfToken = () => document.querySelector('meta[name="csrf-token"]').content;

export default ({ questions, answers, secondsLeft }) => ({
    questions,
    answers: Object.fromEntries(
        questions.map((q) => [q.id, (answers[q.id] ?? []).map(Number)]),
    ),
    current: 0,
    status: {}, // question id → 'pending' | 'saving' | 'saved' | 'error'
    error: null,
    remaining: secondsLeft,
    deadline: Date.now() + secondsLeft * 1000,
    confirming: false,
    submitting: false,
    inFlight: {},
    resend: {},
    debounce: {},

    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);

        window.addEventListener('beforeunload', (event) => {
            if (!this.submitting && this.hasUnsavedChanges) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },

    // ---------- timer ----------
    tick() {
        this.remaining = Math.max(0, Math.round((this.deadline - Date.now()) / 1000));

        if (this.remaining === 0) {
            clearInterval(this.timer);
            this.submit();
        }
    },

    get clock() {
        const h = Math.floor(this.remaining / 3600);
        const m = Math.floor((this.remaining % 3600) / 60);
        const s = this.remaining % 60;
        const pad = (n) => String(n).padStart(2, '0');

        return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    },

    get locked() {
        return this.remaining === 0 || this.submitting;
    },

    // ---------- navigation ----------
    get question() {
        return this.questions[this.current];
    },

    go(index) {
        this.current = Math.min(Math.max(index, 0), this.questions.length - 1);
    },

    // ---------- answers ----------
    isSelected(optionId) {
        return this.answers[this.question.id].includes(optionId);
    },

    isAnswered(question) {
        return this.answers[question.id].length > 0;
    },

    get answeredCount() {
        return this.questions.filter((q) => this.isAnswered(q)).length;
    },

    get hasUnsavedChanges() {
        // Anything not confirmed by the server — including a failed save — is unsaved.
        return Object.values(this.status).some((s) => s !== 'saved');
    },

    select(optionId) {
        if (this.locked) return;

        const q = this.question;
        const selected = this.answers[q.id];

        if (q.type === 'single') {
            this.answers[q.id] = [optionId];
        } else {
            this.answers[q.id] = selected.includes(optionId)
                ? selected.filter((id) => id !== optionId)
                : [...selected, optionId];
        }

        // Single choice saves at once; multi-select waits a moment so ticking 3 boxes is 1 request.
        this.queueSave(q, q.type === 'single' ? 0 : 500);
    },

    clear() {
        if (this.locked) return;
        this.answers[this.question.id] = [];
        this.queueSave(this.question, 0);
    },

    // ---------- auto-save ----------
    queueSave(q, delay) {
        clearTimeout(this.debounce[q.id]);
        this.status[q.id] = 'pending';
        this.debounce[q.id] = setTimeout(() => this.save(q), delay);
    },

    async save(q) {
        delete this.debounce[q.id];

        if (this.inFlight[q.id]) {
            this.resend[q.id] = true; // send the latest selection when the current request returns
            return;
        }

        this.inFlight[q.id] = true;
        this.status[q.id] = 'saving';
        let retryIn = 0;

        try {
            const response = await fetch(q.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json', // → Laravel answers errors with JSON, not a redirect
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ option_ids: this.answers[q.id] }),
            });

            if (response.ok) {
                this.status[q.id] = 'saved';
                this.error = null;
            } else if ([403, 404, 419].includes(response.status)) {
                // Attempt no longer in progress / session expired: let the server decide where we go.
                this.submitting = true;
                window.location.reload();
            } else if (response.status === 429) {
                this.status[q.id] = 'error';
                retryIn = 2000;
            } else {
                const data = await response.json().catch(() => ({}));
                this.status[q.id] = 'error';
                this.error = data.message ?? 'Your answer could not be saved.';
            }
        } catch {
            this.status[q.id] = 'error';
            this.error = 'Connection problem. Retrying…';
            retryIn = 3000;
        } finally {
            this.inFlight[q.id] = false;

            if (this.resend[q.id]) {
                this.resend[q.id] = false;
                this.save(q);
            } else if (retryIn && !this.locked) {
                // Retry later, but keep showing the error until it actually succeeds.
                this.debounce[q.id] = setTimeout(() => this.save(q), retryIn);
            }
        }
    },

    /** Send any debounced saves now and wait (max 5 s) until nothing is in flight. */
    async flush() {
        for (const q of this.questions) {
            if (this.debounce[q.id]) {
                clearTimeout(this.debounce[q.id]);
                this.save(q);
            }
        }

        const started = Date.now();
        while (Object.values(this.inFlight).some(Boolean) && Date.now() - started < 5000) {
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
    },

    // ---------- submit ----------
    async submit() {
        if (this.submitting) return;
        this.submitting = true;
        this.confirming = false;

        await this.flush();
        this.$refs.submitForm.submit();
    },
});
