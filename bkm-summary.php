<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	// print heading
	print "<p align=\"center\" class=\"larger\">Summary</p>\n";

	if ($bkid) {
		if ($custref) {

			// get agreement id
			$sql = "SELECT agreementID FROM bk_manager WHERE ID = '$bkid'";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$agreementID = $row['agreementID'];
			}

			if ($_POST['send']) {

				// get customer details
				$sql = "SELECT username, compname, aemails, addr1, addr2, addr3, addr4, telephone, email FROM users WHERE custref = '$custref'";
				$custresult = $dbcnx->query($sql);
				if (($custresult) && ($custresult->num_rows)) {
					$custrow = $custresult->fetch_assoc();
					$compname  = $custrow['compname'];
					$contact   = $custrow['username'];
					$aemails   = $custrow['aemails'];
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

					require "../../PHPMailer/src/SMTP.php";
					require "../../PHPMailer/src/PHPMailer.php";
					require "../../PHPMailer/src/Exception.php";

					// start email
					$subject  = "Dephna Group";
					$from     = "accounts@dephna.com";
					$fromname = "Dephna Accounts";

					// email message
					$message  = file_get_contents('email-header.html') . "\r\n";
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
					$message .= "&#8226; A Deposit Invoice is attached. To be paid.<br />";
					$message .= "&#8226; A Pro Forma Invoice is attached. To be paid.<br />";
					$message .= "&#8226; A Direct Debit Form is attached. Please complete and return.<br />";
					$message .= "</p>\n";
					$message .= "<p>Your services will go live once you have signed the contract, returned the direct debit form and made the payment.</p>\n";
					$message .= "<p>Kind Regards<br />Accounts</p>\n";
					$message .= file_get_contents('email-footer.html') . "\r\n";
					$message .= "\r\n\r\n";

					$mail = new PHPMailer(true);

					try {

						include_once('../../cron/SMTP.php');

						$mail->setFrom($from, $fromname);
						$mail->addAddress($recipient,$contact);
						$mail->addReplyTo($from, $fromname);
						$mail->addBCC($from, $fromname);
						$mail->isHTML(true);
						$mail->Subject = $subject;
						$mail->Body    = $message;
				
						// include the attachments
						$fileatt = "";
						include("bk-invoice-pdf.php");
						if ($fileatt) {
							$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
						}
						$fileatt = "";
						include("bk-deposit-invoice-pdf.php");
						if ($fileatt) {
							$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
						}
						$fileatt = "";
						include("bk-directdebit-form-pdf.php");
						if ($fileatt) {
							$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
						}

						$mail->send();
						$resultMessage .= "Email Sent to $recipient<br />";

					} catch (Exception $e) {
						$resultMessage .= "Email FAILED to $recipient Mailer Error: {$mail->ErrorInfo}";
					}

					$continue = "summary";

				} else {
					$resultMessage .= "Customer not found!<br />" . $dbcnx->error;
				}
			}

			if ((!$resultMessage) OR ($e)) {

				if ($agreementID) {
					include_once('bk-adobeSignStatus.php');
				}

				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<tr>\n";
				print "	<td align=\"right\"><b>Customer:</b></td>";
				print "	<td>$compname</td>";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Cust Ref:</b></td>";
				print "	<td>DP$custref</td>";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Site:</b></td>";
				print "	<td>$sitename</td>";
				print "</tr>\n";

				print "</table>\n";

				print "<p>&nbsp;</p>\n";

				// collate bookings
				// $allancillaries = array();
				$allunittypes   = array();

				$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					while ($row = $result->fetch_assoc()) {

						$uid		   = $row['ID'];
						$thisrate      = $row['rate'];
						$unitname      = $row['unitname'];
						$unittype      = $row['unittype'];
						$depunittotal  = $row['depunittotal'];
						$depcommtotal += $row['depcommtotal'];
						$increments    = $row['increments'];
						$contstartdate = $row['datefrom'];
						$contperiod	   = $row['cntrper'];
						$per           = $row['per'];
						$access        = $row['access'];
						$ipqty         = $row['ipqty'];
						$iptype        = $row['iptype'];
						$startdate     = date("d-m-Y",$contstartdate);

						$allunittypes[$unittype][$uid]['unitname'] = $unitname;
						$unitnet = $thisrate;
						$unitvat = $thisrate/100*$vatRate;
						$unittot = $unitnet+$unitvat;
						$allunittypes[$unittype][$uid]['net'] = $unitnet;
						$allunittypes[$unittype][$uid]['vat'] = $unitvat;
						$allunittypes[$unittype][$uid]['tot'] = $unittot;
						$allunittypes[$unittype][$uid]['startdate'] = $startdate;
						$allunittypes[$unittype][$uid]['per'] = $per;
						$allunittypes[$unittype][$uid]['iperiod'] = "$ipqty $iptype";
						$allunittypes[$unittype][$uid]['access'] = "$access";
						$allunittypes[$unittype][$uid]['deposit'] = $depunittotal;
						
					}

					// Get the ancillaries
					$sql = "SELECT * FROM bkm_anc WHERE bkid='$bkid'";
					$ancresult = $dbcnx->query($sql);
					if (($ancresult) && ($ancresult->num_rows)) {
						while ($ancrow = $ancresult->fetch_assoc()) {
							$ancid    = $ancrow['ancid'];
							$anctitle = $ancrow['anctitle'];
							$qty     = $ancrow['qty'];
							$rate    = $ancrow['rate'];
							$net     = $qty * $rate;
							$vat     = $ancrow['vat'];
							if ($vat == "Yes") { $vat = $net/100*$vatRate; } else { $vat = 0; }
							$tot = $net + $vat;
							$allunittypes['Ancillaries'][$ancid]['id']   = $ancid;
							$allunittypes['Ancillaries'][$ancid]['unitname'] = $anctitle;
							$allunittypes['Ancillaries'][$ancid]['qty']  = $qty;
							$allunittypes['Ancillaries'][$ancid]['rate'] = $rate;
							$allunittypes['Ancillaries'][$ancid]['net']  = $net;
							$allunittypes['Ancillaries'][$ancid]['vat']  = $vat;
							$allunittypes['Ancillaries'][$ancid]['tot']  = $tot;
							if ($depcommtotal) {
								$allunittypes['Ancillaries'][$ancid]['deposit']  = $depcommtotal;
								$depcommtotal = 0;
							}
						}
					}

					// add unit bookings and ancillaries to addendum
					print "<table align=\"center\" cellspacing=\"4\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"left\">&nbsp;</td>\n";
					print "	<td align=\"left\">&nbsp;</td>\n";
					print "	<td align=\"right\"><b>Deposit</b></td>\n";
					print "	<td align=\"right\"><b>Net</b></td>\n";
					print "	<td align=\"right\"><b>Vat</b></td>\n";
					print "	<td align=\"right\"><b>Total</b></td>\n";
					print "</tr>\n";
					foreach ($allunittypes AS $unittype => $units) {
						foreach ($units AS $unit => $values) {
							$unitname  = $values['unitname'];
							$startdate = $values['startdate'];
							$access    = $values['access'];
							$per       = $values['per'];
							$iperiod   = $values['iperiod'];
							$deposit   = $values['deposit'];
							$net       = $values['net'];
							$vat       = $values['vat'];
							$tot       = $values['tot'];

							$totnet += $net;
							$totvat += $vat;
							$tottot += $tot;
							$totdep += $deposit;

							print "<tr>\n";
							print "	<td align=\"left\">$unittype</td>\n";
							print "	<td align=\"left\">$unitname</td>\n";
							print "	<td align=\"right\">".number_format($deposit,2)."</td>\n";
							print "	<td align=\"right\">".number_format($net,2)."</td>\n";
							print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
							print "	<td align=\"right\">".number_format($tot,2)."</td>\n";
							print "</tr>\n";
						}
					}
					print "<tr>\n";
					print "	<td align=\"left\">&nbsp;</td>\n";
					print "	<td align=\"left\">&nbsp;</td>\n";
					print "	<td align=\"right\"><b>".number_format($totdep,2)."</b></td>\n";
					print "	<td align=\"right\"><b>".number_format($totnet,2)."</b></td>\n";
					print "	<td align=\"right\"><b>".number_format($totvat,2)."</b></td>\n";
					print "	<td align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "<p>&nbsp;</p>\n";
				}

				// print send resend
				print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
				print "<input type=\"hidden\" name=\"module\" value=\"summary\" />\n";
				print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
				print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
                
                if ($agreementID) {
					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\">AdobeSign Contract Status</td>\n";
					print "	<td>$ageementStatus";
					if (($ageementStatus) AND ($ageementStatus != "SIGNED")) {
						print " - <a href=\"bk-manager.php?module=cancel&amp;bkid=$bkid\">Cancel</a>";
					}
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "<p align=\"center\"><input type=\"submit\" value=\"Resend by Adobe\" class=\"button\" /></p>\n";
				} else {
					print "<p align=\"center\"><input type=\"submit\" value=\"Send by Adobe\" class=\"button\" /></p>\n";
				}

				print "</form>\n";

				print "<p align=\"center\">";
				print "<a href=\"bk-contract-combined-pdf.php?bkid=$bkid\" target=\"_blank\">Print Contract</a> | ";
				print "<a href=\"bk-proforma-invoice-print-pdf.php?bkid=$bkid\" target=\"_blank\">Print Pro Forma</a> | ";
				print "<a href=\"bk-deposit-invoice-print-pdf.php?bkid=$bkid\" target=\"_blank\">Print Deposit Inv</a>";
				print "</p>";

				// get agreement events
				if ($agreementID) {
					include_once('bk-adobeSignEvents.php');
				}

			}
		} else {
			$resultMessage .= "<b>Customer Not Yet Selected</b>";
		}
	} else {
		$resultMessage .= "<b>No Units Added Yet</b>";
	}
?>
