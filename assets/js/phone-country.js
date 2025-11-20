/**
 * Phone Number & Country Detection Utilities
 * Client-side phone formatting and country detection
 */

class PhoneCountryManager {
    constructor() {
        this.countries = {};
        this.detectedCountry = null;
        this.initialized = false;
    }

    /**
     * Initialize the phone country manager
     */
    async init() {
        if (this.initialized) return;

        try {
            await this.loadCountries();
            await this.detectUserCountry();
            this.initialized = true;
        } catch (error) {
            console.warn('PhoneCountryManager init failed:', error);
            this.setDefaultCountry();
        }
    }

    /**
     * Load countries data
     */
    async loadCountries() {
        this.countries = {
            'AD': { name: 'Andorra', phone_code: '+376' },
            'AE': { name: 'United Arab Emirates', phone_code: '+971' },
            'AF': { name: 'Afghanistan', phone_code: '+93' },
            'AG': { name: 'Antigua and Barbuda', phone_code: '+1268' },
            'AI': { name: 'Anguilla', phone_code: '+1264' },
            'AL': { name: 'Albania', phone_code: '+355' },
            'AM': { name: 'Armenia', phone_code: '+374' },
            'AO': { name: 'Angola', phone_code: '+244' },
            'AR': { name: 'Argentina', phone_code: '+54' },
            'AS': { name: 'American Samoa', phone_code: '+1684' },
            'AT': { name: 'Austria', phone_code: '+43' },
            'AU': { name: 'Australia', phone_code: '+61' },
            'AW': { name: 'Aruba', phone_code: '+297' },
            'AZ': { name: 'Azerbaijan', phone_code: '+994' },
            'BA': { name: 'Bosnia and Herzegovina', phone_code: '+387' },
            'BB': { name: 'Barbados', phone_code: '+1246' },
            'BD': { name: 'Bangladesh', phone_code: '+880' },
            'BE': { name: 'Belgium', phone_code: '+32' },
            'BF': { name: 'Burkina Faso', phone_code: '+226' },
            'BG': { name: 'Bulgaria', phone_code: '+359' },
            'BH': { name: 'Bahrain', phone_code: '+973' },
            'BI': { name: 'Burundi', phone_code: '+257' },
            'BJ': { name: 'Benin', phone_code: '+229' },
            'BM': { name: 'Bermuda', phone_code: '+1441' },
            'BN': { name: 'Brunei', phone_code: '+673' },
            'BO': { name: 'Bolivia', phone_code: '+591' },
            'BR': { name: 'Brazil', phone_code: '+55' },
            'BS': { name: 'Bahamas', phone_code: '+1242' },
            'BT': { name: 'Bhutan', phone_code: '+975' },
            'BW': { name: 'Botswana', phone_code: '+267' },
            'BY': { name: 'Belarus', phone_code: '+375' },
            'BZ': { name: 'Belize', phone_code: '+501' },
            'CA': { name: 'Canada', phone_code: '+1' },
            'CD': { name: 'Democratic Republic of the Congo', phone_code: '+243' },
            'CF': { name: 'Central African Republic', phone_code: '+236' },
            'CG': { name: 'Republic of the Congo', phone_code: '+242' },
            'CH': { name: 'Switzerland', phone_code: '+41' },
            'CI': { name: 'Côte d\'Ivoire', phone_code: '+225' },
            'CK': { name: 'Cook Islands', phone_code: '+682' },
            'CL': { name: 'Chile', phone_code: '+56' },
            'CM': { name: 'Cameroon', phone_code: '+237' },
            'CN': { name: 'China', phone_code: '+86' },
            'CO': { name: 'Colombia', phone_code: '+57' },
            'CR': { name: 'Costa Rica', phone_code: '+506' },
            'CU': { name: 'Cuba', phone_code: '+53' },
            'CV': { name: 'Cape Verde', phone_code: '+238' },
            'CY': { name: 'Cyprus', phone_code: '+357' },
            'CZ': { name: 'Czech Republic', phone_code: '+420' },
            'DE': { name: 'Germany', phone_code: '+49' },
            'DJ': { name: 'Djibouti', phone_code: '+253' },
            'DK': { name: 'Denmark', phone_code: '+45' },
            'DM': { name: 'Dominica', phone_code: '+1767' },
            'DO': { name: 'Dominican Republic', phone_code: '+1' },
            'DZ': { name: 'Algeria', phone_code: '+213' },
            'EC': { name: 'Ecuador', phone_code: '+593' },
            'EE': { name: 'Estonia', phone_code: '+372' },
            'EG': { name: 'Egypt', phone_code: '+20' },
            'ER': { name: 'Eritrea', phone_code: '+291' },
            'ES': { name: 'Spain', phone_code: '+34' },
            'ET': { name: 'Ethiopia', phone_code: '+251' },
            'FI': { name: 'Finland', phone_code: '+358' },
            'FJ': { name: 'Fiji', phone_code: '+679' },
            'FK': { name: 'Falkland Islands', phone_code: '+500' },
            'FM': { name: 'Micronesia', phone_code: '+691' },
            'FO': { name: 'Faroe Islands', phone_code: '+298' },
            'FR': { name: 'France', phone_code: '+33' },
            'GA': { name: 'Gabon', phone_code: '+241' },
            'GB': { name: 'United Kingdom', phone_code: '+44' },
            'GD': { name: 'Grenada', phone_code: '+1473' },
            'GE': { name: 'Georgia', phone_code: '+995' },
            'GH': { name: 'Ghana', phone_code: '+233' },
            'GI': { name: 'Gibraltar', phone_code: '+350' },
            'GL': { name: 'Greenland', phone_code: '+299' },
            'GM': { name: 'Gambia', phone_code: '+220' },
            'GN': { name: 'Guinea', phone_code: '+224' },
            'GQ': { name: 'Equatorial Guinea', phone_code: '+240' },
            'GR': { name: 'Greece', phone_code: '+30' },
            'GT': { name: 'Guatemala', phone_code: '+502' },
            'GU': { name: 'Guam', phone_code: '+1671' },
            'GW': { name: 'Guinea-Bissau', phone_code: '+245' },
            'GY': { name: 'Guyana', phone_code: '+592' },
            'HK': { name: 'Hong Kong', phone_code: '+852' },
            'HN': { name: 'Honduras', phone_code: '+504' },
            'HR': { name: 'Croatia', phone_code: '+385' },
            'HT': { name: 'Haiti', phone_code: '+509' },
            'HU': { name: 'Hungary', phone_code: '+36' },
            'ID': { name: 'Indonesia', phone_code: '+62' },
            'IE': { name: 'Ireland', phone_code: '+353' },
            'IL': { name: 'Israel', phone_code: '+972' },
            'IN': { name: 'India', phone_code: '+91' },
            'IQ': { name: 'Iraq', phone_code: '+964' },
            'IR': { name: 'Iran', phone_code: '+98' },
            'IS': { name: 'Iceland', phone_code: '+354' },
            'IT': { name: 'Italy', phone_code: '+39' },
            'JM': { name: 'Jamaica', phone_code: '+1876' },
            'JO': { name: 'Jordan', phone_code: '+962' },
            'JP': { name: 'Japan', phone_code: '+81' },
            'KE': { name: 'Kenya', phone_code: '+254' },
            'KG': { name: 'Kyrgyzstan', phone_code: '+996' },
            'KH': { name: 'Cambodia', phone_code: '+855' },
            'KI': { name: 'Kiribati', phone_code: '+686' },
            'KM': { name: 'Comoros', phone_code: '+269' },
            'KN': { name: 'Saint Kitts and Nevis', phone_code: '+1869' },
            'KP': { name: 'North Korea', phone_code: '+850' },
            'KR': { name: 'South Korea', phone_code: '+82' },
            'KW': { name: 'Kuwait', phone_code: '+965' },
            'KY': { name: 'Cayman Islands', phone_code: '+1345' },
            'KZ': { name: 'Kazakhstan', phone_code: '+7' },
            'LA': { name: 'Laos', phone_code: '+856' },
            'LB': { name: 'Lebanon', phone_code: '+961' },
            'LC': { name: 'Saint Lucia', phone_code: '+1758' },
            'LI': { name: 'Liechtenstein', phone_code: '+423' },
            'LK': { name: 'Sri Lanka', phone_code: '+94' },
            'LR': { name: 'Liberia', phone_code: '+231' },
            'LS': { name: 'Lesotho', phone_code: '+266' },
            'LT': { name: 'Lithuania', phone_code: '+370' },
            'LU': { name: 'Luxembourg', phone_code: '+352' },
            'LV': { name: 'Latvia', phone_code: '+371' },
            'LY': { name: 'Libya', phone_code: '+218' },
            'MA': { name: 'Morocco', phone_code: '+212' },
            'MC': { name: 'Monaco', phone_code: '+377' },
            'MD': { name: 'Moldova', phone_code: '+373' },
            'ME': { name: 'Montenegro', phone_code: '+382' },
            'MG': { name: 'Madagascar', phone_code: '+261' },
            'MH': { name: 'Marshall Islands', phone_code: '+692' },
            'MK': { name: 'North Macedonia', phone_code: '+389' },
            'ML': { name: 'Mali', phone_code: '+223' },
            'MM': { name: 'Myanmar', phone_code: '+95' },
            'MN': { name: 'Mongolia', phone_code: '+976' },
            'MO': { name: 'Macao', phone_code: '+853' },
            'MR': { name: 'Mauritania', phone_code: '+222' },
            'MT': { name: 'Malta', phone_code: '+356' },
            'MU': { name: 'Mauritius', phone_code: '+230' },
            'MV': { name: 'Maldives', phone_code: '+960' },
            'MW': { name: 'Malawi', phone_code: '+265' },
            'MX': { name: 'Mexico', phone_code: '+52' },
            'MY': { name: 'Malaysia', phone_code: '+60' },
            'MZ': { name: 'Mozambique', phone_code: '+258' },
            'NA': { name: 'Namibia', phone_code: '+264' },
            'NE': { name: 'Niger', phone_code: '+227' },
            'NG': { name: 'Nigeria', phone_code: '+234' },
            'NI': { name: 'Nicaragua', phone_code: '+505' },
            'NL': { name: 'Netherlands', phone_code: '+31' },
            'NO': { name: 'Norway', phone_code: '+47' },
            'NP': { name: 'Nepal', phone_code: '+977' },
            'NR': { name: 'Nauru', phone_code: '+674' },
            'NZ': { name: 'New Zealand', phone_code: '+64' },
            'OM': { name: 'Oman', phone_code: '+968' },
            'PA': { name: 'Panama', phone_code: '+507' },
            'PE': { name: 'Peru', phone_code: '+51' },
            'PG': { name: 'Papua New Guinea', phone_code: '+675' },
            'PH': { name: 'Philippines', phone_code: '+63' },
            'PK': { name: 'Pakistan', phone_code: '+92' },
            'PL': { name: 'Poland', phone_code: '+48' },
            'PR': { name: 'Puerto Rico', phone_code: '+1' },
            'PS': { name: 'Palestine', phone_code: '+970' },
            'PT': { name: 'Portugal', phone_code: '+351' },
            'PW': { name: 'Palau', phone_code: '+680' },
            'PY': { name: 'Paraguay', phone_code: '+595' },
            'QA': { name: 'Qatar', phone_code: '+974' },
            'RO': { name: 'Romania', phone_code: '+40' },
            'RS': { name: 'Serbia', phone_code: '+381' },
            'RU': { name: 'Russia', phone_code: '+7' },
            'RW': { name: 'Rwanda', phone_code: '+250' },
            'SA': { name: 'Saudi Arabia', phone_code: '+966' },
            'SB': { name: 'Solomon Islands', phone_code: '+677' },
            'SC': { name: 'Seychelles', phone_code: '+248' },
            'SD': { name: 'Sudan', phone_code: '+249' },
            'SE': { name: 'Sweden', phone_code: '+46' },
            'SG': { name: 'Singapore', phone_code: '+65' },
            'SI': { name: 'Slovenia', phone_code: '+386' },
            'SK': { name: 'Slovakia', phone_code: '+421' },
            'SL': { name: 'Sierra Leone', phone_code: '+232' },
            'SM': { name: 'San Marino', phone_code: '+378' },
            'SN': { name: 'Senegal', phone_code: '+221' },
            'SO': { name: 'Somalia', phone_code: '+252' },
            'SR': { name: 'Suriname', phone_code: '+597' },
            'SS': { name: 'South Sudan', phone_code: '+211' },
            'ST': { name: 'São Tomé and Príncipe', phone_code: '+239' },
            'SV': { name: 'El Salvador', phone_code: '+503' },
            'SY': { name: 'Syria', phone_code: '+963' },
            'SZ': { name: 'Eswatini', phone_code: '+268' },
            'TD': { name: 'Chad', phone_code: '+235' },
            'TG': { name: 'Togo', phone_code: '+228' },
            'TH': { name: 'Thailand', phone_code: '+66' },
            'TJ': { name: 'Tajikistan', phone_code: '+992' },
            'TL': { name: 'East Timor', phone_code: '+670' },
            'TM': { name: 'Turkmenistan', phone_code: '+993' },
            'TN': { name: 'Tunisia', phone_code: '+216' },
            'TO': { name: 'Tonga', phone_code: '+676' },
            'TR': { name: 'Turkey', phone_code: '+90' },
            'TT': { name: 'Trinidad and Tobago', phone_code: '+1868' },
            'TV': { name: 'Tuvalu', phone_code: '+688' },
            'TW': { name: 'Taiwan', phone_code: '+886' },
            'TZ': { name: 'Tanzania', phone_code: '+255' },
            'UA': { name: 'Ukraine', phone_code: '+380' },
            'UG': { name: 'Uganda', phone_code: '+256' },
            'US': { name: 'United States', phone_code: '+1' },
            'UY': { name: 'Uruguay', phone_code: '+598' },
            'UZ': { name: 'Uzbekistan', phone_code: '+998' },
            'VA': { name: 'Vatican City', phone_code: '+379' },
            'VC': { name: 'Saint Vincent and the Grenadines', phone_code: '+1784' },
            'VE': { name: 'Venezuela', phone_code: '+58' },
            'VN': { name: 'Vietnam', phone_code: '+84' },
            'VU': { name: 'Vanuatu', phone_code: '+678' },
            'WS': { name: 'Samoa', phone_code: '+685' },
            'YE': { name: 'Yemen', phone_code: '+967' },
            'ZA': { name: 'South Africa', phone_code: '+27' },
            'ZM': { name: 'Zambia', phone_code: '+260' },
            'ZW': { name: 'Zimbabwe', phone_code: '+263' }
        };
    }

    /**
     * Detect user's country based on IP
     */
    async detectUserCountry() {
        try {
            const response = await fetch('./api/detect_country.php');
            if (!response.ok) throw new Error('API request failed');

            const data = await response.json();
            if (data.success && data.country) {
                this.detectedCountry = data.country;
                return data.country;
            }
        } catch (error) {
            console.warn('Country detection failed:', error);
        }

        this.setDefaultCountry();
        return this.detectedCountry;
    }

    /**
     * Set default country (UK)
     */
    setDefaultCountry() {
        this.detectedCountry = {
            country_code: 'GB',
            country_name: 'United Kingdom',
            phone_code: '+44'
        };
    }

    /**
     * Get popular countries for dropdown
     */
    getPopularCountries() {
        const popular = [
            'GB', 'US', 'CA', 'AU', 'IE', 'FR', 'DE', 'ES', 'IT', 'NL',
            'BE', 'CH', 'AT', 'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'HU',
            'PT', 'GR', 'RO', 'BG', 'HR', 'SK', 'SI', 'LT', 'LV', 'EE',
            'IN', 'PK', 'BD', 'CN', 'JP', 'KR', 'TH', 'SG', 'MY', 'ID',
            'PH', 'VN', 'TR', 'IL', 'SA', 'AE', 'EG', 'NG', 'ZA', 'KE',
            'BR', 'AR', 'MX', 'CO', 'CL', 'PE', 'VE'
        ];

        return popular.map(code => ({
            code,
            ...this.countries[code]
        })).filter(country => country.name);
    }

    /**
     * Format phone number
     */
    formatPhoneNumber(phoneNumber, countryCode = 'GB') {
        if (!phoneNumber) return '';

        // Remove all non-digit characters
        const clean = phoneNumber.replace(/[^0-9]/g, '');

        if (!clean) return '';

        const country = this.countries[countryCode];
        if (!country) return phoneNumber;

        const phoneCode = country.phone_code.replace(/[^0-9]/g, '');

        // Remove country code if already included
        let localNumber = clean;
        if (localNumber.startsWith(phoneCode)) {
            localNumber = localNumber.substring(phoneCode.length);
        }

        // Remove leading zero
        if (localNumber.startsWith('0')) {
            localNumber = localNumber.substring(1);
        }

        return `${country.phone_code} ${localNumber}`;
    }

    /**
     * Create phone input with country selector
     */
    createPhoneInput(options = {}) {
        const {
            phoneInputId = 'phone',
            countrySelectId = 'country_code',
            phoneInputName = 'phone',
            countrySelectName = 'country_code',
            placeholder = 'Enter phone number',
            required = false,
            initialCountry = null
        } = options;

        const selectedCountry = initialCountry || this.detectedCountry?.country_code || 'GB';
        const countries = this.getPopularCountries();

        const container = document.createElement('div');
        container.className = 'phone-input-container';

        container.innerHTML = `
            <div class="input-group">
                <select class="form-select country-selector" id="${countrySelectId}" name="${countrySelectName}" style="max-width: 120px;">
                    ${countries.map(country => `
                        <option value="${country.code}" 
                                data-phone-code="${country.phone_code}"
                                data-country-name="${country.name}"
                                ${country.code === selectedCountry ? 'selected' : ''}>
                            ${country.phone_code} ${country.name}
                        </option>
                    `).join('')}
                </select>
                <input type="tel" 
                       class="form-control phone-input" 
                       id="${phoneInputId}" 
                       name="${phoneInputName}"
                       placeholder="${placeholder}"
                       ${required ? 'required' : ''}>
            </div>
        `;

        // Add event listeners
        const countrySelect = container.querySelector('.country-selector');
        const phoneInput = container.querySelector('.phone-input');

        // Initialize the display to show only country code
        this.updateSelectDisplay(countrySelect, false);

        countrySelect.addEventListener('focus', () => {
            // Show full names when dropdown is opened
            this.updateSelectDisplay(countrySelect, true);
        });

        countrySelect.addEventListener('blur', () => {
            // Show only country code when dropdown is closed
            this.updateSelectDisplay(countrySelect, false);
        });

        countrySelect.addEventListener('change', () => {
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            const phoneCode = selectedOption.dataset.phoneCode;

            // Update placeholder to show the phone code
            phoneInput.placeholder = `${phoneCode} ${placeholder}`;

            // Re-format existing number if any
            if (phoneInput.value) {
                phoneInput.value = this.formatPhoneNumber(phoneInput.value, countrySelect.value);
            }
        });

        phoneInput.addEventListener('input', () => {
            const formatted = this.formatPhoneNumber(phoneInput.value, countrySelect.value);
            if (formatted !== phoneInput.value) {
                const cursorPos = phoneInput.selectionStart;
                phoneInput.value = formatted;
                phoneInput.setSelectionRange(cursorPos, cursorPos);
            }
        });

        // Set initial placeholder
        const initialOption = countrySelect.options[countrySelect.selectedIndex];
        if (initialOption) {
            phoneInput.placeholder = `${initialOption.dataset.phoneCode} ${placeholder}`;
        }

        return container;
    }

    /**
     * Update select display to show either full names or just codes
     */
    updateSelectDisplay(selectElement, showFullNames) {
        const options = selectElement.querySelectorAll('option');
        options.forEach(option => {
            const phoneCode = option.dataset.phoneCode;
            const countryName = option.dataset.countryName;

            if (showFullNames) {
                option.textContent = `${phoneCode} ${countryName}`;
            } else {
                option.textContent = phoneCode;
            }
        });
    }

    /**
     * Enhanced phone input setup for existing form fields
     */
    enhancePhoneInput(phoneInputSelector, options = {}) {
        const phoneInputs = document.querySelectorAll(phoneInputSelector);

        phoneInputs.forEach(phoneInput => {
            const wrapper = document.createElement('div');
            wrapper.className = 'phone-input-wrapper';

            // Create country selector
            const countrySelect = document.createElement('select');
            countrySelect.className = 'form-select country-selector';
            countrySelect.style.maxWidth = '120px';
            countrySelect.style.marginRight = '5px';

            const countries = this.getPopularCountries();
            const selectedCountry = options.initialCountry || this.detectedCountry?.country_code || 'GB';

            countries.forEach(country => {
                const option = document.createElement('option');
                option.value = country.code;
                option.dataset.phoneCode = country.phone_code;
                option.dataset.countryName = country.name;
                option.textContent = `${country.phone_code} ${country.name}`;
                option.selected = country.code === selectedCountry;
                countrySelect.appendChild(option);
            });

            // Wrap phone input with country selector
            phoneInput.parentNode.insertBefore(wrapper, phoneInput);

            const inputGroup = document.createElement('div');
            inputGroup.className = 'input-group';
            inputGroup.appendChild(countrySelect);
            inputGroup.appendChild(phoneInput);

            wrapper.appendChild(inputGroup);

            // Add event listeners
            // Initialize the display to show only country code
            this.updateSelectDisplay(countrySelect, false);

            countrySelect.addEventListener('focus', () => {
                // Show full names when dropdown is opened
                this.updateSelectDisplay(countrySelect, true);
            });

            countrySelect.addEventListener('blur', () => {
                // Show only country code when dropdown is closed
                this.updateSelectDisplay(countrySelect, false);
            });

            countrySelect.addEventListener('change', () => {
                if (phoneInput.value) {
                    phoneInput.value = this.formatPhoneNumber(phoneInput.value, countrySelect.value);
                }
                this.updatePhonePlaceholder(phoneInput, countrySelect);
            });

            phoneInput.addEventListener('input', () => {
                const formatted = this.formatPhoneNumber(phoneInput.value, countrySelect.value);
                if (formatted !== phoneInput.value) {
                    phoneInput.value = formatted;
                }
            });

            // Set initial placeholder
            this.updatePhonePlaceholder(phoneInput, countrySelect);
        });
    }

    /**
     * Update phone input placeholder with country code
     */
    updatePhonePlaceholder(phoneInput, countrySelect) {
        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        if (selectedOption) {
            const phoneCode = selectedOption.dataset.phoneCode;
            const originalPlaceholder = phoneInput.dataset.originalPlaceholder || phoneInput.placeholder;
            phoneInput.dataset.originalPlaceholder = originalPlaceholder;
            phoneInput.placeholder = `${phoneCode} ${originalPlaceholder.replace(/^\+\d+\s*/, '')}`;
        }
    }

    /**
     * Validate phone number
     */
    validatePhoneNumber(phoneNumber, countryCode = 'GB') {
        const clean = phoneNumber.replace(/[^0-9]/g, '');
        return clean.length >= 7 && clean.length <= 15;
    }

    /**
     * Get detected country
     */
    getDetectedCountry() {
        return this.detectedCountry;
    }

    /**
     * Get country by code
     */
    getCountryByCode(code) {
        return this.countries[code] || null;
    }
}

// Global instance
window.phoneCountryManager = new PhoneCountryManager();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    await window.phoneCountryManager.init();

    // Auto-enhance any existing phone inputs with the class 'enhance-phone'
    if (document.querySelector('.enhance-phone')) {
        window.phoneCountryManager.enhancePhoneInput('.enhance-phone');
    }
});

// Utility functions for backward compatibility
window.createPhoneInput = (options) => window.phoneCountryManager.createPhoneInput(options);
window.enhancePhoneInput = (selector, options) => window.phoneCountryManager.enhancePhoneInput(selector, options);
window.formatPhoneNumber = (phone, country) => window.phoneCountryManager.formatPhoneNumber(phone, country);
window.validatePhoneNumber = (phone, country) => window.phoneCountryManager.validatePhoneNumber(phone, country);