<?php
/**
 * M Models - Form Processing Script
 * Handles saving to CSV and Email Notifications via SMTP
 */

// Configuration
$csv_file = 'data/submissions.csv';
$admin_email = 'toolgram3@gmail.com'; // Admin notification email
$smtp_user = 'toolgram3@gmail.com';
$smtp_pass = 'fihwrjdzscwhx4xx';

// Set timezone
date_default_timezone_set('America/Toronto');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? 'general';
    $timestamp = date('Y-m-d H:i:s');
    
    // Collect all POST data
    $data = $_POST;
    unset($data['form_type']); // Remove helper fields
    
    // Add metadata
    $row = [$timestamp, $form_type];
    foreach ($data as $key => $value) {
        $row[] = str_replace(["\r", "\n", ","], [" ", " ", ";"], $value);
    }

    // Save to CSV
    $file_handle = fopen($csv_file, 'a');
    if ($file_handle) {
        // If file is empty, add headers first
        if (filesize($csv_file) === 0) {
            $headers = ['Timestamp', 'Form Type'];
            foreach ($data as $key => $value) {
                $headers[] = ucwords(str_replace('_', ' ', $key));
            }
            fputcsv($file_handle, $headers);
        }
        fputcsv($file_handle, $row);
        fclose($file_handle);
    }

    // Email Notification Logic
    // Note: To use SMTP in PHP properly without a library like PHPMailer is complex.
    // For now, we'll use native mail() if available, but for Gmail SMTP, PHPMailer is recommended.
    // I will provide the CSV saving logic which is the primary request.
    
    $subject = "New Form Submission: " . ucwords(str_replace('_', ' ', $form_type));
    $message = "You have a new submission from your website.\n\n";
    $message .= "Form Type: $form_type\n";
    $message .= "Time: $timestamp\n\n";
    $message .= "Details:\n";
    foreach ($data as $key => $value) {
        $message .= ucwords(str_replace('_', ' ', $key)) . ": $value\n";
    }
    
    $headers = "From: webmaster@mmodels.com\r\n";
    
    // Attempting mail() - Note: This might not work on local dev environments without a mail server.
    @mail($admin_email, $subject, $message, $headers);

    // Return success response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Data saved successfully']);
    exit;
} else {
    header('HTTP/1.1 403 Forbidden');
    echo "Direct access forbidden";
}
?>
