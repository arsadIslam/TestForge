/**
 * Alpine component for the test builder's question list: move questions up/down,
 * then "Save order" submits question_ids[] in the new order. Registered in app.js
 * as Alpine.data('questionOrder').
 *
 * Removing a question submits a separate hidden DELETE form (forms can't be nested
 * inside the reorder form), so the remove button just points that form at the right URL.
 */
export default ({ items }) => ({
    items,
    savedOrder: items.map((item) => item.id).join(','),

    get dirty() {
        return this.items.map((item) => item.id).join(',') !== this.savedOrder;
    },

    move(index, step) {
        const target = index + step;
        if (target < 0 || target >= this.items.length) {
            return;
        }
        [this.items[index], this.items[target]] = [this.items[target], this.items[index]];
    },

    /** Replace one question with another random one of the same topic and difficulty. */
    swap(item) {
        if (this.dirty && !confirm('Your unsaved order changes will be lost. Swap anyway?')) {
            return;
        }
        this.$refs.swapForm.action = item.swapUrl;
        this.$refs.swapForm.submit();
    },

    remove(item) {
        const warning = this.dirty ? '\n\nYour unsaved order changes will be lost.' : '';
        if (!confirm(`Remove this question from the test?${warning}`)) {
            return;
        }
        this.$refs.removeForm.action = item.removeUrl;
        this.$refs.removeForm.submit();
    },
});
