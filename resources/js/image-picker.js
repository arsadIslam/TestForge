/**
 * Alpine component for a single image field (the question's diagram): preview the chosen
 * file, or mark the saved image for removal. Registered in app.js as Alpine.data('imagePicker').
 */
import { imageProblem } from './question-form';

export default ({ savedUrl = null }) => ({
    savedUrl,
    preview: null,
    remove: false,

    get shown() {
        return this.preview ?? (this.remove ? null : this.savedUrl);
    },

    pick(event) {
        const file = event.target.files[0];
        if (!file) return;

        const problem = imageProblem(file);
        if (problem) {
            alert(problem);
            event.target.value = '';
            return;
        }

        if (this.preview) URL.revokeObjectURL(this.preview);
        this.preview = URL.createObjectURL(file);
        this.remove = false;
    },

    clear() {
        if (this.preview) {
            URL.revokeObjectURL(this.preview);
            this.preview = null;
            this.$refs.file.value = '';
        } else {
            this.remove = true;
        }
    },
});
