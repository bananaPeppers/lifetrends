<?php
// Endpoint to save today's index into `life` table
header('Content-Type: application/json; charset=utf-8');
// Allow same-origin POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    // Fallback to form-encoded
    $data = $_POST;
}

$date = isset($data['date']) && trim($data['date']) !== '' ? trim($data['date']) : null;
$index = isset($data['index']) ? $data['index'] : null;

// validate index
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

// if no date supplied by client, use server's current date
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

try {
    $pdo = require __DIR__ . '/../config/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB config error']);
    exit;
}

try {
    $sql = "INSERT INTO `life` (`date`, `index`) VALUES (:date, :index)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $dbDate, ':index' => $idx]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
