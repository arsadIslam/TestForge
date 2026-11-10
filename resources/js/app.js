import './bootstrap';

import Alpine from 'alpinejs';
import questionForm from './question-form';
import questionOrder from './question-order';
import testTaker from './test-taker';

window.Alpine = Alpine;

Alpine.data('questionForm', questionForm);
Alpine.data('questionOrder', questionOrder);
Alpine.data('testTaker', testTaker);

Alpine.start();
