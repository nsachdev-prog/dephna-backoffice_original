<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";
$from = "robot@dephna.com";
$fromname = "Dephna Robot";
$emailto = array(
    "rajive@dephna.com",
    "phooisee@dephna.com",
    "nimesh@dephna.com",
    "julie@dephna.com"
);
$body = "<p>A utility payment has been made.</p>\n\n";
$body .= "<p><b>Supplier:</b> $suppname<br>\n";
$body .= "<b>Site:</b> $sitename<br>\n";
$body .= "<b>Amount:</b> " . number_format($amount, 2) . "\n";
if ($reference) {
    $body .= "<br><b>Reference:</b> $reference\n";
}
$body .= "</p>\n";
$body .= "<p><b>Allocations:</b></p>\n";
if ($emailAllocations) {
    $body .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n";
    $body .= $emailAllocations;
    $body .= "</table>\n";
    $subject = "Utility Payment Notification";
} else {
    $body .= "<p>No allocations.</p>\n";
    $subject = "Utility Unallocated Payment Notification";
}
$body .= "<p>Entered by {$_SESSION['username']}</p>\n";

$mail = new PHPMailer(true);
try {
    include('../../cron/SMTP.php');

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($from, $fromname);
    foreach ($emailto as $to) {
        $mail->addAddress($to);
    }
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->send();
}
catch (Exception $e) {
    $resulMessage .= "<span class=\"redtext\">Notification could not be sent. Mailer Error: {$mail->ErrorInfo}</span>";
}
?>