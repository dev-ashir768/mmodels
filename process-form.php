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

// Error Reporting for Debugging (Remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check server limits
    $max_upload = ini_get('upload_max_filesize');
    $max_post = ini_get('post_max_size');

    // Ensure data directory exists
    if (!file_exists('data')) {
        mkdir('data', 0755, true);
    }

    $form_type = $_POST['form_type'] ?? 'general';
    $timestamp = date('Y-m-d H:i:s');
    
    // Collect all POST data
    $data = $_POST;
    unset($data['form_type']); // Remove helper fields
    
    // Handle File Uploads (Convert to Base64)
    $upload_errors = [];
    $photo_keys = ['photo1', 'photo2', 'photo3'];
    
    foreach ($photo_keys as $key) {
        if (isset($_FILES[$key])) {
            $file = $_FILES[$key];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $type = pathinfo($file['name'], PATHINFO_EXTENSION);
                $img_data = file_get_contents($file['tmp_name']);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($img_data);
                $data[$key] = $base64;
            } else {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $err = "File exceeds server limit ($max_upload)"; break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $err = "File exceeds form limit"; break;
                    case UPLOAD_ERR_PARTIAL:
                        $err = "Upload was partial"; break;
                    case UPLOAD_ERR_NO_FILE:
                        $err = "No photo selected"; break;
                    default:
                        $err = "Upload error (" . $file['error'] . ")";
                }
                $data[$key] = $err;
                $upload_errors[] = "$key: $err";
            }
        } else {
            $data[$key] = "Not sent by browser";
        }
    }

    // Add metadata
    $row = [$timestamp, $form_type];
    foreach ($data as $key => $value) {
        // Handle arrays (e.g., multi-select or checkboxes)
        if (is_array($value)) {
            $value = implode('; ', $value);
        }
        // If it's a base64 image, don't strip commas/newlines as it breaks the encoding
        if (strpos($value, 'data:image/') === 0) {
            $row[] = $value;
        } else {
            $row[] = str_replace(["\r", "\n", ","], [" ", " ", ";"], $value);
        }
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
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Cannot open CSV file. Check permissions.']);
        exit;
    }

    // 1. Admin Notification Email
    $admin_subject = "New Form Submission: " . ucwords(str_replace('_', ' ', $form_type));
    $admin_message = "You have a new submission from your website.\n\n";
    $admin_message .= "Form Type: $form_type\n";
    $admin_message .= "Time: $timestamp\n\n";
    $admin_message .= "Details:\n";
    foreach ($data as $key => $value) {
        if (strpos($value, 'data:image/') === 0) {
            $admin_message .= ucwords(str_replace('_', ' ', $key)) . ": [Image Stored in CSV]\n";
        } else {
            $admin_message .= ucwords(str_replace('_', ' ', $key)) . ": $value\n";
        }
    }
    
    $headers = "From: info@mmodels.com\r\n";
    @mail($admin_email, $admin_subject, $admin_message, $headers);

    // 2. Applicant Greeting Email
    $applicant_email = $_POST['email'] ?? '';
    if (!empty($applicant_email)) {
        $greet_subject = "Thank you for applying to M Models!";
        $greet_message = "Hello " . ($_POST['first_name'] ?? 'there') . ",\n\n";
        $greet_message .= "Thank you for submitting your application to M Models & Talent Agency. We have received your details and our team will review your profile shortly.\n\n";
        $greet_message .= "If your look matches our current client requirements, we will contact you for an interview.\n\n";
        $greet_message .= "Best regards,\n";
        $greet_message .= "M Models Team\n";
        $greet_message .= "www.mmodels.ca";
        
        @mail($applicant_email, $greet_subject, $greet_message, $headers);
    }

    // Return response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => 'Application received!',
        'debug' => [
            'upload_errors' => $upload_errors,
            'server_limits' => [
                'upload_max_filesize' => $max_upload,
                'post_max_size' => $max_post
            ]
        ]
    ]);
    exit;
} else {
    header('HTTP/1.1 403 Forbidden');
    echo "Direct access forbidden";
}
?>
