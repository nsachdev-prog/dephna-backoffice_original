<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "SRF";
$subpage = "Confirm";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">Service Request Forms</p>\n";
	
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_REQUEST['id'];
			$custref = $_REQUEST['custref'];

			if ($id AND $custref) {

				include('srf2-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

				// get srf
				$sql = "SELECT * FROM srf2 WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$authby  = $row['authby'];
					$email   = $row['email'];
					$siteid  = $row['siteid'];
					$unitid  = $row['unitid'];
					$unitname = $row['unitname'];
					$details = $row['details'];
					$notes   = $row['notes'];
					$adnotes = $row['adnotes'];
					$charge  = $row['charge'];
					$vat     = $row['vat'];
					$group   = $row['group'];
					$type    = $row['type'];
					$lastupd = $row['lastupd'];
					$updby   = $row['updby'];
					$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
					$agreementID = $row['agreementID'];
					$confirmed = $row['confirmed'];
					if ($vat == "on") {
						$vat = "Yes";
					} else {
						$vat = "No";
					}

					if ($agreementID) {
						include_once('bk-adobeSignStatus.php');
					}

					// get customer name and address
					$sql = "SELECT compname, addr1, addr2, addr3, addr4, email, aemails FROM users WHERE custref = '$custref'";
					$result = $dbcnx->query($sql);
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$compname = $row['compname'];
						$addr1    = $row['addr1'];
						$addr2    = $row['addr2'];
						$addr3    = $row['addr3'];
						$addr4    = $row['addr4'];
						$memail   = $row['email'];
						$aemails  = $row['aemails'];
						if (!$aemails) { $aemails = $memail; }
					}

					// get site name
					$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
					$result = $dbcnx->query($sql);
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$sitename = $row['title'];
					}

					print "<p class=\"larger\" align=\"center\">Confirm</p>\n";
					print "<p class=\"larger\" align=\"center\">$compname - $unitname</p>\n";
						
					if ($_POST['action'] == "confirm") {

						$confirmAnyway = $_REQUEST['confirmAnyway'];
						$pdffilename   = "Dephna-SRF-DP$custref-$id.pdf";
						$pdfatfilename = "Dephna-SRF-AuditTrail-DP$custref-$id.pdf";
						$now = time();
						$mailit = $_POST['mailit'];
	
						if ($access_token) {

							if ($agreementID AND $ageementStatus == "SIGNED") {
											
								// get audit trail
								$headers = array(
									'Authorization: Bearer '.$access_token,
									'Accept: application/pdf'
								);
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/auditTrail"); 
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								$result = curl_exec($ch);
								curl_close($ch);
								if ($result !== FALSE) {
									// save as file
									$filename = date(YmdHis) . rand(1000,9999) . ".pdf";
									if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
										if (fwrite($fh,$result)) {
											// add to files table
											$sql = "INSERT INTO bookings_files VALUES (
												null,
												'$custref',
												'',
												'Audit Trail',
												'',
												'$pdfatfilename',
												'$filename',
												'$now',
												'{$_SESSION['username']}'
											)";
											if ($result = $dbcnx->query($sql)) {
												$resultMessage .= "Pdf Audit Trail Saved<br />";
												$gotAuditTrail = 1;
											} else {
												$resultMessage .= "Could NOT Pdf Audit Trail<br />" . $dbcnx->error . "<br />";
											}

										} else {
											$resultMessage .= "File Data Could NOT be Saved.<br />";
										}
									} else {
										$resultMessage .= "Data File Could NOT be Openned.<br />";
									}
								} else {
									$resultMessage .= "Audit Trail DNL FAILED: Nothing was returned from Adobe Sign.<br />";
								}

								if ($gotAuditTrail) {

									// get signed contract
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/combinedDocument"); 
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									$result = curl_exec($ch);
									curl_close($ch);
									if ($result !== FALSE) {
										// save as file
										$filename = date(YmdHis) . rand(1000,9999) . ".pdf";
										if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
											if (fwrite($fh,$result)) {
												// add to files table
												$now = time();
												$sql = "INSERT INTO bookings_files VALUES (
													null,
													'$custref',
													'$bkid',
													'Contract',
													'',
													'$pdffilename',
													'$filename',
													'$now',
													'{$_SESSION['username']}'
												)";
												if ($result = $dbcnx->query($sql)) {
													$gotContract = 1;
													$resultMessage .= "Pdf Agreement Saved<br />";
												} else {
													$resultMessage .= "Could NOT Save Pdf Contract<br />" . $dbcnx->error . "<br />";
												}

											} else {
												$resultMessage .= "File Data Could NOT be Saved.<br />";
											}
										} else {
											$resultMessage .= "Data File Could NOT be Openned.<br />";
										}
									} else {
										$resultMessage .= "Contract DNL FAILED: Nothing was returned from Adobe Sign.<br />";
									}
								}
							}
								
							elseif ($confirmAnyway) {

								// cancel contract
								$headers = array(
									'Content-Type: application/json',
									'Authorization: Bearer '.$access_token
								);
								$data = array(
									"state" => "CANCELLED",
									"agreementCancellationInfo" => array(
										"comment" => "Agreement Cancelled by {$_SESSION['username']}",
										"notifyOthers" => TRUE
									)
								);
								$data = json_encode($data, TRUE);
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
								curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/state");
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								$result = curl_exec($ch);
								curl_close($ch);
								if ($result !== FALSE) {
									$result = json_decode($result, TRUE);
									if ($result['code']) {
										$resultMessage .= "Error: {$result['code']}<br />{$result['message']}<br />";
									} else {
										$resultMessage .= "Unsigned Agreement Cancelled<br />";
										// Update SRF
										$sql = "UPDATE srf2 SET transientDocumentId = '', agreementID = '' WHERE ID = '$id'";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage .= "Agreement $pdffilename Cancelled<br>\n";
										} else {
											$resultMessage .= "Could NOT Cancel in CAM<br>\n";
										}
									}
								} else {
									$resultMessage .= "Error - Nothing was returned from Adobe Sign.<br />";
								}
							}

							if (($gotContract) OR ($confirmAnyway)) {

								// produce invoice
								// get next tx number
								$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype`='I' ORDER BY `invnumber` DESC LIMIT 0, 1";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$inv = $row['invnumber'];
									$inv++;
								}
								$trndate = date("Y-m-d");
								list($iyear,$imonth,$iday) = explode("-",$trndate);
								
								$net = $charge;
								if ($vat == "Yes") {
									$vat = $net/100*$vatRate;
									$thisvatrate = $vatRate;
									$vatcol = 1;
								} else {
									$vat = 0;
									$thisvatrate = 0;
								}
								$vat = round($vat, 2);
								$net = round($net, 2);
								$tot = $net + $vat;

								// start inv header
								$sql = "INSERT INTO inv_headers VALUES (
									null,
									'I',
									'$inv',
									'$iyear',
									'$imonth',
									'$iday',
									'$custref',
									'$tot',
									'$tot',
									'No',
									'',
									'$now',
									'{$_SESSION['username']}'
								)";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage .= "Invoice $inv Created<br />\n";
									$hid = $dbcnx->insert_id;

									$sql = "INSERT INTO inv_lines VALUES (
										null,
										'$hid',
										'$sitename Fitout/Sundry',
										'$unitname - $details',
										'',
										'',
										'1',
										'$net',
										'$net',
										'$thisvatrate',
										'$vat',
										'$tot',
										'$sitename',
										'Service Request',
										'Fitout/Sundry'
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Added Invoice Line<br />";

										// Update SRF
										$confdate = date("Y-m-d");
										$sql = "UPDATE srf2 SET confirmed = '$confdate' WHERE ID='$id'";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage .= "SRF Comfirmed<br />";

											if ($mailit) {
												// start email
												$subject = "Dephna Service Request";
												$from = "Dephna Accounts <accounts@dephna.com>";
												$mime_boundary = md5(uniqid(time()));
												$headers = "From: $from\r\n";
												$headers .= "Reply-To: $from\r\n";
												$headers .= "Bcc: $from\r\n";
												$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
												$headers .= "MIME-Version: 1.0\r\n";
												$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
												$message = "--".$mime_boundary."\r\n";
												$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
												$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				
												include("email-header.php");
												$message .= "<p>Dear $authby</p>\n";
                        						$message .= "<p>Re: $sitename - $unitname</p>\n";
												$message .= "<p>Your service request is complete.<br />Please find attached an invoice as agreed.</p>\n";
												$message .= "<p><b>Kind Regards<br />Accounts</b></p>\n";
												include("email-footer.php");

												if ($inv) {
													include("srf2-make-inv-pdf.php");
												}
				
												$message .= "--".$mime_boundary."--";
				
												// send email
												if (mail($email, $subject, $message, $headers)) {
													$resultMessage .= "Email Successfully Sent to $email<br />";
												} else {
													$resultMessage .= "Email FAILED to send to $email<br />";
												}
												
											} else {
												$resultMessage .= "Email Customer Not Selected<br />";
											}


										} else {
											$resultMessage .= "Could NOT Update Listing Status<br />" . $dbcnx->error . "<br />";
										}
									} else {
										$resultMessage .= "Could NOT add Invoice Line<br />" . $dbcnx->error . "<br />";
									}
									
								} else {
									$resultMessage .= "Could NOT Create Invoice Header<br />" . $dbcnx->error . "<br />";
								}

							}

						} else {
							$resultMessage .= "Could NOT Get Access Token.<br />";
						}

					} else {
						$mailit = "on";
					}

					if ((!$resultMessage) OR ($e)) {

						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Date</b></td>\n";
                        print "	<td>$txndate</td>\n";
                        print "</tr>\n";
                        
                        print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Details</b></td>\n";
						print "	<td>$details</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
						print "	<td align=\"right\"><b>Charge (Before Vat)</b></td>\n";
						print "	<td>$charge</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
					    print "	<td align=\"right\"><b>Apply Vat</b></td>\n";
					    print "	<td>$vat</td>\n";
					    print "</tr>\n";
                        
                        print "<tr>\n";
						print "	<td align=\"right\"><b>Authorised By</b></td>\n";
						print "	<td>$authby</td>\n";
                        print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Signatory Email</b></td>\n";
						print "	<td>$email</td>\n";
						print "</tr>\n";

						print "</table>\n";

						if (!$confirmed) {

							if ($agreementID) {

								print "<p align=\"center\" class=\"redtext\"><b>This will download the agreement<br />Send the invoice to the client<br />and mark the SRF as confirmed.<br />One click. It cannot be reversed.</b></p>\n";
								if (($agreementID) AND ($ageementStatus != "SIGNED")) {
									print "<p align=\"center\" class=\"redtext\"><b>AGREEMENT NOT SIGNED</b>.</p>\n";
								}
								print "<form name=\"addform\" method=\"post\" action=\"srf2-confirm.php\">\n";
								print "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
								print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
								print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";

								print "<table width=\"420px\" align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

								print "<tr>\n";
								print "	<td align=\"right\">Email Client</td>\n";
								print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
								print "</tr>\n";

								print "	</table>\n";

								if ($ageementStatus == "SIGNED") {
									print "<p align=\"center\"><input type=\"submit\" value=\"Download Signed Contract and Confirm\" class=\"button\" /></p>\n";
								} else {
									print "<p align=\"center\"><b>Agreement Not Signed Yet!</b><br />Please encourage the client to sign it.</p>";
									print "<input type=\"hidden\" name=\"confirmAnyway\" value=\"1\" />\n";
									print "<p align=\"center\"><input type=\"submit\" value=\"Cancel Contract and Confirm Anyway\" class=\"button\" /></p>\n";
								}
								print "</form>\n";
							} else {
								$resultMessage .= "<p align=\"center\" class=\"redtext\"><b>Contract Not Sent Yet</b></p>\n";
							}
							
						} else {
							$resultMessage .= "<p align=\"center\" class=\"redtext\"><b>Agreement Already Confirmed</b></p>\n";
						}
					}
				} else {
					$resultMessage = "SRF Not Found!<br />" . $dbcnx->error;
				}

			} else {
				$resultMessage = "Page called without ids<br />";
			}
			$dbcnx->close();
			
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"srf2-list.php\">Return</a>";
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
