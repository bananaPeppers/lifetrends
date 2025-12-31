<?php
// Simple DB test page: shows rows from `life` table using config/db.php
try {
    $pdo = require __DIR__ . '/../config/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo "Could not load DB config: " . htmlspecialchars($e->getMessage());
    exit;
}

try {
    $sql = "SELECT `id`, `date`, `index` FROM `life` ORDER BY `date` DESC LIMIT 200";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Query failed: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RateMyLife — DB Test</title>
  <link rel="stylesheet" href="../Assets/css/style.css">
  <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #ccc;text-align:left}</style>
</head>
<body>
  <main class="app-container">
    <header class="top">
      <div class="date-pill">
        <h1>DB Test — life table</h1>
      </div>
      <div class="save-area">
        <a href="index.php" class="save-btn">Back</a>
      </div>
    </header>

    <section>
      <?php if (empty($rows)): ?>
        <p>No rows found in the <strong>life</strong> table.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr><th>id</th><th>date</th><th>index</th></tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['id']); ?></td>
              <td><?php echo htmlspecialchars($r['date']); ?></td>
              <td><?php echo htmlspecialchars($r['index']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
