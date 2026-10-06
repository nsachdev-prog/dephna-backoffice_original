<?php
// cron-inv-pdf-mailout.php - Written by David Sherwood - www.davidsherwood.co.uk
// Called by cron-invoicing.php
// Creates and emails pdf invoices using tcpdf to customers where pdfinvs is set to ON in the customer database
// 15/05/2020 Changed to send to all customers regardless of pdfinvs setting.
// 29/04/2021 Changed to use dephna.com SMTP

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$sent   = 0;
$failed = 0;
$fullyear = "20" . $year;
$iday   = $day;
$imonth = $month;
$iyear  = $year;

$SYSCompanyName = "Dephna Group Ltd.";
$SYSCompanyAddr = "2 Portal Way, London W3 6RT";
$SYSCompanyVAT  = "505 6096 55";
$SYSCompanyBank = "Barclays Bank: Sort Code 20-95-61, Acc Num 40311332";

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

	$pdfmailoutResult  = "<p><b>4. Pdf Mailout</b></p>\n";
	$pdfmailoutResult .= "<table cellspacing=\"2\" cellpadding=\"2\">\n";

	require "../PHPMailer/src/SMTP.php";
	require "../PHPMailer/src/PHPMailer.php";
	require "../PHPMailer/src/Exception.php";

	// loop through invoiced customers
	foreach($custarray AS $key => $custref) {

		$sql = "SELECT `username`, `compname`, `email`, `aemails`, `addr1`, `addr2`, `addr3`, `addr4` FROM `users` WHERE `custref`='$custref'";
		$cresult = $dbcnx->query($sql);
		if (($cresult) && ($cresult->num_rows)) {
			$crow  = $cresult->fetch_assoc();
			$compname = $crow['compname'];
			$custname = $crow['username'];
			$email    = $crow['email'];
			$aemails  = $crow['aemails'];
			$addr1    = $crow['addr1'];
			$addr2    = $crow['addr2'];
			$addr3    = $crow['addr3'];
			$addr4    = $crow['addr4'];
			if (!$aemails) { $aemails = $email; }

			if ($aemails) {

				$pdfmailoutResult .= "<tr><td valign=\"top\">$compname</td><td valign=\"top\">\n";
				$thisResult = array();
				// Get inv headers for this customer
				$sql = "SELECT * FROM `inv_headers` WHERE `custref`='$custref' AND `year`='$fullyear' AND `month`='$month'";
				$invresult = $dbcnx->query($sql);
				if (($invresult) && ($invresult->num_rows)) {

					$thisbody = "Dear $custname,\n\n" . $body;
					$message = "";
					include("../public_html/backoffice/email-header.php");
					$message .= nl2br($thisbody);
					include("../public_html/backoffice/email-footer.php");

					$emails = explode(",",$aemails);

					$mail = new PHPMailer(true);

					try {
						include('SMTP.php');

						$mail->setFrom($from, $fromname);
						foreach ($emails AS $to) {
							$mail->addAddress($to);
						}
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
							include("../public_html/backoffice/mailouts-pdf-make-inv.php");
							if ($fileatt) {
								$thisResult[] = "&nbsp;&nbsp;-Invoice $inv: Attached";
								$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
							}

						}

						$mail->send();
						$sent++;
						$thisResult[] = "&nbsp;&nbsp;-Email sent to: $aemails";

					} catch (Exception $ee) {
						$failed++;
						$thisResult[] = "&nbsp;&nbsp;-Email failed to: $aemails <br />&nbsp;&nbsp; Mailer Error: {$mail->ErrorInfo}";
					}

					sleep(2); // Microsoft limits sending to 30 emails per minute. Dephna.com uses outlook SMTP

				} else {
					$pdfmailoutResult .= "&nbsp;&nbsp;-No Invoices to send";
				}
				$pdfmailoutResult .= implode("<br />",$thisResult);
				$pdfmailoutResult .= "</td></tr>\n";
				unset($custarray[$key]);
			} else {
				$resultMessage .= "No accounts email address for customer DP$custref, Pdfs not sent.<br />\n";
			}
		} else {
			$resultMessage .= "Could not find customer DP$custref, Pdfs not sent.<br />\n";
		}
	}
	$pdfmailoutResult .= "</table>\n";
} else {
	$resultMessage .= "Could not get pdf email body template, Pdfs not sent.<br />" . $dbcnx->error . "<br />\n";
}

?>
