/**
 * Simple Phone Input with Country Codes
 * Clean, minimal implementation that just works
 */

// Simple country data with most common countries
const COUNTRIES = {
    'GB': { name: 'United Kingdom', code: '+44' },
    'US': { name: 'United States', code: '+1' },
    'CA': { name: 'Canada', code: '+1' },
    'AU': { name: 'Australia', code: '+61' },
    'IE': { name: 'Ireland', code: '+353' },
    'FR': { name: 'France', code: '+33' },
    'DE': { name: 'Germany', code: '+49' },
    'ES': { name: 'Spain', code: '+34' },
    'IT': { name: 'Italy', code: '+39' },
    'NL': { name: 'Netherlands', code: '+31' },
    'BE': { name: 'Belgium', code: '+32' },
    'CH': { name: 'Switzerland', code: '+41' },
    'AT': { name: 'Austria', code: '+43' },
    'SE': { name: 'Sweden', code: '+46' },
    'NO': { name: 'Norway', code: '+47' },
    'DK': { name: 'Denmark', code: '+45' },
    'FI': { name: 'Finland', code: '+358' },
    'PL': { name: 'Poland', code: '+48' },
    'CZ': { name: 'Czech Republic', code: '+420' },
    'HU': { name: 'Hungary', code: '+36' },
    'PT': { name: 'Portugal', code: '+351' },
    'GR': { name: 'Greece', code: '+30' },
    'IN': { name: 'India', code: '+91' },
    'CN': { name: 'China', code: '+86' },
    'JP': { name: 'Japan', code: '+81' },
    'KR': { name: 'South Korea', code: '+82' },
    'SG': { name: 'Singapore', code: '+65' },
    'MY': { name: 'Malaysia', code: '+60' },
    'TH': { name: 'Thailand', code: '+66' },
    'ID': { name: 'Indonesia', code: '+62' },
    'PH': { name: 'Philippines', code: '+63' },
    'VN': { name: 'Vietnam', code: '+84' },
    'TR': { name: 'Turkey', code: '+90' },
    'IL': { name: 'Israel', code: '+972' },
    'SA': { name: 'Saudi Arabia', code: '+966' },
    'AE': { name: 'UAE', code: '+971' },
    'EG': { name: 'Egypt', code: '+20' },
    'ZA': { name: 'South Africa', code: '+27' },
    'NG': { name: 'Nigeria', code: '+234' },
    'KE': { name: 'Kenya', code: '+254' },
    'BR': { name: 'Brazil', code: '+55' },
    'AR': { name: 'Argentina', code: '+54' },
    'MX': { name: 'Mexico', code: '+52' },
    'CO': { name: 'Colombia', code: '+57' },
    'CL': { name: 'Chile', code: '+56' },
    'PE': { name: 'Peru', code: '+51' },
    'NZ': { name: 'New Zealand', code: '+64' }
};

// Default country (fallback)
let detectedCountry = 'GB';

/**
 * Detect user's country (simple version)
 */
async function detectCountry() {
    try {
        const response = await fetch('api/detect_country.php');
        const data = await response.json();
        if (data.success && data.country && COUNTRIES[data.country.country_code]) {
            detectedCountry = data.country.country_code;
        }
    } catch (e) {
        // Fallback to UK if detection fails
        detectedCountry = 'GB';
    }
}

/**
 * Create a phone input with country selector
 */
function createPhoneInput(container, options = {}) {
    const {
        name = 'phone',
        placeholder = 'Enter phone number',
        required = false,
        initialCountry = detectedCountry
    } = options;

    const html = `
        <div class="input-group">
            <select class="form-select country-selector" style="max-width: 120px;" name="${name}_country">
                ${Object.entries(COUNTRIES).map(([code, country]) =>
        `<option value="${code}" data-code="${country.code}" ${code === initialCountry ? 'selected' : ''}>
                        ${country.code} ${country.name}
                    </option>`
    ).join('')}
            </select>
            <input type="tel" 
                   class="form-control phone-input" 
                   name="${name}" 
                   placeholder="${placeholder}"
                   ${required ? 'required' : ''}>
        </div>
    `;

    container.innerHTML = html;

    // Add simple formatting
    const phoneInput = container.querySelector('input[type="tel"]');
    const countrySelect = container.querySelector('select');

    phoneInput.addEventListener('input', function () {
        // Simple phone formatting - remove non-digits and format
        let value = this.value.replace(/\D/g, '');
        if (value.length > 0) {
            // Basic formatting - add spaces every 3-4 digits
            value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1 $2 $3');
        }
        this.value = value;
    });

    // Show only country code when closed, full name when opened
    countrySelect.addEventListener('focus', function () {
        this.classList.add('opened');
        // Show full country names
        Array.from(this.options).forEach(option => {
            const code = option.getAttribute('data-code');
            const countryCode = option.value;
            const countryName = COUNTRIES[countryCode].name;
            option.textContent = `${code} ${countryName}`;
        });
    });

    countrySelect.addEventListener('blur', function () {
        this.classList.remove('opened');
        // Show only country codes
        Array.from(this.options).forEach(option => {
            const code = option.getAttribute('data-code');
            option.textContent = code;
        });
    });

    // Initialize with country codes only
    Array.from(countrySelect.options).forEach(option => {
        const code = option.getAttribute('data-code');
        option.textContent = code;
    });

    countrySelect.addEventListener('change', function () {
        const selectedCode = this.options[this.selectedIndex].dataset.code;
        phoneInput.placeholder = `${selectedCode} ${placeholder.replace(/^\+\d+\s*/, '')}`;
    });

    // Set initial placeholder
    const initialCode = countrySelect.options[countrySelect.selectedIndex].dataset.code;
    phoneInput.placeholder = `${initialCode} ${placeholder.replace(/^\+\d+\s*/, '')}`;

    return container;
}

/**
 * Auto-enhance existing phone inputs
 */
function enhancePhoneInputs() {
    document.querySelectorAll('.phone-input-group').forEach(container => {
        const id = container.id;
        let name = 'phone';
        let placeholder = 'Enter phone number';

        // Determine name and placeholder based on container ID
        if (id.includes('company-phone')) {
            name = 'company_phone';
            placeholder = 'Enter company phone';
        } else if (id.includes('admin-mobile')) {
            name = 'admin_mobile';
            placeholder = 'Enter mobile number';
        } else if (id.includes('mobile')) {
            name = 'mobile';
            placeholder = 'Enter mobile number';
        } else if (id.includes('phone')) {
            name = 'phone';
            placeholder = 'Enter phone number';
        }

        createPhoneInput(container, { name, placeholder });
    });
}

/**
 * Get formatted phone number
 */
function getFormattedPhone(container) {
    const countrySelect = container.querySelector('select');
    const phoneInput = container.querySelector('input[type="tel"]');
    const countryCode = countrySelect.options[countrySelect.selectedIndex].dataset.code;
    const phone = phoneInput.value.replace(/\D/g, '');

    return phone ? `${countryCode} ${phone}` : '';
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', async function () {
    await detectCountry();
    enhancePhoneInputs();
});

// Global functions for easy access
window.createPhoneInput = createPhoneInput;
window.enhancePhoneInputs = enhancePhoneInputs;
window.getFormattedPhone = getFormattedPhone;