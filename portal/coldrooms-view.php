<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
</style>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">Cold Rooms</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref  = $_SESSION['custref'];
		$custid   = $_SESSION['user'];
		$compname = $_SESSION['compname'];
		$siteid   = $_REQUEST['siteid'];
		$unitid   = $_REQUEST['unitid'];
		$action   = $_REQUEST['action'];

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			if ($siteid AND $unitid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];

					// get unit
					$sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unittype = $row['unittype'];
						$unitname = $row['title'];
						list($pre,$unitname) = explode(" ",$unitname);

						print "<h3>$sitename - $unitname</h3>\n";

						// get device id from history
						$sql = "SELECT deviceid, xsub
							FROM xweb_history1
							WHERE siteid = '$siteid' AND unitid = '$unitid'
							ORDER BY xdatetime DESC
							LIMIT 0,1
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$deviceid = $row['deviceid'];
							$xsub     = $row['xsub'];

							include_once('../backoffice/XWebGetAccessToken.php');

							// Get sensor ids
							$sql = "SELECT * FROM XWebAuth WHERE siteid = '$siteid' AND xsub = '$xsub'";
							if ($x1result = $dbcnx->query($sql) AND $x1result->num_rows) {
								$xrow = $x1result->fetch_assoc();
							
								if ($access_token) {

									if ($action) {

										$emessage = "";
										$passdis = $_POST['passdis'];

										if ($passdis == $_SESSION['pwd']) {

											if ($action == "setpoint") {

												$setpoint = $_POST['setpoint'];

												if (is_numeric($setpoint)) {

													if ($setpoint >= -18 AND $setpoint <= 3) {

														// setup defrost parameters for chiller or freezer

														if ($setpoint < 0) { // Freezer...
															$Type = "Freezer";

															$dPo = "no";     // Defrost at power on 	               
															$dAF = "02:00";  // Defrost delay after fast freezing 	   
															$tdF = "rE";     // Defrost type 	                       
															$dFd = "it";     // Display during defrost 	               
															$EdF = "in";     // End of defrost : time or temperature   
															$SdF = "0";      // Set point for Smart Defrost 	       
															$Fdt = "3";      // Draining time 	                       
															$dSd = "0";      // Defrost delay 	                       
															$dtE = "15";     // Defrost end temperature 	           
															$idF = "4";      // Defrost intervall 	                   
															$dAd = "30";     // Display delay after defrost 	       
															$MdF = "45";     // Maximum defrost duration 1st evaporator

														} else {             // Chiller...
															$Type = "Chiller";

															$dPo = "no";     // Defrost at power on 	               
															$dAF = "02:00";  // Defrost delay after fast freezing 	   
															$tdF = "rE";     // Defrost type 	                       
															$dFd = "it";     // Display during defrost 	               
															$EdF = "in";     // End of defrost : time or temperature   
															$SdF = "0";      // Set point for Smart Defrost 	       
															$Fdt = "3";      // Draining time 	                       
															$dSd = "0";      // Defrost delay 	                       
															$dtE = "15";     // Defrost end temperature 	           
															$idF = "3";      // Defrost intervall 	                   
															$dAd = "30";     // Display delay after defrost 	       
															$MdF = "35";     // Maximum defrost duration 1st evaporator

														}

														$parameters = array(
															array('section' => 1,'label' => 'SEt','value' => $setpoint),
															array('section' => 1,'label' => 'dPo','value' => $dPo),
															array('section' => 1,'label' => 'dAF','value' => $dAF),
															array('section' => 1,'label' => 'tdF','value' => $tdF),
															array('section' => 1,'label' => 'dFd','value' => $dFd),
															array('section' => 1,'label' => 'EdF','value' => $EdF),
															array('section' => 1,'label' => 'SdF','value' => $SdF),
															array('section' => 1,'label' => 'Fdt','value' => $Fdt),
															array('section' => 1,'label' => 'dSd','value' => $dSd),
															array('section' => 1,'label' => 'dtE','value' => $dtE),
															array('section' => 1,'label' => 'idF','value' => $idF),
															array('section' => 1,'label' => 'dAd','value' => $dAd),
															array('section' => 1,'label' => 'MdF','value' => $MdF)
														);
														
														$targets = array($deviceid);
					
														$postdata = http_build_query(
															array(
																'auth'       => $access_token,
																'action'     => 'write_parameters'
															)
														);
					
														$postdata .= "&targets="    . json_encode($targets);
														$postdata .= "&parameters=" . json_encode($parameters);
					
														$ch = curl_init();
														curl_setopt($ch, CURLOPT_POST, 1);
														curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
														curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
														curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
														curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
														curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
														$result = curl_exec($ch);
														curl_close($ch);
					
														if ($result !== FALSE) {
															$result = json_decode($result, TRUE);
															if ($result['status'] == "ok") {
																$cmdresult = "Okay - Setpoint Changed to $setpoint";
																$subject  = "$pre $unitname Setpoint Changed $compname";
																$emessage = "You have changed the setpoint in this cold room to $setpoint.\n";
																$datetime = date("Y-m-d H:i");
																$now = time();

																$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$pre $unitname Setpoint Changed to $setpoint','Client','$now')");

																// log it
																$sql = "INSERT INTO `xweb_actions` VALUES (
																	null,
																	'$datetime',
																	'$siteid',
																	'$unitid',
																	'$unitname',
																	'$deviceid',
																	'$custref',
																	'$compname',
																	'40',
																	'Client',
																	'0',
																	'',
																	'Set Point Changed to $setpoint',
																	'',
																	'0',
																	'0',
																	'',
																	'No'
																)";
																if ($dbcnx->query($sql)) {
																	$aid = $dbcnx->insert_id;
																	$sql = "INSERT INTO `xweb_actions_notes` VALUES (
																		'',
																		'$aid',
																		'$siteid',
																		'$unitid',
																		'$custref',
																		'$datetime',
																		'Changed Setpoint to $setpoint',
																		'Client Changed Setpoint to $setpoint<br>Parameters Updated with $Type Settings',
																		'$now',
																		'Client'
																	)";
																	$dbcnx->query($sql);
																}
																$setpoint = "";
															}
															elseif ($result['status'] == "error") {
																$cmdresult = $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "<br />Please contact support";
															}
												
														} else {
															$cmdresult = "No Response from XWeb<br />Please contact support.";
														}

													} else {
														$ersetpoint = "Must be between -18 and 3";
													}
				
												} else {
													$ersetpoint = "Must be numeric";
												}
				
											}

											elseif ($action == "power") {

												$cmdid = $_POST['cmdid'];

												if ($cmdid) {
													// send command
													$command = $_POST['command'];
													if ($deviceid) {
														$postdata = http_build_query(
															array(
																'auth'   => $access_token,
																'action' => 'send_command',
																'device'  => "$deviceid",
																'command' => "$cmdid"
															)
														);
								
														$ch = curl_init();
														curl_setopt($ch, CURLOPT_POST, 1);
														curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
														curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
														curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
														curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
														curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
														$result = curl_exec($ch);
														curl_close($ch);
								
														if ($result !== FALSE) {
															$result = json_decode($result, TRUE);
															if ($result['status'] == "ok") {
																$cmdresult = "Okay - Power Changed to $command";
																$subject  = "$pre $unitname Power Turned $command $compname";
																$emessage = "You have turned the power $command in this cold room.\n";
																if ($command == "ON") {$newao = 0; } else { $newao = 1; }
																// if they they turn power off, set approved off
																$sql = "UPDATE `units` SET `approvedoff` = '$newao' WHERE `ID` = '$unitid'";
																$dbcnx->query($sql);
																$datetime = date("Y-m-d H:i");
																$now = time();

																$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$pre $unitname Power Turned $command','Client','$now')");

																// log it
																$sql = "INSERT INTO `xweb_actions` VALUES (
																	null,
																	'$datetime',
																	'$siteid',
																	'$unitid',
																	'$unitname',
																	'$deviceid',
																	'$custref',
																	'$compname',
																	'30',
																	'Client',
																	'0',
																	'',
																	'Power $command',
																	'',
																	'0',
																	'0',
																	'',
																	'No'
																)";
																if ($dbcnx->query($sql)) {
																	$aid = $dbcnx->insert_id;
																	$sql = "INSERT INTO `xweb_actions_notes` VALUES (
																		'',
																		'$aid',
																		'$siteid',
																		'$unitid',
																		'$custref',
																		'$datetime',
																		'Changed Power to $command',
																		'Client Changed Power to $command',
																		'$now',
																		'Client'
																	)";
																	$dbcnx->query($sql);
																}
															} elseif ($result['status'] == "error") {
																$cmdresult = $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "<br />Please contact support";
										
															} else {
																$cmdresult = "Failed<br />Please contact support";
															}
														} else {
															$cmdresult = "No Response From Xweb<br />Please contact support";
														}
						
														/* print "<pre>";
														print_r($result);
														print "</pre>"; */
								
														sleep(5);
													}
												}

											}

										} else {
											$resultMessage .= "Password Incorrect<br>";
										}

										if ($emessage) {
											// email client
											$sql = "SELECT `email`, `aemails` FROM `users` WHERE `custref` = '$custref'";
											if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
												$row = $result->fetch_assoc();
												$aemails  = $row['aemails'];
												$email    = $row['email'];
												if (!$aemails) { $aemails = $email; }

												require "../../PHPMailer/src/SMTP.php";
												require "../../PHPMailer/src/PHPMailer.php";
												require "../../PHPMailer/src/Exception.php";

												$from = "support@dephna.com";
												$fromname = "Dephna Support";
				
												// email message
												$message  = file_get_contents('../backoffice/email-header.html') . "\r\n";
												$message .= "<p><b>$compname</b></p>\n";
												$message .= "<p><b>$sitename - $unitname</b></p>\n";
												$message .= "<p>$emessage</p>\n";
												$message .= "<p>If this was not you please contact support@dephna.com</p>\n";
												$message .= "<p>Kind Regards<br />Dephna Support</p>\n";
												$message .= file_get_contents('../backoffice/email-footer.html') . "\r\n";

												$emails = explode(",",$aemails);

												$mail = new PHPMailer(true);

												try {
													
													include('../../cron/SMTP.php');

													$mail->setFrom($from, $fromname);
													foreach ($emails AS $to) {
														$mail->addAddress($to);
													}
													$mail->addReplyTo($from, $fromname);
													// $mail->addBCC($from, $fromname);
													$mail->isHTML(true);
													$mail->Subject = $subject;
													$mail->Body    = $message;

													$mail->send();
													$resultMessage .= "Email Sent to $to<br />";

												} catch (Exception $e) {
													$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
												}

												
											} else {
												$resultMessage .= "Customer Not Found<br />" . $dbcnx->error . "<br />";
											}
										}
									}

									print "<div id=\"LoadingOnClick\">\n";

									if ($cmdresult) {
										print "<p align=\"center\">$cmdresult<br />It may take a few minutes for the change to show <a href=\"coldrooms-view.php?siteid=$siteid&amp;unitid=$unitid\" class=\"displayloader\">Refresh</a></p>\n";
									}

									$postdata = http_build_query(
										array(
											'auth'   => $access_token,
											'action' => 'runtime',
											'query'  => json_encode(array("$deviceid" => null))
										)
									);
						
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_POST, 1);
									curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
									curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
									curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
									$xresult = curl_exec($ch);
									curl_close($ch);

									if ($xresult !== FALSE) {
										$xresult = json_decode($xresult, TRUE);
										if ($xresult['status'] == "ok") {

											$commands = $xresult['runtime']['devices'][0]['commands'];
											$points = $xresult['runtime']['devices'][0]['points'];
											$data = array();
											foreach($points AS $point) {
												$data[$point['id']] = $point['value'];
											}

											print "<p>&nbsp;</p>\n";
											print "<form name=\"pwForm\" id=\"pwForm\" method=\"post\" action=\"coldrooms-view.php\">\n";
											print "<input type=\"hidden\" name=\"action\" value=\"power\" />\n";
											print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
											print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";

											print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
											print "<tr>\n";
											if ($data[$xrow['onoff']] == 1) {
												print "<h3>Power is ON</h3>\n";
												print "<td><input type=\"hidden\" name=\"cmdid\" value=\"{$commands[0]['id']}\" />";
												print "<input type=\"hidden\" name=\"command\" value=\"OFF\" />";
												print "<b>Enter Your Password</b> <input name=\"passdis\" type=\"password\" maxlength=\"30\" />\n";
												print "<input name=\"Submit\" type=\"submit\" value=\"Switch OFF\" /></td>\n";
											} else {
												print "<h3>Power is OFF</h3>\n";
												print "<td><input type=\"hidden\" name=\"cmdid\" value=\"{$commands[1]['id']}\" />";
												print "<input type=\"hidden\" name=\"command\" value=\"ON\" />";
												print "<b>Enter Your Password</b> <input name=\"passdis\" type=\"password\" maxlength=\"30\" />\n";
												print "<input name=\"Submit\" type=\"submit\" value=\"Switch ON\" /></td>\n";
											}
											print "</tr>\n";

											print "</table>\n";
											print "</form>\n";

											if ($data[$xrow['onoff']] == 1) {
												print "<p>&nbsp;</p>\n";
												print "<form name=\"spForm\" id=\"pwForm\" method=\"post\" action=\"coldrooms-view.php\">\n";
												print "<input type=\"hidden\" name=\"action\" value=\"setpoint\" />\n";
												print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
												print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";

												print "<h3>Set Point is {$data[$xrow['setpoint']]}&deg;</h3>\n";

												print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
												print "<tr>\n";
												print "<td><b>Change To</b> <input name=\"setpoint\" type=\"text\" style=\"width:60px\" maxlength=\"3\" value=\"$setpoint\" />";
												if ($ersetpoint) { print "<span style=\"color:red\">$ersetpoint</span><br>"; }
												print "<b>Enter Your Password</b> <input name=\"passdis\" type=\"password\" maxlength=\"30\" />\n";
												print "<input name=\"Submit\" type=\"submit\" value=\"Change Setpoint\" /><br>";
												print "Enter temperature between -18 and 3</td>\n";
												print "</tr>\n";

												print "</table>\n";
												print "</form>\n";
											}

										} elseif ($xresult['status'] == "error") {
											$resultMessage .= $xresult['status'] . "<br />" . $xresult['error'] . "<br />" . $xresult['message'] . "<br />";
										} else {
											$resultMessage .= "Unknown Xweb Error<br />Please contact support<br />";
										}

									} else {
										$resultMessage .= "Error: <br />Nothing was returned from XWeb.<br />Please contact support<br />";
									}

									print "</div>"; // end loading div

								} else {
									$resultMessage .= "Could Not get Xweb access token<br />" . $AuthResultMessage . "<br />Please contact support<br />";
								}

							} else {
								$resultMessage .= "Could not get sensor ids<br />" . $dbcnx->error . "<br />Please contact support<br />";
							}

						} else {
							$resultMessage .= "Device ID Not Found<br />" . $dbcnx->error . "<br />Please contact support<br />";
						}
						
					} else {
						$resultMessage .= "Unit Not Found<br />" . $dbcnx->error . "<br />Please contact support<br />";
					}

				} else {
					$resultMessage .= "Site Not Found<br />" . $dbcnx->error . "<br />Please contact support<br />";
				}

			} else {
				$resultMessage .= "Page called without ids<br />Please contact support<br />";
			}

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"coldrooms-list.php\">Return</a></p>";
	print "<p align=\"center\"><a href=\"index.php\">MAIN MENU</a></p>";

	/* print "<p><b>Raw Data From XWeb</b></p>";
	print "<pre>";
	print_r($xresult);
	print "</pre>"; */

?>
			</div>
	</div>

<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>

<?php			
	include('elements/footer.php');
?>
