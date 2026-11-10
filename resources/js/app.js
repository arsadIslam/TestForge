import './bootstrap';

import Alpine from 'alpinejs';
import questionForm from './question-form';

window.Alpine = Alpine;

Alpine.data('questionForm', questionForm);

Alpine.start();
