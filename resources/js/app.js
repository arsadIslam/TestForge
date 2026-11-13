import './bootstrap';

import Alpine from 'alpinejs';
import imagePicker from './image-picker';
import questionForm from './question-form';
import questionOrder from './question-order';
import razorpayCheckout from './razorpay-checkout';
import testGenerator from './test-generator';
import testTaker from './test-taker';

window.Alpine = Alpine;

/*
 * LaTeX: KaTeX (~300 KB with fonts) is only downloaded on pages that need it.
 * Static content marks itself with data-math; Alpine components call window.renderMath(el)
 * after they change text. Vite splits ./math into its own chunk because of the dynamic import.
 */
const HAS_MATH = /\\\(|\\\[|\$\$/;
let mathModule = null;

window.hasMath = (text) => HAS_MATH.test(text ?? '');

window.renderMath = async (element) => {
    if (!element || !HAS_MATH.test(element.textContent)) return;
    mathModule ??= import('./math');
    (await mathModule).renderMath(element);
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-math]').forEach((element) => window.renderMath(element));
});

Alpine.data('imagePicker', imagePicker);
Alpine.data('questionForm', questionForm);
Alpine.data('questionOrder', questionOrder);
Alpine.data('razorpayCheckout', razorpayCheckout);
Alpine.data('testGenerator', testGenerator);
Alpine.data('testTaker', testTaker);

Alpine.start();
