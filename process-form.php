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
function sendEmail($to, $subject, $message, $from_name = 'M Models', $attachments = []) {
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

        // Add attachments
        if (!empty($attachments)) {
            foreach ($attachments as $key => $file) {
                if (is_array($file['name'])) {
                    // Handle multiple file uploads array structure
                    foreach ($file['name'] as $idx => $name) {
                        if ($file['error'][$idx] === UPLOAD_ERR_OK) {
                            $mail->addAttachment($file['tmp_name'][$idx], $name);
                        }
                    }
                } else {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $mail->addAttachment($file['tmp_name'], $file['name']);
                    }
                }
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $message));

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
    
    // Build Details Table for Admin
    $details_html = "";
    foreach ($data as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        if (is_string($value) && strpos($value, 'data:') === 0 && strpos($value, ';base64,') !== false) {
            $value = "<em>[File Attached]</em>";
        }
        $details_html .= "<tr><td style='padding:10px; border-bottom:1px solid #eee; font-weight:bold; color:#666;'>$label</td><td style='padding:10px; border-bottom:1px solid #eee; color:#333;'>$value</td></tr>";
    }

    $admin_email_content = "
    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
        <div style='background: #000; padding: 20px; text-align: center;'>
            <img src='https://mmodels.ca/assets/others/logo.png' alt='M Models' style='height: 40px;'>
        </div>
        <div style='padding: 30px;'>
            <h2 style='color: #C50A76; margin-top: 0;'>New Submission Received</h2>
            <p style='color: #666;'>You have a new submission from the <strong>" . ucwords(str_replace('_', ' ', $form_type)) . "</strong> form.</p>
            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                $details_html
            </table>
            <div style='margin-top: 30px; font-size: 12px; color: #999; text-align: center;'>
                Received at $timestamp
            </div>
        </div>
    </div>";

    sendEmail($admin_email, $admin_subject, $admin_email_content, 'M Models System', $_FILES);

    // 2. Applicant Greeting Email
    $applicant_email = $_POST['email'] ?? '';
    if (!empty($applicant_email)) {
        $first_name = $_POST['first_name'] ?? 'there';
        $greet_subject = "Thank you for applying to M Models!";
        
        $greet_email_content = "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
            <div style='background: #000; padding: 20px; text-align: center;'>
                <img src='https://mmodels.ca/assets/others/logo.png' alt='M Models' style='height: 40px;'>
            </div>
            <div style='padding: 30px; text-align: center;'>
                <h2 style='color: #C50A76; margin-top: 0;'>Hello $first_name,</h2>
                <p style='color: #333; line-height: 1.6;'>Thank you for submitting your application to <strong>M Models & Talent Agency</strong>. We have received your details and our team will review your profile shortly.</p>
                <p style='color: #666; font-size: 14px;'>If your look matches our current client requirements, our casting team will contact you for an interview.</p>
                <div style='margin: 30px 0;'>
                    <a href='https://mmodels.ca' style='background: #C50A76; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Visit Website</a>
                </div>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='color: #999; font-size: 12px;'>Best regards,<br>M Models Team<br><a href='https://mmodels.ca' style='color: #C50A76;'>www.mmodels.ca</a></p>
            </div>
        </div>";

        sendEmail($applicant_email, $greet_subject, $greet_email_content);
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