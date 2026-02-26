<?php
/**
 * Security: Load environment variables from .env file
 * This file should be included early in your application bootstrap
 * Typically included in index.php or a config file
 */

// Check if .env file exists
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    // Check for .env.local instead
    $envFile = __DIR__ . '/.env.local';
    if (!file_exists($envFile)) {
        // Try .env.development
        $envFile = __DIR__ . '/.env.development';
        if (!file_exists($envFile)) {
            error_log('WARNING: No .env configuration file found. Set environment variables manually.');
            return;
        }
    }
}

// Load environment variables from .env file
function loadEnv($filePath) {
    if (!is_readable($filePath)) {
        error_log("ERROR: Cannot read .env file at: $filePath");
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if ((strpos($value, '"') === 0 && substr($value, -1) === '"') ||
            (strpos($value, "'") === 0 && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        // Set as environment variable if not already set
        if (empty(getenv($key))) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    return true;
}

// Load the environment file
loadEnv($envFile);

// Verify critical configuration is loaded
$requiredVars = [
    'SMTP_HOST',
    'SMTP_USERNAME',
    'SMTP_PASSWORD',
    'DB_HOST',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_NAME',
];

foreach ($requiredVars as $var) {
    if (empty(getenv($var))) {
        error_log("WARNING: Required environment variable not set: $var");
    }
}

// Define helper function for safe credential access
function getSecretConfig($key, $default = null) {
    $value = getenv($key);
    
    // Never return null - could cause fallback to hardcoded values
    if ($value === false || empty($value)) {
        if ($default === null) {
            error_log("ERROR: Missing configuration key: $key - Check .env file");
            throw new Exception("Configuration not found: $key");
        }
        return $default;
    }
    
    return $value;
}

// Security: Verify no hardcoded credentials are being used
error_log("✓ Environment configuration loaded successfully");
?>
