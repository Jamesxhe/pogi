<?php
$configFile = __DIR__ . '/config.local.php';
if (!is_file($configFile)) {
    error_log('PUPick database configuration is missing: config.local.php');
    http_response_code(500);
    exit('Database setup is incomplete. Copy config.local.php.example to config.local.php and set your database credentials.');
}

$dbConfig = require $configFile;
$requiredConfig = ['host', 'database', 'username', 'password'];
if (
    !is_array($dbConfig)
    || array_diff($requiredConfig, array_keys($dbConfig))
    || !is_string($dbConfig['host'])
    || !is_string($dbConfig['database'])
    || !is_string($dbConfig['username'])
    || !is_string($dbConfig['password'])
    || $dbConfig['host'] === ''
    || $dbConfig['database'] === ''
    || $dbConfig['username'] === ''
    || $dbConfig['password'] === ''
    || $dbConfig['password'] === 'CHANGE_THIS_TO_A_NON_EMPTY_PASSWORD'
) {
    error_log('PUPick database configuration is incomplete or uses a placeholder password.');
    http_response_code(500);
    exit('Database setup is incomplete. Set a non-empty database password in config.local.php.');
}

try {
    $conn = new PDO(
        'mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['database'] . ';charset=utf8mb4',
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Avoid exposing database credentials/details to visitors.
    error_log("PUPick AI database connection error: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Check your XAMPP and db.php settings.");
}
