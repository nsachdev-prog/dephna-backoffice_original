<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Inventory";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_REQUEST['id'];
			$custref = $_REQUEST['custref'];
			$now = time();
			if ($id AND $custref) {

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

				// get customer
				$sql = "SELECT * FROM users WHERE custref = '$custref'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$custname = $row['username'];
					$addr1    = $row['addr1'];
					$addr2    = $row['addr2'];
					$addr3    = $row['addr3'];
					$addr4    = $row['addr4'];

					// get inventory
					$sql = "SELECT * FROM inventory WHERE ID = '$id'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$siteid      = $row['siteid'];
						$aemails     = $row['email'];
						$txndate     = $row['txndate'];
						$inout       = $row['inout'];
						$details     = $row['details'];
						$notes       = $row['notes'];
						$lastupd     = $row['lastupd'];
						$updby       = $row['updby'];
						$agreementID = $row['agreementID'];
						$bkid        = $row['bkid'];
						$confirmed   = $row['confirmed'];
						$sent        = $row['sent'];
						$called      = $row['called'];
						$meeting     = $row['meeting'];
						$fileid      = $row['transientDocumentId']; // was Adobe doc. now bookings_files id if sent & confirmed

						$cntrenddate = date("d/m/Y",strtotime($txndate));

						// get sitename
						$sql = "SELECT title AS sitename FROM sites WHERE ID = '$siteid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$sitename = $row['sitename'];
						}

						// load image requirements
						$imagesIndex = array();
						$lines = file('inventory-images.csv', FILE_IGNORE_NEW_LINES);
						foreach ($lines as $line) {
							list(
								$tunittype,
								$tcode,
								$ttitle,
								$checkIn,
								$checkOut,
								$joined
							) = explode(",",$line);
							$imagesIndex[$tunittype][$tcode]['title'] = $ttitle;
							$imagesIndex[$tunittype][$tcode]['joinedrequired'] = $joined;
							if ($inout == "in") {
								$imagesIndex[$tunittype][$tcode]['required'] = $checkIn;
							} elseif ($inout == "out") {
								if ($checkOut == "on") {
									$imagesIndex[$tunittype][$tcode]['required'] = $checkOut;
								} else {
									$imagesIndex[$tunittype][$tcode]['required'] = $checkIn;
								}
							}
						}

						// get units and count image requirements for each
						$imagesNotUploaded  = 0;
						$imagesUploaded     = 0;
						$sql = "SELECT `ID`, `unitname`, `unittype`, `join` FROM `inventory_units` WHERE `iid` = '$id'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$uid      = $row['ID'];
								$unittype = $row['unittype'];
								$units[]  = $row['unitname'];
								$join     = $row['join'];
								// check we have images
								if ($join) {
									foreach($imagesIndex[$unittype] AS $code => $items) {
										if ($items['joinedrequired'] == "on") {
											$sql = "SELECT ID FROM `inventory_images` WHERE `uid` = '$uid' AND `code` = '$code' AND `inout` = '$inout'";
											if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
												$imagesUploaded++;
											} else {
												$imagesNotUploaded++;
											}
										}
									}
								} else {
									foreach($imagesIndex[$unittype] AS $code => $items) {
										if ($items['required'] == "on") {
											$sql = "SELECT ID FROM `inventory_images` WHERE `uid` = '$uid' AND `code` = '$code' AND `inout` = '$inout'";
											if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
												$imagesUploaded++;
											} else {
												$imagesNotUploaded++;
											}
										}
									}
								}
							}
							$units = implode(", ",$units);
						} else {
							$units .= "No Units Found!";
						}

						print "<p class=\"larger\" align=\"center\">Summary</p>\n";
						print "<p class=\"larger\" align=\"center\">$compname</p>\n";
						print "<p class=\"larger\" align=\"center\">Check{$inout}</p>\n";

						if ($_POST['addnote']) {
							$note = $_POST['note'];
							if ($note) {
								$note = nl2br($note);
								$note = addslashes($note);
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','$note')");
							}
						}

						if ($_POST['send']) {

							if ($inout == "out") {
								// collect utilities for check out
								$sql = "SELECT * FROM inventory_meters WHERE iid = '$id'";
								if (($comresult = $dbcnx->query($sql)) && ($comresult->num_rows)) {

									// start invoice
									// get next tx number
									$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype`='I' ORDER BY `invnumber` DESC LIMIT 0, 1";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$inv = $row['invnumber'];
										$inv++;
									}
									$trndate = date("Y/m/d");
									list($iyear,$imonth,$iday) = explode("/",$trndate);

									// start inv header
									$sql = "INSERT INTO inv_headers VALUES (
										null,
										'I',
										'$inv',
										'$iyear',
										'$imonth',
										'$iday',
										'$custref',
										'0',
										'0',
										'',
										'',
										'$now',
										'System'
									)";
									$result = $dbcnx->query($sql);
									if ($result) {
										$resultMessage .= "Invoice $inv Created<br />";
										$hid = $dbcnx->insert_id;

										while ($comrow = $comresult->fetch_assoc()) {
											$prevdate  = $comrow['prevdate'];
											$previous  = $comrow['previous'];
											$current   = $comrow['meter'];
											$commodity = $comrow['commodity'];
											$unitname  = $comrow['unitname'];
											$unit      = $comrow['unit'];

											// get charge rate
											// try customer rate first
											$sql = "SELECT rate FROM UtlCommodities
													WHERE sitename='$sitename'
													AND commodity = '$commodity'
													AND custref = '$custref'
											";
											if (($rateresult = $dbcnx->query($sql)) AND ($rateresult->num_rows)) {
												$raterow = $rateresult->fetch_assoc();
												$rate = $raterow['rate'];
											} else {
												// get default rate for site
												$sql = "SELECT rate FROM UtlCommodities
														WHERE sitename = '$sitename'
														AND commodity = '$commodity'
														AND custref = 'Default'
												";
												if (($rateresult = $dbcnx->query($sql)) AND ($rateresult->num_rows)) {
													$raterow = $rateresult->fetch_assoc();
													$rate = $raterow['rate'];
												}
											}
											if (!$rate) {
												$rate = 0;
											}

											$used = $current - $previous;
											if ($used) {
												$net = $used * $rate;
												$vat = $net/100*$vatRate;
												$vat = round($vat, 2);
												$net = round($net, 2);
												$tot = $net + $vat;
												$thisqty = 1;
											} else {
												$net = 0;
												$vat = 0;
												$tot = 0;
												$thisqty = 1;
											}

											$convprevdate = str_replace("-","/",$prevdate);

											if (($commodity == "Hot") || ($commodity == "Cold")) {
												$sltext = "$commodity Water";
											} else {
												$sltext = "$commodity Charges";
											}

											$sql = "INSERT INTO inv_lines VALUES (
												null,
												'$hid',
												'$sitename Utilities',
												'$sltext $unit',
												'$convprevdate',
												'$cntrenddate',
												'$thisqty',
												'$net',
												'$net',
												'$vatRate',
												'$vat',
												'$tot',
												'$sitename',
												'$sltext',
												'Utilities'
											)";
											if ($dbcnx->query($sql)) {
												$resultMessage .= "Added $unitname $commodity $tot<br />";
											} else {
												$resultMessage .= "Could NOT add $unitname $commodity $tot<br />";
											}
											$sql = "UPDATE inv_headers SET invtotal = invtotal+'$tot', outstanding = outstanding+'$tot' WHERE ID='$hid'";
											$dbcnx->query($sql);
										}
									} else {
										$resultMessage .= "Could NOT Create Invoice Header<br />";
									}
								}
							}

							// send email
							// get email template
							$sql = "SELECT * FROM mailouts WHERE mref='inventorysend'";
							$tresult = $dbcnx->query($sql);
							if ($tresult->num_rows) {
								$trow = $tresult->fetch_assoc();
								$subject = $trow['subject'];
								$body    = $trow['html'];
							}
							$search  = array("[[custname]]","[[units]]","[[inout]]");
							$replace = array($custname,$units,$inout);
							$body    = str_replace($search,$replace,$body);
							$subject = str_replace("[[inout]]",$inout,$subject);
							$body    = nl2br($body);

							$from     = "accounts@dephna.com";
							$fromname = "Dephna Accounts";

							// email message
							include("email-header.php");
							$message .= "<p>$body</p>\n";
							include("email-footer.php");

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							// send email(s)
							$emails = explode(",",$aemails);

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								foreach ($emails AS $to) {
									$mail->addAddress($to,$custname);
								}
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC($from, $fromname);
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;
						
								// attachments
								$byemail = 2;
								$fileatt = "";
								include("inventory-checkin-document-pdf.php");
								if ($fileatt) {
									$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
								}
								if ($inv) {
									$fileatt = "";
									include("inventory-make-inv-pdf.php");
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype);
									}
								}

								$mail->send();
								$resultMessage .= "Email Sent to $aemails<br />";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED: {$mail->ErrorInfo}";
							}
							
							// mark as sent
							$sql = "UPDATE `inventory` SET `sent` = '$now' WHERE ID='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Agreement Marked As Sent<br />\n";
							} else {
								$resultMessage .= "Agreement Could NOT Be Marked As Sent<br />\n";
							}

							$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','<b>Agreement Sent</b><br />$resultMessage')");
						}

						if ((!$resultMessage) OR ($e)) {
							
							$notes   = str_replace('\n',"<br />",$notes);
							$details = str_replace('\n',"<br />",$details);

							if ($called) {
								$called = date("d-m-Y H:i",$called);
							} else {
								$called = "Not Called";
							}

							if ($meeting) {
								$meeting = strtotime($meeting);
								$meeting = date("d-m-Y H:i",$meeting);
							} else {
								$meeting = "Not Set";
							}

							if ($txndate) {
								$txndate = strtotime($txndate);
								$txndate = date("d-m-Y",$txndate);
							} else {
								$txndate = "Not Set";
							}

							if ($sent) {
								$senttext = date("d-m-Y H:i",$sent);
							} else {
								$senttext = "Not Sent";
							}

							if ($confirmed) {
								$confirmedtext = strtotime($confirmed);
								$confirmedtext = date("d-m-Y",$confirmedtext);
							} else {
								$confirmedtext = "Not Confirmed";
							}

							// get the meters
							$sql = "SELECT unitname, commodity, meter FROM inventory_meters WHERE iid = '$id'";
							if (($mresult = $dbcnx->query($sql)) AND ($mresult->num_rows)) {
								$meters = array();
								while ($mrow = $mresult->fetch_assoc()) {
									$thisunitname  = $mrow['unitname'];
									$thiscommodity = $mrow['commodity'];
									$thismeter     = $mrow['meter'];
									if (!$thismeter) {
										$meters[] = "<span class=\"redtext\">$thisunitname - $thiscommodity: $thismeter</span>";
									} else {
										$meters[] = "$thisunitname - $thiscommodity: $thismeter";
									}
								}
								$meters = implode("<br />",$meters);
							} else {
								$meters = "<span class=\"redtext\">No Meters Added!</span>";
							}

						
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Units</b></td>\n";
							$units = wordwrap($units, 55, "<br />\n");
							print "	<td valign=\"top\">$units</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Email</b></td>\n";
							$dispemails = str_replace(",","<br />",$aemails);
							print "	<td valign=\"top\">$dispemails</td>";
							print "</tr>\n";

							print "<tr>\n";
							if ($inout == "in") {
								print "	<td align=\"right\"><b>Contract Start Date</b></td>\n";
							} else {
								print "	<td align=\"right\"><b>Contract End Date</b></td>\n";
							}
							print "	<td>$txndate</td>";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Called</b></td>\n";
							print "	<td>$called</td>";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Check $inout</b></td>\n";
							print "	<td>$meeting</td>";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Agreement Sent</b></td>";
							print "	<td>$senttext</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Confirmed</b></td>\n";
							print "	<td>$confirmedtext</td>";
							print "</tr>\n";

							if (!$confirmed) {

								if ($inout == "in") {
									$inouttext = "Images";
								} else {
									$inouttext = "Images/Damage Ckeck";
								}

								print "<tr>\n";
								print "	<td align=\"right\"><b>$inouttext</b></td>\n";
								print "	<td>$imagesUploaded Completed</td>";
								print "</tr>\n";

								if ($imagesNotUploaded) {
									print "<tr>\n";
									print "	<td align=\"right\">&nbsp;</td>\n";
									print "	<td><span class=\"redtext\">$imagesNotUploaded Missing</span></td>";
									print "</tr>\n"; 
								}

							} else {

								if ($sent AND $fileid) {
									// get file link
									$sql = "SELECT `pdf`, `file` FROM `bookings_files` WHERE `ID` = '$fileid'";
									if (($result = $dbcnx->query ($sql)) AND ($result->num_rows)) {
										$row  = $result->fetch_assoc();
										$pdf  = $row['pdf'];
										$file = $row['file'];
										print "<tr>\n";
										print "	<td align=\"right\"><b>Contract</b></td>\n";
										print "<td><a href=\"getpdf.php?pdf=$pdf&file=$file\" target=\"_blank\">$pdf</a></td>\n";
										print "</tr>\n";
									}
								}

							}

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Meters</b></td>\n";
							print "	<td>$meters</td>";
							print "</tr>\n";

							if ($details) {
								print "<tr>\n";
								print "	<td colspan=\"2\" width=\"400\"><b>Customer Notes</b><br />";
								print "$details</td>\n";
								print "</tr>\n";
							}

							if ($notes) {
								print "<tr>\n";
								print "	<td colspan=\"2\" width=\"400\"><b>Internal Notes</b><br />";
								print "$notes</td>\n";
								print "</tr>\n";
							}

							print "</table>\n";

							if ($imagesUploaded AND !$imagesNotUploaded AND !$confirmed) {
								// print send resend
								print "<form name=\"sendform\" method=\"post\" action=\"inventory-summary.php\">\n";
								print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
								print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
								print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";

								if ($sent) {
									print "<span class=\"notclicked\">\n";
									print "<p align=\"center\"><input type=\"submit\" value=\"Resend Check $inout\" class=\"button\" /></p>\n";
									print "</span>\n";
									print "<span class=\"uploading\">\n";
									print "<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
									print "</span>\n";
								} else {
									print "<span class=\"notclicked\">\n";
									print "<p align=\"center\"><input type=\"submit\" value=\"Send Check $inout\" class=\"button\" /></p>\n";
									print "</span>\n";
									print "<span class=\"uploading\">\n";
									print "<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
									print "</span>\n";
								}

								print "</form>\n";
							}

							if (!$confirmed) {
								print "<p align=\"center\"><a href=\"inventory-checkin-document-pdf.php?id=$id&amp;custref=$custref\" target=\"_blank\">Print Check $inout Document</a></p>\n";
							}

							// get agreement events
							print "<p align=\"center\" class=\"larger\">Agreement History</p>\n";
							$sql = "SELECT * FROM inventory_history WHERE iid='$id' ORDER BY crdate DESC";
							$result = $dbcnx->query($sql);
							if (($result) && ($result->num_rows)) {
								print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
								print "	<tr>\n";
								print "		<td style=\"border-bottom:1px solid #666\" valign=\"top\"><b>Date/Time</b></td>\n";
								print "		<td style=\"border-bottom:1px solid #666\" valign=\"top\"><b>By</b></td>\n";
								print "		<td style=\"border-bottom:1px solid #666\" valign=\"top\"><b>Details</b></td>\n";
								print "	</tr>\n";

								// row background colours
								print "<style>\n";
								print ".r0 { background-color:#fff }\n";
								print ".r1 { background-color:#ddd }\n";
								print "</style>\n";

								while ($row = $result->fetch_assoc()) {
									$crdate  = $row['crdate'];
									$crby    = $row['crby'];
									$details = $row['details'];
									$crdate  = date("d/m/Y H:i",$crdate);
									$details = stripslashes($details);

									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print "		<td valign=\"top\">$crdate</td>\n";
									print "		<td valign=\"top\">$crby</td>\n";
									print "		<td valign=\"top\">$details</td>\n";
									print "	</tr>\n";
								}
								print "<tr>\n";
								print " <td style=\"border-top:1px solid #666\" colspan=\"3\">&nbsp;</td>\n";
								print "</tr>\n";
								print "</table>\n";
							} else {
								print "<p align=\"center\">No History Found</p>\n";
							}

							// add manual note to history
							print "<p align=\"center\" class=\"larger\">Add Manual Note To History</p>\n";
							print "<form name=\"noteform\" method=\"post\" action=\"inventory-summary.php\">\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"addnote\" value=\"1\" />\n";

							print "<p align=\"center\"><textarea name=\"note\" rows=\"4\" class=\"element_box_large\">$note</textarea></p>\n";

							print "<p align=\"center\"><input type=\"submit\" value=\"Add Note\" class=\"button\" /></p>\n";

							print "</form>\n";

                            // get adobe agreement events (legacy)
							if ($agreementID) {
								include_once('bk-adobeSignEvents.php');
							}

							print "<script>\n";
							print "  $('.uploading').hide();\n";
							print "  	$(\"form[name='sendform']\").submit(function () {\n";
							print " 	$('.uploading').show();\n";
							print "  	$('.notclicked').hide();\n";
							print "  });\n";
							print "</script>\n";

						}
					} else {
						$resultMessage .= "Record Not Found!<br />" . $dbcnx->error . "<br />";
					}

				} else {
					$resultMessage .= "Could not find customer<br />" . $dbcnx->error . "<br />";
				}
			} else {
				$resultMessage .= "Page called without ids<br />";
			}
			$dbcnx->close();
			
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"inventory-list.php\">Inventory</a> | ";
		print "<a href=\"index.php\">Home</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
