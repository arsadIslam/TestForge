/**
 * Alpine component for the checkout page: opens Razorpay's Checkout popup.
 * Registered in app.js as Alpine.data('razorpayCheckout').
 *
 * On success Razorpay gives us payment id + order id + signature; we POST them to the server,
 * which verifies the HMAC signature with the secret key before granting anything. The browser
 * is never trusted on its own (and the webhook confirms the payment even if this page is closed).
 */
export default (options) => ({
    busy: false,
    message: '',

    init() {
        this.$nextTick(() => this.open()); // open straight away; the button re-opens it
    },

    open() {
        if (typeof window.Razorpay !== 'function') {
            this.message = 'The payment window could not be loaded. Check your connection and try again.';
            return;
        }

        this.busy = true;
        this.message = '';

        const checkout = new window.Razorpay({
            ...options,
            handler: (response) => {
                const form = this.$refs.verifyForm;
                form.razorpay_order_id.value = response.razorpay_order_id;
                form.razorpay_payment_id.value = response.razorpay_payment_id;
                form.razorpay_signature.value = response.razorpay_signature;
                this.message = 'Payment received — confirming…';
                form.submit();
            },
            modal: {
                ondismiss: () => {
                    this.busy = false;
                    this.message = 'Payment window closed. You have not been charged — press Pay to try again.';
                },
            },
        });

        checkout.on('payment.failed', (response) => {
            this.busy = false;
            this.message = `Payment failed: ${response.error?.description ?? 'please try again'}.`;
        });

        checkout.open();
    },
});
