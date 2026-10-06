<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Invoicing";
$subpage = "Sales Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">SALES LEDGER EMAIL</p>\n";
	include('invoicing-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			// get fields
			$tx       = $_POST['tx'];
			$posted   = $_POST['posted'];

			if ($tx) {
				
				// reorder by custref
				$custrefs = array();
				foreach($tx AS $invnumber) {
					$sql = "SELECT custref FROM inv_headers WHERE invnumber='$invnumber'";
					if ($result = $dbcnx->query($sql)) {
						$row = $result->fetch_assoc();
						$custref = $row['custref'];
						$custrefs[$custref][] = $invnumber;
					} else {
						$resultMessage .= "Tx $invnumber not found<br />" . $dbcnx->error . "<br />\n";
					}
				}
				
				if ($custrefs) {
					
					if ($posted) {
						
						$subject = strip_tags($_POST['subject']);
						$body    = strip_tags($_POST['body']);
						
						if (!$subject) { $ersubject = "No Subject"; $e++; }
						if (!$body)    { $erbody    = "No Message"; $e++; }
						if (!$e) {
							
							require "../../tcpdf6.8/tcpdf.php";
							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							// send from
							$from     = "accounts@dephna.com";
							$fromname = "Dephna Accounts";

							foreach($custrefs AS $custref => $txs) {
								
								// get this customers details
								$sql = "SELECT `username`, `email`, `aemails`, `compname`, `addr1`, `addr2`, `addr3`, `addr4` FROM `users` WHERE `custref`='$custref'";
								$cresult = $dbcnx->query($sql);
								if ($cresult->num_rows) {
									$crow = $cresult->fetch_assoc();
									$compname = $crow['compname'];
									$custname = $crow['username'];
									$aemails  = $crow['aemails'];
									$email    = $crow['email'];
									if (!$aemails) { $aemails = $email; }
									$addr1 = $crow['addr1'];
									$addr2 = $crow['addr2'];
									$addr3 = $crow['addr3'];
									$addr4 = $crow['addr4'];

									// replace fields in this template
									$search  = array("[[custname]]","[[staffname]]");
									$replace = array($custname,$_SESSION['username']);
									$thisbody = str_replace($search,$replace,$body);

									// send email to client

									$message = "";
								    include("email-header.php");
								    $message .= nl2br($thisbody);
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

											$bysmtp = 1;

											// attach transactions
											foreach ($txs AS $key => $inv) {
												# get inv total
												$sql = "SELECT * FROM `inv_headers` WHERE `invnumber`='$inv'";
												$invresult = $dbcnx->query($sql);
												$invrow = $invresult->fetch_assoc();
												$hid    = $invrow['ID'];
												$txtype = $invrow['txtype'];
												$iyear  = $invrow['year'];
												$imonth = $invrow['month'];
												$iday   = $invrow['day'];
												$invtotal = $invrow['invtotal'];
												$fileatt = "";
												include("mailouts-pdf-make-inv2.php");
												if ($fileatt) {
													$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
												}
											}

											$mail->send();
											$resultMessage .= "Email Sent to $to<br />";

										} catch (Exception $e) {
											$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
										}

									}

								} else {
									$resultMessage .= "<b>Could not get customer DP$custref</b><br />" . $dbcnx->error . "<br />";
								}
							}
						}
					} else {
						# get email template
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'InvMultiTx'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow  = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];
						} else {
							$resultMessage .= "<b>Could not get email template InvMultiTx.</b>\n";
						}
					}
					
					if ((!$resultMessage) OR ($e)) {
						print "<form name=\"emailform\" method=\"post\" action=\"invoicing-ledger-email.php\">\n";
						print "<input type=\"hidden\" name=\"posted\"  value=\"1\" />\n";
						foreach ($tx AS $inv) {
							print "<input type=\"hidden\" name=\"tx[]\" value=\"$inv\" />\n";
						}
						print "<p align=\"center\"><b>Edit Template</b></p>\n";

						print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
						if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
						print "</p>\n";

						if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
						print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
						print "</form>\n";
					}
					
				} else {
					$resultMessage .= "<b>No customers found.</b>\n";
				}
					
			} else {
				$resultMessage .= "<b>No transactions selected.</b>\n";
			}
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br />" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"invoicing-ledger.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
