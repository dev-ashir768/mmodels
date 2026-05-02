<?php
/**
 * M Models - Dynamic CSV Export
 * Fetches latest data from database and generates CSV
 */

session_start();
if (!isset($_SESSION['loggedin'])) {
    die("Unauthorized access");
}

require '../includes/db.php';

if (!isset($_GET['type'])) {
    die("Form type not specified");
}

$form_type = $_GET['type'];
$filename = "mmodels_" . $form_type . "_" . date('Y-m-d') . ".csv";

// Fetch data from database
try {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE form_type = ? ORDER BY timestamp DESC");
    $stmt->execute([$form_type]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        die("No data found for this form type.");
    }

    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Dynamically build headers from form_data JSON keys
    $headers_set = false;
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

    foreach ($rows as $row) {
        $formData = json_decode($row['form_data'], true);
        $attachments = json_decode($row['attachments'], true);

        if (!$headers_set) {
            $headers = ['Timestamp', 'Form Type'];
            foreach ($formData as $k => $v) {
                $headers[] = ucwords(str_replace('_', ' ', $k));
            }
            if (!empty($attachments)) {
                $headers[] = 'Attachment Links';
            }
            fputcsv($output, $headers);
            $headers_set = true;
        }

        // Build the row
        $csv_row = [$row['timestamp'], $row['form_type']];
        foreach ($formData as $k => $v) {
            if (is_array($v)) {
                $csv_row[] = implode('; ', $v);
            } elseif (is_string($v) && strpos($v, 'data:') === 0) {
                $csv_row[] = "[Base64 Image]"; // Don't put raw base64 in CSV
            } else {
                $csv_row[] = $v;
            }
        }

        // Add attachment URLs
        if (!empty($attachments)) {
            $links = [];
            foreach ($attachments as $path) {
                $links[] = $base_url . '/' . $path;
            }
            $csv_row[] = implode(' | ', $links);
        }

        fputcsv($output, $csv_row);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Export Failed: " . $e->getMessage());
}
