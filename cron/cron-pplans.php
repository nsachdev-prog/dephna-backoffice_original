#!/usr/bin/php

<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	$cronjob = 1;
	chdir(__DIR__);
	include('../public_html/backoffice/settings.php');
	require "../PHPMailer/src/SMTP.php";
	require "../PHPMailer/src/PHPMailer.php";
	require "../PHPMailer/src/Exception.php";
	// open database
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$today = date("Y-m-d");
		$wa_messages = array();

		$xmasday   = date("Y-m-d",strtotime("25 December"));
		$boxingday = date("Y-m-d",strtotime("26 December"));

		if ($today == $xmasday OR $today == $boxingday) {
			$resultMessage .= "It's Christmas Day or Boxing Day<br>Payment plans not checked<br>Will try again next working day.<br>\r\n";
		} else {
			// look for payment plans that have fallen behind
			$sql = "SELECT
						SUM(CASE WHEN BINARY l.txtype = 'DR' THEN l.amount END) as debits,
						SUM(CASE WHEN BINARY l.txtype = 'CR' THEN l.amount END) as credits,
						h.*, c.compname, c.username, c.tim
					FROM pplans_lines AS l
					LEFT JOIN pplans_headers AS h ON h.ID = l.ppid
					LEFT JOIN users AS c ON c.custref = h.custref
					WHERE l.duedate <= '$today'
					AND h.status = '1'
					GROUP BY l.ppid
					ORDER BY c.compname
			";
			if ($ppresult = $dbcnx->query($sql) AND $ppresult->num_rows) {

				// get email template
				$sql = "SELECT * FROM mailouts WHERE mref='payplanrestrict'";
				if ($tresult = $dbcnx->query($sql) AND $tresult->num_rows) {
					$trow = $tresult->fetch_assoc();
					$subject = $trow['subject'];
					$body    = $trow['html'];
				}

				$from     = "accounts@dephna.com";
				$fromname = "Dephna Accounts";

				while ($pprow = $ppresult->fetch_assoc()) {
					$ppid     = $pprow['ID'];
					$debits   = $pprow['debits'];
					$credits  = $pprow['credits'];
					$compname = $pprow['compname'];
					$custname = $pprow['username'];
					$custref  = $pprow['custref'];
					$aemails  = $pprow['aemails'];
					$tim      = $pprow['tim'];

					if (!$credits) { $credits = 0; }

					if (($credits+1) < $debits) { // allow for £1 underpayment

						$resultMessage .= "<br><b>$compname</b><br>\r\n";
						$resultMessage .= "Amount expected to date: $debits<br>\r\n";
						$resultMessage .= "Amount received to date: $credits<br>\r\n";

						if ($tim == "Yes") {

							$resultMessage .= "Customer Already Restricted<br>\r\n";

						} else {

							// check account balance to make sure they haven't paid without it being added to the plan
							$sql = "SELECT SUM(outstanding) as accountBalance FROM inv_headers WHERE custref = '$custref'";
							$accountBalance = 0;
							if (($abresult = $dbcnx->query($sql)) AND ($abresult->num_rows)) {
								$abrow = $abresult->fetch_assoc();
								$accountBalance = $abrow['accountBalance'];
							}
							if ($accountBalance < 1) {
								$resultMessage .= "<b>Account balance is zero</b>, Payment plan must be complete, skipping restriction<br>\r\n";
								continue;
							}

							// otherwise, try to restrict access
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
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
										$uresult = curl_exec($ch);
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
										if ($uresult !== FALSE) {
											if ($uresult = json_decode($uresult, TRUE)) {
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
															$resultMessage .= "$sitename - $firstName $surname - Restricted<br>\r\n";
														} else {
															$resultMessage .= "Error: $sitename - $firstName $surname Code $httpcode<br>\r\n";
														}
													} else {
														$resultMessage .= "Error: $sitename - $firstName $surname Code $httpcode<br>\r\n";
													}
												}
												$wa_messages[] = "PPlan Default\n$compname\nRestricted";
											}
										} else {
											$resultMessage .= "No response from paxton $sitename Code $httpcode<br>Result: $uresult<br>\r\n";
										}

									} else {
										$resultMessage .= "Could not get paxton access token for $sitename<br>\r\n";
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
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
									curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
									$i2result = curl_exec($ch);
									$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

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
											curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
											curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
											$i3result = curl_exec($ch);
											$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					
											if ($httpcode == 200) {
												$resultMessage .= "Internet $action VLAN $vlan Done<br>\r\n";
											} elseif ($httpcode == 400) {
													$resultMessage .= "Internet $action FAILED code 400<br>\r\n";
											} elseif ($httpcode == 401) {
												$resultMessage .= "Internet $action FAILED Unauthorised<br>\r\n";
											} else {
												$resultMessage .= "Internet $action FAILED code $httpcode<br>\r\n";
											}

										}

									} else {
										$resultMessage .= "VLAN NOT FOUND for DP$custref<br>\r\n";
									}

								} else {
									$resultMessage .= "Could NOT get ice access token<br>\r\n";
								}

							} else {
								$resultMessage .= "Customer does not have internet<br>\r\n";
							}

							// update user
							$sql = "UPDATE users SET tim = 'Yes' WHERE custref = '$custref'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Restriction Done<br>\r\n";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'Robot','Access','DP$custref Restriction','$page','$now')");
							} else {
								$resultMessage .= "Could NOT update database<br>" . $dbcnx->error . "<br>\r\n";
							}

							// get current plan
							$sql = "SELECT * FROM pplans_lines WHERE ppid = '$ppid' ORDER BY duedate";
							if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
								$plan = "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\r\n";
								$plan .= "<tr>\r\n";
								$plan .= "<td><b>Date</b></td>\r\n";
								$plan .= "<td align=\"right\"><b>Due</b></td>\r\n";
								$plan .= "<td align=\"right\"><b>Received</b></td>\r\n";
								$plan .= "</tr>\r\n";
								$totdr = 0;
								$totcr = 0;
								while ($lrow = $lresult->fetch_assoc()) {
									$duedate = $lrow['duedate'];
									$amount  = $lrow['amount'];
									$txtype  = $lrow['txtype'];
									list($y,$m,$d) = explode("-",$duedate);
									$plan .= "<tr>\r\n";
									$plan .= "<td>$d-$m-$y</td>\r\n";
									if ($txtype == "DR") {
										$plan .= "<td align=\"right\">$amount</td>\n";
										$plan .= "<td align=\"right\">&nbsp;</td>\n";
										$totdr += $amount;
									} else {
										$plan .= "<td align=\"right\">&nbsp;</td>\n";
										$plan .= "<td align=\"right\">$amount</td>\n";
										$totcr += $amount;
									}
									$plan .= "</tr>\r\n";
								}
								$plan .= "<tr>\n";
								$plan .= "	<td><b>Totals</b></td>\n";
								$plan .= "	<td align=\"right\"><b>". number_format($totdr,2) ."</b></td>\n";
								$plan .= "	<td align=\"right\"><b>". number_format($totcr,2) ."</b></td>\n";
								$plan .= "</tr>\n";
								$plan .= "</table>";
							}

							// send email
							$search   = array("[[custname]]","[[debits]]","[[credits]]","[[plan]]");
							$replace  = array($custname,$debits,$credits,$plan);
							$thisbody = str_replace($search,$replace,$body);

							// email message
							$message  = file_get_contents('../public_html/backoffice/email-header.html') . "\r\n";
							$message .= "<p>$thisbody</p>\n";
							$message .= file_get_contents('../public_html/backoffice/email-footer.html') . "\r\n";

							// send email(s)
							$emails = explode(",",$aemails);
							$now = time();
							
							$mail = new PHPMailer(true);

							try {
								include('SMTP.php');

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
								$resultMessage .= "Email Sent to $aemails<br>\r\n";
								// increase defaults
								$sql = "UPDATE pplans_headers SET defaults = defaults + 1 WHERE ID = '$ppid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Plan Defaults Increased<br>\r\n";
								} else {
									$resultMessage .= "Could NOT Increase Plan Defaults<br>\r\n";
								}

								$aemails = str_replace(",","<br>",$aemails);
								$thisbody = addslashes($thisbody);
								$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','Cron','Default Emailed To:<br>$aemails','$thisbody')");

							} catch (Exception $e) {
								$thisbody = addslashes($thisbody);
								$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','Cron','One or more addresses FAILED<br>Mailer Error: {$mail->ErrorInfo}','$thisbody')");
								$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
							}

							// add to ad_notes
							$mydate = date("Y-m-d");
							$sql = "INSERT INTO `ad_notes` VALUES (
								null,
								'$mydate',
								'$custref',
								'Payment Plan Default',
								'note',
								'0',
								'',
								'0',
								'$now',
								'Cron'
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Note Added<br>\r\n";
							} else {
								$resultMessage .= "Could NOT add note<br>\r\n";
							};

						}

					} else {
						$resultMessage .= "<br><b>$compname</b><br>\r\n";
						$resultMessage .= "Amount expected to date: $debits<br>\r\n";
						$resultMessage .= "Amount received to date: $credits<br>\r\n";
						$resultMessage .= "Okay<br>\r\n";
					}

				}

			} else {
				$resultMessage .= "Nothing Found<br>\r\n";
			}
		}

		$dbcnx->close();
	} else {
		$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
	}

	if ($wa_messages) {
		foreach ($wa_messages AS $wa_message) {
			$postData = array(
				'group_admin' => $WA_GROUP_ADMIN,
				'group_name' => $WA_GROUP_NAME,
				'message' => $wa_message
			);
			$headers = array(
				'Content-Type: application/json',
				'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
				'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
			);
			$url = $WA_URL . $WA_INSTANCE_ID;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
			$response = curl_exec($ch);
			// echo "Response: ".$response;
			curl_close($ch);
		}
	}

	$subject = "Payment Plan Defaults";
	$emails = array(
		"rajive@dephna.com",
		"accounts@dephna.com"
	);
	$from = "robot@dephna.com";
	
	$mail = new PHPMailer(true);

	try {
		include('SMTP.php');

		$mail->setFrom($from);
		foreach ($emails as $to) {
			$mail->addAddress($to);
		}
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $resultMessage;

		$mail->send();

	} catch (Exception $e) {
		echo "Email failed<br>&nbsp;&nbsp; Mailer Error: {$mail->ErrorInfo}";
	}

?>
