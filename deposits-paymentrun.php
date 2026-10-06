<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";
$page = "Deposits";
$subpage = "Payment Run";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		// print heading and submenu
		print "<p align=\"center\" class=\"heading\">DEPOSITS</p>\n";
		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"heading\">Payment Run</p>\n";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

			if ($_POST['makepayments']) {

				$subject  = $_POST['subject'];
				$body     = $_POST['body'];
				$erem     = $_POST['erem'];
				$payments = $_POST['payments'];
				$accpay   = $_POST['accpay'];
                $apbody   = $_POST['apbody'];
				if (!$subject) { $ersubject = "No Subject"; $e++; }
				if (!$body)    { $erbody    = "No Message"; $e++; }
				if ($accpay == "Select") { $eraccpay = "Please Select"; $e++; }
				if (!$e) {

					$today = date("d-m-Y");
					list($d,$m,$y) = explode("-",$today);
					$now = time();
					$recType = "p"; // payment pending

					// get next refunded run number
					$sql = "SELECT refunded FROM deposit_requests ORDER BY refunded DESC LIMIT 0, 1";
					if ($rresult = $dbcnx->query($sql)) {
						if ($rresult->num_rows) {
							$rrow = $rresult->fetch_assoc();
							$rid  = $rrow['refunded'];
							$rid++;
						} else {
							$rid = 1;
						}
					}

					// get next sl payment number
					$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'P' ORDER BY `invnumber` DESC LIMIT 0, 1";
					$result = $dbcnx->query($sql);
					if ($result AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$txnnum = $row['invnumber'];
						$txnnum++;
					} else {
						$txnnum = 1001;
					}

					// start bank csv list
					$bank = "";

					// start report to screen
					$resultMessage = "<table>\n";

					// get all payments
					foreach ($payments AS $id => $total) { 

						// get customer details
						$sql = "SELECT d.custref, d.amount, c.compname, c.username, c.email, c.aemails, d.bnkAccName, d.bnkSort, d.bnkAccNum, d.duedate
								FROM deposit_requests AS d
								LEFT JOIN users AS c ON d.custref = c.custref
								WHERE d.ID = '$id'
						";
						if ($result = $dbcnx->query($sql)) {
							while ($row = $result->fetch_assoc()) {
								$custref    = $row['custref'];
								$bnkAccName = $row['bnkAccName'];
								$bnkSort    = $row['bnkSort'];
								$bnkAccNum  = $row['bnkAccNum'];
								$duedate    = $row['duedate'];
								$compname   = $row['compname'];
								$contname   = $row['username'];
								$email      = $row['email'];
								$aemails    = $row['aemails'];
								$amount	 	= $row['amount'];
								if (!$aemails) { $aemails = $email; }

								$bnkAccName = trim($bnkAccName);
								$bnkSort    = trim($bnkSort);
								$bnkAccNum  = trim($bnkAccNum);

								$resultMessage .= "<tr><td colspan=\"5\"><b>$compname</b></td></tr>\n";

								// update deposit request
								$sql = "UPDATE deposit_requests SET refunded = '$rid' WHERE ID = '$id'";
								$dresult = $dbcnx->query($sql);

								// post payment to sales ledger account
								// create payment header
								$sql = "INSERT INTO inv_headers VALUES (
									null,
									'$recType',
									'$txnnum',
									'$y',
									'$m',
									'$d',
									'$custref',
									'$amount',
									'$amount',
									'No',
									'',
									'$now',
									'{$_SESSION['username']}')
								";
								if ($slresult = $dbcnx->query($sql)) {
									$hid = $dbcnx->insert_id;
									$resultMessage .= "	<tr>\n";
									$resultMessage .= "		<td colspan=\"5\">Sales Ledger Payment Created</td>\n";
									$resultMessage .= "	</tr>\n";
									// create sl_extra 
									$sql = "INSERT INTO sl_extra VALUES (
										null,
										'$hid',
										'$txnnum',
										'$custref',
										'DR',
										''
									)";
									if ($slresult = $dbcnx->query($sql)) {
										$resultMessage .= "	<tr>\n";
										$resultMessage .= "		<td colspan=\"5\">Extra Details Created</td>\n";
										$resultMessage .= "	</tr>\n";
									} else {
										$resultMessage .= "	<tr>\n";
										$resultMessage .= "		<td colspan=\"5\">Could NOT Create Extra Details<br>".$dbcnx->error."</td>\n";
										$resultMessage .= "	</tr>\n";
									}
								} else {
									$resultMessage .= "	<tr>\n";
									$resultMessage .= "		<td colspan=\"5\">Could NOT Create SL Payment<br>".$dbcnx->error."</td>\n";
									$resultMessage .= "	</tr>\n";
								}

								// report to screen
								$resultMessage .= "	<tr>\n";
								$resultMessage .= "		<td>$duedate</td>\n";
								$resultMessage .= "		<td>$custref</td>\n";
								$resultMessage .= "		<td>$compname</td>\n";
								$resultMessage .= "		<td align=\"right\">&nbsp;</td>\n";
								$resultMessage .= "		<td align=\"right\">$amount</td>\n";
								$resultMessage .= "	</tr>\n";

								// add this supplier to bank csv
								$bank  .= "$bnkSort,$bnkAccName,$bnkAccNum,".number_format($amount,2,'.','').",DP$custref,99\r\n";

								// replace fields in this email body
								$search   = array("[[CustomerContactName]]","[[CustomerRef]]","[[Payment]]","[[staffname]]");
								$replace  = array($contname,$custref,$amount,$_SESSION['username']);
								$thisbody = str_replace($search,$replace,$body);

								// email customer
								if ($erem == "on") {

									$from = "accounts@dephna.com";
									$fromname = "Dephna Accounts";
									$emails = explode(",",$aemails);

									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= nl2br($thisbody);
									$message .= file_get_contents('email-footer.html') . "\r\n";

									$mail = new PHPMailer(true);

									try {
										include('../../cron/SMTP.php');

										$mail->CharSet = 'UTF-8';
										$mail->setFrom($from, $fromname);
										$mail->addReplyTo($from, $fromname);
										$mail->addBCC('accounts@dephna.com', 'Dephna Accounts');
										foreach ($emails AS $to) {
											$mail->addAddress($to);
										}
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;
										$mail->AltBody = strip_tags(str_replace("<br />","\r\n",$message));
										$mail->send();
										
										$resultMessage .= "	<tr>\n";
										$resultMessage .= "		<td colspan=\"5\">Email Sent to $aemails</td>\n";
										$resultMessage .= "	</tr>\n";
										
									} catch (Exception $e) {
										$resultMessage .= "	<tr>\n";
										$resultMessage .= "		<td colspan=\"5\">COULD NOT Send Email<br />" . $mail->ErrorInfo . "</td>\n";
										$resultMessage .= "	</tr>\n";
									}

								} else {
									$resultMessage .= "	<tr>\n";
									$resultMessage .= "		<td colspan=\"5\">Email Remittance set to No</td>\n";
									$resultMessage .= "	</tr>\n";
								}

								// add line break
								$resultMessage .= "	<tr>\n";
								$resultMessage .= "		<td>&nbsp;</td>\n";
								$resultMessage .= "	</tr>\n";

								// add to grand totals
								$GrandtotalPaid += $amount;
								// increase sl payment number
								$txnnum++;
							}
						} else {
							$resultMessage .= "	<tr>\n";
							$resultMessage .= "		<td colspan=\"5\">Could NOT Get Customer Details for Request ID $id<br>".$dbcnx->error."</td>\n";
							$resultMessage .= "	</tr>\n";
						}
					}

					// save bank csv file
					$csv_dir = "../../dis2_db/csv";
					$filedt  = date("Y-m-d-H-i-s");
					$userid  = sprintf("%03s",$_SESSION['user']);
					if ($accpay == "Yes") {
						$bankfn  = "DB-$filedt-$rid-$userid.csv";
					} else {
						$bankfn  = "DN-$filedt-$rid-$userid.csv";
					}

					$handle = fopen("$csv_dir/$bankfn", "w");
					fwrite($handle, $bank);
					fclose($handle);

					$GrandtotalPaid = number_format($GrandtotalPaid,2);

					// end payment list
					$resultMessage .= "	<tr>\n";
					$resultMessage .= "		<td colspan=\"4\"><b>Total Paid<b></td>\n";
					$resultMessage .= "		<td align=\"right\"><b>$GrandtotalPaid</b></td>\n";
					$resultMessage .= "	</tr>\n";

					$resultMessage .= "</table>\n";

					// add bank link
					$resultMessage .= "<br><a href=\"deposits-csvdl.php?fl=$bankfn\"><b>Download Bank File</b></a><br>\n";		

					// email accounts payable
					$from     = "no-reply@dephna.com";
					$fromname = "Dephna Robot";
					$subject  = "DEPRET-$rid";
					$apbody .= "<br><b>Deposit Repayments</b>";
					$apbody .= "<br>DEPRET-$rid";
					$apbody .= "<br>Amount: $GrandtotalPaid";
					$apbody .= "<br>Entered By: {$_SESSION['username']}<br>";
					$apbody .= "<br>Payment List:<br>\r\n";
					$apbody .= $resultMessage . "\r\n";

					$message  = file_get_contents('email-header.html') . "\r\n";
					$message .= $apbody;
					$message .= file_get_contents('email-footer.html') . "\r\n";

					$mail = new PHPMailer(true);
					try {
						include('../../cron/SMTP.php');
						$mail->setFrom($from, $fromname);
						$mail->addAddress($accountsPayable);
						$mail->addCC('rajive@dephna.com', 'Rajive Sachdev');
						// $mail->addCC('accounts@dephna.com', 'Dephna Accounts');
						$mail->isHTML(true);
						$mail->Subject = $subject;
						$mail->Body    = $message;
						$mail->send();
						$resultMessage .= "<br>Email Sent to Accounts Payable<br>\n";
					} catch (Exception $e) {
						$resultMessage .= "<br>COULD NOT Send Email<br>" . $mail->ErrorInfo . "<br>\n";
					}

					// log it
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Deposit Run Total: $GrandtotalPaid','$page','$now')");
				}
			} else {
				$erem = 'on';
			}

			if ((!$_POST['makepayments']) OR ($e)) {

				//get email template
				$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'DepPayment'";
				$tresult = $dbcnx->query($sql);
				if ($tresult->num_rows) {
					$trow = $tresult->fetch_assoc();
					$subject = $trow['subject'];
					$body    = $trow['html'];

					if (!$apbody) {
                        $apbody = "Three day payment please.";
                    }

					$sql = "SELECT d.ID, d.custref, d.amount AS total, c.compname, d.bnkSort, d.bnkAccNum
							FROM deposit_requests AS d
							LEFT JOIN users AS c ON d.custref = c.custref
							WHERE refunded = '0' AND d.approve = '1' AND d.topay = '1'
							ORDER BY d.duedate
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<p align=\"center\"><b>Make Payments To</b></p>\n";
						print "<form name=\"paymentsform\" method=\"post\" action=\"deposits-paymentrun.php\">\n";
						print "<input type=\"hidden\" name=\"makepayments\" value=\"1\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td><b>Client</b></td>\n";
						print "	<td><b>Sort Code</b></td>\n";
						print "	<td><b>Account Number</b></td>\n";
						print "	<td align=\"right\"><b>Total</b></td>\n";
						print "	<td><b>Pay</b></td>\n";
						print "</tr>\n";
						while ($row = $result->fetch_assoc() AND $result->num_rows) {
							$id       = $row['ID'];
							$custref  = $row['custref'];
							$total    = $row['total'];
							$compname = $row['compname'];
							$bnkSort  = $row['bnkSort'];
							$bnkAccNum= $row['bnkAccNum'];
							$grandTotal += $total;
							$duedate = date("d-m-Y",strtotime($row['duedate']));
							if (!$bnkSort OR !$bnkAccNum) {
								if (!$bnkSort) $bnkSort = "<span class=\"redtext\">Not Set</span>";
								if (!$bnkAccNum) { $bnkAccNum = "<span class=\"redtext\">Not Set</span>"; }
								$bnkWarn = "WARNING:<br>Some bank details Not Set<br>Bank will reject the whole payment run";
							}
							print "<tr>\n";
							print "	<td>$compname</td>\n";
							print "	<td>$bnkSort</td>\n";
							print "	<td>$bnkAccNum</td>\n";
							print "	<td align=\"right\">$total</td>\n";
							print "	<td align=\"center\"><input type=\"checkbox\" name=\"payments[$id]\" value=\"$total\" /></td>\n";
							print "</tr>\n";

							if ($bnkWarn) {
								print "<p align=\"center\"><span class=\"redtext\">$bnkWarn</span></p>\n";
							}
						}
						print "<tr>\n";
						print "	<td colspan=\"3\" align=\"right\"><b>Total if all Ticked</b></td>\n";
						print "	<td align=\"right\"><b>" . number_format($grandTotal, 2) . "</b></td>\n";
						print "</tr>\n";
						print "</table>\n";

						// get accounts payable email
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Email Client:</b></td>\n";
						print "	<td><input type=\"checkbox\" name=\"erem\""; if ($erem == "on") { print " CHECKED"; } print "></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Subject:</b></td>\n";
						print "	<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\">\n";
						if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Client Email:</b></td>\n";
						print " <td><textarea name=\"body\" rows=\"16\" class=\"element_box_large\">$body</textarea>";
						if ($erbody) { print "<br><span class=\"redtext\">$erbody</span>"; }
						print "<td>\n";	
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Email Accounts Payable:</b></td>\n";
						print "	<td><select name=\"accpay\" class=\"element\">\n";
						print "		<option "; if ($accpay == "Yes") { print "SELECTED "; } print "value=\"Yes\">Yes, Process Payment</option>";
						print "		<option "; if ($accpay == "No")  { print "SELECTED "; } print "value=\"No\">No, Do NOT Process</option>";
						print "	</select>";
						if ($eraccpay) { print "<br><span class=\"redtext\">$eraccpay</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Payable Instructions:</b></td>\n";
						print " <td><textarea name=\"apbody\" rows=\"4\" class=\"element_box_large\">$apbody</textarea>";
						if ($erapbody) { print "<br><span class=\"redtext\">$erapbody</span>"; }
						print "<td>\n";	
						print "</tr>\n";

						print "</table>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Refunds\" /></p>\n";
						print "</form>\n";
					
					} else {
						$resultMessage = "<p align=\"center\"><b>No Payments Found</b></p>\n";
					}

				} else {
					$resultMessage = "Could Not Get Email Template";
				}
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
