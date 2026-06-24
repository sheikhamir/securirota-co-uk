<?php
if (!function_exists('loadEnvFile')) {
    function loadEnvFile($path) {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (stripos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }

            $separatorPosition = strpos($line, '=');
            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPosition));
            $value = trim(substr($line, $separatorPosition + 1));

            if ($key === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
                continue;
            }

            if (
                (strlen($value) >= 2) &&
                (($value[0] === '"' && substr($value, -1) === '"') ||
                 ($value[0] === "'" && substr($value, -1) === "'"))
            ) {
                $quote = $value[0];
                $value = substr($value, 1, -1);

                if ($quote === '"') {
                    $value = str_replace(
                        ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                        ["\n", "\r", "\t", '"', '\\'],
                        $value
                    );
                }
            } else {
                $value = preg_replace('/\s+#.*$/', '', $value);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('envValue')) {
    function envValue($key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        if (is_string($value)) {
            $lowerValue = strtolower($value);

            if ($lowerValue === 'true') {
                return true;
            }

            if ($lowerValue === 'false') {
                return false;
            }

            if ($lowerValue === 'null') {
                return null;
            }
        }

        return $value;
    }
}

if (!function_exists('defineFromEnv')) {
    function defineFromEnv($constant, $envKey, $default = null, $type = 'string') {
        if (defined($constant)) {
            return;
        }

        $value = envValue($envKey, $default);

        switch ($type) {
            case 'int':
                $value = (int) $value;
                break;
            case 'bool':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'bytes':
                $value = parseByteSize($value, (int) $default);
                break;
        }

        define($constant, $value);
    }
}

if (!function_exists('parseByteSize')) {
    function parseByteSize($value, $default) {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);
        if (!preg_match('/^(\d+)\s*([KMG])?B?$/i', $value, $matches)) {
            return $default;
        }

        $size = (int) $matches[1];
        $unit = strtoupper($matches[2] ?? '');

        switch ($unit) {
            case 'G':
                return $size * 1024 * 1024 * 1024;
            case 'M':
                return $size * 1024 * 1024;
            case 'K':
                return $size * 1024;
            default:
                return $size;
        }
    }
}

loadEnvFile(dirname(__DIR__) . '/.env');
