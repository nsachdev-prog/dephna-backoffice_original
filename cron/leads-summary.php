#!/usr/bin/php

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');

$html = "";
$resultMessage = "";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$html = "<p><b>Leads Summary</b></p>\r\n";
	$yesterday = date("Y-m-d",strtotime("-1 Day"));
	$thismonth = date("Y-m");
	$lastmonth = date("Y-m",strtotime("-1 Month"));
	$twomonths = date("Y-m",strtotime("-2 Months"));
	$yesterdayTxt = date("d-m-Y",strtotime("-1 Day"));

	$html .= "<table cellspacing=\"5\" cellpadding=\"2\">\n";
	$html .= "<tr>\n";
	$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
	$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>$yesterdayTxt<br>00:00 - 23:59</b></td>\n";
	$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>This Month</b></td>\n";
	$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Last Month</b></td>\n";
	$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Two Months Ago</b></td>\n";
	$html .= "</tr>\n";
	// Web
	$query   = "SELECT
					COUNT(CASE WHEN tdatetime LIKE '$yesterday%' AND crby = 'Web' THEN 1 ELSE NULL END) AS yesterday,
					COUNT(CASE WHEN tdatetime LIKE '$thismonth%' AND crby = 'Web' THEN 1 ELSE NULL END) AS thismonth,
					COUNT(CASE WHEN tdatetime LIKE '$lastmonth%' AND crby = 'Web' THEN 1 ELSE NULL END) AS lastmonth,
					COUNT(CASE WHEN tdatetime LIKE '$twomonths%' AND crby = 'Web' THEN 1 ELSE NULL END) AS twomonths
				FROM leads_tickets
	";
	$result  = $dbcnx->query($query);
	$row     = $result->fetch_assoc();
	$html .= "<tr>\n";
	$html .= "	<td>Web</td>\n";
	$html .= "	<td align=\"right\">{$row['yesterday']}</td>\n";
	$html .= "	<td align=\"right\">{$row['thismonth']}</td>\n";
	$html .= "	<td align=\"right\">{$row['lastmonth']}</td>\n";
	$html .= "	<td align=\"right\">{$row['twomonths']}</td>\n";
	$html .= "</tr>\n";
	// Moneypenny
	$query   = "SELECT
					COUNT(CASE WHEN tdatetime LIKE '$yesterday%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS yesterday,
					COUNT(CASE WHEN tdatetime LIKE '$thismonth%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS thismonth,
					COUNT(CASE WHEN tdatetime LIKE '$lastmonth%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS lastmonth,
					COUNT(CASE WHEN tdatetime LIKE '$twomonths%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS twomonths
				FROM leads_tickets
	";
	$result = $dbcnx->query($query);
	$row = $result->fetch_assoc();
	$html .= "<tr>\n";
	$html .= "	<td>Moneypenny</td>\n";
	$html .= "	<td align=\"right\">{$row['yesterday']}</td>\n";
	$html .= "	<td align=\"right\">{$row['thismonth']}</td>\n";
	$html .= "	<td align=\"right\">{$row['lastmonth']}</td>\n";
	$html .= "	<td align=\"right\">{$row['twomonths']}</td>\n";
	$html .= "</tr>\n";
	// LiveChat
	$query   = "SELECT
					COUNT(CASE WHEN tdatetime LIKE '$yesterday%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS yesterday,
					COUNT(CASE WHEN tdatetime LIKE '$thismonth%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS thismonth,
					COUNT(CASE WHEN tdatetime LIKE '$lastmonth%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS lastmonth,
					COUNT(CASE WHEN tdatetime LIKE '$twomonths%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS twomonths
				FROM leads_tickets
	";
	$result = $dbcnx->query($query);
	$row = $result->fetch_assoc();
	$html .= "<tr>\n";
	$html .= "	<td>LiveChat</td>\n";
	$html .= "	<td align=\"right\">{$row['yesterday']}</td>\n";
	$html .= "	<td align=\"right\">{$row['thismonth']}</td>\n";
	$html .= "	<td align=\"right\">{$row['lastmonth']}</td>\n";
	$html .= "	<td align=\"right\">{$row['twomonths']}</td>\n";
	$html .= "<tr>\n";
	$html .= "</table>\n";
	
	$dbcnx->close();

} else {
	$resultMessage = "<b>Could not connect to database $dbname!</b><br>" . $dbcnx->connect_error;
}

if ($resultMessage) {
	$html .= "<p>$resultMessage</p>\n";
}

require "../PHPMailer/src/SMTP.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/Exception.php";

$fromname = "Dephna Robot";
$fromemail = "robot@dephna.com";
$subject = "Dephna Leads Summary";

$emails = array(
	"rajive@dephna.com",
	"nimesh@dephna.com",
	"brindha@dephna.com",
	"emily@seventhelement.agency",
	"gabriel@seventhelement.agency"
);

$mail = new PHPMailer(true);

try {

	include('SMTP.php');
	
	$mail->setFrom($fromemail, $fromname);
	foreach ($emails as $email) {
		$mail->addAddress($email);
	}
	$mail->isHTML(true);
	$mail->Subject = $subject;
	$mail->Body    = $html;

	$mail->send();

	echo "Email sent successfully<br>";

} catch (Exception $e) {
	echo "Email FAILED Mailer Error: {$mail->ErrorInfo}<br>";
}
?>
