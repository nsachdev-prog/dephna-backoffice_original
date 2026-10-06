<?php
// The field allocatedto equals
// for txtypes I & C: the record id of the payment
// txtype P : the payment run id
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";
$page = "Purchases";
$subpage = "Payment Run";
include('settings.php');
include('session.php');
include('header.php');
$bankaccounts = array( "main" => "Barclays Bank", "cc" => "Barclaycard", "am" => "American Express");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		// print heading and submenu
		print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Payment Run</p>\n";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

			if ($_POST['makepayment']) {

                $suppid  = $_POST['suppid'];
				$subject = $_POST['subject'];
				$body    = $_POST['body'];
                $bankacc = $_POST['bankacc'];
				$payment = $_POST['payment'];
				$accpay  = $_POST['accpay'];
                $apbody  = $_POST['apbody'];
                if (!$suppid) { $ersuppid = "Please Select"; $e++; }
				if (!$subject) { $ersubject = "No Subject"; $e++; }
				if (!$body)    { $erbody    = "No Message"; $e++; }
				if ($accpay == "Select") { $eraccpay = "Please Select"; $e++; }
				if (!$e) {

					$recType = "p";
					if ($bankacc == "main") { $bankacc = ""; }
					$body = nl2br($body);

					$now = time();
					$py = date("Y");
					$pm = date("m");
					$pd = date("d");

					// get next payment run number
					$sql = "SELECT allocatedto FROM purchases WHERE txtype='P' ORDER BY allocatedto DESC LIMIT 0, 1";
					if ($prresult = $dbcnx->query($sql)) {
						if ($prresult->num_rows) {
							$prrow = $prresult->fetch_assoc();
							$prid  = $prrow['allocatedto'];
							$prid++;
						} else {
							$prid = 101;
						}
					}

					// start bank csv
					$bank = "";

					foreach ($payments AS $suppid => $total) {
						$sql = "SELECT * FROM suppliers WHERE ID = '$suppid'";
						if ($suppresult = $dbcnx->query($sql) AND $suppresult->num_rows) {
							$supprow = $suppresult->fetch_assoc();
							$suppid     = $supprow['ID'];
							$suppname   = $supprow['suppname'];
							$suppcustref= $supprow['suppcustref'];
							$suppref    = $supprow['suppref'];
							$email      = $supprow['email'];
							$aemails    = $supprow['aemails'];
							$contname   = $supprow['contname'];
							$bnkAccName = $supprow['bnkAccName'];
							$bnkSort    = $supprow['bnkSort'];
							$bnkAccNum  = $supprow['bnkAccNum'];
							$erem       = $supprow['erem'];

							$warning = 0;

							$bnkAccName = trim($bnkAccName);
							$bnkSort    = trim($bnkSort);
							$bnkAccNum  = trim($bnkAccNum);

							if (!$bnkSort) { $bnkSort = "000000"; $warning++; }
							if (!$bnkAccNum) { $bnkAccNum = "00000000"; $warning++; }
							if (!$bnkAccName) { $bnkAccName = $suppname; }

							if (!$aemails) { $aemails = $email; }
							if (!$contname) { $contname = $suppname; }
							if (!$suppcustref) { $suppcustref = "Dephna"; }

							if (strlen($bnkAccName)  > 18) { $bnkAccName  = substr($bnkAccName, 0, 18); }
							if (strlen($suppcustref) > 18) { $suppcustref = substr($suppcustref, 0, 18); }

							$te = 0;

							// get payable transactions for this supplier
							$sql = "SELECT  ID, txtype, dephnaref, supplierref, year, month, day, net, vat, total, topay
									FROM purchases
									WHERE suppid = '$suppid' AND topay > '1'
									ORDER BY year, month, day, ABS(dephnaref)
							";
							if ($txresult = $dbcnx->query($sql) AND $txresult->num_rows) {

								$allocdate = date("Y-m-d");

								$resultMessage .= "<tr><td><b>$suppname</b></td></tr>\n";
								if ($warning) {
									$resultMessage .= "<tr><td style=\"color:red\"><b>WARNING: Incomplete Bank Details</b></td></tr>\n";
								}

								// start a payment record to this supplier
								// first get next dephna ref
								$sql = "SELECT dephnaref FROM purchases WHERE txtype='P' ORDER BY dephnaref DESC LIMIT 0, 1";
								if ($srresult = $dbcnx->query($sql)) {
									if ($srresult->num_rows) {
										$srrow = $srresult->fetch_assoc();
										$newdephnaref = $srrow['dephnaref'];
										$newdephnaref++;
									} else {
										$newdephnaref = 101;
									}
								}

								// add payment to database (We'll update net, vat & total later)
								$sql = "INSERT INTO purchases VALUES (
									null,
									'p',
									'$suppid',
									'$newdephnaref',
									'$bankacc',
									'',
									'$py',
									'$pm',
									'$pd',
									'',
									'0',
									'0',
									'0',
									'0',
									'0',
									'',
									'',
									'0',
									'0',
									'0',
									'$now',
									'$prid',
									'$now',
									'{$_SESSION['username']}'
								)";
								$payresult = $dbcnx->query($sql);
								$paymentid = $dbcnx->insert_id;

								// start remittance list for this supplier
								$remittance = "<table>\r\n";
								$remittance .= "<tr>\r\n";
								$remittance .= "<td><b>Date</b></td>\r\n";
								$remittance .= "<td><b>Your Inv No</b></td>\r\n";
								$remittance .= "<td align=\"right\"><b>Net</b></td>\r\n";
								$remittance .= "<td align=\"right\"><b>VAT</b></td>\r\n";
								$remittance .= "<td align=\"right\"><b>Total</b></td>\r\n";
								$remittance .= "</tr>\r\n";
								$totalNet  = 0;
								$totalVat  = 0;
								$totalPaid = 0;
									
								// make allocation header
								$sql = "INSERT INTO pl_allocations_headers VALUES(
									'',
									'$allocdate',
									'P',
									'$suppid',
									'$paymentid',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($aresult = $dbcnx->query($sql)) {
									$aid = $dbcnx->insert_id;
								}

								// start transactions loop for this supplier
								while ($txrow = $txresult->fetch_assoc()) {
									$txid   = $txrow['ID'];
									$txtype = $txrow['txtype'];
									$net    = $txrow['net'];
									$vat    = $txrow['vat'];
									$total  = $txrow['total'];
									$supplierref = $txrow['supplierref'];
									$dephnaref = $txrow['dephnaref'];
									$date   = $txrow['day']."/".$txrow['month']."/".$txrow['year'];

									// add to remittance list
									$remittance .= "<tr>\r\n";
									$remittance .= "<td>$date</td>\r\n";
									$remittance .= "<td>$supplierref</td>\r\n";
									$remittance .= "<td align=\"right\">$net</td>\r\n";
									$remittance .= "<td align=\"right\">$vat</td>\r\n";
									$remittance .= "<td align=\"right\">$total</td>\r\n";
									$remittance .= "</tr>\r\n";
									$totalNet  += $net;
									$totalVat  += $vat;
									$totalPaid += $total;

									// add allocation line
									$sql = "INSERT INTO pl_allocations_items VALUES(
										'',
										'$aid',
										'$paymentid',
										'$txid',
										'$dephnaref',
										'$total'
									)";
									$alresult = $dbcnx->query($sql);

									// mark this transaction as paid
									$sql = "UPDATE purchases SET topay='0', outstanding='0', paidon='$now', allocatedto='$paymentid' WHERE ID='$txid'";
									$txxresult = $dbcnx->query($sql);

									// report to screen
									$resultMessage .= "<tr>";
									$resultMessage .= "<td>$date</td>";
									$resultMessage .= "<td>$dephnaref</td>\r\n";
									$resultMessage .= "<td align=\"right\">$net</td>";
									$resultMessage .= "<td align=\"right\">$vat</td>";
									$resultMessage .= "<td align=\"right\">$total</td>";
									$resultMessage .= "</tr>\n";

								} // end of transactions loop

								$remittance .= "<tr>";
								$remittance .= "<td colspan=\"4\">&nbsp;</td>";
								$remittance .= "<td align=\"right\"><b>" . number_format($totalPaid,2) . "</b></td>";
								$remittance .= "</tr>";
								$remittance .= "</table>\n"; // end this remittance

								// report to screen
								$resultMessage .= "<tr>";
								$resultMessage .= "<td colspan=\"4\">&nbsp;</td>";
								$resultMessage .= "<td align=\"right\"><b>" . number_format($totalPaid,2) . "<b></td>";
								$resultMessage .= "</tr>\n";

								// add this supplier to bank csv
								$bank  .= "$bnkSort,$bnkAccName,$bnkAccNum,".number_format($totalPaid,2,'.','').",$suppcustref,99\r\n";

								// update payment record with negative totals for a payment
								$negtotalNet  = $totalNet;  if ($negtotalNet > 0)  { $negtotalNet  *= -1; }
								$negtotalVat  = $totalVat;  if ($negtotalVat > 0)  { $negtotalVat  *= -1; }
								$negtotalPaid = $totalPaid; if ($negtotalPaid > 0) { $negtotalPaid *= -1; }
								$sql = "UPDATE purchases SET net='$negtotalNet', vat='$negtotalVat', total='$negtotalPaid', outstanding='0' WHERE ID='$paymentid'";
								$txxresult = $dbcnx->query($sql);

								// replace fields in this email body
								$search   = array("[[SupplierContactName]]","[[CustomerRef]]","[[TransactionList]]");
								$replace  = array($contname,$suppcustref,$remittance);
								$thisbody = str_replace($search,$replace,$body);

								// email supplier
								if ($erem == "Yes") {
									$from = "accounts@dephna.com";
									$fromname = "Dephna Group";

									$emails = explode(",",$aemails);
									
									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= $thisbody;
									$message .= file_get_contents('email-footer.html') . "\r\n";

									$mail = new PHPMailer(true);

									try {
										include('../../cron/SMTP.php');
										$mail->setFrom($from, $fromname);
										$mail->addReplyTo($from, $fromname);
										$mail->addBCC('accounts@dephna.com', 'Dephna Accounts');
										foreach ($emails AS $to) {
											$mail->addAddress($to);
										}
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;
										$mail->send();
										
										$resultMessage .= "<tr>";
										$resultMessage .= "<td>Email Sent to $aemails</td>";
										$resultMessage .= "</tr>\n";
										
									} catch (Exception $e) {
											$resultMessage .= "<tr>";
											$resultMessage .= "<td>COULD NOT Send Email<br>" . $mail->ErrorInfo . "</td>";
											$resultMessage .= "</tr>\n";
									}

								} else {
									$resultMessage .= "<tr>";
									$resultMessage .= "<td colspan=\"5\">Email Remittance set to No</td>";
									$resultMessage .= "</tr>\n";
								}

								// add line break
								$resultMessage .= "<tr>";
								$resultMessage .= "<td colspan=\"5\">&nbsp;</td>";
								$resultMessage .= "</tr>\n";

								// add to grand totals
								$GrandtotalNet  += $totalNet;
								$GrandtotalVat  += $totalVat;
								$GrandtotalPaid += $totalPaid;
							} else {
								$resultMessage .= "<tr>";
								$resultMessage .= "<td colspan=\"5\"><b>Could not get transactions</b><br>" . $dbcnx->error . "</td>";
								$resultMessage .= "</tr>\n";
								// add line break
								$resultMessage .= "<tr>";
								$resultMessage .= "<td colspan=\"5\">&nbsp;</td>";
								$resultMessage .= "</tr>\n";
							}
						} else {
							$resultMessage .= "<tr>";
							$resultMessage .= "<td colspan=\"5\"><b>Could not get supplier</b><br>" . $dbcnx->error . "</td>";
							$resultMessage .= "</tr>\n";
							// add line break
							$resultMessage .= "<tr>";
							$resultMessage .= "<td colspan=\"5\">&nbsp;</td>";
							$resultMessage .= "</tr>\n";
						}
					}

					if ($GrandtotalPaid > 0) {

						// save bank csv file
						$csv_dir = "../../dis2_db/csv";
						$filedt  = date("Y-m-d-H-i-s");
						$userid  = sprintf("%03s",$_SESSION['user']);

						if ($accpay == "Yes") {
							$bankfn  = "PB-$filedt-$prid-$userid.csv";
						} else {
							$bankfn  = "PN-$filedt-$prid-$userid.csv";
						}

						$handle  = fopen("$csv_dir/$bankfn", "w");
						fwrite($handle, $bank);
						fclose($handle);

						$GrandtotalNet  = number_format($GrandtotalNet,2);
						$GrandtotalVat  = number_format($GrandtotalVat,2);
						$GrandtotalPaid = number_format($GrandtotalPaid,2);

						// end payment list
						$resultMessage .= "<tr>";
						$resultMessage .= "<td colspan=\"4\"><b>Total Paid<b></td>";
						$resultMessage .= "<td align=\"right\"><b>$GrandtotalPaid</b></td>";
						$resultMessage .= "</tr>\n";

						// email accounts payable
						$from     = "no-reply@dephna.com";
						$fromname = "Dephna Robot";
						$to       = "accounts@dephna.com";
						$subject  = "PAYRUN-$prid";
						$apbody .= "<br><b>Purchase Ledger Payments</b>";
						$apbody .= "<br>PAYRUN-$prid";
						$apbody .= "<br>Amount: $GrandtotalPaid";
						$apbody .= "<br>Entered By: {$_SESSION['username']}<br>";
						$apbody .= "<br>Payment List:<br>\r\n";
						$apbody .= "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						$apbody .= $resultMessage . "</table>\n";

						$message  = file_get_contents('email-header.html') . "\r\n";
						$message .= $apbody;
						$message .= file_get_contents('email-footer.html') . "\r\n";

						$mail = new PHPMailer(true);
						try {
							include('../../cron/SMTP.php');
							$mail->setFrom($from, $fromname);
							$mail->addAddress($accountsPayable);
							$mail->addCC('rajive@dephna.com', 'Rajive Sachdev');
							$mail->addCC('accounts@dephna.com', 'Dephna Accounts');
							$mail->isHTML(true);
							$mail->Subject = $subject;
							$mail->Body    = $message;
							$mail->send();
							$resultMessage .= "<tr><td colspan=\"5\">Email Sent to Accounts Payable</td></tr>\n";
						} catch (Exception $e) {
							$resultMessage .= "<tr><td colspan=\"5\">COULD NOT Send Email<br>" . $mail->ErrorInfo . "</td></tr>\n";
						}

						// add bank download links
						$resultMessage .= "<tr>";
						$resultMessage .= "<td colspan=\"5\" align=\"center\"><a href=\"purchases-csvdl.php?fl=$bankfn\"><b>Download Bank File</b></a></td>";
						$resultMessage .= "</tr>\n";

						// log it
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Payment Run $prid Total: $GrandtotalPaid','$page','$now')");

					} else {
						$resultMessage .= "<tr><td colspan=\"5\"><b>Nothing to pay!</b></td></tr>\n";
					}
					$resultMessage = "<table align=\"center\">" . $resultMessage . "</table>\n";
				}
			} else {
				$accpay = "Yes";
			}

			if (!$resultMessage OR $e) {

                if (!$apbody) {
                    $apbody = "Three day payment please";
                }

                print "<p align=\"center\"><b>Make Payment On Account</b></p>\n";

                print "<form name=\"emailform\" method=\"post\" action=\"purchases-pay-onaccount.php\">\n";
                print "<input type=\"hidden\" name=\"makepayment\" value=\"1\" />\n";
                print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
                print "<tr>\n";
                print "	<td><b>Supplier</b></td>\n";
                $sql = "SELECT ID, title FROM suppliers ORDER BY title";
                if ($txresult = $dbcnx->query($sql) AND $txresult->num_rows) {
                    print "	<td><select name=\"suppid\" class=\"element\">\n";
                    print "		<option>Select</option>\n";
                    while ($txrow = $txresult->fetch_assoc()) {
                        $suppid = $txrow['ID'];
                        $title  = $txrow['title'];
                        print "		<option value=\"$suppid\"" . ($suppid == $suppid ? " SELECTED" : "") . ">$title</option>\n";
                    }

                    print "<p>&nbsp;</p>\n";

                    print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Subject:</b></td>\n";
                    print "	<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />\n";
                    if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
                    print "	</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Allocate To:</b></td>\n";
                    print "	<td><select name=\"bankacc\" class=\"element\">";
                    foreach ($bankaccounts AS $key => $value) {
                        print "		<option "; if ($bankacc == $key) { print "SELECTED "; } print "value=\"$key\">$value</option>";
                    }
                    print "	</select>";
                    print "	</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Client Email:</b></td>\n";
                    print " <td><textarea name=\"body\" rows=\"16\" class=\"element_box_large\">$body</textarea>";
                    if ($erbody) { print "<br><span class=\"redtext\">$erbody</span>"; }
                    print "<td>\n";	
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Email Accounts Payable:</b></td>\n";
                    print "	<td><select name=\"accpay\" class=\"element\">\n";
                    print "		<option>Select</option>\n";
                    print "		<option "; if ($accpay == "Yes") { print "SELECTED "; } print "value=\"Yes\">Yes, Process Payment</option>";
                    print "		<option "; if ($accpay == "No")  { print "SELECTED "; } print "value=\"No\">No, Manual Payment</option>";
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

                    print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Make Payments\" /></p>\n";
                    print "</form>\n";

                } else {
                    print "<p align=\"center\"><b>Nothing to Pay</b></p>\n";
                }
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<p align=\"center\" class=\"redtext\"><b>Could not connect to database!</b></p>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<p align=\"center\" class=\"redtext\"><b>Access Denied</b></p>";
	}
	if ($resultMessage) {
		print $resultMessage;
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
