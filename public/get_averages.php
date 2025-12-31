<?php
header('Content-Type: application/json; charset=utf-8');
// config/db.php returns a PDO instance
$pdo = require __DIR__ . '/../config/db.php';

try {

    // Current year and month
    $year = (int)date('Y');
    $month = (int)date('m');

    // Month average (current month)
    $stmt = $pdo->prepare('SELECT AVG(`index`) AS avg_index FROM `life` WHERE YEAR(`date`) = :y AND MONTH(`date`) = :m');
    $stmt->execute([':y' => $year, ':m' => $month]);
    $monthAvg = $stmt->fetchColumn();

    // Year average (current year)
    $stmt = $pdo->prepare('SELECT AVG(`index`) AS avg_index FROM `life` WHERE YEAR(`date`) = :y');
    $stmt->execute([':y' => $year]);
    $yearAvg = $stmt->fetchColumn();

    // Overall average
    $stmt = $pdo->query('SELECT AVG(`index`) AS avg_index FROM `life`');
    $overallAvg = $stmt->fetchColumn();

    $format = function ($v) {
        if ($v === null) return null;
        return round((float)$v, 2);
    };

    echo json_encode([
        'success' => true,
        'month' => $format($monthAvg),
        'year'  => $format($yearAvg),
        'overall' => $format($overallAvg),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

