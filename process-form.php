<?php
/**
 * M Models - Form Processing Script
 * Handles saving to CSV and Email Notifications via SMTP
 */

// Configuration
// We dynamically set the csv_file later based on form_type
// $admin_email = 'info@mmodels.ca'; // Now dynamically set below based on form type
$smtp_user = 'toolgram3@gmail.com';
$smtp_pass = 'fihwrjdzscwhxixy';

// Debug Logger
function debugLog($msg) {
    $log_file = __DIR__ . '/data/form_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
}

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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port       = 465; // Port for SSL

        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo('info@mmodels.com', 'M Models');

        // Add attachments
        if (!empty($attachments)) {
            foreach ($attachments as $key => $file) {
                // Check if it's a direct path string
                if (is_string($file)) {
                    if (file_exists($file)) {
                        $mail->addAttachment($file);
                    }
                } 
                // Check if it's a standard PHP upload array
                else if (isset($file['name']) && is_array($file['name'])) {
                    foreach ($file['name'] as $idx => $name) {
                        if (isset($file['tmp_name'][$idx]) && file_exists($file['tmp_name'][$idx])) {
                            $mail->addAttachment($file['tmp_name'][$idx], $name);
                        }
                    }
                } 
                else if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
                    $mail->addAttachment($file['tmp_name'], $file['name']);
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
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
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

    // Ensure data and upload directories exist
    if (!file_exists('data')) {
        mkdir('data', 0755, true);
    }
    $upload_dir = 'uploads/submissions/' . date('Y/m/');
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $form_type = $_POST['form_type'] ?? 'general';

    // Route to correct email based on form type
    switch ($form_type) {
        case 'hire_a_model':
            $admin_email = 'bookings@mmodels.ca';
            break;
        case 'become_a_model':
        case 'Influencer Registration':
        case 'application':
            $admin_email = 'applications@mmodels.ca';
            break;
        case 'contact':
        default:
            $admin_email = 'info@mmodels.ca';
            break;
    }
    $timestamp = date('Y-m-d H:i:s');
    $data = $_POST;
    unset($data['form_type']);

    debugLog("New Submission: $form_type from " . ($data['email'] ?? 'unknown'));

    // --- IMMEDIATE CSV WRITE (Before any other processing) ---
    $sanitized_type = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($form_type));
    if ($form_type === 'Influencer Registration') $sanitized_type = 'influencer_talent';
    
    $csv_file = __DIR__ . "/data/submissions_" . $sanitized_type . ".csv";
    $is_new_file = !file_exists($csv_file);
    $file_handle = fopen($csv_file, 'a');
    
    if ($file_handle) {
        if ($is_new_file) {
            $headers = ['Timestamp', 'Form Type'];
            foreach ($data as $key => $value) {
                $headers[] = ucwords(str_replace('_', ' ', $key));
            }
            fputcsv($file_handle, $headers);
        }
        
        $row = [$timestamp, $form_type];
        foreach ($data as $key => $value) {
            $row[] = is_array($value) ? implode(', ', $value) : (string)$value;
        }
        
        fputcsv($file_handle, $row);
        fflush($file_handle);
        fclose($file_handle);
        chmod($csv_file, 0644);
        debugLog("CSV Write Success: $csv_file");
    } else {
        debugLog("CSV Write FAILED: Could not open $csv_file");
    }

    // --- Continue with File Uploads ---
    $upload_errors = [];
    $attachment_paths = [];
    foreach ($_FILES as $key => $file) {
        if (is_array($file['name'])) {
            foreach ($file['name'] as $idx => $name) {
                if ($file['error'][$idx] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $filename = uniqid($key . '_') . '.' . $ext;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'][$idx], $target_path)) {
                        $attachment_paths[] = $target_path;
                        $data[$key . '_' . $idx] = "[File: $filename]";
                    }
                }
            }
        } else {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid($key . '_') . '.' . $ext;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $attachment_paths[] = $target_path;
                    $data[$key] = "[File: $filename]";
                }
            } else if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE: $err = "File exceeds server limit ($max_upload)"; break;
                    case UPLOAD_ERR_PARTIAL: $err = "Upload was partial"; break;
                    default: $err = "Upload error (" . $file['error'] . ")";
                }
                $data[$key] = $err;
                $upload_errors[] = "$key: $err";
            }
        }
    }

    // Add metadata
    $row = [$timestamp, $form_type];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $row[] = implode(', ', $value);
        } elseif (is_string($value) && strpos($value, 'data:') === 0 && strpos($value, ';base64,') !== false) {
            $row[] = "[Base64 Image]";
        } else {
            $row[] = str_replace(["\r", "\n", ","], [" ", " ", ";"], (string)$value);
        }
    }

    // Set dynamic CSV file based on form type
    $sanitized_type = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($form_type));
    $csv_file = __DIR__ . "/data/submissions_" . $sanitized_type . ".csv";

    // Ensure data directory exists (just in case)
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0755, true);
    }

    // Save to CSV
    $is_new_file = !file_exists($csv_file);
    $file_handle = fopen($csv_file, 'a');
    
    if ($file_handle) {
        // If file is new, add headers first
        if ($is_new_file) {
            $headers = ['Timestamp', 'Form Type'];
            foreach ($data as $key => $value) {
                $headers[] = ucwords(str_replace('_', ' ', $key));
            }
            fputcsv($file_handle, $headers);
        }
        
        fputcsv($file_handle, $row);
        fflush($file_handle);
        fclose($file_handle);
        chmod($csv_file, 0644); // Ensure it's readable
    } else {
        error_log("Failed to open CSV file for writing: " . $csv_file);
    }

    // Save to Database
    try {
        $stmt = $pdo->prepare("INSERT INTO submissions (form_type, form_data, attachments, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $form_type,
            json_encode($data),
            json_encode($attachment_paths),
            $timestamp
        ]);
    } catch (PDOException $e) {
        error_log("Database Insert Failed: " . $e->getMessage());
    }


    // 1. Admin Notification Email
    $admin_subject = "New Form Submission: " . ucwords(str_replace('_', ' ', $form_type));
    
    // Build Details Table for Admin
    $details_html = "";
    foreach ($data as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        if (is_array($value)) {
            $value = implode(', ', $value);
        } elseif (is_string($value) && strpos($value, 'data:') === 0 && strpos($value, ';base64,') !== false) {
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
                            <td style='background-color: #fff; padding: 30px; text-align: center;'>
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

    // 1. Admin Notification Email
    $admin_subject = "!! NEW TALENT: " . ($data['first_name'] ?? 'Inquiry') . " (" . $form_type . ")";
    
    debugLog("Attempting Admin Email to $admin_email");
    // Use moved file paths ($attachment_paths) for attachments instead of $_FILES 
    // because files were moved from temp location already.
    $email_sent = sendEmail($admin_email, $admin_subject, $admin_email_content, 'M Models Scout', $attachment_paths);
    
    if (!$email_sent) {
        debugLog("Admin Email with attachments FAILED. Retrying without attachments...");
        $email_sent = sendEmail($admin_email, "LOW-RES: " . $admin_subject, $admin_email_content . "<p>Check admin panel for HD photos.</p>", 'M Models Scout');
    }
    
    debugLog("Admin Email Final Status: " . ($email_sent ? "SUCCESS" : "FAILED"));

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
                                <td style='background-color: #fff; padding: 40px; text-align: center;'>
                                    <img src='$logo_url' alt='M Models' style='height: 50px; width: auto;'>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 50px; text-align: center;'>
                                    <h1 style='font-family: sans-serif; font-size: 28px; font-weight: 700; color: #C50A76; margin: 0 0 20px 0;'>Hello $first_name,</h1>
                                    <p style='font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #333333; margin: 0 0 25px 0;'>
                                        " . ($form_type === 'Influencer Registration' 
                                            ? "Thank you for applying to join the <strong>M Models Influencer Network</strong>. We’ve received your portfolio and social analytics, and our creative team is excited to review your content style."
                                            : "Thank you for choosing <strong>M Models & Talent Agency</strong>. We’ve successfully received your application and our scouts are eager to review your profile.") . "
                                    </p>
                                    <p style='font-family: sans-serif; font-size: 15px; color: #666666; margin: 0 0 35px 0;'>
                                        " . ($form_type === 'Influencer Registration'
                                            ? "If your profile aligns with our upcoming brand campaigns, one of our talent managers will contact you to discuss representation and partnership opportunities."
                                            : "If your look matches our current portfolio needs, one of our agents will reach out to you directly for a personal interview.") . "
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