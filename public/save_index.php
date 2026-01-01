<?php
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/New_York');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$date = isset($data['date']) && trim($data['date']) !== '' ? trim($data['date']) : null;
$index = $data['index'] ?? null;

if ($index === null || $index === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing index']);
    exit;
}

$idx = filter_var($index, FILTER_VALIDATE_INT);
if ($idx === false || $idx < -10 || $idx > 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Index must be integer between -10 and 10']);
    exit;
}

if ($date === null) {
    $dbDate = date('Y-m-d');
} else {
    $ts = strtotime($date);
    if ($ts === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date']);
        exit;
    }
    $dbDate = date('Y-m-d', $ts);
}

$pdo = require __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->prepare("INSERT INTO `life` (`date`, `index`) VALUES (:date, :index)");
    $stmt->execute([':date' => $dbDate, ':index' => $idx]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
