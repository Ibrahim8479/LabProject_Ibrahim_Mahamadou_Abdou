<?php
function loadEnv($path = '.env') {
    $envPath = __DIR__ . '/../' . $path;
    
    if (!file_exists($envPath)) {
        die('Error: .env file not found at: ' . $envPath);
    }
    
    if (!is_readable($envPath)) {
        die('Error: .env file is not readable. Please run: chmod 644 ' . $envPath);
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        die('Error: Cannot read .env file. Check file permissions.');
    }
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');

            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

try {
    loadEnv('.env');
} catch (Exception $e) {
    die('Error loading environment: ' . $e->getMessage());
}
?>
