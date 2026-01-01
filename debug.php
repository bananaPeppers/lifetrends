<?php
/**
 * debug.php - Check available environment variables
 */

header('Content-Type: application/json; charset=utf-8');

$mysqlVars = [
    'MYSQL_HOST',
    'MYSQL_PORT', 
    'MYSQL_DATABASE',
    'MYSQL_USER',
    'MYSQL_PASSWORD',
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLDATABASE', 
    'MYSQLUSER',
    'MYSQLPASSWORD',
    'MYSQL_URL',
    'MYSQL_PUBLIC_URL',
    'DATABASE_URL',
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'DB_PASS'
];

$found = [];
foreach ($mysqlVars as $var) {
    $val = getenv($var);
    if ($val !== false) {
        // Mask password
        if (stripos($var, 'PASS') !== false || stripos($var, 'URL') !== false) {
            $found[$var] = substr($val, 0, 3) . '***' . substr($val, -3);
        } else {
            $found[$var] = $val;
        }
    }
}

echo json_encode([
    'found_variables' => $found,
    'count' => count($found)
], JSON_PRETTY_PRINT);
