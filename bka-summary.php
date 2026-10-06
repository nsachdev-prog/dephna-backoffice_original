<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "BKManager";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Additional Services</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['bkaid']) {

				$bkaid = $_REQUEST['bkaid'];

				include('bka-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				$sql = "SELECT b.*, u.title AS unitname, c.compname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						LEFT JOIN users AS c ON c.custref = b.custref
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$unitid   = $row['unitid'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
					$unitname = $row['unitname'];
					$compname = $row['compname'];
					$depanc   = $row['depanc'];
					$aemails  = $row['aemails'];
					$start    = $row['start'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
					$agreementID = $row['agreementID'];

					$totdep = $depanc;
			
					print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
					
					if ($_POST['send']) {

						$proforma     = $_POST['proforma'];
						$depositinv   = $_POST['depositinv'];
						$ddform       = $_POST['ddform'];

						// get customer details
						$sql = "SELECT username, compname, addr1, addr2, addr3, addr4, telephone, email FROM users WHERE custref = '$custref'";
						$custresult = $dbcnx->query($sql);
						if (($custresult) && ($custresult->num_rows)) {
							$custrow = $custresult->fetch_assoc();
							$compname  = $custrow['compname'];
							$contact   = $custrow['username'];
							$addr1     = $custrow['addr1'];
							$addr2     = $custrow['addr2'];
							$addr3     = $custrow['addr3'];
							$addr4     = $custrow['addr4'];
							$telephone = $custrow['telephone'];
							$email     = $custrow['email'];

							if (!$aemails) { $aemails = $email; }
							$emails = explode(",",$aemails);
							$recipient = $emails[0];

							if ($agreementID) {
								include_once('bk-adobeSignSigningUrls.php');
							} else {
								include_once('bk-adobeSignContract.php');
							}

							// start email
							$subject  = "Dephna Estates";
							$from     = "accounts@dephna.com";
							$fromname = "Dephna Accounts";

							// email message
							$message .= "<p>Dear $contact</p>\n";
							$message .= "<p>Re: $sitename</p>\n";
							$message .= "<p>Thank you for your interest in Dephna Services.</p>\n";
							if (($agreementID) AND (!$byadobesign)) {
								if ($url) {
									$message .= "<p>";
									$message .= "Please use the link below to sign our contract.<br />";
									$message .= "<a href=\"$url\">Sign Contract</a>";
									$message .= "</p>\r\n";
								}
							} else {
								$message .= "<p>This is email 1 of 2.<br />You will also receive an email from Adobe Sign with instructions to sign our contract.</p>\n";
							}
							$message .= "<p>\n";
							if ($depanc) {
								$message .= "&#8226 A Deposit Invoice is attached. To be paid.<br />"; $tobepaid++;
							}
							if ($start) {
								$message .= "&#8226; A Pro Forma Invoice is attached. To be paid.<br />"; $tobepaid++;
							}

							$message .= "</p>\n";
							if ($tobepaid) {
								$message .= "<p>Your services will go live once you have signed the contract and made the payment.</p>\n";
							} else {
								$message .= "<p>Your services will go live once you have signed the contract.</p>\n";
							}
							$message .= "<p>Kind Regards</p>\n";
							$message .= "<p>Accounts<br /><a href=\"mailto:accounts@dephna.com\">accounts@dephna.com</a></p>\n";
							$message .= "<p><br />DEPHNA ESTATES LTD<br /><a href=\"http://www.dephna.com\" target=\"_blank\">www.dephna.com</a><br />2 Portal Way<br />London<br />W3 6RT<br />Tel: 020 8896 7575</p>\n";
							$message .= "<p>Serviced Offices - Self Storage - Serviced Kitchens - Cold Rooms - Sound Studios - Virtual Offices<br />Business Centre Locations: Finchley N3 - Neasden NW10 - North Acton NW10 - Cumberland Avenue NW10 - Coronation Road NW10 - Acton Lane NW10 - Portal Way W3</p>\n";
							$message .= "<p><font face=\"Times,Times New Roman,serif\">Internet communications are not secure and therefore we cannot accept legal responsibility for the contents of this message. The information transmitted is intended only for the person or entity to whom it is addressed and may contain confidential and/or privileged material. Any review, retransmission, dissemination or other use of, or taking of any action in reliance upon, this information by persons or entities other than the intended recipient is prohibited. If you received this in error, please contact the sender and delete the material from any computer.</font></p>\n";

							$message .= "\r\n\r\n";

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							$mail = new PHPMailer(true);

							try {

								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addAddress($recipient,$contact);
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC($from, $fromname);
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								// include the attachments if any
								$byemail = 1;
								if ($start) {
									$fileatt = "";
									include("bka-proforma-invoice-pdf.php");
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
									}
								}
								if ($depanc) {
									$fileatt = "";
									include("bka-deposit-invoice-pdf.php");
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
									}
								}
								
								$mail->send();
								$resultMessage .= "Email Sent to $recipient<br />";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $recipient Mailer Error: {$mail->ErrorInfo}";
							}

						} else {
							$resultMessage = "Customer not found!<br />" . $dbcnx->error;
						}
					
					}

					if ((!$resultMessage) OR ($e)) {

						if ($agreementID) {
							include_once('bk-adobeSignStatus.php');
						}

						print "<table align=\"center\" cellspacing=\"4\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"left\">&nbsp;</td>\n";
						print "	<td align=\"right\"><b>Net</b></td>\n";
						print "	<td align=\"right\"><b>Vat</b></td>\n";
						print "	<td align=\"right\"><b>Total</b></td>\n";
						print "</tr>\n";
		
						// Get the ancillaries
						$sql = "SELECT * FROM bka_anc WHERE bkaid = '$bkaid'";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

							while ($row = $result->fetch_assoc()) {
								$ancid    = $row['ancid'];
								$anctitle = $row['anctitle'];
								$qty      = $row['qty'];
								$rate     = $row['rate'];
								$net      = $qty * $rate;
								$vat      = $row['vat'];
								if ($vat == "Yes") { $vat = $net/100*$vatRate; } else { $vat = 0; }
								$tot = $net + $vat;
								
								$totnet += $net;
								$totvat += $vat;
								$tottot += $tot;
	
								print "<tr>\n";
								print "	<td align=\"left\">$anctitle</td>\n";
								print "	<td align=\"right\">".number_format($net,2)."</td>\n";
								print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
								print "	<td align=\"right\">".number_format($tot,2)."</td>\n";
								print "</tr>\n";

							}
						}

						print "<tr>\n";
						print "	<td align=\"left\">&nbsp;</td>\n";
						print "	<td align=\"right\"><b>".number_format($totnet,2)."</b></td>\n";
						print "	<td align=\"right\"><b>".number_format($totvat,2)."</b></td>\n";
						print "	<td align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"4\">&nbsp;</td>\n";
						print "</tr>\n";

						if ($depanc) {
							print "<tr>\n";
							print "	<td align=\"left\">Deposit Ancillaries</td>\n";
							print "	<td align=\"right\" colspan=\"3\">".number_format($depanc,2)."</td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "	<td align=\"left\"><b>Deposit Total</b></td>\n";
						print "	<td align=\"right\" colspan=\"3\"><b>".number_format($totdep,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";
	
						// print email attachment choices
						print "<form name=\"addform\" method=\"post\" action=\"bka-summary.php\">\n";
						print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
						print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";

						if ($agreementID) {
							print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
							print "<tr>\n";
							print "	<td align=\"right\">AdobeSign Contract Status</td>\n";
							print "	<td>$ageementStatus";
							if (($ageementStatus) AND ($ageementStatus != "SIGNED")) {
								print " - <a href=\"bka-contract-cancel.php?bkaid=$bkaid\">Cancel</a>";
							}
							print "</td>\n";
							print "</tr>\n";
							print "</table>\n";
							print "	<p align=\"center\"><input type=\"submit\" value=\"Resend\" class=\"button\" /></p>\n";
						} else {
							print "	<p align=\"center\"><input type=\"submit\" value=\"Send\" class=\"button\" /></p>\n";
						}

						print "</form>\n";

						print "<p align=\"center\">";
						print "<a href=\"bka-contract-combined-pdf.php?bkaid=$bkaid\" target=\"_blank\">Print Contract</a> | \n";
						print "<a href=\"bka-proforma-invoice-pdf.php?bkaid=$bkaid\" target=\"_blank\">Print Pro Forma</a> | \n";
						print "<a href=\"bka-deposit-invoice-pdf.php?bkaid=$bkaid\" target=\"_blank\">Print Deposit Inv</a>";
						print "</p>\n";

						// get agreement events
						if ($agreementID) {
							include_once('bk-adobeSignEvents.php');
						}

					}
			
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Additional Services Not Found!</b></span><br />" . $dbcnx->error;
				}
			} else {
				$resultMessage .= "Page called without id";
			}		
			$dbcnx->close();
		} else {
			$resultMessage .= "<b>Could Not Connect To Database!</b><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
