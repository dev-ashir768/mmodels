<?php
/**
 * M Models - Form Processing Script
 * Handles saving to CSV and Email Notifications via SMTP
 */

// Configuration
// We dynamically set the csv_file later based on form_type
$smtp_user = 'info@mmodels.ca';
$smtp_pass = 'Shahzab889!889&!!!!!';

// Debug Logger
function debugLog($msg)
{
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
function sendEmail($to, $subject, $message, $from_name = 'M Models', $attachments = [])
{
    global $smtp_user, $smtp_pass;
    $mail = new PHPMailer(true);
    $last_error = "";

    // Attempt 1: SSL on 465
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.mmodels.ca';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Timeout = 30; // Increased timeout for attachments

        // Add SSL options for compatibility with some shared hosts
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($smtp_user, 'M Models');

        // Log email details
        debugLog("EMAIL DETAILS:");
        debugLog("  From: $smtp_user ($from_name)");
        debugLog("  To: $to");
        debugLog("  ReplyTo: $smtp_user");
        debugLog("  Subject: $subject");
        debugLog("  Message Length: " . strlen($message) . " chars");
        debugLog("  Message Type: " . ($mail->ContentType));

        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                if (is_string($file) && file_exists($file)) {
                    $mail->addAttachment($file);
                }
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $message));

        $mail->send();
        debugLog("Email sent successfully to $to via SSL/465");
        return ['success' => true];
    } catch (Exception $e) {
        $last_error = $mail->ErrorInfo;
        debugLog("SSL/465 Failed for $to: $last_error. Retrying with TLS/587...");

        // Attempt 2: TLS on 587
        try {
            $mail->clearAddresses();
            $mail->clearAttachments();

            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Timeout = 30;

            $mail->addAddress($to);
            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    if (is_string($file) && file_exists($file)) {
                        $mail->addAttachment($file);
                    }
                }
            }

            $mail->send();
            debugLog("Email sent successfully to $to via TLS/587");
            return ['success' => true];
        } catch (Exception $e2) {
            $last_error = $mail->ErrorInfo;
            debugLog("CRITICAL: Email failed for $to: $last_error");
            error_log("Email could not be sent. Mailer Error: $last_error");
            return ['success' => false, 'error' => $last_error];
        }
    }
}

// Error Reporting for Debugging (Remove in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
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
    switch (trim($form_type)) {
        case 'hire_a_model':
            $admin_email = 'bookings@mmodels.ca';
            break;
        case 'become_a_model':
        case 'Influencer Registration':
        case 'application':
            $admin_email = 'application@mmodels.ca';
            break;
        case 'contact':
        default:
            $admin_email = 'info@mmodels.ca';
            break;
    }
    $timestamp = date('Y-m-d H:i:s');
    $data = $_POST;
    unset($data['form_type']);

    // Debug: Log all incoming POST data
    debugLog("=== NEW SUBMISSION START ===");
    debugLog("Form Type: $form_type");
    debugLog("All POST Data Keys: " . implode(', ', array_keys($data)));
    debugLog("Full POST Data: " . json_encode($data));

    // Check for email in various fields
    $debug_email = '';
    if (!empty($data['email']))
        $debug_email = "email={$data['email']}";
    elseif (!empty($data['E-mail']))
        $debug_email = "E-mail={$data['E-mail']}";
    elseif (!empty($data['Email']))
        $debug_email = "Email={$data['Email']}";
    elseif (!empty($_POST['email']))
        $debug_email = "_POST[email]={$_POST['email']}";
    else
        $debug_email = "NO EMAIL FOUND";

    debugLog("Email Field Check: $debug_email");
    debugLog("New Submission: $form_type from " . ($data['email'] ?? 'unknown'));

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
                    case UPLOAD_ERR_INI_SIZE:
                        $err = "File exceeds server limit ($max_upload)";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $err = "Upload was partial";
                        break;
                    default:
                        $err = "Upload error (" . $file['error'] . ")";
                }
                $data[$key] = $err;
                $upload_errors[] = "$key: $err";
            }
        }
    }

    // Consolidation: CSV writing moved here after all data is processed (including file paths)
    $sanitized_type = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($form_type));
    if ($form_type === 'Influencer Registration')
        $sanitized_type = 'influencer_talent';

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
            if (is_array($value)) {
                $row[] = implode(', ', $value);
            } elseif (is_string($value) && strpos($value, 'data:') === 0 && strpos($value, ';base64,') !== false) {
                $row[] = "[Base64 Image]";
            } else {
                $row[] = str_replace(["\r", "\n", ","], [" ", " ", ";"], (string) $value);
            }
        }

        fputcsv($file_handle, $row);
        fflush($file_handle);
        fclose($file_handle);
        chmod($csv_file, 0666);
        debugLog("CSV Write Success: $csv_file");
    } else {
        debugLog("CSV Write FAILED: Could not open $csv_file");
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

    // 1. Admin Notification Email
    $admin_subject = "!! NEW TALENT: " . ($data['first_name'] ?? ($data['firstName'] ?? 'Inquiry')) . " (" . $form_type . ")";

    // Build Admin Email Body (Note: $details_html and $logo_url are already prepared)
    $admin_email_content = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>New Application Received</title>
    </head>
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
    </body>
    </html>";

    debugLog("Attempting Admin Email to $admin_email");
    $admin_res = sendEmail($admin_email, $admin_subject, $admin_email_content, 'M Models Scout', $attachment_paths);
    $admin_email_sent = $admin_res['success'];
    $admin_error = $admin_res['error'] ?? "";

    if (!$admin_email_sent) {
        debugLog("Admin Email with attachments FAILED. Retrying without attachments...");
        $admin_res = sendEmail($admin_email, "LOW-RES: " . $admin_subject, $admin_email_content . "<p>Check admin panel for HD photos.</p>", 'M Models Scout');
        $admin_email_sent = $admin_res['success'];
        $admin_error = $admin_res['error'] ?? "";
    }

    debugLog("Admin Email Final Status: " . ($admin_email_sent ? "SUCCESS" : "FAILED"));

    // 2. Applicant Greeting Email
    // Try multiple common email field names
    $applicant_email = '';
    $email_field_names = ['email', 'E-mail', 'Email', 'contact_email', 'user_email', 'applicant_email'];
    foreach ($email_field_names as $field) {
        if (!empty($data[$field])) {
            $applicant_email = $data[$field];
            break;
        }
    }

    debugLog("Captured Applicant Email: $applicant_email");
    $applicant_email_sent = false;
    $applicant_error = "";

    if (!empty($applicant_email)) {
        $first_name = $data['first_name'] ?? ($data['firstName'] ?? 'there');
        $greet_subject = "Thank you for applying to M Models!";

        $greet_email_content = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$greet_subject</title>
        </head>
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
            ? "Thank you for applying to join the <strong>M Models Influencer Network</strong>. We've received your portfolio and social analytics, and our creative team is excited to review your content style."
            : "Thank you for choosing <strong>M Models & Talent Agency</strong>. We've successfully received your application and our scouts are eager to review your profile.") . "
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
        </body>
        </html>";

        $applicant_res = sendEmail($applicant_email, $greet_subject, $greet_email_content);
        $applicant_email_sent = $applicant_res['success'];
        $applicant_error = $applicant_res['error'] ?? "";
        debugLog("Applicant Email Status: " . ($applicant_email_sent ? "SUCCESS" : "FAILED"));

        // Also send a plain text test email for verification
        if ($applicant_email_sent) {
            debugLog("Sending PLAIN TEXT confirmation to $applicant_email as fallback...");
            $plain_text_subject = "M Models - Application Received";
            $plain_text_body = "Hello " . $first_name . ",\n\n";
            $plain_text_body .= "Thank you for your application to M Models.\n";
            $plain_text_body .= "We have received your submission and our team will review it shortly.\n\n";
            $plain_text_body .= "Submitted: " . $timestamp . "\n";
            $plain_text_body .= "Form Type: " . $form_type . "\n\n";
            $plain_text_body .= "Best regards,\nM Models Team\nwww.mmodels.ca";

            $plain_test = sendEmail($applicant_email, $plain_text_subject, $plain_text_body);
            debugLog("Plain Text Email Result: " . ($plain_test['success'] ? "SENT" : "FAILED - " . $plain_test['error']));
        }
    } else {
        debugLog("Applicant Email skipped: No email provided in data.");
    }

    // Return response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'status' => ($admin_email_sent) ? 'success' : 'partial_success',
        'message' => ($admin_email_sent) ? 'Application received!' : 'Application saved, but notification failed.',
        'email_status' => [
            'admin' => $admin_email_sent,
            'admin_error' => $admin_error,
            'applicant' => $applicant_email_sent,
            'applicant_error' => $applicant_error,
            'applicant_email' => $applicant_email
        ],
        'debug' => [
            'upload_errors' => $upload_errors,
            'server_limits' => [
                'upload_max_filesize' => $max_upload,
                'post_max_size' => $max_post
            ],
            'all_post_keys' => array_keys($data),
            'email_field_found' => !empty($applicant_email),
            'captured_applicant_email' => $applicant_email,
            'form_type' => $form_type
        ]
    ]);
    exit;
} else {
    header('HTTP/1.1 403 Forbidden');
    echo "Direct access forbidden";
}
?>