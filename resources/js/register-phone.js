import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/dist/css/intlTelInput.css';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form.account__form');
    const phoneInput = document.querySelector('#phone');
    if (!form || !phoneInput) {
        return;
    }

    const iti = intlTelInput(phoneInput, {
        initialCountry: 'us',
        separateDialCode: true,
        nationalMode: false,
        autoPlaceholder: 'aggressive',
        formatOnDisplay: true,
        allowDropdown: true,
        dropdownContainer: document.body,
        loadUtils: () => import('intl-tel-input/utils'),
    });

    if (phoneInput.value) {
        iti.setNumber(phoneInput.value);
    }

    form.addEventListener('submit', () => {
        phoneInput.value = iti.getNumber() || phoneInput.value;
    });
});
