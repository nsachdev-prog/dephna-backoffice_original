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

	// get field names and defaults for cold room reports
	$rlines = file('../public_html/backoffice/coldroom-reports-fields.csv', FILE_IGNORE_NEW_LINES);

	// get next report number
	$sql = "SELECT `reportno` FROM `cr_reports_headers` ORDER BY `reportno` DESC LIMIT 0, 1";
	if (($result = $dbcnx->query($sql) AND $result->num_rows)) {
		$row = $result->fetch_assoc();
		$reportno = $row['reportno'];
		if ($reportno < 20001) {
			$reportno = 20001;
		} else {
			$reportno++;
		}
	} else {
		$reportno = 20001;
	}

    $now   = time();
	$count = 0;
	$nextservyear = date("Y");
    $resultMessage = "";

	$nextserv = "Dec 22nd $nextservyear";
    $sdate    = date("Y-01-05");
    $stime    = "08:00";
    $ftime    = "17:00";
    $engname  = "Ahmed";
    $engnotes = "Report produced on January 5th for yearly service completed in December";

	// get all cold rooms
	$sql = "SELECT ID, siteid, title
			FROM units
			WHERE unittype = 'Cold Room'
			ORDER BY siteid, title 
	";
	if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
		while ($row = $result->fetch_assoc()) {
			$unitid = $row['ID'];
			$siteid = $row['siteid'];
			$unitname = $row['title'];

			list($junk,$unitname) = explode(" ",$unitname);					
			
			$sql = "INSERT INTO cr_reports_headers VALUES (
				null,
				'$reportno',
				'Yearly',
				'$sdate',
				'$stime',
				'$ftime',
				'AF Refrigeration',
				'2291474862',
				'$siteid',
				'$unitid',
				'$unitname',
				'$nextserv',
				'$engnotes',
				'$engname',
				'',
				'',
				'',
				'$now',
				'Cronjob',
				'$now',
				'Cronjob',
				'$now',
				'Cronjob'
			)";
			if ($dbcnx->query($sql)) {
				$rid = $dbcnx->insert_id;
				$resultMessage .= "<br>Added Header Siteid:$siteid Unit:$unitname<br>Codes:";
				$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','ID:$rid Siteid:$siteid Unit:$unitname','Coldroom_Reports','$now')");
				// add new
				foreach ($rlines as $thisline) {
					list($lsect,$lcode,$ltitle,$lvalue) = explode(",",$thisline);
					$sql = "INSERT INTO cr_reports_lines VALUES (
						null,
						'$rid',
						'$lsect',
						'$lcode',
						'$lvalue',
						'',
						'0',
						'0'
					)";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "$lcode ";
					} else {
						$resultMessage .= "<span class=\"redtext\">$lcode</span> ";
					}
				}
				$resultMessage .= "<br>";
			} else {
				$resultMessage .= "<span class=\"redtext\">Could not add header Siteid:$siteid Unit:$unitname<br>" . $dbcnx->error;
			}
			$reportno++;
			$count++;
		}
		$resultMessage .= "<br>$count Reports Added<br>";
	} else {
		$resultMessage .= "No Cold Rooms Found!<br>" . $dbcnx->error;
	}
		
	$dbcnx->close();

} else {
	$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
}

// Mail the processing report to $reportEmail

require "../PHPMailer/src/SMTP.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/Exception.php";

$fromname  = "Robot";
$fromemail = "robot@dephna.com";
$subject   = "Cold Room Yearly Reports";
$message   = "<p><b>Result</b></p><p>Red means error.</p><p>$resultMessage</p>";

$mail = new PHPMailer(true);

try {

	include('SMTP.php');

	$mail->setFrom($fromemail, $fromname);
	$mail->addAddress("rajive@dephna.com", "Rajive Sachdev");
	$mail->addAddress("david@davidsherwood.co.uk", "David Sherwood");
	$mail->isHTML(true);
	$mail->Subject = $subject;
	$mail->Body    = $message;

	$mail->send();

} catch (Exception $e) {
	echo "Email FAILED Mailer Error: {$mail->ErrorInfo}<br>";
}
?>
