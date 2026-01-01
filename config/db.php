<?php
/**
 * config/db.php
 * Returns a PDO instance.
 */

// Railway production environment variables (with local fallbacks)
// Railway uses MYSQL_* format (with underscore)
$dbHost = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$dbName = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$dbUser = getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$dbPass = getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
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
