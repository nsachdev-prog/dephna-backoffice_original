<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "BKManager";
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

			$bkaid = $_REQUEST['bkaid'];
			
			if ($bkaid) {

				include('bka-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				$sql = "SELECT b.*, u.title AS unitname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
					$unitid   = $row['unitid'];
					$unitname = $row['unitname'];
					$depanc   = $row['depanc'];
					$aemails  = $row['aemails'];
					$start    = $row['start'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
					$transientDocumentId = $row['transientDocumentId'];
					$agreementID = $row['agreementID'];
					$invoiced = $row['invoiced'];

					if ($agreementID) {
						// get agreement status
						include_once('bk-adobeSignStatus.php');
					}

					$totaldeposits = $depanc;

					list($sd,$sm,$sy) = explode("-",$start);
					$daysthismonth = date("t");
					$daystopay = $daysthismonth - $sd + 1;

					$datefrom = "$sd/$sm/$sy";
					$dateto   = "$daysthismonth/$sm/$sy";

					list($y,$m,$d) = explode("-",date("Y-m-d"));
					
					// get customer details
					$sql = "SELECT username, compname, email, aemails, addr1, addr2, addr3, addr4, telephone FROM users WHERE custref = '$custref'";
					$custresult = $dbcnx->query($sql);
					if (($custresult) && ($custresult->num_rows)) {
						$custrow = $custresult->fetch_assoc();
						$compname  = $custrow['compname'];
						$custname  = $custrow['username'];
						$addr1     = $custrow['addr1'];
						$addr2     = $custrow['addr2'];
						$addr3     = $custrow['addr3'];
						$addr4     = $custrow['addr4'];
						$telephone = $custrow['telephone'];
						if (!$aemails) { $aemails = $email; }

						print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";

						// find the original booking
						$sql = "SELECT ID, tempid FROM bookings WHERE custid = '$custref' AND siteid = '$siteid' AND unitid = '$unitid'";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$bkid = $row['ID'];
							$bkgid = $row['tempid'];
							if (!$bkgid) { $bkgid = $bkid; }

							if ($_POST['confirm']) {

								if (!$invoiced) {

									$mailit = $_POST['mailit'];
									if ($mailit) {
										$subject  = strip_tags($_POST['subject']);
										$body     = strip_tags($_POST['body']);
										$received = strip_tags($_POST['received']);
										if (!$subject) { $ersubject = "No Subject"; $e++; }
										if (!$body)    { $erbody    = "No Message"; $e++; }
										if (!$received) { $erreceived = "Enter Received"; $e++; }
										else {
											if (!is_numeric($received)) {
												$erreceived = "Not Numeric"; $e++;
											}
										}
									}

									if (!$e) {

										// get next invoice number.
										$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype`='I' ORDER BY `invnumber` DESC LIMIT 0, 1";
										$iresult = $dbcnx->query($sql);
										if (($iresult) && ($iresult->num_rows)) {
											$irow = $iresult->fetch_assoc();
											$invnumber = $irow['invnumber'];
										}

										if ($start) {

											$invnumber++;
											$invnet = 0;
											$invvat = 0;
											$invtotal = 0;

											// start invoice header
											$y = date("Y"); $m = date("m"); $d = date("d");
											$now = time();
											$sql = "INSERT INTO inv_headers VALUES (
												null,
												'I',
												'$invnumber',
												'$y',
												'$m',
												'$d',
												'$custref',
												'0',
												'0',
												'No',
												'',
												'$now',
												'{$_SESSION['username']}'
											)";
											$result = $dbcnx->query($sql);
											if ($result) {
												$invoices[] = $invnumber;
												$hid = $dbcnx->insert_id;
												$rdb .= "INSERT INTO inv_headers VALUES ('$hid','I','$invnumber','$y','$m','$d','$custref','','','No','','$now','{$_SESSION['username']}');\n";
											}    

											// add ancillaries
											$sql = "SELECT * FROM bka_anc WHERE bkaid = '$bkaid' ORDER BY anctitle";
											$anresult = $dbcnx->query($sql);
											if (($anresult) && ($anresult->num_rows)) {
												while ($anrow = $anresult->fetch_assoc()) {
													$ancid    = $anrow['ancid'];
													$anctitle = $anrow['anctitle'];
													$ancqty   = $anrow['qty'];
													$ancrate  = $anrow['rate'];
													$ancvat   = $anrow['vat'];

													if ($ancid == 5) {
														$newinternet++; // internet added so do ice later
													}

													// add to invoice
													// get report group
													$sql = "SELECT ancgroup FROM ancillaries WHERE title='$anctitle'";
													$gresult = $dbcnx->query($sql);
													if (($gresult) && ($gresult->num_rows)) {
														$grow = $gresult->fetch_assoc();
														$repgrp = $grow['ancgroup'];
													} else {
														$repgrp = "Unknown";
													}

													$dayrate = $ancrate * 12 / 365;
													$linenet = $daystopay * $dayrate * $ancqty;
													$invnet += $linenet;
													if ($ancvat == "Yes") {
														$vattotal = $linenet/100*$vatRate;
														$vatrate = $vatRate;
													} else {
														$vattotal = 0;
														$vatrate = 0;
													}
													$linetotal = $linenet + $vattotal;
													$linenet = round($linenet, 2);
													$vattotal = round($vattotal, 2);
													$linetotal = round($linetotal, 2);
													$invvat += $vattotal;
													$invtotal += $linetotal;

													$sql = "INSERT INTO inv_lines VALUES (
														null,
														'$hid',
														'$sitename $repgrp',
														'$anctitle $daystopay Days',
														'$datefrom',
														'$dateto',
														'$ancqty',
														'$ancrate',
														'$linenet',
														'$vatrate',
														'$vattotal',
														'$linetotal',
														'$sitename',
														'$unitname',
														'$repgrp'
													)";
													if ($lresult = $dbcnx->query($sql)) {
														$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
													}
												}
											} else {
												$resultMessage .= "No ancillaries to add<br />";
											}

											// post invoice total
											$sql = "UPDATE inv_headers SET invtotal='$invtotal', outstanding='$invtotal' WHERE ID='$hid'";
											if ($tresult = $dbcnx->query($sql)) {
												$rdb .= $sql.";\n";
											}

										} else {
											$resultMessage .= "No Invoices To Do<br />";
										}

										// post deposit invoice if any
										if ($totaldeposits) {

											// start invoice header

											$invnumber++;

											$sql = "INSERT INTO inv_headers VALUES (
												null,
												'I',
												'$invnumber',
												'$y',
												'$m',
												'$d',
												'$custref',
												'$totaldeposits',
												'$totaldeposits',
												'No',
												'',
												'$now',
												'{$_SESSION['username']}'
											)";
											$hresult = $dbcnx->query($sql);
											if ($hresult) {
												$invoices[] = $invnumber;
												$hid = $dbcnx->insert_id;
												$rdb .= "INSERT INTO inv_headers VALUES ('$hid','I','$invnumber','$y','$m','$d','$custref','$totaldeposits','$totaldeposits','No','','$now','{$_SESSION['username']}');\n";
											}

											if ($depanc) {
												$sql = "INSERT INTO inv_lines VALUES (
													null,
													'$hid',
													'$sitename Deposit',
													'Ancillaries Deposit',
													'',
													'',
													'1',
													'$depanc',
													'$depanc',
													'0',
													'0',
													'$depanc',
													'$sitename',
													'$unitname',
													'Deposit'
												)";
												if ($lresult = $dbcnx->query($sql)) {
													$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
												}
											}
										}

										// add ancillaries to original booking
										$sql = "SELECT * FROM bka_anc WHERE bkaid = '$bkaid' ORDER BY anctitle";
										$anresult = $dbcnx->query($sql);
										if (($anresult) && ($anresult->num_rows)) {
											while ($anrow = $anresult->fetch_assoc()) {

												$ancid    = $anrow['ancid'];
												$anctitle = $anrow['anctitle'];
												$ancqty   = $anrow['qty'];
												$ancrate  = $anrow['rate'];
												$ancvat   = $anrow['vat'];

												$sql = "INSERT INTO bookings_an VALUES (
													null,
													'$bkid',
													'$custref',
													'$ancid',
													'$anctitle',
													'$ancqty',
													'$ancrate',
													'$ancvat',
													'Month'
												)";
												$result = $dbcnx->query($sql);
												if ($result) {
													$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
													$resultMessage .= "Ancillary $anctitle added to booking<br />";
												} else {
													$resultMessage .= "Ancillary $anctitle Could NOT be added to booking<br />";
												}

											}
										}

										if ($newinternet) {

											$resultMessage .= "Customer has new internet<br />";
		
											// add new vlan to ice connect
		
											// do they already have a vlan at this site
											$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
											if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
												$irow = $iresult->fetch_assoc();
												$iceapi_access_point = $irow['api_access_point'];
												$iceaccess_token     = $irow['access_token'];
		
												$postdata = http_build_query(
													array(
														'apikey' => $iceaccess_token,
														'action' => 'listall'
													)
												);
		
												$ch = curl_init(); 
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
												curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
												curl_setopt($ch, CURLINFO_HEADER_OUT, true);
												$result = curl_exec($ch);
												$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
												curl_close($ch);
		
												if ($httpcode == 200) {
													if ($result = json_decode($result, TRUE)) {
														foreach ($result AS $id => $item) {
															if ($item['Data']['ID'] == $custref) {
																$vlansite = $item['Data']['Site'];
															}
														}
													} else {
														$resultMessage .= "Json Decode FAILED getting vlan<br />";
													}
												} elseif ($httpcode == 400) {
													$resultMessage .= "Error 400 Getting VLAN<br />";
												} elseif ($httpcode == 401) {
													$resultMessage .= "Unauthorised Getting VLAN<br />";
												} else {
													$resultMessage .= "Unknown error code $httpcode Getting VLAN<br />";
												}
		
												if (($vlansite) AND ($vlansite == $icesites[$siteid])) {
		
													$resultMessage .= "Customer already has VLAN at this site<br />";
		
												} else {
		
													// get available vlans at this site
													$postdata = http_build_query(
														array(
															'apikey' => $iceaccess_token,
															'action' => 'listunused'
														)
													);
										
													$ch = curl_init(); 
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
													curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
													curl_setopt($ch, CURLINFO_HEADER_OUT, true);
													$result = curl_exec($ch);
													$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
													curl_close($ch);
										
													if ($httpcode == 200) {
														if ($result = json_decode($result, TRUE)) {
															// reorg
															$columns = array_column($result, 'VLAN');
															array_multisort($columns, SORT_ASC, $result);
															foreach ($result AS $key => $item) {
																if ($item['Site'] == $icesites[$siteid]) {
																	$vlan = $item['VLAN'];
																	break;
																}
															}
														} else {
															$resultMessage .= "Json Decode Failed getting available VLAN<br />";
														}
													} elseif ($httpcode == 400) {
														$resultMessage .= "Error getting available VLAN<br />";
													} elseif ($httpcode == 401) {
														$resultMessage .= "Error getting available VLAN - Unauthorised<br />";
													} else {
														$resultMessage .= "Unknown error getting available VLAN code $httpcode<br />";
													}
		
													if ($vlan) {
		
														// generate ice password
														$icepassword = randomPassword();
														// strip non alpha numeric chars from compname
														$compname = preg_replace('/[^A-Za-z0-9]/', '', $compname);
														// reduce compname to 18 chars
														if (strlen($compname) > 18) { $compname = substr($compname, 0, 18); }
		
														$postdata = http_build_query(
															array(
																'apikey'   => $iceaccess_token,
																'action'   => 'newtenant',
																'tenant'   => $compname,
																'id'       => $custref,
																'site'     => $icesites[$siteid],
																'vlan'     => $vlan,
																'password' => $icepassword
															)
														);
								
														$ch = curl_init(); 
														curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
														curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
														curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
														curl_setopt($ch, CURLINFO_HEADER_OUT, true);
														$result = curl_exec($ch);
														$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
														curl_close($ch);
							
														if ($httpcode == 200) {
															$result = strip_tags($result);
															if ($result = json_decode($result, TRUE)) {
																$iceusername = $result['Username'];
																$icepassword = $result['Password'];
																$resultMessage .= "Internet VLAN Added<br />Username: $iceusername<br />Password: $icepassword<br />";
															} else {
																$resultMessage .= "Json Decode Failed getting returned username for VLAN<br />";
															}
														} elseif ($httpcode == 400) {
																$resultMessage .= "Internet VLAN FAILED code 400<br />";
														} elseif ($httpcode == 401) {
															$resultMessage .= "Internet VLAN FAILED Unauthorised<br />";
														} else {
															$resultMessage .= "Internet VLAN FAILED code $httpcode<br />";
														}
		
													}
		
												}
		
											} else {
												$resultMessage .= "Could NOT get ice access token<br />";
											}
		
										}

										if ($mailit) {

											require "../../PHPMailer/src/SMTP.php";
											require "../../PHPMailer/src/PHPMailer.php";
											require "../../PHPMailer/src/Exception.php";

											// replace fields in template with data
											$search  = array("[[custname]]","[[staffname]]","[[login]]","[[password]]","[[received]]");
											$replace = array($custname,$_SESSION['username'],$email,$thispassword,$received);
											$body    = str_replace($search,$replace,$body);

											// start email
											$from = "accounts@dephna.com";
											$fromname = "Dephna Accounts";
											
											include("email-header.php");
											$message .= nl2br($body);
											include("email-footer.php");

											// send email(s)
											$emails = explode(",",$aemails);
											$mail = new PHPMailer(true);

											try {
												
												include('../../cron/SMTP.php');

												$mail->setFrom($from, $fromname);
												foreach ($emails AS $to) {
													$mail->addAddress($to);
												}
												$mail->addReplyTo($from, $fromname);
												$mail->addBCC($from, $fromname);
												$mail->isHTML(true);
												$mail->Subject = $subject;
												$mail->Body    = $message;

												$mail->send();
												$resultMessage .= "Email Sent to $aemails<br />";

											} catch (Exception $ee) {
												$resultMessage .= "Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
											}

											if ($newinternet) {

												$subject = "Dephna Internet WiFi";
												$body  = "<p>Dear $custname,</p>\r\n";
												$body .= "<p>Below is your username and password for internet access.</p>\r\n";
												$body .= "<p>Username: $iceusername<br>";
												$body .= "Password: $icepassword</p>\n";
												$body .= "Attached is a pdf with information about our WiFi service.</p>\n";
												$body .= "<p>Kind Regards<br>Dephna IT</p>\r\n";
		
												$from = "it@dephna.com";
												$fromname = "Dephna IT";
		
												include("email-header.php");
												$message .= nl2br($body);
												include("email-footer.php");
		
												$mail = new PHPMailer(true);
		
												try {

													include('../../cron/SMTP.php');
		
													$mail->setFrom($from, $fromname);
													foreach ($emails AS $to) {
														$mail->addAddress($to);
													}
													$mail->addReplyTo($from, $fromname);
													$mail->addBCC($from, $fromname);
													$mail->isHTML(true);
													$mail->Subject = $subject;
													$mail->Body    = $message;
		
													$fileatt = "";
													if (is_file("pdf/WiFi-Setup-Guide.pdf")) {
														$fileatt = file_get_contents("pdf/WiFi-Setup-Guide.pdf");
														if ($fileatt) {
															$mail->AddStringAttachment($fileatt, "WiFi-Setup-Guide.pdf", "base64", "application/pdf"); 
														}
													}
													
													$mail->send();
													$resultMessage .= "WiFi Email Sent to $aemails<br />";
		
												} catch (Exception $ee) {
													$resultMessage .= "WiFi Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
												}
		
											}
		
											
										} else {
											$resultMessage .= "Email customer not selected<br />";
										}

										$now = time();
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Confirmed','$compname','$page','$now')");
								
									}
								} // end if !$invoiced

								if (!$e) {

									if (($agreementID) AND ($ageementStatus == "SIGNED")) {

										// get contract and audit trail
										
										// get api access token
										include_once('AdobeSignGetAccessToken.php');
										if ($access_token) {

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
												$filename = date("YmdHis") . rand(1000,9999) . ".pdf";
												if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
													if (fwrite($fh,$result)) {

														// add to files table
														$now = time();
														$pdffilename = "Adobe-Sign-AuditTrail-DP$custref-$bkaid.pdf";
														$sql = "INSERT INTO bookings_files VALUES (
															null,
															'$custref',
															'$bkgid',
															'Audit Trail',
															'',
															'$pdffilename',
															'$filename',
															'$now',
															'{$_SESSION['username']}'
														)";
														if ($result = $dbcnx->query($sql)) {
															$resultMessage .= "Pdf Audit Trail Saved<br />";
														} else {
															$resultMessage .= "Could NOT Pdf Audit Trail<br />";
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

											// get signed contract
											$ch = curl_init();
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/combinedDocument"); 
											curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
											$result = curl_exec($ch);
											curl_close($ch);
											if ($result !== FALSE) {
												// save as file
												$filename = date("YmdHis") . rand(1000,9999) . ".pdf";
												if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
													if (fwrite($fh,$result)) {

														// add to files table
														$now = time();
														$pdffilename = "Dephna-Contract-DP$custref-$bkaid.pdf";
														$sql = "INSERT INTO bookings_files VALUES (
															null,
															'$custref',
															'$bkgid',
															'Contract',
															'',
															'$pdffilename',
															'$filename',
															'$now',
															'{$_SESSION['username']}'
														)";
														if ($result = $dbcnx->query($sql)) {
															$resultMessage .= "Pdf Contract Saved<br />";
														} else {
															$resultMessage .= "Could NOT Save Pdf Contract<br />";
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

										} else {
											$resultMessage .= "Could NOT Get Access Token.<br />";
										}
									}

									if ((!$agreementID) OR ($ageementStatus == "SIGNED")) {
										
										// Delete everything from bk manager
										// Delete ancillaries
										$sql = "DELETE FROM bka_anc WHERE bkaid='$bkaid'";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage .= "Ancillaries Deleted<br />";
											// delete header
											$sql = "DELETE FROM bka_head WHERE ID = '$bkaid'";
											$result = $dbcnx->query($sql);
											if ($result) {
												$resultMessage .= "Header Deleted<br />";
											} else {
												$resultMessage .= "Could not delete header<br />";
											}
										} else {
											$resultMessage .= "Could not delete ancillaries<br />";
										}

										$now = time();
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Confirmed','$compname','$page','$now')");
									
									} else {
										// mark as invoiced
										$sql = "UPDATE bka_head SET invoiced = 'Yes' WHERE ID = '$bkaid'";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage .= "Order marked as invoiced<br />";
										} else {
											$resultMessage .= "Could NOT mark order as invoiced<br />";
										}

									}
									
								}

								if ($rdb) {
									include_once("rdb-save.php");
									include_once("rdb-mail.php");
								}

								// report email
								$mailto   = "david@dsi3.net,rajive@dephna.com";
								$from     = "robot@dephna.com";
								$mailsjt  = "Additional Services Confirm Result DP$custref";
								$mime_boundary = md5(uniqid(time()));
								$headers = "From: $from\r\n";
								$headers .= "Reply-To: $from\r\n";
								$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
								$headers .= "MIME-Version: 1.0\r\n";
								$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
								$message  = "--".$mime_boundary."\r\n";
								$message .= "Content-type:text/html; charset=utf-8\r\n";
								$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
								$message .= "<p><b>BKM Comfirm Result DP$custref<br />$compname</b></p>\r\n";
								$message .= "<p>$resultMessage</p>";
								$message .= "<p>By {$_SESSION['username']} " . date("Y-m-d H:i") . "</p>";
								$message .= "\r\n\r\n";
								$message .= "--".$mime_boundary."--";
								mail($mailto, $mailsjt, $message, $headers);
								// $resultMessage = "";
								
							} else {

								$template = "BKExistingCustomer";

								$sql = "SELECT * FROM `mailouts` WHERE `mref` = '$template'";
								$tresult = $dbcnx->query($sql);
								if (($tresult) && ($tresult->num_rows)) {
									$trow = $tresult->fetch_assoc();
									$subject = $trow['subject'];
									$body    = $trow['html'];
								} else {
									$resultMessage .= "<b>Could not get email template $template.</b>\n";
								}
								$mailit = "on";

							}

						} else {
							$resultMessage .= "<b>Original booking not found DP$custref, Siteid $siteid, Unitid $unitid</b>\n";
						}

					} else {
						$resultMessage .= "<b>Customer not found</b>\n";
					}

					if ((!$resultMessage) OR ($e)) {

						print "	<span class=\"notclicked\">\n";

						if (!$invoiced) {

							print "<p align=\"center\"><b>Confirm Payment. Transfer to bookings. Send email to customer.</b><br />";
							print "[[fields]] will be replaced automatically.";
							print "</p>\n";
							print "<p align=\"center\" class=\"redtext\"><b>This will make the booking live. One click. It cannot be reversed.</b></p>\n";
							if ($ageementStatus != "SIGNED") {
								print "<p align=\"center\"><b>The Contract Is NOT SIGNED</b><br />You can still go live and download the signed contract later.</p>\n";
							}
							print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
							print "<form name=\"addform\" method=\"post\" action=\"bka-confirm.php\">\n";
							print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
							print "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Subject</b></td>\n";
							print "	<td><input type=\"text\" name=\"subject\" value=\"$subject\" class=\"element\" />";
							if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\"><b>Message</b>"; if ($erbody) { print "&nbsp;<span class=\"redtext\">$erbody</span>"; } print "<br />";
							print "<textarea name=\"body\" wrap=\"virtual\" rows=\"8\" class=\"element_box\">$body</textarea></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Amount Received</b></td>\n";
							print "	<td><input type=\"text\" name=\"received\" value=\"$received\" class=\"element\" />";
							if ($erreceived) { print "&nbsp;<span class=\"redtext\">$erreceived</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Email Client</b></td>\n";
							print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";

							print "	</table>\n";

							print "<p align=\"center\"><input type=\"submit\" value=\"Send &amp; Go Live\" class=\"button\" /></p>\n";

							print "</form>\n";

						} else {

							if ($ageementStatus == "SIGNED") {

								print "<p align=\"center\"><b>Already Invoiced</b><br />Download Signed Contract</p>\n";
								print "<form name=\"addform\" method=\"post\" action=\"bka-confirm.php\">\n";
								print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
								print "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
								print "<p align=\"center\"><input type=\"submit\" value=\"Download\" class=\"button\" /></p>\n";
								print "</form>\n";

							} else {

								print "<p align=\"center\"><b>Contract Not Signed</b></p>";

							}
						}

						print "	</span>\n";

						print "	<span class=\"uploading\">\n";
						print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
						print "	</span>\n";

					}

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";

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

function randomPassword($len = 10) {
	// define character libraries - remove ambiguous characters like iIl|1 0oO
	$sets = array();
	$sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
	$sets[] = 'abcdefghjkmnpqrstuvwxyz';
	$sets[] = '23456789';
	$password = '';
	//append a character from each set - gets first 4 characters
	foreach ($sets as $set) {
		$password .= $set[array_rand(str_split($set))];
	}
	//use all characters to fill up to $len
	while(strlen($password) < $len) {
		//get a random set
		$randomSet = $sets[array_rand($sets)];
		
		//add a random char from the random set
		$password .= $randomSet[array_rand(str_split($randomSet))]; 
	}
	//shuffle the password string before returning!
	return str_shuffle($password);
}
?>
