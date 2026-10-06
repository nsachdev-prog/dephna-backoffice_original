#!/usr/bin/php7.2

<?php
// invoicing-pdf-mailout.php - Written by David Sherwood - www.davidsherwood.co.uk
// Stand alone pdf mailout. Use if mailout fails in invoice run on 1st of month.
// Creates and emails pdf invoices using tcpdf to customers where pdfinvs is set to ON in the customer database.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once('../../tcpdf6.3/tcpdf.php');
include('../public_html/backoffice/settings.php');

$sent   = 0;
$failed = 0;
$year   = date("Y");
$month  = date("m");
$day    = "01";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	// get the email from, subject and body.
	$sql = "SELECT * FROM mailouts_pdf_invs WHERE ID='1'";
	$headresult = $dbcnx->query($sql);
	if (($headresult) && ($headresult->num_rows)) {
		$headrow = $headresult->fetch_assoc();
		$from    = $headrow['from'];
		$subject = $headrow['subject'];
		$body    = $headrow['body'];

		if ($from) {
			$fromname = "Dephna Accounts";
		} else {
			$from     = "accounts@dephna.com";
			$fromname = "Dephna Accounts";
		}

		$pdfmailoutResult  = "Pdf Mailout\r\n";

		require "../PHPMailer/src/SMTP.php";
		require "../PHPMailer/src/PHPMailer.php";
		require "../PHPMailer/src/Exception.php";

		$sql = "SELECT `custref`, `username`, `compname`, `aemails`, `addr1`, `addr2`, `addr3`, `addr4` FROM `users` WHERE `custref`='20199'";
		$cresult = $dbcnx->query($sql);
		if (($cresult) && ($cresult->num_rows)) {
			while ($crow  = $cresult->fetch_assoc()) {
				$compname = $crow['compname'];
				$custname = $crow['username'];
				$aemails  = $crow['aemails'];
				$custref  = $crow['custref'];
				$addr1    = $crow['addr1'];
				$addr2    = $crow['addr2'];
				$addr3    = $crow['addr3'];
				$addr4    = $crow['addr4'];

				if ($aemails) {

					$pdfmailoutResult .= "$compname\r\n";
					$thisResult = array();

					// Get inv headers for this customer
					$sql = "SELECT *
							FROM `inv_headers`
							WHERE `custref`   = '$custref'
								  AND `year`  = '$year'
								  AND `month` = '$month'
								  AND `day`   = '$day'
					";
					$invresult = $dbcnx->query($sql);
					if (($invresult) && ($invresult->num_rows)) {

						$thisbody = "Dear $custname,\n\n" . $body;
						$message = "";
						include("../public_html/backoffice/email-header.php");
						$message .= nl2br($thisbody);
						include("../public_html/backoffice/email-footer.php");

						$emails = explode(",",$aemails);

						foreach ($emails AS $to) {
	
							$mail = new PHPMailer(true);
	
							try {
								include('../../cron/SMTP.php');
	
								$mail->setFrom($from, $fromname);
								$mail->addAddress($to,$custname);
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC($from, $fromname);
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;
	
								while ($invrow = $invresult->fetch_assoc()) {
	
									$id       = $invrow['ID'];
									$inv      = $invrow['invnumber'];
									$year     =	$invrow['year'];
									$month    = $invrow['month'];
									$day      = $invrow['day'];
									$invtotal =	$invrow['invtotal'];
	
									$fileatt = "";
									$thisResult[] = "&nbsp;&nbsp;-Invoice $inv: Attached";
									include("../public_html/backoffice/mailouts-pdf-make-inv.php");
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
									}
	
								}
	
								$mail->send();
								$sent++;
								$thisResult[] = "&nbsp;&nbsp;-Email sent to: $to";
	
							} catch (Exception $e) {
								$failed++;
								$thisResult[] = "&nbsp;&nbsp;-Email failed to: $to <br />&nbsp;&nbsp; Mailer Error: {$mail->ErrorInfo}";
							}
						}
					} else {
						$pdfmailoutResult .= "&nbsp;&nbsp;-No Invoices to send for DP$custref on $day/$month/$year";
					}
					$pdfmailoutResult .= implode("\r\n",$thisResult);
				} else {
					$resultMessage .= "No accounts email address for customer DP$custref, Pdfs not sent.<br />\n";
				}
			}
		} else {
			$resultMessage .= "Could not get any customers.<br />" . $dbcnx->error . "<br />\n";
		}
	} else {
		$resultMessage .= "Could not get pdf email body template, Pdfs not sent.<br />" . $dbcnx->error . "<br />\n";
	}
$dbcnx->close();
} else {
	$errorMessage = "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
}
if ($pdfmailoutResult) {
	print "<p>$pdfmailoutResult</p>\n";
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
?>
