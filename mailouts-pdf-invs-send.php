<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Mailouts";
$subpage = "Pdf Invoices";
# ini_set('memory_limit', '-1');
include('settings.php');
include('session.php');
include('header.php');
require_once('../../../tcpdf/config/lang/eng.php');
require_once('../../../tcpdf/tcpdf.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MAILOUT PDF INVOICES</p>\n";
	if ($accessRights[$page] > 1) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$month  = date("m");
			$year   = date("Y");
			$callsmonth = $month - 1;
			if ($callsmonth == 0) {
				$callsmonth = 12;
				$callsyear  = $year - 1;
			} else {
				$callsyear = $year;
			}
			$callsmonth = sprintf("%02s",$callsmonth);
			$sent   = 0;
			$failed = 0;
			if ($_POST['custref']) {

				# get the email from, subject and body.
				$sql = "SELECT * FROM mailouts_pdf_invs WHERE ID='1'";
				$headresult = $dbcnx->query($sql);
				if (($headresult) && ($headresult->num_rows)) {
					$hrow = $headresult->fetch_assoc();
					$from    = $hrow['from'];
					$subject = $hrow['subject'];
					$body    = $hrow['body'];

					if ($from) {
						$fromname = "Dephna Accounts";
					} else {
						$from     = "accounts@dephna.com";
						$fromname = "Dephna Accounts";
					}

					// loop through each selected customer
					foreach($_POST['custref'] as $custref)  {
						$sql = "SELECT custref, compname, username, aemails, addr1, addr2, addr3, addr4, bank FROM users WHERE custref = '$custref'";
						$custresult = $dbcnx->query($sql);
						if (($custresult) && ($custresult->num_rows)) {
							$crow = $custresult->fetch_assoc();
							$compname = $crow['compname'];
							$custname = $crow['username'];
							$aemails  = $crow['aemails'];
							$addr1    =	$crow['addr1'];
							$addr2    =	$crow['addr2'];
							$addr3    =	$crow['addr3'];
							$addr4    =	$crow['addr4'];

							$thisbody = "Dear $custname,\n\n" . $body;

							$resultMessage .= "$compname - $aemails:<br />\n";

							// Get inv headers for this customer
							$sql = "SELECT * FROM inv_headers WHERE custref = '$custref' AND year = '$year' AND month = '$month'";
							$invresult = $dbcnx->query($sql);
							if (($invresult) && ($invresult->num_rows)) {

								include("email-header.php");
								$message .= $thisbody;
								include("email-footer.php");

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
											$resultMessage .= "&nbsp;&nbsp;-Invoice $inv: Attached";
											include("mailouts-pdf-make-inv.php");
											if ($fileatt) {
												$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
											}
			
										}
			
										$mail->send();
										$sent++;
										$resultMessage .= "&nbsp;&nbsp;-Email sent to: $to";
			
									} catch (Exception $e) {
										$failed++;
										$resultMessage .= "&nbsp;&nbsp;-Email failed to: $to <br />&nbsp;&nbsp; Mailer Error: {$mail->ErrorInfo}";
									}
								}

							} else {
								$resultMessage .= "&nbsp;&nbsp;&nbsp;&nbsp;- No Invoices<br />\n";
							}
						} else {
							$resultMessage .= "$custref: Failed to get customer details<br />\n";
						}
					}
				} else {
					$resultMessage .= "Could not get email body<br />" . $dbcnx->error . "<br />\n";
				}
			} else {
				$resultMessage .= "No Customers Selected\n";
			}
			$dbcnx->close();
		} else {
			$errorMessage = "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
		}

		if ($resultMessage) {
			print "<blockquote>\n";
			print "<p><b>Emailing Pdf Invoices for $month/$year</b></p>\n";
			print "$resultMessage\n";
			print "<p>$sent Sent - $failed Failed</p>\n";
			print "</blockquote>\n";
		}
		print "<p align=\"center\"><a href=\"mailouts-pdf-invs-select.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
