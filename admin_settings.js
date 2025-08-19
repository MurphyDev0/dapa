document.addEventListener('DOMContentLoaded', function() {
    // Dark mode kezelése
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode');
            document.cookie = `dark_mode=${this.checked ? '1' : '0'}; path=/; max-age=${60 * 60 * 24 * 365}`;
        });
    }

    // Pénznem API integráció
    const currencySelect = document.getElementById('default_currency');
    if (currencySelect) {
        fetch('https://api.exchangerate-api.com/v4/latest/HUF')
            .then(response => response.json())
            .then(data => {
                const rates = data.rates;
                Object.keys(rates).forEach(currency => {
                    const option = document.createElement('option');
                    option.value = currency;
                    option.textContent = `${currency} - ${(1/rates[currency]).toFixed(2)} HUF`;
                    currencySelect.appendChild(option);
                });
            })
            .catch(error => console.error('Hiba történt az árfolyamok betöltésekor:', error));
    }

    // Form validáció
    const settingsForm = document.querySelector('form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Email validáció
            const emailInputs = document.querySelectorAll('input[type="email"]');
            let isValid = true;

            emailInputs.forEach(input => {
                if (input.value && !isValidEmail(input.value)) {
                    isValid = false;
                    input.classList.add('border-red-500');
                    showError(input, 'Érvénytelen email cím formátum');
                } else {
                    input.classList.remove('border-red-500');
                    removeError(input);
                }
            });

            // URL validáció
            const urlInputs = document.querySelectorAll('input[type="url"]');
            urlInputs.forEach(input => {
                if (input.value && !isValidUrl(input.value)) {
                    isValid = false;
                    input.classList.add('border-red-500');
                    showError(input, 'Érvénytelen URL formátum');
                } else {
                    input.classList.remove('border-red-500');
                    removeError(input);
                }
            });

            // Számok validációja
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                const value = parseFloat(input.value);
                if (input.hasAttribute('min') && value < parseFloat(input.getAttribute('min'))) {
                    isValid = false;
                    input.classList.add('border-red-500');
                    showError(input, `Minimum érték: ${input.getAttribute('min')}`);
                } else if (input.hasAttribute('max') && value > parseFloat(input.getAttribute('max'))) {
                    isValid = false;
                    input.classList.add('border-red-500');
                    showError(input, `Maximum érték: ${input.getAttribute('max')}`);
                } else {
                    input.classList.remove('border-red-500');
                    removeError(input);
                }
            });

            if (isValid) {
                this.submit();
            }
        });
    }

    // Segédfüggvények
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    function showError(input, message) {
        const errorDiv = input.parentElement.querySelector('.error-message');
        if (!errorDiv) {
            const div = document.createElement('div');
            div.className = 'error-message text-red-500 text-sm mt-1';
            div.textContent = message;
            input.parentElement.appendChild(div);
        }
    }

    function removeError(input) {
        const errorDiv = input.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // Telefonszámok dinamikus kezelése
    const addPhoneButton = document.getElementById('add-phone');
    const phoneContainer = document.getElementById('phone-container');
    
    if (addPhoneButton && phoneContainer) {
        addPhoneButton.addEventListener('click', function() {
            const phoneInput = document.createElement('div');
            phoneInput.className = 'flex items-center gap-2 mb-2';
            phoneInput.innerHTML = `
                <input type="tel" name="phones[]" class="flex-1 p-2 border rounded" placeholder="Telefonszám">
                <button type="button" class="text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            phoneContainer.appendChild(phoneInput);
        });
    }

    // Szállítási zónák dinamikus kezelése
    const addZoneButton = document.getElementById('add-zone');
    const zoneContainer = document.getElementById('zone-container');
    
    if (addZoneButton && zoneContainer) {
        addZoneButton.addEventListener('click', function() {
            const zoneInput = document.createElement('div');
            zoneInput.className = 'grid grid-cols-3 gap-2 mb-2';
            zoneInput.innerHTML = `
                <input type="text" name="shipping_zones[]" class="p-2 border rounded" placeholder="Zóna neve">
                <input type="number" name="shipping_costs[]" class="p-2 border rounded" placeholder="Szállítási díj">
                <button type="button" class="text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            zoneContainer.appendChild(zoneInput);
        });
    }
}); 