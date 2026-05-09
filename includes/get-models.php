<?php
/**
 * M Models - API to fetch models
 */
require 'db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM models ORDER BY CASE WHEN sequence = 0 THEN 999999 ELSE sequence END ASC, id DESC");
    $models = $stmt->fetchAll();
    
    // Process JSON strings for output
    foreach ($models as &$m) {
        $m['measurements'] = json_decode($m['measurements'], true);
        $m['images'] = json_decode($m['images'], true);
    }
    
    echo json_encode(['status' => 'success', 'data' => $models]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
