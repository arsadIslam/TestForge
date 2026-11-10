import './bootstrap';

import Alpine from 'alpinejs';
import questionForm from './question-form';
import questionOrder from './question-order';

window.Alpine = Alpine;

Alpine.data('questionForm', questionForm);
Alpine.data('questionOrder', questionOrder);

Alpine.start();
