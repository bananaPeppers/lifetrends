<?php
/**
 * migrate.php
 * Database migration script - creates the life table
 * 
 * Usage:
 *   Local: http://localhost/ratemylife/migrate.php
 *   Production: https://yourdomain.railway.app/migrate.php
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    // Create life table
    $sql = "
    CREATE TABLE IF NOT EXISTS `life` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `date` DATE NOT NULL,
      `index` TINYINT NOT NULL COMMENT 'Daily happiness index (-10 to +10)',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY `unique_date` (`date`),
      INDEX `idx_date` (`date`),
      INDEX `idx_index` (`index`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Daily life satisfaction index tracking'
    ";
    
    $pdo->exec($sql);
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'life'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        // Get table info
        $stmt = $pdo->query("DESCRIBE `life`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Migration completed successfully',
            'table' => 'life',
            'columns' => $columns
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Table creation failed'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Migration failed: ' . $e->getMessage()
    ]);
}
