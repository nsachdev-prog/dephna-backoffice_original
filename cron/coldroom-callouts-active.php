#!/usr/bin/php

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$cronjob = 1;
chdir(__DIR__);
include_once('../public_html/backoffice/settings.php');

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    $active = 0;

    $sql = "SELECT a.*, s.title AS sitename, st.username AS assigned
            FROM xweb_actions AS a
            LEFT JOIN sites AS s ON s.ID = a.siteid
            LEFT JOIN staff AS st ON st.ID = a.assignedto
            WHERE a.xstatus = '1' AND a.cleared = '0'
            ORDER BY a.xdatetime
    ";
    if (($xresult = $dbcnx->query($sql)) AND ($xresult->num_rows)) {
        $resultMessage = "<tr><td><b>Active Since</b></td><td><b>Site</b></td><td><b>Unit</b></td><td><b>Client</b></td><td><b>Assigned To</b></td></tr>";
        while ($row = $xresult->fetch_assoc()) {
            $resultMessage .= "<tr><td>{$row['xdatetime']}</td><td>{$row['sitename']}</td><td>{$row['unitname']}</td><td>{$row['compname']}</td><td>{$row['assigned']}</td></tr>";
            $active++;
        }
    } else {
        $resultMessage .= "<tr><td colspan=\"5\">No Active Callouts Found!<br>" . $dbcnx->error . "</td></tr>";
    }

	$dbcnx->close();

} else {
	$resultMessage .= "<tr><td colspan=\"5\"><b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error . "</td></tr>";
}

if ($active > 0) {

    require "../PHPMailer/src/SMTP.php";
    require "../PHPMailer/src/PHPMailer.php";
    require "../PHPMailer/src/Exception.php";

    $fromname  = "Dephna System";
    $fromemail = "system@dephna.com";
    $subject   = "Cold Room Callouts Still Active";
    $message   = "<p><b>Cold Room Callouts Still Active</b></p>";
    $message   .= "<table cellspacing=\"0\" cellpadding=\"4\">$resultMessage</table>";

    $mail = new PHPMailer(true);

    try {

        include('SMTP.php');

        $mail->setFrom($fromemail, $fromname);
        $mail->addAddress("support@dephna.com", "Dephna Support");
        // $mail->addAddress("david@davidsherwood.co.uk", "David Sherwood");
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();

    } catch (Exception $e) {
        echo "Email FAILED Mailer Error: {$mail->ErrorInfo}";
    }

    // Prepare WhatsApp notification
    $postData = array(
        'group_admin' => $WA_GROUP_ADMIN,
        'group_name' => $WA_GROUP_NAME,
        'message' => "There are currently $active active cold room callouts."
    );
    $headers = array(
        'Content-Type: application/json',
        'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
        'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
    );
    $url = $WA_URL . $WA_INSTANCE_ID;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    $response = curl_exec($ch);
    // echo "Response: ".$response;
    curl_close($ch);

}
?>
