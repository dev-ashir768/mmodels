<?php
/**
 * M Models - Database Configuration
 */

$db_host = 'localhost';
$db_name = 'mmodels_db';
$db_user = 'ashir';
$db_pass = 'Ty=gj,ja4\'FF)8Z';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create submissions table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        form_type VARCHAR(50),
        form_data LONGTEXT,
        attachments LONGTEXT,
        status VARCHAR(20) DEFAULT 'new',
        INDEX (form_type),
        INDEX (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($createTable);

} catch (PDOException $e) {
    // Log error and stop if connection fails
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
