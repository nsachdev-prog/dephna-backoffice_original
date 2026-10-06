#!/usr/bin/php

<?php
// cron-support.php - Written by David Sherwood - dsi3.net
// Checks the age of support tickets
// Warns clients after 48 Hours // removed
// Closes tickets after another 72 Hours

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$cronjob = 1;
chdir(dirname(__FILE__));
include('../public_html/backoffice/settings.php');
$reportEmail   = "rajive@dephna.com";
// $reportEmail = "david@davidsherwood.co.uk";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$threeDaysAgo = date("Y-m-d H:i",strtotime("-3 Days"));

	// run through the open/replied tickets
	$sql = "SELECT `ID`, `ticketno`, `subject`
			  FROM `support_tickets`
			 WHERE `status` = 'Open'
			   AND `replied` = '1'
			   AND `flagged` = '0'
	";
	if ($result = $dbcnx->query($sql) AND $result->num_rows) {
		while ($row = $result->fetch_assoc()) {
			$tid       = $row['ID'];
			$ticketno  = $row['ticketno'];
			$subject   = $row['subject'];

			// check age of newest email
			$sql = "SELECT `edatetime`
					  FROM `support_emails`
					 WHERE `tid` = '$tid'
					 ORDER BY `edatetime` DESC
					 LIMIT 0,1
			";
			if ($eresult = $dbcnx->query($sql) AND $eresult->num_rows) {
				$erow = $eresult->fetch_assoc();
				$edatetime = $erow['edatetime'];

				$resultMessage .= "<br><b>$edatetime - Tkt:$ticketno - $subject</b><br>\n";

				if ($edatetime < $threeDaysAgo) {

					$resultMessage .= "Last Response $edatetime<br>\n";

					// delete attachments
					$failed = 0;
					$sql = "SELECT ID, attname, attfilename FROM support_attachments WHERE tid = '$tid'";
					if ($aresult = $dbcnx->query($sql) AND $aresult->num_rows) {
						while ($arow = $aresult->fetch_assoc()) {
							$aid         = $arow['ID'];
							$attname     = $arow['attname'];
							$attfilename = $arow['attfilename'];
							$filename = "../dis2_db/sup/$attfilename";

							if (!unlink($filename)) {
								$resultMessage .= "Attachment $attfilename Could NOT Be Deleted.<br>\n";
								$failed++;
							} else {
								$resultMessage .= "Attachment $attfilename Deleted.<br>\n";
								$sql = "DELETE FROM support_attachments WHERE ID = '$aid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Attachment ($attfilename) Record Deleted.<br>\n";
								} else {
									$resultMessage .= "Attachment Record($attfilename) Could NOT Be Deleted.<br>\n";
									$failed++;
								}
							}
						}
					} else {
						$resultMessage .= "No Attachments<br>\n";
					}
					if (!$failed) {
						$now = time();
						$sql = "UPDATE `support_tickets` SET `status` = 'Closed', `pending` = '0', `flagged` = '0', `lastupd` = '$now', `updby` = 'Robot' WHERE `ID` = '$tid'";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Ticket Closed<br>\r\n";
							
						} else {
							$resultMessage .= "Could NOT Close Ticket<br>\r\n";
						}
					}
				} else {
					$resultMessage .= "Current<br>\n";
				}
			} else {
				$resultMessage .= "No Emails Found!<br>\r\n";
			}
		}
	} else {
		$resultMessage .= "No Open/Answered Tickets Found!<br>\r\n";
	}

	$dbcnx->close();
} else {
	$resultMessage .= "Could not connect to database!<br>\r\n" . $dbcnx->connect_error . "<br>\r\n";
}

// Mail the processing report to $reportEmail

if ($resultMessage) {
	$subject = "Support Closures Report";
	$from = "Robot <robot@dephna.com>";
	$mime_boundary = md5(uniqid(time()));

	$headers  = "From: $from\r\n";
	$headers .= "Reply-To: $from\r\n";
	$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

	$message = "--".$mime_boundary."\r\n";
	$message .= "Content-type:text/html; charset=utf-8\r\n";
	$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$message .= "<p><b>Support Closures</b></p>\n";
	$message .= "<p>Answered tickets with no response for 72 hours and not flagged</p>\n";
	$message .= "<p>$resultMessage</p>";
	$message .= "\r\n\r\n";
	$message .= "--".$mime_boundary."--";

	mail($reportEmail, $subject, $message, $headers);
}
?>
