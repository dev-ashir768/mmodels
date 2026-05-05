<?php
/**
 * M Models - Database Configuration
 */

// $db_host = 'srv1466.hstgr.io';
$db_host = 'localhost';
$db_name = 'u774534919_mmodels_db';
$db_user = 'u774534919_admin';
$db_pass = 'Ty=gj,ja4FF)8Z';

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

    // Create news table if it doesn't exist
    $createNewsTable = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT NOT NULL,
        category VARCHAR(100),
        news_date DATE,
        status VARCHAR(20) DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (news_date),
        INDEX (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($createNewsTable);

} catch (PDOException $e) {
    // Log error and stop if connection fails
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
