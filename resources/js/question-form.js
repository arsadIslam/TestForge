/**
 * Alpine component for the question create/edit form: add/remove options, tick the
 * correct one(s) — single/multiple choice follows from how many are ticked — and attach
 * an image to any option. Registered in app.js as
 * Alpine.data('questionForm').
 *
 * The real form fields are inputs rendered from this state
 * (options[i][id], options[i][text], options[i][is_correct], options[i][image],
 * options[i][remove_image]), so what the server receives always matches what the teacher sees.
 */
let nextKey = 0;

export const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
export const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

// old() input comes back as strings ("1"/"0"), and "0" is truthy in JS.
const toBool = (value) => value === true || value === 1 || value === '1' || value === 'true';

/**
 * Quick check in the browser so the teacher hears about a bad file before uploading it.
 * The server validates again (type from the file's content, size, dimensions).
 *
 * @returns {string|null} an error message, or null if the file looks fine
 */
export const imageProblem = (file) => {
    if (!IMAGE_TYPES.includes(file.type)) return 'Please choose a JPG, PNG or WebP image.';
    if (file.size > MAX_IMAGE_BYTES) return 'Images must be 2 MB or smaller.';
    return null;
};

export default ({ options, min, max, savedImages = {} }) => ({
    min,
    max,
    options: options.map((option) => ({
        key: nextKey++, // stable x-for key, so removing a row does not shuffle input focus
        id: option.id ?? null,
        text: option.text ?? '',
        is_correct: toBool(option.is_correct),
        // Saved image: from the option itself, or (after a validation error, when old() input
        // replaced the options) looked up by id.
        image_url: option.image_url ?? (option.id ? savedImages[option.id] ?? null : null),
        remove_image: toBool(option.remove_image),
        preview: null, // object URL of a newly chosen file
    })),

    get correctCount() {
        return this.options.filter((option) => option.is_correct).length;
    },

    /** Mirrors the server rule (QuestionRules::typeFor): 2+ correct → multiple, else single. */
    get derivedType() {
        return this.correctCount > 1 ? 'multiple' : 'single';
    },

    get canAdd() {
        return this.options.length < this.max;
    },

    get canRemove() {
        return this.options.length > this.min;
    },

    /** The image to show for an option: a new preview, else the saved one unless removed. */
    shownImage(option) {
        return option.preview ?? (option.remove_image ? null : option.image_url);
    },

    add() {
        if (this.canAdd) {
            this.options.push({ key: nextKey++, id: null, text: '', is_correct: false, image_url: null, remove_image: false, preview: null });
        }
    },

    remove(index) {
        if (this.canRemove) {
            const [removed] = this.options.splice(index, 1);
            if (removed.preview) URL.revokeObjectURL(removed.preview);
        }
    },

    toggleCorrect(index) {
        this.options[index].is_correct = !this.options[index].is_correct;
    },

    pickImage(option, event) {
        const file = event.target.files[0];
        if (!file) return;

        const problem = imageProblem(file);
        if (problem) {
            alert(problem);
            event.target.value = '';
            return;
        }

        if (option.preview) URL.revokeObjectURL(option.preview);
        option.preview = URL.createObjectURL(file);
        option.remove_image = false;
    },

    /** Clears a newly chosen file, or marks the saved image for removal. */
    clearImage(option, event) {
        if (option.preview) {
            URL.revokeObjectURL(option.preview);
            option.preview = null;
            event.target.closest('[data-option-row]').querySelector('input[type=file]').value = '';
        } else {
            option.remove_image = true;
        }
    },
});
