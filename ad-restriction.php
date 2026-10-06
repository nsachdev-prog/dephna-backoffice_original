<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Restriction";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">DEBTOR RESTRICTION</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			// get filters
			$custref = $_REQUEST['custref'];
			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$lo = $_REQUEST['lo'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";
			$now = time();

			if ($custref) {
				// get customer details
				$sql = "SELECT ID, `username`, `email`, `aemails`, `compname`, `addr1`, `addr2`, `addr3`, `addr4`, `tim` FROM `users` WHERE `custref`='$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$id	      = $crow['ID'];
					$compname = $crow['compname'];
					$custname = $crow['username'];
					$aemails  = $crow['aemails'];
					$email    = $crow['email'];
					if (!$aemails) { $aemails = $email; }
					$addr1 = $crow['addr1'];
					$addr2 = $crow['addr2'];
					$addr3 = $crow['addr3'];
                    $addr4 = $crow['addr4'];
					$tim   = $crow['tim']; // tim is now used for restrict access toggle Yes/No(or blank)

					// restriction toggle
					if ($tim == "Yes") {
						$action = "Release Restriction";
						$mailout = "DebtorRelease";
						$tim = "No";
						$newexpires = "";
						$icestatus = "active";
					} else {
						$action = "Restrict Access";
						$mailout = "DebtorRestrict";
						$tim = "Yes";
						$newexpires = date("Y-m-d",strtotime("-1 Day"));
						$icestatus = "suspend";
					}

					print "<p align=\"center\" class=\"larger\">$compname</p>\n";
					print "<p align=\"center\" class=\"larger\">$action</p>\n";
                    
					// get outstanding invoices
					$sql = "SELECT * FROM inv_headers WHERE custref=$custref AND outstanding <> 0 ORDER BY year,month,day";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"R"=>"Receipt",
							"X"=>"Unknown"
						);
						if ($_POST['posted']) {

							$mydate = date("Y-m-d");
							$resultMessage = "";
							$rdb = "";

							// process form
							$e = 0;
							if ($action == "Release Restriction") {
								// check release conditions
								if (!$_POST['underThreshold']) {
									$resultMessage .= "Condition NOT met: Amount owed under &pound;1000.<br />";
									$e++;
								}
								if (!$_POST['noPrevious']) {
									$resultMessage .= "Condition NOT met: No Previous Restrictions in last 3 months.<br />";
									$e++;
								}
								if (!$_POST['depositCoversdebt']) {
									$resultMessage .= "Condition NOT met: Outstanding covered by deposits.<br />";
									$e++;
								}
							}

							if (!$e) {

								if ($action == "Release Restriction") {
									$resultMessage .= "All Release Conditions met.<br />";
								}

								if ($_POST['send']) {

									// process and send email
									$subject = strip_tags($_POST['subject']);
									$body    = strip_tags($_POST['body']);
									$trefs   = $_POST['trefs'];
									$statement = $_POST['statement'];
									$tdate = $row['date'];
									if (!$subject) { $ersubject = "No Subject"; $e++; }
									if (!$body)    { $erbody    = "No Message"; $e++; }
									if (!$e) {

										require "../../tcpdf6.8/tcpdf.php";
										require "../../PHPMailer/src/SMTP.php";
										require "../../PHPMailer/src/PHPMailer.php";
										require "../../PHPMailer/src/Exception.php";

										// send email to client
										$from     = "accounts@dephna.com";
										$fromname = "Dephna Accounts";
										
										$message = "";
										$message  = file_get_contents('email-header.html') . "\r\n";
										$message .= nl2br($body);
										$message .= file_get_contents('email-footer.html') . "\r\n";

										$emails = explode(",",$aemails);
										$mailed = 0;

										$mail = new PHPMailer(true);

										try {
											
											include_once('../../cron/SMTP.php');

											$mail->setFrom($from, $fromname);
											foreach ($emails AS $to) {
												$to = trim($to);
												if ($to) {
													$mail->addAddress($to, $custname);
												}
											}
											$mail->addReplyTo($from, $fromname);
											$mail->addBCC($from, $fromname);
											$mail->isHTML(true);
											$mail->Subject = $subject;
											$mail->Body    = $message;
									
											$bysmtp = 1;

											// attach statement if selected
											if ($statement == "on") {
												$fileatt = "";
												include("mailouts-pdf-make-statement.php");
												if ($fileatt) {
													$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
												}
											}

											// attach invoices if selected
											if ($trefs) {
												foreach ($trefs AS $inv => $value) {
													// get inv total
													$sql = "SELECT * FROM `inv_headers` WHERE `invnumber`='$inv'";
													$invresult = $dbcnx->query($sql);
													$invrow = $invresult->fetch_assoc();
													$hid    = $invrow['ID'];
													$iyear  = $invrow['year'];
													$imonth = $invrow['month'];
													$iday   = $invrow['day'];
													$invtotal = $invrow['invtotal'];
													$txtype = $invrow['txtype'];
													$fileatt = "";
													include("mailouts-pdf-make-inv2.php");
													if ($fileatt) {
														$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
													}
												}
											}

											$mail->send();
											$resultMessage .= "Email Sent to $to<br />";
											$mailed++;

										} catch (Exception $e) {
											$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
										}
										
										if ($mailed) {
											// add to notes	
											$sql = "INSERT INTO `ad_notes` VALUES (
												null,
												'$mydate',
												'$custref',
												'Email sent to $to',
												'note',
												'',
												'',
												'',
												'$now',
												'{$_SESSION['username']}'
											)";
											if ($result = $dbcnx->query($sql)) {
												// add to rdb string
												$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
											}
										}
									}
								} else {
									$resultMessage .= "Email customer not selected<br />";
								}

								// update paxton
								// get paxton sites
								$sql = "SELECT p.siteid, s.title AS sitename
										FROM PaxAuth AS p
										LEFT JOIN sites AS s ON s.ID = p.siteid
								";
								if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
									while ($srow = $sresult->fetch_assoc()) {
										$siteid   = $srow['siteid'];
										if ($siteid == 34) {
											$sitename = "Brittania/Coronation";
										} else {
											$sitename = $srow['sitename'];
										}
										// check if user has bookings at this site
										if ($siteid == 34) {
											$sql = "SELECT ID FROM `bookings` WHERE `custid` = '$custref' AND `siteid` IN (34,11)";
										} else {
											$sql = "SELECT ID FROM `bookings` WHERE `custid` = '$custref' AND `siteid` = '$siteid'";
										}
										if (($bresult = $dbcnx->query($sql)) AND ($bresult->num_rows)) {
											// get paxton access token
											$access_token = "";
											include('PaxGetAccessToken.php');
											if ($access_token) {
												// get all users with this custref at this site
												$headers = array(
													'Authorization: bearer '.$access_token,
													'Accept: application/json'
												);
												$postdata = http_build_query(
													array(
														'query' => "SELECT userID, firstName, surname FROM UsersEx WHERE field4_50 = '$custref'"
													)
												);
												$ch = curl_init();
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
												curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
												curl_setopt($ch, CURLINFO_HEADER_OUT, true);
												curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

												$uresult = curl_exec($ch);
												$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
												if ($uresult !== FALSE) {
													if ($uresult = json_decode($uresult, TRUE)) {
														$restricted = 0;
														foreach($uresult AS $uid => $puser) {
															$userid    = $puser['userID'];
															$firstName = $puser['firstName'];
															$surname   = $puser['surname'];
															$headers = array(
																'Authorization: bearer '.$access_token,
																'Accept: application/json'
															);
															$postdata = http_build_query(
																array(
																	'id'         => $userid,
																	'expiryDate' => $newexpires
																)
															);
															$ch = curl_init();
															curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
															curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
															curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
															curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
															curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
															curl_setopt($ch, CURLINFO_HEADER_OUT, true);
															curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
															curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
															$u2result = curl_exec($ch);
															$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
															if ($u2result !== FALSE) {
																if ($httpcode == 200) {
																	$resultMessage .= "$sitename - $firstName $surname - $action<br />";
																	$restricted++;
																} else {
																	$resultMessage .= "Error2: $sitename - $firstName $surname Code $httpcode<br />";
																}
															} else {
																$resultMessage .= "Error1: $sitename - $firstName $surname Code $httpcode<br />";
															}
														}
														if ($restricted) {
															$resultMessage .= "$action $restricted users at $sitename<br />";
														} else {
															$resultMessage .= "No users found at $sitename<br />";
														}
													} else {
														$resultMessage .= "No Users found at $sitename<br />";
													}
												} else {
													$resultMessage .= "No response from paxton $sitename Code $httpcode<br />Result: $uresult<br />";
												}

											} else {
												$resultMessage .= "Could not get paxton access token for $sitename<br />";
											}
										} else {
											$resultMessage .= "No bookings found at $sitename<br />";
										}
									}
								} else {
									$resultMessage .= "Could not get paxton sites<br />";
								}

								// do they have internet?
								$sql = "SELECT ID FROM `bookings_an` WHERE `custref` = '$custref' AND `ancid` = '5'";
								if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {

									$resultMessage .= "Customer has internet. Trying to $action ...<br />";

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
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
										curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
										$result = curl_exec($ch);
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

										if ($httpcode == 200) {
											if ($result = json_decode($result, TRUE)) {
												$vlans = array();
												foreach ($result AS $iceid => $item) {
													if ($item['Data']['ID'] == $custref) {
														$vlans[] = $item['VLAN'];
														$vlan++;
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

										if ($vlan) {

											foreach($vlans AS $vlan) {

												$postdata = http_build_query(
													array(
														'apikey' => $iceaccess_token,
														'action' => 'servicestatus',
														'vlan'   => $vlan,
														'status' => $icestatus
													)
												);
						
												$ch = curl_init(); 
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
												curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
												curl_setopt($ch, CURLINFO_HEADER_OUT, true);
												curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
												curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
												$result = curl_exec($ch);
												$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						
												if ($httpcode == 200) {
													$resultMessage .= "Internet $action VLAN $vlan Done<br />";
												} elseif ($httpcode == 400) {
														$resultMessage .= "Internet $action FAILED code 400<br />";
												} elseif ($httpcode == 401) {
													$resultMessage .= "Internet $action FAILED Unauthorised<br />";
												} else {
													$resultMessage .= "Internet $action FAILED code $httpcode<br />";
												}

											}

										} else {
											$resultMessage .= "VLAN NOT FOUND for DP$custref<br />";
										}

									} else {
										$resultMessage .= "Could NOT get ice access token<br />";
									}

								} else {
									$resultMessage .= "Customer does not have internet<br />";
								}

								// update user
								$sql = "UPDATE users SET tim = '$tim' WHERE custref = '$custref'";
								if ($result = $dbcnx->query($sql)) {
									// add to rdb string
									$rdb .= $sql.";\n";
									$resultMessage .= "$action Done<br />";
									// add to notes	
									$sql = "INSERT INTO `ad_notes` VALUES (
										null,
										'$mydate',
										'$custref',
										'$action<br>$resultMessage',
										'note',
										'0',
										'',
										'',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($result = $dbcnx->query($sql)) {
										// add to rdb string
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Access','DP$custref $action','$page','$now')");
									} else {
										$resultMessage .= "Could NOT add to notes<br />" . $dbcnx->error . "<br />";
									}
								} else {
									$resultMessage .= "Could NOT update database<br />" . $dbcnx->error . "<br />";
								}

								// mail report
								$mailto   = "rajive@dephna.com";
								$from     = "robot@dephna.com";
								$mailsjt  = "Restriction Result DP$custref";
								$mime_boundary = md5(uniqid(time()));
								$headers = "From: $from\r\n";
								$headers .= "Reply-To: $from\r\n";
								$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
								$headers .= "MIME-Version: 1.0\r\n";
								$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
								$message  = "--".$mime_boundary."\r\n";
								$message .= "Content-type:text/html; charset=utf-8\r\n";
								$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
								$message .= "<p><b>Restriction Result DP$custref<br />$compname</b></p>\r\n";
								$message .= "<p>$resultMessage</p>";
								$message .= "<p>By {$_SESSION['username']} - " . date("Y-m-d H:i") . "</p>\r\n";
								$message .= "\r\n\r\n";
								$message .= "--".$mime_boundary."--";
								mail($mailto, $mailsjt, $message, $headers);

							} else {
								// add to notes	
								$sql = "INSERT INTO `ad_notes` VALUES (
									null,
									'$mydate',
									'$custref',
									'$action<br>$resultMessage',
									'note',
									'0',
									'',
									'',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$resultMessage .= "<b>Cannot Unrestrict and Action logged.</b><br />";
								} else {
									$resultMessage .= "<b>Cannot Unrestrict and Could NOT add to notes.</b><br />" . $dbcnx->error . "<br />";
								}
							}
						}
    
						if ((!$resultMessage) OR ($e)) {
							// display the form with outstanding transactions
							$totalDue = 0;
							print "<form name=\"emailform\" method=\"post\" action=\"ad-restriction.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"fromcus\" value=\"$fromcus\" />\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";
							print "<tr>\n";
							print "	<td colspan=\"7\" align=\"right\"><b>Send Email</b></td>\n";
							print "	<td align=\"right\"><input type=\"checkbox\" name=\"send\" /></td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td>Date</td>\n";
							print "	<td>Ref</td>\n";
							print "	<td>Type</td>\n";
							print "	<td align=\"right\">Amount</td>\n";
							print "	<td align=\"right\">Outstanding</td>\n";
							print "	<td align=\"right\">Days</td>\n";
							print "	<td>&nbsp;</td>\n";
							print "	<td align=\"right\">Include</td>\n";
							print "</tr>\n";
							
							while ($row = $result->fetch_assoc()) {
								$tid   = $row['ID'];
								$year  = $row['year'];
								$month = $row['month'];
								$day   = $row['day'];
								$type  = $row['txtype'];
								$tref  = $row['invnumber'];
								$amount  = $row['invtotal'];
								$pending = $row['outstanding'];
								$tdate = "$day-$month-$year";
								$totalDue += $pending;

								$now = time();
								$unixdate = strtotime("$year-$month-$day");
								$seconds = $now - $unixdate;
								$days = round($seconds / (60 * 60 * 24));

								print "<tr>\n";
								print "	<td>$tdate</td>\n";
								print "	<td>$tref</td>\n";
								print "	<td>{$types[$type]}</td>\n";
								print "	<td align=\"right\">$amount</td>\n";
								print "	<td align=\"right\">$pending</td>\n";
								print "	<td align=\"right\">$days</td>\n";
								if ($type == "I") {
									print "	<td><a href=\"customer-pdf-invoice.php?inv=$tref\" target=\"_blank\">View</a></td>\n";
									print "	<td align=\"right\"><input type=\"checkbox\" name=\"trefs[$tref]\" /></td>\n";

								} else {
									print "	<td align=\"right\">&nbsp;</td>\n";
									print "	<td align=\"right\">&nbsp;</td>\n";
								}
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td colspan=\"4\" align=\"right\"><b>Total Due</b></td>\n";
							print "	<td align=\"right\"><b>" . number_format($totalDue,2) . "</b></td>\n";
							print "	<td>&nbsp;</td>\n";
							print "	<td>&nbsp;</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td colspan=\"7\" align=\"right\">Statement</td>\n";
							print "	<td align=\"right\"><input type=\"checkbox\" name=\"statement\" /></td>\n";
							print "</tr>\n";
							print "</table>\n";

							// add three tickboxes in table if $action = "Release Restriction", $underThreshold, $noPrevious, and $depositCoversdebt
							if ($action == "Release Restriction") {
								print "<p align=\"center\"><b>Release Conditions</b><br>(tick to confirm you have checked all the conditions.)</p>\n";
								print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";
								print "<tr>\n";
								print "	<td><input type=\"checkbox\" name=\"underThreshold\" /></td><td>Amount owed under &pound;1000.</td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "	<td><input type=\"checkbox\" name=\"noPrevious\" /></td><td>No Previous Restrictions in last 3 months.</td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "	<td><input type=\"checkbox\" name=\"depositCoversdebt\" /></td><td>Outstanding amount covered by deposits.</td>\n";
								print "</tr>\n";
								print "</table>\n";
								print "<p align=\"center\">If these conditions cannot be met, please contact your manager.</p>\n";
							}

							// get email template
							$sql = "SELECT * FROM `mailouts` WHERE `mref` = '$mailout'";
							$tresult = $dbcnx->query($sql);
							if ($tresult->num_rows) {
								$trow  = $tresult->fetch_assoc();
								$subject = $trow['subject'];
								$body    = $trow['html'];

								$search  = array("[[custname]]","[[staffname]]");
								$replace = array($custname,$_SESSION['username']);
								$body    = str_replace($search,$replace,$body);

								print "<p align=\"center\"><b>To:</b> $aemails</p>\n";

								print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
								if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
								print "</p>\n";

								if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
								print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

								print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"$action\" /></p>\n";
								print "</form>\n";

							} else {
								$resultMessage .= "Email template $mailout not found<br />" . $dbcnx->error;
							}
						}
					} else {
						$resultMessage .= "No Outstanding Transactions Found!<br />";
					}
				} else {
					$resultMessage .= "Customer Not Found!<br />";
				}
			} else {
				$resultMessage .= "Page Called Without Custref!<br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "Could Not Connect To Database!<br />" . $dbcnx->connect_error . "<br />";
		}
	} else {
		$resultMessage .= "Access Denied for {$_SESSION['username']}<br />";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	
	print "<p align=\"center\">";
	print "<a href=\"ad-list.php?$qString\">Debtors</a>";
	print "&nbsp;&nbsp;|&nbsp;&nbsp;";
	print "<a href=\"sl-ledger.php?id=$id\">Ledger</a>";
	print "</p>\n";
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

?>
