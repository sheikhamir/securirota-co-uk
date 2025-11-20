# Phone Number Input with Country Code Enhancement

This system provides enhanced phone number input fields with automatic country detection and formatting.

## Features

- **Auto Country Detection**: Automatically detects user's country based on IP address
- **Smart Country Selector**: Shows only country code when closed, full country names when opened
- **Phone Number Formatting**: Automatic formatting based on selected country
- **Validation**: Client-side phone number validation
- **Responsive Design**: Mobile-friendly interface with adaptive country selector

## Usage

### 1. Include Required Files

Add to your PHP page head section:
```php
$additional_css = ['assets/css/phone-input.css'];
$additional_js = ['assets/js/phone-country.js'];
```

### 2. Basic Implementation

#### Method A: Replace Existing Phone Inputs (Automatic)
Simply add the class `enhance-phone` to any existing phone input:
```html
<input type="tel" class="form-control enhance-phone" name="phone" placeholder="Enter phone number">
```

#### Method B: Create New Phone Input (Manual)
```html
<div id="phone-container"></div>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    await window.phoneCountryManager.init();
    
    const phoneInput = window.phoneCountryManager.createPhoneInput({
        phoneInputId: 'phone_input',
        countrySelectId: 'country_select',
        phoneInputName: 'phone',
        countrySelectName: 'country_code',
        placeholder: 'Enter phone number',
        required: true
    });
    
    document.getElementById('phone-container').appendChild(phoneInput);
});
</script>
```

### 3. Advanced Implementation with Hidden Fields

For complex forms where you need separate storage of country and formatted phone:

```html
<div class="mb-3">
    <label class="form-label">Phone Number</label>
    <div class="phone-input-container" id="phone-container">
        <!-- Phone input will be inserted here -->
    </div>
    <input type="hidden" name="phone_country" id="phone_country">
    <input type="hidden" name="phone_formatted" id="phone_formatted">
</div>

<script>
async function initPhoneInput() {
    await window.phoneCountryManager.init();
    
    const phoneInput = window.phoneCountryManager.createPhoneInput({
        phoneInputId: 'phone_input',
        countrySelectId: 'country_select',
        phoneInputName: 'phone_display',
        countrySelectName: 'country_display',
        placeholder: 'Enter phone number'
    });
    
    document.getElementById('phone-container').appendChild(phoneInput);
    
    // Sync with hidden fields
    function syncPhoneData() {
        const formatted = window.phoneCountryManager.formatPhoneNumber(
            document.getElementById('phone_input').value,
            document.getElementById('country_select').value
        );
        document.getElementById('phone_formatted').value = formatted;
        document.getElementById('phone_country').value = document.getElementById('country_select').value;
    }
    
    document.getElementById('phone_input').addEventListener('input', syncPhoneData);
    document.getElementById('country_select').addEventListener('change', syncPhoneData);
}

document.addEventListener('DOMContentLoaded', initPhoneInput);
</script>
```

## API Reference

### PhoneCountryManager Class

#### Methods

- `init()` - Initialize the manager with country detection
- `createPhoneInput(options)` - Create a new phone input element
- `enhancePhoneInput(selector, options)` - Enhance existing phone inputs
- `formatPhoneNumber(phone, countryCode)` - Format a phone number
- `validatePhoneNumber(phone, countryCode)` - Validate a phone number
- `getDetectedCountry()` - Get the detected user country
- `getCountryByCode(code)` - Get country data by code

#### Options for createPhoneInput()

```javascript
{
    phoneInputId: 'unique_phone_id',        // ID for phone input
    countrySelectId: 'unique_country_id',   // ID for country select
    phoneInputName: 'phone_field_name',     // Name attribute for phone input
    countrySelectName: 'country_field_name',// Name attribute for country select
    placeholder: 'Enter phone number',      // Placeholder text
    required: false,                        // Whether field is required
    initialCountry: 'GB'                    // Initial country code (optional)
}
```

## PHP Backend Integration

### Include Phone Helper

```php
require_once 'includes/phone_helper.php';
```

### Process Form Data

```php
// Get formatted phone and country from form
$phone_formatted = $_POST['phone_formatted'] ?? '';
$phone_country = $_POST['phone_country'] ?? '';

// Or format manually
$raw_phone = $_POST['phone'] ?? '';
$country = $_POST['country_code'] ?? 'GB';
$formatted_phone = formatPhoneNumber($raw_phone, $country);

// Validate
if (isValidPhoneNumber($formatted_phone, $country)) {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO table (phone, country_code) VALUES (?, ?)");
    $stmt->execute([$formatted_phone, $country]);
}
```

### Country Detection API

The system provides an API endpoint for country detection:
- URL: `api/detect_country.php`
- Method: GET
- Response: JSON with country information

## Styling

The phone inputs are styled with Bootstrap 5 compatibility. The country selector uses a smart display system:

**Country Selector Behavior:**
- **Closed State**: Shows only country code (e.g., "+44") - compact 120px width
- **Open State**: Shows full country names (e.g., "+44 United Kingdom") - expands to 200px
- **After Selection**: Returns to compact code-only display

Additional CSS classes:
- `.phone-input-container` - Main container for new phone inputs
- `.phone-input-wrapper` - Wrapper for enhanced existing inputs  
- `.country-selector` - Smart country dropdown with focus-based expansion
- `.phone-input` - Phone input field styling
- `.enhance-phone` - Class to auto-enhance existing inputs

The country selector adapts to screen size:
- Desktop: 120px closed, 200px open
- Mobile: 80px closed, 150px open

## Examples in Existing Files

See these files for working examples:
- `root/onboarding.php` - Company onboarding wizard
- `test_phone_helper.php` - Backend function testing

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Troubleshooting

### Common Issues

1. **Country not detected**: Falls back to UK (+44)
2. **API endpoint not working**: Check file permissions and PHP configuration
3. **Styling issues**: Ensure Bootstrap 5 is loaded before phone-input.css
4. **JavaScript errors**: Ensure phone-country.js is loaded after DOM ready

### Debug Mode

Enable debug logging:
```javascript
window.phoneCountryManager.debug = true;
```

## Dependencies

- Bootstrap 5.x
- Modern browser with fetch() support
- PHP 7.4+ with cURL support (for IP detection)