/**
 * Alpine component for the question create/edit form: add/remove options and
 * mark the correct one(s). Registered in app.js as Alpine.data('questionForm').
 *
 * The real form fields are hidden inputs rendered from this state
 * (options[i][id], options[i][text], options[i][is_correct]), so what the
 * server receives always matches what the teacher sees.
 */
let nextKey = 0;

// old() input comes back as strings ("1"/"0"), and "0" is truthy in JS.
const toBool = (value) => value === true || value === 1 || value === '1' || value === 'true';

export default ({ type, options, min, max }) => ({
    type,
    min,
    max,
    options: options.map((option) => ({
        key: nextKey++, // stable x-for key, so removing a row does not shuffle input focus
        id: option.id ?? null,
        text: option.text ?? '',
        is_correct: toBool(option.is_correct),
    })),

    init() {
        // Switching multiple → single keeps only the first ticked option.
        this.$watch('type', (value) => {
            if (value === 'single') {
                const first = this.options.findIndex((option) => option.is_correct);
                this.options.forEach((option, index) => (option.is_correct = index === first));
            }
        });
    },

    get correctCount() {
        return this.options.filter((option) => option.is_correct).length;
    },

    get canAdd() {
        return this.options.length < this.max;
    },

    get canRemove() {
        return this.options.length > this.min;
    },

    add() {
        if (this.canAdd) {
            this.options.push({ key: nextKey++, id: null, text: '', is_correct: false });
        }
    },

    remove(index) {
        if (this.canRemove) {
            this.options.splice(index, 1);
        }
    },

    toggleCorrect(index) {
        if (this.type === 'single') {
            this.options.forEach((option, i) => (option.is_correct = i === index));
        } else {
            this.options[index].is_correct = !this.options[index].is_correct;
        }
    },
});
