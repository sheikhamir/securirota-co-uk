<?php
/**
 * Phone & Country Helper Functions
 * Provides phone number formatting with country codes and IP-based country detection
 */

require_once 'country_helper.php';

/**
 * Get user's country based on IP address using multiple detection methods
 * @param string $ip Optional IP address (defaults to user's IP)
 * @return array Country information with code, name, and phone_code
 */
function detectUserCountry($ip = null) {
    if (!$ip) {
        $ip = getUserIP();
    }
    
    // Default to UK if detection fails
    $defaultCountry = [
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'phone_code' => '+44'
    ];
    
    // Skip detection for local/private IPs
    if (isPrivateIP($ip)) {
        return $defaultCountry;
    }
    
    // Try multiple detection methods
    $country = detectCountryFromAPI($ip);
    if (!$country) {
        $country = detectCountryFromHeaders();
    }
    
    return $country ?: $defaultCountry;
}

/**
 * Get user's real IP address
 * @return string IP address
 */
function getUserIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle comma-separated IPs (from proxies)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Check if IP is private/local
 * @param string $ip IP address
 * @return bool True if private IP
 */
function isPrivateIP($ip) {
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

/**
 * Detect country using free IP geolocation API
 * @param string $ip IP address
 * @return array|null Country data or null if failed
 */
function detectCountryFromAPI($ip) {
    try {
        // Using ip-api.com (free, no API key required)
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'Mozilla/5.0 (compatible; SecuriRota/1.0)'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if (!$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        if (!$data || $data['status'] !== 'success') {
            return null;
        }
        
        $phoneCode = getPhoneCodeByCountryCode($data['countryCode']);
        
        return [
            'country_code' => $data['countryCode'],
            'country_name' => $data['country'],
            'phone_code' => $phoneCode
        ];
        
    } catch (Exception $e) {
        error_log("Country detection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Detect country from HTTP headers (CloudFlare, etc.)
 * @return array|null Country data or null if not available
 */
function detectCountryFromHeaders() {
    $countryCode = null;
    
    // CloudFlare country header
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'];
    }
    // AWS CloudFront
    elseif (!empty($_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'])) {
        $countryCode = $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'];
    }
    
    if (!$countryCode || $countryCode === 'XX') {
        return null;
    }
    
    $phoneCode = getPhoneCodeByCountryCode($countryCode);
    $countryName = getCountryNameByCode($countryCode);
    
    return [
        'country_code' => $countryCode,
        'country_name' => $countryName,
        'phone_code' => $phoneCode
    ];
}

/**
 * Get comprehensive list of countries with phone codes
 * @return array Array of countries with phone codes
 */
function getCountriesWithPhoneCodes() {
    return [
        'AD' => ['name' => 'Andorra', 'phone_code' => '+376'],
        'AE' => ['name' => 'United Arab Emirates', 'phone_code' => '+971'],
        'AF' => ['name' => 'Afghanistan', 'phone_code' => '+93'],
        'AG' => ['name' => 'Antigua and Barbuda', 'phone_code' => '+1268'],
        'AI' => ['name' => 'Anguilla', 'phone_code' => '+1264'],
        'AL' => ['name' => 'Albania', 'phone_code' => '+355'],
        'AM' => ['name' => 'Armenia', 'phone_code' => '+374'],
        'AO' => ['name' => 'Angola', 'phone_code' => '+244'],
        'AQ' => ['name' => 'Antarctica', 'phone_code' => '+672'],
        'AR' => ['name' => 'Argentina', 'phone_code' => '+54'],
        'AS' => ['name' => 'American Samoa', 'phone_code' => '+1684'],
        'AT' => ['name' => 'Austria', 'phone_code' => '+43'],
        'AU' => ['name' => 'Australia', 'phone_code' => '+61'],
        'AW' => ['name' => 'Aruba', 'phone_code' => '+297'],
        'AX' => ['name' => 'Åland Islands', 'phone_code' => '+358'],
        'AZ' => ['name' => 'Azerbaijan', 'phone_code' => '+994'],
        'BA' => ['name' => 'Bosnia and Herzegovina', 'phone_code' => '+387'],
        'BB' => ['name' => 'Barbados', 'phone_code' => '+1246'],
        'BD' => ['name' => 'Bangladesh', 'phone_code' => '+880'],
        'BE' => ['name' => 'Belgium', 'phone_code' => '+32'],
        'BF' => ['name' => 'Burkina Faso', 'phone_code' => '+226'],
        'BG' => ['name' => 'Bulgaria', 'phone_code' => '+359'],
        'BH' => ['name' => 'Bahrain', 'phone_code' => '+973'],
        'BI' => ['name' => 'Burundi', 'phone_code' => '+257'],
        'BJ' => ['name' => 'Benin', 'phone_code' => '+229'],
        'BL' => ['name' => 'Saint Barthélemy', 'phone_code' => '+590'],
        'BM' => ['name' => 'Bermuda', 'phone_code' => '+1441'],
        'BN' => ['name' => 'Brunei', 'phone_code' => '+673'],
        'BO' => ['name' => 'Bolivia', 'phone_code' => '+591'],
        'BQ' => ['name' => 'Bonaire, Sint Eustatius and Saba', 'phone_code' => '+599'],
        'BR' => ['name' => 'Brazil', 'phone_code' => '+55'],
        'BS' => ['name' => 'Bahamas', 'phone_code' => '+1242'],
        'BT' => ['name' => 'Bhutan', 'phone_code' => '+975'],
        'BV' => ['name' => 'Bouvet Island', 'phone_code' => '+47'],
        'BW' => ['name' => 'Botswana', 'phone_code' => '+267'],
        'BY' => ['name' => 'Belarus', 'phone_code' => '+375'],
        'BZ' => ['name' => 'Belize', 'phone_code' => '+501'],
        'CA' => ['name' => 'Canada', 'phone_code' => '+1'],
        'CC' => ['name' => 'Cocos Islands', 'phone_code' => '+61'],
        'CD' => ['name' => 'Democratic Republic of the Congo', 'phone_code' => '+243'],
        'CF' => ['name' => 'Central African Republic', 'phone_code' => '+236'],
        'CG' => ['name' => 'Republic of the Congo', 'phone_code' => '+242'],
        'CH' => ['name' => 'Switzerland', 'phone_code' => '+41'],
        'CI' => ['name' => 'Côte d\'Ivoire', 'phone_code' => '+225'],
        'CK' => ['name' => 'Cook Islands', 'phone_code' => '+682'],
        'CL' => ['name' => 'Chile', 'phone_code' => '+56'],
        'CM' => ['name' => 'Cameroon', 'phone_code' => '+237'],
        'CN' => ['name' => 'China', 'phone_code' => '+86'],
        'CO' => ['name' => 'Colombia', 'phone_code' => '+57'],
        'CR' => ['name' => 'Costa Rica', 'phone_code' => '+506'],
        'CU' => ['name' => 'Cuba', 'phone_code' => '+53'],
        'CV' => ['name' => 'Cape Verde', 'phone_code' => '+238'],
        'CW' => ['name' => 'Curaçao', 'phone_code' => '+599'],
        'CX' => ['name' => 'Christmas Island', 'phone_code' => '+61'],
        'CY' => ['name' => 'Cyprus', 'phone_code' => '+357'],
        'CZ' => ['name' => 'Czech Republic', 'phone_code' => '+420'],
        'DE' => ['name' => 'Germany', 'phone_code' => '+49'],
        'DJ' => ['name' => 'Djibouti', 'phone_code' => '+253'],
        'DK' => ['name' => 'Denmark', 'phone_code' => '+45'],
        'DM' => ['name' => 'Dominica', 'phone_code' => '+1767'],
        'DO' => ['name' => 'Dominican Republic', 'phone_code' => '+1'],
        'DZ' => ['name' => 'Algeria', 'phone_code' => '+213'],
        'EC' => ['name' => 'Ecuador', 'phone_code' => '+593'],
        'EE' => ['name' => 'Estonia', 'phone_code' => '+372'],
        'EG' => ['name' => 'Egypt', 'phone_code' => '+20'],
        'EH' => ['name' => 'Western Sahara', 'phone_code' => '+212'],
        'ER' => ['name' => 'Eritrea', 'phone_code' => '+291'],
        'ES' => ['name' => 'Spain', 'phone_code' => '+34'],
        'ET' => ['name' => 'Ethiopia', 'phone_code' => '+251'],
        'FI' => ['name' => 'Finland', 'phone_code' => '+358'],
        'FJ' => ['name' => 'Fiji', 'phone_code' => '+679'],
        'FK' => ['name' => 'Falkland Islands', 'phone_code' => '+500'],
        'FM' => ['name' => 'Micronesia', 'phone_code' => '+691'],
        'FO' => ['name' => 'Faroe Islands', 'phone_code' => '+298'],
        'FR' => ['name' => 'France', 'phone_code' => '+33'],
        'GA' => ['name' => 'Gabon', 'phone_code' => '+241'],
        'GB' => ['name' => 'United Kingdom', 'phone_code' => '+44'],
        'GD' => ['name' => 'Grenada', 'phone_code' => '+1473'],
        'GE' => ['name' => 'Georgia', 'phone_code' => '+995'],
        'GF' => ['name' => 'French Guiana', 'phone_code' => '+594'],
        'GG' => ['name' => 'Guernsey', 'phone_code' => '+44'],
        'GH' => ['name' => 'Ghana', 'phone_code' => '+233'],
        'GI' => ['name' => 'Gibraltar', 'phone_code' => '+350'],
        'GL' => ['name' => 'Greenland', 'phone_code' => '+299'],
        'GM' => ['name' => 'Gambia', 'phone_code' => '+220'],
        'GN' => ['name' => 'Guinea', 'phone_code' => '+224'],
        'GP' => ['name' => 'Guadeloupe', 'phone_code' => '+590'],
        'GQ' => ['name' => 'Equatorial Guinea', 'phone_code' => '+240'],
        'GR' => ['name' => 'Greece', 'phone_code' => '+30'],
        'GS' => ['name' => 'South Georgia and the South Sandwich Islands', 'phone_code' => '+500'],
        'GT' => ['name' => 'Guatemala', 'phone_code' => '+502'],
        'GU' => ['name' => 'Guam', 'phone_code' => '+1671'],
        'GW' => ['name' => 'Guinea-Bissau', 'phone_code' => '+245'],
        'GY' => ['name' => 'Guyana', 'phone_code' => '+592'],
        'HK' => ['name' => 'Hong Kong', 'phone_code' => '+852'],
        'HM' => ['name' => 'Heard Island and McDonald Islands', 'phone_code' => '+672'],
        'HN' => ['name' => 'Honduras', 'phone_code' => '+504'],
        'HR' => ['name' => 'Croatia', 'phone_code' => '+385'],
        'HT' => ['name' => 'Haiti', 'phone_code' => '+509'],
        'HU' => ['name' => 'Hungary', 'phone_code' => '+36'],
        'ID' => ['name' => 'Indonesia', 'phone_code' => '+62'],
        'IE' => ['name' => 'Ireland', 'phone_code' => '+353'],
        'IL' => ['name' => 'Israel', 'phone_code' => '+972'],
        'IM' => ['name' => 'Isle of Man', 'phone_code' => '+44'],
        'IN' => ['name' => 'India', 'phone_code' => '+91'],
        'IO' => ['name' => 'British Indian Ocean Territory', 'phone_code' => '+246'],
        'IQ' => ['name' => 'Iraq', 'phone_code' => '+964'],
        'IR' => ['name' => 'Iran', 'phone_code' => '+98'],
        'IS' => ['name' => 'Iceland', 'phone_code' => '+354'],
        'IT' => ['name' => 'Italy', 'phone_code' => '+39'],
        'JE' => ['name' => 'Jersey', 'phone_code' => '+44'],
        'JM' => ['name' => 'Jamaica', 'phone_code' => '+1876'],
        'JO' => ['name' => 'Jordan', 'phone_code' => '+962'],
        'JP' => ['name' => 'Japan', 'phone_code' => '+81'],
        'KE' => ['name' => 'Kenya', 'phone_code' => '+254'],
        'KG' => ['name' => 'Kyrgyzstan', 'phone_code' => '+996'],
        'KH' => ['name' => 'Cambodia', 'phone_code' => '+855'],
        'KI' => ['name' => 'Kiribati', 'phone_code' => '+686'],
        'KM' => ['name' => 'Comoros', 'phone_code' => '+269'],
        'KN' => ['name' => 'Saint Kitts and Nevis', 'phone_code' => '+1869'],
        'KP' => ['name' => 'North Korea', 'phone_code' => '+850'],
        'KR' => ['name' => 'South Korea', 'phone_code' => '+82'],
        'KW' => ['name' => 'Kuwait', 'phone_code' => '+965'],
        'KY' => ['name' => 'Cayman Islands', 'phone_code' => '+1345'],
        'KZ' => ['name' => 'Kazakhstan', 'phone_code' => '+7'],
        'LA' => ['name' => 'Laos', 'phone_code' => '+856'],
        'LB' => ['name' => 'Lebanon', 'phone_code' => '+961'],
        'LC' => ['name' => 'Saint Lucia', 'phone_code' => '+1758'],
        'LI' => ['name' => 'Liechtenstein', 'phone_code' => '+423'],
        'LK' => ['name' => 'Sri Lanka', 'phone_code' => '+94'],
        'LR' => ['name' => 'Liberia', 'phone_code' => '+231'],
        'LS' => ['name' => 'Lesotho', 'phone_code' => '+266'],
        'LT' => ['name' => 'Lithuania', 'phone_code' => '+370'],
        'LU' => ['name' => 'Luxembourg', 'phone_code' => '+352'],
        'LV' => ['name' => 'Latvia', 'phone_code' => '+371'],
        'LY' => ['name' => 'Libya', 'phone_code' => '+218'],
        'MA' => ['name' => 'Morocco', 'phone_code' => '+212'],
        'MC' => ['name' => 'Monaco', 'phone_code' => '+377'],
        'MD' => ['name' => 'Moldova', 'phone_code' => '+373'],
        'ME' => ['name' => 'Montenegro', 'phone_code' => '+382'],
        'MF' => ['name' => 'Saint Martin', 'phone_code' => '+590'],
        'MG' => ['name' => 'Madagascar', 'phone_code' => '+261'],
        'MH' => ['name' => 'Marshall Islands', 'phone_code' => '+692'],
        'MK' => ['name' => 'North Macedonia', 'phone_code' => '+389'],
        'ML' => ['name' => 'Mali', 'phone_code' => '+223'],
        'MM' => ['name' => 'Myanmar', 'phone_code' => '+95'],
        'MN' => ['name' => 'Mongolia', 'phone_code' => '+976'],
        'MO' => ['name' => 'Macao', 'phone_code' => '+853'],
        'MP' => ['name' => 'Northern Mariana Islands', 'phone_code' => '+1670'],
        'MQ' => ['name' => 'Martinique', 'phone_code' => '+596'],
        'MR' => ['name' => 'Mauritania', 'phone_code' => '+222'],
        'MS' => ['name' => 'Montserrat', 'phone_code' => '+1664'],
        'MT' => ['name' => 'Malta', 'phone_code' => '+356'],
        'MU' => ['name' => 'Mauritius', 'phone_code' => '+230'],
        'MV' => ['name' => 'Maldives', 'phone_code' => '+960'],
        'MW' => ['name' => 'Malawi', 'phone_code' => '+265'],
        'MX' => ['name' => 'Mexico', 'phone_code' => '+52'],
        'MY' => ['name' => 'Malaysia', 'phone_code' => '+60'],
        'MZ' => ['name' => 'Mozambique', 'phone_code' => '+258'],
        'NA' => ['name' => 'Namibia', 'phone_code' => '+264'],
        'NC' => ['name' => 'New Caledonia', 'phone_code' => '+687'],
        'NE' => ['name' => 'Niger', 'phone_code' => '+227'],
        'NF' => ['name' => 'Norfolk Island', 'phone_code' => '+672'],
        'NG' => ['name' => 'Nigeria', 'phone_code' => '+234'],
        'NI' => ['name' => 'Nicaragua', 'phone_code' => '+505'],
        'NL' => ['name' => 'Netherlands', 'phone_code' => '+31'],
        'NO' => ['name' => 'Norway', 'phone_code' => '+47'],
        'NP' => ['name' => 'Nepal', 'phone_code' => '+977'],
        'NR' => ['name' => 'Nauru', 'phone_code' => '+674'],
        'NU' => ['name' => 'Niue', 'phone_code' => '+683'],
        'NZ' => ['name' => 'New Zealand', 'phone_code' => '+64'],
        'OM' => ['name' => 'Oman', 'phone_code' => '+968'],
        'PA' => ['name' => 'Panama', 'phone_code' => '+507'],
        'PE' => ['name' => 'Peru', 'phone_code' => '+51'],
        'PF' => ['name' => 'French Polynesia', 'phone_code' => '+689'],
        'PG' => ['name' => 'Papua New Guinea', 'phone_code' => '+675'],
        'PH' => ['name' => 'Philippines', 'phone_code' => '+63'],
        'PK' => ['name' => 'Pakistan', 'phone_code' => '+92'],
        'PL' => ['name' => 'Poland', 'phone_code' => '+48'],
        'PM' => ['name' => 'Saint Pierre and Miquelon', 'phone_code' => '+508'],
        'PN' => ['name' => 'Pitcairn', 'phone_code' => '+872'],
        'PR' => ['name' => 'Puerto Rico', 'phone_code' => '+1'],
        'PS' => ['name' => 'Palestine', 'phone_code' => '+970'],
        'PT' => ['name' => 'Portugal', 'phone_code' => '+351'],
        'PW' => ['name' => 'Palau', 'phone_code' => '+680'],
        'PY' => ['name' => 'Paraguay', 'phone_code' => '+595'],
        'QA' => ['name' => 'Qatar', 'phone_code' => '+974'],
        'RE' => ['name' => 'Réunion', 'phone_code' => '+262'],
        'RO' => ['name' => 'Romania', 'phone_code' => '+40'],
        'RS' => ['name' => 'Serbia', 'phone_code' => '+381'],
        'RU' => ['name' => 'Russia', 'phone_code' => '+7'],
        'RW' => ['name' => 'Rwanda', 'phone_code' => '+250'],
        'SA' => ['name' => 'Saudi Arabia', 'phone_code' => '+966'],
        'SB' => ['name' => 'Solomon Islands', 'phone_code' => '+677'],
        'SC' => ['name' => 'Seychelles', 'phone_code' => '+248'],
        'SD' => ['name' => 'Sudan', 'phone_code' => '+249'],
        'SE' => ['name' => 'Sweden', 'phone_code' => '+46'],
        'SG' => ['name' => 'Singapore', 'phone_code' => '+65'],
        'SH' => ['name' => 'Saint Helena', 'phone_code' => '+290'],
        'SI' => ['name' => 'Slovenia', 'phone_code' => '+386'],
        'SJ' => ['name' => 'Svalbard and Jan Mayen', 'phone_code' => '+47'],
        'SK' => ['name' => 'Slovakia', 'phone_code' => '+421'],
        'SL' => ['name' => 'Sierra Leone', 'phone_code' => '+232'],
        'SM' => ['name' => 'San Marino', 'phone_code' => '+378'],
        'SN' => ['name' => 'Senegal', 'phone_code' => '+221'],
        'SO' => ['name' => 'Somalia', 'phone_code' => '+252'],
        'SR' => ['name' => 'Suriname', 'phone_code' => '+597'],
        'SS' => ['name' => 'South Sudan', 'phone_code' => '+211'],
        'ST' => ['name' => 'São Tomé and Príncipe', 'phone_code' => '+239'],
        'SV' => ['name' => 'El Salvador', 'phone_code' => '+503'],
        'SX' => ['name' => 'Sint Maarten', 'phone_code' => '+1721'],
        'SY' => ['name' => 'Syria', 'phone_code' => '+963'],
        'SZ' => ['name' => 'Eswatini', 'phone_code' => '+268'],
        'TC' => ['name' => 'Turks and Caicos Islands', 'phone_code' => '+1649'],
        'TD' => ['name' => 'Chad', 'phone_code' => '+235'],
        'TF' => ['name' => 'French Southern Territories', 'phone_code' => '+262'],
        'TG' => ['name' => 'Togo', 'phone_code' => '+228'],
        'TH' => ['name' => 'Thailand', 'phone_code' => '+66'],
        'TJ' => ['name' => 'Tajikistan', 'phone_code' => '+992'],
        'TK' => ['name' => 'Tokelau', 'phone_code' => '+690'],
        'TL' => ['name' => 'East Timor', 'phone_code' => '+670'],
        'TM' => ['name' => 'Turkmenistan', 'phone_code' => '+993'],
        'TN' => ['name' => 'Tunisia', 'phone_code' => '+216'],
        'TO' => ['name' => 'Tonga', 'phone_code' => '+676'],
        'TR' => ['name' => 'Turkey', 'phone_code' => '+90'],
        'TT' => ['name' => 'Trinidad and Tobago', 'phone_code' => '+1868'],
        'TV' => ['name' => 'Tuvalu', 'phone_code' => '+688'],
        'TW' => ['name' => 'Taiwan', 'phone_code' => '+886'],
        'TZ' => ['name' => 'Tanzania', 'phone_code' => '+255'],
        'UA' => ['name' => 'Ukraine', 'phone_code' => '+380'],
        'UG' => ['name' => 'Uganda', 'phone_code' => '+256'],
        'UM' => ['name' => 'United States Minor Outlying Islands', 'phone_code' => '+1'],
        'US' => ['name' => 'United States', 'phone_code' => '+1'],
        'UY' => ['name' => 'Uruguay', 'phone_code' => '+598'],
        'UZ' => ['name' => 'Uzbekistan', 'phone_code' => '+998'],
        'VA' => ['name' => 'Vatican City', 'phone_code' => '+379'],
        'VC' => ['name' => 'Saint Vincent and the Grenadines', 'phone_code' => '+1784'],
        'VE' => ['name' => 'Venezuela', 'phone_code' => '+58'],
        'VG' => ['name' => 'British Virgin Islands', 'phone_code' => '+1284'],
        'VI' => ['name' => 'United States Virgin Islands', 'phone_code' => '+1340'],
        'VN' => ['name' => 'Vietnam', 'phone_code' => '+84'],
        'VU' => ['name' => 'Vanuatu', 'phone_code' => '+678'],
        'WF' => ['name' => 'Wallis and Futuna', 'phone_code' => '+681'],
        'WS' => ['name' => 'Samoa', 'phone_code' => '+685'],
        'YE' => ['name' => 'Yemen', 'phone_code' => '+967'],
        'YT' => ['name' => 'Mayotte', 'phone_code' => '+262'],
        'ZA' => ['name' => 'South Africa', 'phone_code' => '+27'],
        'ZM' => ['name' => 'Zambia', 'phone_code' => '+260'],
        'ZW' => ['name' => 'Zimbabwe', 'phone_code' => '+263']
    ];
}

/**
 * Get phone code by country code
 * @param string $countryCode 2-letter country code
 * @return string Phone code with + prefix
 */
function getPhoneCodeByCountryCode($countryCode) {
    $countries = getCountriesWithPhoneCodes();
    return $countries[strtoupper($countryCode)]['phone_code'] ?? '+44';
}

/**
 * Get country name by country code
 * @param string $countryCode 2-letter country code
 * @return string Country name
 */
function getCountryNameByCode($countryCode) {
    $countries = getCountriesWithPhoneCodes();
    return $countries[strtoupper($countryCode)]['name'] ?? 'United Kingdom';
}

/**
 * Get popular countries for dropdown (prioritized list)
 * @return array Array of popular countries with phone codes
 */
function getPopularCountriesWithPhoneCodes() {
    $countries = getCountriesWithPhoneCodes();
    $popular = [
        'GB', 'US', 'CA', 'AU', 'IE', 'FR', 'DE', 'ES', 'IT', 'NL',
        'BE', 'CH', 'AT', 'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'HU',
        'PT', 'GR', 'RO', 'BG', 'HR', 'SK', 'SI', 'LT', 'LV', 'EE',
        'IN', 'PK', 'BD', 'CN', 'JP', 'KR', 'TH', 'SG', 'MY', 'ID',
        'PH', 'VN', 'TR', 'IL', 'SA', 'AE', 'EG', 'NG', 'ZA', 'KE',
        'BR', 'AR', 'MX', 'CO', 'CL', 'PE', 'VE'
    ];
    
    $result = [];
    foreach ($popular as $code) {
        if (isset($countries[$code])) {
            $result[$code] = $countries[$code];
        }
    }
    
    return $result;
}

/**
 * Format phone number according to country standards
 * @param string $phoneNumber Raw phone number
 * @param string $countryCode 2-letter country code
 * @return string Formatted phone number
 */
function formatPhoneNumber($phoneNumber, $countryCode = 'GB') {
    // Remove all non-digit characters
    $clean = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    if (empty($clean)) {
        return '';
    }
    
    // Get phone code for the country
    $phoneCode = getPhoneCodeByCountryCode($countryCode);
    $phoneCodeDigits = preg_replace('/[^0-9]/', '', $phoneCode);
    
    // Remove country code if it's already included
    if (substr($clean, 0, strlen($phoneCodeDigits)) === $phoneCodeDigits) {
        $clean = substr($clean, strlen($phoneCodeDigits));
    }
    
    // Remove leading zero if present (common in many countries)
    if (substr($clean, 0, 1) === '0') {
        $clean = substr($clean, 1);
    }
    
    return $phoneCode . ' ' . $clean;
}

/**
 * Validate phone number format
 * @param string $phoneNumber Phone number to validate
 * @param string $countryCode 2-letter country code
 * @return bool True if valid
 */
function isValidPhoneNumber($phoneNumber, $countryCode = 'GB') {
    $clean = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    // Basic length validation (most phone numbers are 7-15 digits)
    if (strlen($clean) < 7 || strlen($clean) > 15) {
        return false;
    }
    
    // Country-specific validation can be added here
    return true;
}

/**
 * Generate country selector HTML for phone inputs
 * @param string $selectedCountry Currently selected country code
 * @param string $name HTML name attribute
 * @param string $id HTML id attribute
 * @return string HTML select element
 */
function generateCountryPhoneSelector($selectedCountry = 'GB', $name = 'country_code', $id = 'country_code') {
    $countries = getPopularCountriesWithPhoneCodes();
    
    $html = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '" class="form-select country-selector">';
    
    foreach ($countries as $code => $data) {
        $selected = ($code === $selectedCountry) ? ' selected' : '';
        $html .= sprintf(
            '<option value="%s" data-phone-code="%s"%s>%s %s</option>',
            htmlspecialchars($code),
            htmlspecialchars($data['phone_code']),
            $selected,
            htmlspecialchars($data['phone_code']),
            htmlspecialchars($data['name'])
        );
    }
    
    $html .= '</select>';
    
    return $html;
}

/**
 * Get API endpoint for country detection
 * @return array API response with user's country
 */
function getCountryDetectionAPI() {
    header('Content-Type: application/json');
    
    try {
        $country = detectUserCountry();
        echo json_encode([
            'success' => true,
            'country' => $country
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to detect country',
            'country' => [
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'phone_code' => '+44'
            ]
        ]);
    }
}
?>