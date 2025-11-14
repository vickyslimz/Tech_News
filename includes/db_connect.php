<?php
/**
 * Load DB credentials from a local .env file (ignored by git) or environment variables.
 * Falls back to sane defaults for local development so secrets never end up in version control.
 */
$envPath = dirname(__DIR__) . '/.env';
$env = [];

if (is_readable($envPath)) {
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
}

$getEnv = static function (string $key, $default = null) use ($env) {
    if (array_key_exists($key, $env) && $env[$key] !== '') {
        return $env[$key];
    }

    $value = getenv($key);
    return $value !== false && $value !== '' ? $value : $default;
};

$host = $getEnv('DB_HOST', 'localhost');
$dbname = $getEnv('DB_NAME', 'blog_db');
$username = $getEnv('DB_USER', 'root');
$password = $getEnv('DB_PASS', '');
$port = (int) $getEnv('DB_PORT', 3306);

try {
    // ADD THE PORT TO THE CONNECTION STRING
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
