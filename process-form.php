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
require 'includes/db.php';


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
    }

    // Save to Database
    try {
        // Separate base64 files from text data for cleaner storage if needed, 
        // but for now we'll store the full data object as JSON.
        $stmt = $pdo->prepare("INSERT INTO submissions (form_type, form_data, timestamp) VALUES (?, ?, ?)");
        $stmt->execute([
            $form_type,
            json_encode($data),
            $timestamp
        ]);
    } catch (PDOException $e) {
        error_log("Database Insert Failed: " . $e->getMessage());
        // We don't exit here because CSV and Email might have succeeded
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
        $details_html .= "<tr>
            <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; font-family: sans-serif; font-size: 13px; font-weight: 600; color: #4a5568; width: 40%;'>$label</td>
            <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; font-family: sans-serif; font-size: 13px; color: #1a202c;'>$value</td>
        </tr>";
    }

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $logo_url = $base_url . "/assets/others/logo.png";

    $admin_email_content = "
    <body style='margin: 0; padding: 0; background-color: #f4f7f9;'>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f9; padding: 40px 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                        <tr>
                            <td style='background-color: #000000; padding: 30px; text-align: center;'>
                                <img src='$logo_url' alt='M Models' style='height: 45px; width: auto;'>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 40px 50px;'>
                                <h1 style='font-family: sans-serif; font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0 0 10px 0;'>New Application Received</h1>
                                <p style='font-family: sans-serif; font-size: 16px; color: #666666; margin: 0 0 30px 0;'>A new submission has been recorded for <strong>" . ucwords(str_replace('_', ' ', $form_type)) . "</strong>.</p>
                                
                                <div style='background-color: #f8fafc; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0;'>
                                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                        $details_html
                                    </table>
                                </div>

                                <div style='margin-top: 40px; padding-top: 25px; border-top: 1px solid #eeeeee; text-align: center;'>
                                    <p style='font-family: sans-serif; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 0;'>Automated System Notification</p>
                                    <p style='font-family: sans-serif; font-size: 12px; color: #94a3b8; margin: 5px 0 0 0;'>Time: $timestamp</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>";

    sendEmail($admin_email, $admin_subject, $admin_email_content, 'M Models System', $_FILES);

    // 2. Applicant Greeting Email
    $applicant_email = $_POST['email'] ?? '';
    if (!empty($applicant_email)) {
        $first_name = $_POST['first_name'] ?? 'there';
        $greet_subject = "Thank you for applying to M Models!";
        
        $greet_email_content = "
        <body style='margin: 0; padding: 0; background-color: #f4f7f9;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f9; padding: 40px 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
                            <tr>
                                <td style='background-color: #000000; padding: 40px; text-align: center;'>
                                    <img src='$logo_url' alt='M Models' style='height: 50px; width: auto;'>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 50px; text-align: center;'>
                                    <h1 style='font-family: sans-serif; font-size: 28px; font-weight: 700; color: #C50A76; margin: 0 0 20px 0;'>Hello $first_name,</h1>
                                    <p style='font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #333333; margin: 0 0 25px 0;'>
                                        Thank you for choosing <strong>M Models & Talent Agency</strong>. We’ve successfully received your application and our scouts are eager to review your profile.
                                    </p>
                                    <p style='font-family: sans-serif; font-size: 15px; color: #666666; margin: 0 0 35px 0;'>
                                        If your look matches our current portfolio needs, one of our agents will reach out to you directly for a personal interview.
                                    </p>
                                    <a href='https://mmodels.ca' style='display: inline-block; background-color: #C50A76; color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(197, 10, 118, 0.2); transition: all 0.3s ease;'>Explore M Models</a>
                                    
                                    <div style='margin-top: 50px; padding-top: 30px; border-top: 1px solid #eeeeee;'>
                                        <p style='font-family: sans-serif; font-size: 14px; color: #999999; margin: 0;'>
                                            Best regards,<br>
                                            <span style='color: #1a1a1a; font-weight: 600;'>The M Models Casting Team</span><br>
                                            <a href='https://mmodels.ca' style='color: #C50A76; text-decoration: none;'>www.mmodels.ca</a>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <p style='font-family: sans-serif; font-size: 11px; color: #a0aec0; text-align: center; margin-top: 20px;'>
                            © 2026 M Models & Talent Agency. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </body>";

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