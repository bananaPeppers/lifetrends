<?php
header('Content-Type: application/json; charset=utf-8');
// return averages for the same month/week/day one year ago
$pdo = require __DIR__ . '/../config/db.php';

try {
    $today = new DateTime();
    $oneYearAgo = (clone $today)->modify('-1 year');
    $lyYear = (int)$oneYearAgo->format('Y');
    $lyMonth = (int)$oneYearAgo->format('m');
    $lyDate = $oneYearAgo->format('Y-m-d');
    $lyWeek = (int)$oneYearAgo->format('W');

    // month average (same month last year)
    $stmt = $pdo->prepare('SELECT AVG(`index`) FROM `life` WHERE YEAR(`date`) = :y AND MONTH(`date`) = :m');
    $stmt->execute([':y' => $lyYear, ':m' => $lyMonth]);
    $monthAvg = $stmt->fetchColumn();

    // week average (week number same as the date one year ago)
    // use WEEK(...,3) to use ISO-8601 week; compute week number for the date one year ago in PHP
    $stmt = $pdo->prepare('SELECT AVG(`index`) FROM `life` WHERE YEAR(`date`) = :y AND WEEK(`date`, 3) = :w');
    $stmt->execute([':y' => $lyYear, ':w' => $lyWeek]);
    $weekAvg = $stmt->fetchColumn();

    // day average (exact same date last year)
    $stmt = $pdo->prepare('SELECT AVG(`index`) FROM `life` WHERE `date` = :d');
    $stmt->execute([':d' => $lyDate]);
    $dayAvg = $stmt->fetchColumn();

    $format = function ($v) {
        if ($v === null) return null;
        return round((float)$v, 2);
    };

    echo json_encode([
        'success' => true,
        'month' => $format($monthAvg),
        'week' => $format($weekAvg),
        'day' => $format($dayAvg),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
