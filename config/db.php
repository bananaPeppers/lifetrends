<?php
/**
 * config/db.php
 * Returns a PDO instance.
 */

// Helper to get env var from multiple sources (Railway uses $_ENV)
function getEnvVar($keys, $default = '') {
    if (!is_array($keys)) $keys = [$keys];
    foreach ($keys as $key) {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
    }
    return $default;
}

// Railway environment variables (with local fallbacks)
$dbHost = getEnvVar(['MYSQL_HOST', 'MYSQLHOST', 'DB_HOST'], '127.0.0.1');
$dbPort = getEnvVar(['MYSQL_PORT', 'MYSQLPORT', 'DB_PORT'], '3306');
$dbName = getEnvVar(['MYSQL_DATABASE', 'MYSQLDATABASE', 'DB_NAME'], 'railway');
$dbUser = getEnvVar(['MYSQL_USER', 'MYSQLUSER', 'DB_USER'], 'root');
$dbPass = getEnvVar(['MYSQL_PASSWORD', 'MYSQLPASSWORD', 'DB_PASS'], '');
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  return new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}
