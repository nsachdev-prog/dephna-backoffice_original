#!/usr/bin/php

<?php
// cron-debtors.php - Written by David Sherwood - www.davidsherwood.co.uk
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$cronjob = 1;
chdir(__DIR__);
include_once('../public_html/backoffice/settings.php');
include_once('../TMRestClient/TMRestClient.php');
require_once('../tcpdf6.8/tcpdf.php');
require_once "../PHPMailer/src/SMTP.php";
require_once "../PHPMailer/src/PHPMailer.php";
require_once "../PHPMailer/src/Exception.php";

$datetime = date("d/m/Y H:i");

// $reportEmail = "david@davidsherwood.co.uk";
$reportEmails = array(
	"rajive@dephna.com",
	"accounts@dephna.com"
);

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	// start email
	$from = "accounts@dephna.com";
	$fromname = "Dephna Accounts";

	// setup type names
	$types = array(
		"I"=>"Invoice",
		"C"=>"Credit Note",
		"P"=>"Returned Payment",
		"p"=>"Returned Payment",
		"R"=>"Payment Received",
		"r"=>"Payment Received",
		"X"=>"Unknown Transaction"
	);

	// setup slex type names
	$slexTypes = array(
		"RC" => "Returned Cheque",
		"BD" => "Bounced DD",
		"DR" => "Deposit Repayment"
	);

	// get levels (warning days) from debtors settings
	$sql = "SELECT * FROM ad_settings WHERE `ID`='1'";
	$result = $dbcnx->query($sql);
	if ($result->num_rows) {
		$row = $result->fetch_assoc();
		$warnings = $row['warnings'];
		$level1   = $row['level1'];
		$level2   = $row['level2'];
		$level3   = $row['level3'];
		$level4   = $row['level4'];

		if ($warnings == "On") {

			$thismonth = date("Y-m");
			$depthismonth = date("Ym");

			/* // if levels are not weekdays this month change them to the next monday
			if (date("N", strtotime("$thismonth-$level1")) > 5) {
				$level1 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level1")));
				$resultMessage .= "Level 1 is Weekend, Changed to Monday<br>";
			};
			if (date("N", strtotime("$thismonth-$level2")) > 5) {
				$level2 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level2")));
				$resultMessage .= "Level 2 is Weekend, Changed to Monday<br>";
			};
			if (date("N", strtotime("$thismonth-$level3")) > 5) {
				$level3 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level3")));
				$resultMessage .= "Level 3 is Weekend, Changed to Monday<br>";
			};
			if (date("N", strtotime("$thismonth-$level4")) > 5) {
				$level4 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level4")));
				$resultMessage .= "Level 4 is Weekend, Changed to Monday<br>";
			};
		
			// get bank holidays uk/england and wales
			$bankholidays = file_get_contents("https://www.gov.uk/bank-holidays.json");
			$bankholidays = json_decode($bankholidays, true);
			$bankholidays = $bankholidays['england-and-wales']['events'];
		
			// are any of the levels bank holidays?
			foreach ($bankholidays as $bankholiday) {
				if ($bankholiday['date'] == "$thismonth-$level1") {
					$level1 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level1")));
					$resultMessage .= "Level 1 is Bank Holiday, Changed to Tuesday<br>";
				};
				if ($bankholiday['date'] == "$thismonth-$level2") {
					$level2 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level2")));
					$resultMessage .= "Level 2 is Bank Holiday, Changed to Tuesday<br>";
				};
				if ($bankholiday['date'] == "$thismonth-$level3") {
					$level3 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level3")));
					$resultMessage .= "Level 3 is Bank Holiday, Changed to Tuesday<br>";
				};
				if ($bankholiday['date'] == "$thismonth-$level4") {
					$level4 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level4")));
					$resultMessage .= "Level 4 is Bank Holiday, Changed to Tuesday<br>";
				};
			}; */

			$thisday = date("d");

			// see if this is a warnings day
			if (($thisday == $level1) OR ($thisday == $level2) OR ($thisday == $level3) OR ($thisday == $level4)) {

				// setup template name based on warnings level
				if ($thisday == $level1) {
					$template = "DebtorsWarning1"; $thislevel = 1;
					$search = "u.bank != 'Yes' AND u.ad_optout != 'Yes'";
				}
				elseif ($thisday == $level2) {
					$template = "DebtorsWarning2"; $thislevel = 2;
					$search = "u.bank != 'Yes' AND u.ad_optout != 'Yes'";
				}
				elseif ($thisday == $level3) {
					$template = "DebtorsWarning3"; $thislevel = 3;
					$search = "u.ad_optout != 'Yes'";
				}
				elseif ($thisday == $level4) {
					$template = "DebtorsWarning4"; $thislevel = 4;
					$search = "u.ad_optout != 'Yes'";
				}

				$resultMessage .= "<b>Level $thislevel Warning Day Today</b><br>";

				// get the template
				$sql = "SELECT * FROM mailouts WHERE mref = '$template'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row  = $result->fetch_assoc();
					$subject = $row['subject'];
					$body    = $row['html'];
					$body    = nl2br($body);

					// get the debtors
					$sql = "SELECT h.custref, SUM(h.outstanding) AS outstanding,
							u.username, u.compname, u.aemails, u.email, u.addr1, u.addr2, u.addr3, u.addr4, u.tim, u.telephone, u.telephone2
							FROM inv_headers AS h
							LEFT JOIN users AS u ON u.custref = h.custref
							WHERE $search
							AND CONCAT(year,month,day) < '{$depthismonth}02'
							GROUP BY h.custref
							HAVING outstanding > '0'
					";
					if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {

						// loop through each debtor
						while ($drow = $dresult->fetch_assoc()) {
							$custref  = $drow['custref'];
							$compname = $drow['compname'];
							$custname = $drow['username'];
							$aemails  = $drow['aemails'];
							$email    = $drow['email'];
							if (!$aemails) { $aemails = $email; }
							$addr1 = $drow['addr1'];
							$addr2 = $drow['addr2'];
							$addr3 = $drow['addr3'];
							$addr4 = $drow['addr4'];
							$tim   = $drow['tim'];
							$telephone  = $drow['telephone'];
							$telephone2 = $drow['telephone2'];
							$balancedue = $drow['outstanding'];

							$SMSmessage = "";

							$resultMessage .= "<br><b>Processing $compname</b><br>";

							if ($tim != "Yes") {

								// check if client has bookings
								$sql = "SELECT ID FROM `bookings` WHERE `custid` = '$custref'";
								if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {

									// check if client has a payment plan
									$sql = "SELECT ID FROM `pplans_headers` WHERE `custref` = '$custref' AND `status` = '1'";
									if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
										$resultMessage .= "Client has an active payment plan<br>\r\n";
									} else {
										// remove future deposit increments from balance due
										$sql = "SELECT SUM(amount) AS depincs
										FROM deposits
										WHERE custref = '$custref'
										AND CONCAT(year,month) > '$depthismonth'
										";
										if ($diresult = $dbcnx->query($sql) AND $diresult->num_rows) {
											$dirow = $diresult->fetch_assoc();
											$depincs = $dirow['depincs'];
											if ($depincs > 0) {
												$balancedue -= $depincs;
												$resultMessage .= "Removed Deposit Increments of $depincs from balance due<br>";
											} else {
												$resultMessage .= "No Deposit Increments found<br>";
											}
										}
										if ($balancedue > 0) {
											$resultMessage .= "Balance Due is $balancedue, proceeding...<br>";
										} else {
											$resultMessage .= "Balance Due less Deposit Increments is $balancedue, no email sent<br>";
											continue;
										}
										// update fields in template
										$search   = array("[[custname]]","[[balancedue]]");
										$replace  = array($custname,$balancedue);
										$thisbody = str_replace($search,$replace,$body);

										if ($thisday == $level4) {
											// try to restrict access
											// get paxton sites
											$newexpires = date("Y-m-d",strtotime("-1 Day"));
											$icestatus  = "suspend";
											$sql = "SELECT p.siteid, s.title AS sitename
													FROM PaxAuth AS p
													LEFT JOIN sites AS s ON s.ID = p.siteid
											";
											if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
												while ($srow = $sresult->fetch_assoc()) {
													$siteid   = $srow['siteid'];
													$sitename = $srow['sitename'];
													// check if user has bookings at this site
													$sql = "SELECT ID FROM `bookings` WHERE `custid` = '$custref' AND `siteid` = '$siteid'";
													if (($bresult = $dbcnx->query($sql)) AND ($bresult->num_rows)) {
														// get paxton access token
														$access_token = "";
														include('../public_html/backoffice/PaxGetAccessToken.php');
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
															$uresult = curl_exec($ch);
															$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
															curl_close($ch);
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
																		$u2result = curl_exec($ch);
																		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
																		curl_close($ch);
																		if ($u2result !== FALSE) {
																			if ($httpcode == 200) {
																				$resultMessage .= "$sitename - $firstName $surname - Restricted<br>\r\n";
																				$restricted++;
																			} else {
																				$resultMessage .= "Error: $sitename - $firstName $surname Code $httpcode<br>\r\n";
																			}
																		} else {
																			$resultMessage .= "Error: $sitename - $firstName $surname Code $httpcode<br>\r\n";
																		}
																	}
																	if ($restricted) {
																		$resultMessage .= "Restricted $restricted users at $sitename<br>\r\n";
																	} else {
																		$resultMessage .= "No users found at $sitename<br>\r\n";
																	}
																} else {
																	$resultMessage .= "No Users found at $sitename<br>\r\n";
																}
															} else {
																$resultMessage .= "No response from paxton $sitename Code $httpcode<br>\r\n";
															}

														} else {
															$resultMessage .= "Could not get paxton access token for $sitename<br>\r\n";
														}
													} else {
														$resultMessage .= "No bookings found at $sitename<br>\r\n";
													}
												}
											} else {
												$resultMessage .= "Could not get paxton sites<br>\r\n";
											}

											// do they have internet?
											$sql = "SELECT ID FROM `bookings_an` WHERE `custref` = '$custref' AND `ancid` = '5'";
											if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {

												$resultMessage .= "Customer has internet, trying to Restrict ...<br>\r\n";

												$vlan = 0;
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
													$i2result = curl_exec($ch);
													$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
													curl_close($ch);

													if ($httpcode == 200) {
														if ($i2result = json_decode($i2result, TRUE)) {
															$vlans = array();
															foreach ($i2result AS $id => $item) {
																if ($item['Data']['ID'] == $custref) {
																	$vlans[] = $item['VLAN'];
																	$vlan++;
																}
															}
														} else {
															$resultMessage .= "Json Decode FAILED getting vlan<br>\r\n";
														}
													} elseif ($httpcode == 400) {
														$resultMessage .= "Error 400 Getting VLAN<br>\r\n";
													} elseif ($httpcode == 401) {
														$resultMessage .= "Unauthorised Getting VLAN<br>\r\n";
													} else {
														$resultMessage .= "Unknown error code $httpcode Getting VLAN<br>\r\n";
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
															$i3result = curl_exec($ch);
															$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
															curl_close($ch);
									
															if ($httpcode == 200) {
																$resultMessage .= "Internet Restrict VLAN $vlan Done<br>\r\n";
															} elseif ($httpcode == 400) {
																	$resultMessage .= "Internet Restrict FAILED code 400<br>\r\n";
															} elseif ($httpcode == 401) {
																$resultMessage .= "Internet Restrict FAILED Unauthorised<br>\r\n";
															} else {
																$resultMessage .= "Internet Restrict FAILED code $httpcode<br>\r\n";
															}

														}

													} else {
														$resultMessage .= "NO VLANS FOUND for DP$custref<br>\r\n";
													}

												} else {
													$resultMessage .= "Could NOT get ice access token<br>\r\n";
												}

											} else {
												$resultMessage .= "Customer does not have internet<br>\r\n";
											}

											// update user
											$now = time();
											$sql = "UPDATE users SET tim = 'Yes' WHERE custref = '$custref'";
											if ($dbcnx->query($sql)) {
												$resultMessage .= "Restriction Done<br>\r\n";
												$dbcnx->query("INSERT INTO actions_log VALUES (null,'Robot','Access','DP$custref Restricted','$page','$now')");
											} else {
												$resultMessage .= "Could NOT update database<br>" . $dbcnx->error . "<br>\r\n";
											}
											$thisbody = str_replace("[[AccessRestricted]]","ACCESS TO YOUR SERVICES HAS BEEN RESTRICTED",$thisbody);
											$SMSmessage = "Access to your services have been restricted due to late payment. Please contact our accounts team. Dephna Estates.";
										} else {
											$thisbody = str_replace("[[AccessRestricted]]","",$thisbody);
											if ($thisday == $level3) {
												$SMSmessage = "Your account is now in arrears. Please make a payment to avoid restriction of your services. Dephna Estates.";
											}
										}

										// email message
										$message  = file_get_contents('../public_html/backoffice/email-header.html') . "\r\n";
										$message .= "<p>$thisbody</p>\n";
										$message .= file_get_contents('../public_html/backoffice/email-footer.html') . "\r\n";

										// send email(s)
										$emails = explode(",",$aemails);
										
										$mail = new PHPMailer(true);

										try {
											include('SMTP.php');

											$mail->setFrom($from, $fromname);
											foreach ($emails AS $to) {
												$mail->addAddress($to);
											}
											$mail->addReplyTo($from, $fromname);
											$mail->addBCC($from, $fromname);
											// $mail->addBCC("david@davidsherwood.co.uk", "David Sherwood");
											$mail->isHTML(true);
											$mail->Subject = $subject;
											$mail->Body    = $message;

											// attachment
											$bysmtp = 1;
											// get outstanding invoices
											$sql = "SELECT h.*, e.type AS slextype
													FROM inv_headers AS h
													LEFT JOIN sl_extra AS e ON h.ID = e.hid
													WHERE h.custref = $custref
														AND h.outstanding <> 0
													ORDER BY year,month,day
											";
											if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

												// attach statement
												include("../public_html/backoffice/mailouts-pdf-make-statement.php");
												if ($fileatt) {
													$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype);
												}

												// attach invoices
												while ($row = $result->fetch_assoc()) {
													$id  = $row['ID'];
													$inv = $row['invnumber'];
													$iyear  = $row['year'];
													$imonth = $row['month'];
													$iday   = $row['day'];
													$fileatt = "";
													$fileattname = "";
													$fileatttype = "";
													// get invoice
													include("../public_html/backoffice/mailouts-pdf-make-invoice.php");
													if ($fileatt) {
														$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype);
													} else {
														$resultMessage .= "Could not attach invoice $inv<br>\r\n";
													}
												}
												
											} else {
												$resultMessage .= "No invoices found<br>\r\n";
											}

											$mail->send();

											$resultMessage .= "Email Sent to $aemails<br>\r\n";

										} catch (Exception $e) {
											$resultMessage .= "Mailer Error: {$mail->ErrorInfo}";
										}

										// SMS message
										/* if ($SMSmessage != "") {
											$SMSto = $telephone;
											if ($SMSto == "") {
												$SMSto = $telephone2;
											}
											if ($SMSto) {
												$replace = array(" ", "(", ")", "-", "+");
												$SMSto = str_replace($replace, "", $SMSto);
												// check that it's numeric
												if (!is_numeric($SMSto)) {
													$SMSto = "";
												} else {
													// check that it begins with 07
													if (substr($SMSto, 0, 2) == "07") {
														$SMSto = "447" . substr($SMSto, 2);
													} else {
														$SMSto = "";
													}
												}
											}
											if ($SMSto != "") {
												// send the SMS
												$SMSresultMessage = "Send SMS to $SMSto<br>\n";
												include('../public_html/backoffice/SMS-send.php');
												$resultMessage .= $SMSresultMessage;
											} else {
												$SMSresultMessage .= "Client has no or invalid mobile number on file.<br>\n";
											}
										} */
									}
									$mydate = date("Y-m-d");
									$sql = "INSERT INTO `ad_notes` VALUES (
										null,
										'$mydate',
										'$custref',
										'Warning Level $thislevel Sent to $aemails',
										'note',
										'$thislevel',
										'',
										'0',
										'$now',
										'Cron'
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Note Added<br>\r\n";
									} else {
										$resultMessage .= "Could NOT add note<br>\r\n";
									}
								} else {
									$resultMessage .= "Client has no bookings, must have vacated.<br>\r\n";
								}
							} else {
								$resultMessage .= "Client is alreadey restricted.<br>\r\n";
							}
						}
					} else {
						$resultMessage .= "No debtors found<br>" . $dbcnx->error;
					}
				} else {
					$resultMessage .= "Email template $template not found<br>" . $dbcnx->error;
				}
			} else {
				$resultMessage .= "Nothing scheduled on this day<br />";
			}
		} else {
			$resultMessage .= "Debtor warnings are switched off<br />";
		}

	} else {
		$resultMessage .= "Could not get debtors settings<br />";
	}
	$dbcnx->close();
} else {
	$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error;
}

// Mail the processing report to $reportEmail

$mail = new PHPMailer(true);

try {
	include('SMTP.php');

	$mail->setFrom($from, $fromname);
	foreach ($reportEmails AS $to) {
		$mail->addAddress($to);
	}
	$mail->addReplyTo($from, $fromname);
	$mail->isHTML(true);
	$mail->Subject = "Cron Debtors Report";
	$mail->Body    = $resultMessage;

	$mail->send();

} catch (Exception $e) {
	echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
