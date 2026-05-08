<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendEmail()
{
    try {
        // extract((array)$request);
        // include_once("../../library/phpmailer/class.phpmailer.php");
        $mail = new PHPMailer;
        $mail->SMTPDebug = 2;
        $mail->IsSMTP();
        $mail->Host       = 'smtp.mmodels.ca';
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->Username   = 'info@mmodels.ca';
        $mail->Password   = 'Shahzab889!889&!!!!!';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom('info@mmodels.ca', 'M Models'); // Set the sender's email address and name
        $mail->Timeout = 3600;
        $mail->Subject = 'test sub';
        $mail->AddAddress('application@mmodels.ca');
        $mail->MsgHTML("hello test smtp");
        $return = $mail->Send();
        return $return;die;
        if ($return == 1) {
            return json_encode(["status" => 1, "message" => "Email Sent", "payload" => []]);
        } else {
            return json_encode(["status" => 0, "message" => "Failed to sent email", "payload" => []]);
        }
        return $return;
    } catch (Exception $error) {
        return json_encode(["status" => 0, "message" => "Failed to sent email", "payload" => $error->getMessage()]);
    }
}

print_r(sendEmail());