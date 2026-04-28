<?php
/**
 * M Models - Form Processing Script
 * Handles saving to CSV and Email Notifications via SMTP
 */

// Configuration
// We dynamically set the csv_file later based on form_type
$admin_email = 'toolgram3@gmail.com'; // Admin notification email
$smtp_user = 'toolgram3@gmail.com';
$smtp_pass = 'fihwrjdzscwhxixy';

// PHPMailer configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Helper function to send email via SMTP
function sendEmail($to, $subject, $message, $from_name = 'M Models') {
    global $smtp_user, $smtp_pass;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo('info@mmodels.com', 'M Models');

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

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

    foreach ($_FILES as $key => $file) {
        if (is_array($file['name'])) {
            // Handle multiple file uploads array structure if needed
            // Currently assuming single file per key or specific naming
            foreach ($file['name'] as $idx => $name) {
                if ($file['error'][$idx] === UPLOAD_ERR_OK) {
                    $type = pathinfo($name, PATHINFO_EXTENSION);
                    $img_data = file_get_contents($file['tmp_name'][$idx]);
                    $base64 = 'data:' . $file['type'][$idx] . ';base64,' . base64_encode($img_data);
                    $data[$key . '_' . $idx] = $base64;
                }
            }
        } else {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $type = pathinfo($file['name'], PATHINFO_EXTENSION);
                $img_data = file_get_contents($file['tmp_name']);
                $mime = isset($file['type']) && !empty($file['type']) ? $file['type'] : 'application/octet-stream';
                $base64 = 'data:' . $mime . ';base64,' . base64_encode($img_data);
                $data[$key] = $base64;
            } else {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $err = "File exceeds server limit ($max_upload)";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $err = "File exceeds form limit";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $err = "Upload was partial";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $err = "No file selected";
                        break;
                    default:
                        $err = "Upload error (" . $file['error'] . ")";
                }
                $data[$key] = $err;
                $upload_errors[] = "$key: $err";
            }
        }
    }

    // Add metadata
    $row = [$timestamp, $form_type];
    foreach ($data as $key => $value) {
        // Handle arrays (e.g., multi-select or checkboxes)
        if (is_array($value)) {
            $value = implode('; ', $value);
        }
        if (is_string($value) && strpos($value, 'data:') === 0 && strpos($value, ';base64,') !== false) {
            $row[] = $value;
        } else {
            $row[] = str_replace(["\r", "\n", ","], [" ", " ", ";"], $value);
        }
    }

    // Set dynamic CSV file based on form type
    $csv_file = "data/submissions_" . preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($form_type)) . ".csv";

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
        if (is_string($value) && strpos($value, 'data:') === 0 && strpos($value, ';base64,') !== false) {
            $admin_message .= ucwords(str_replace('_', ' ', $key)) . ": [File/Image Stored in CSV]\n";
        } else {
            $admin_message .= ucwords(str_replace('_', ' ', $key)) . ": $value\n";
        }
    }

    sendEmail($admin_email, $admin_subject, $admin_message, 'M Models System');

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

        sendEmail($applicant_email, $greet_subject, $greet_message);
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