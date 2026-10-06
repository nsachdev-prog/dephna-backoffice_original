<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	include('settings.php');
	include('session.php');
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
				   <h2 class="page-header__subtitle">Users &amp; Key Fobs</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$custid  = $_SESSION['user'];
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid  = $_REQUEST['siteid'];
			$userid  = $_REQUEST['userid'];
			$expire  = $_POST['expire'];
			$passdis = $_POST['passdis'];

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			print "<h4 align=\"center\">Expire User</h4>\n";

			if ($siteid AND $userid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];

					include_once('../backoffice/PaxGetAccessToken.php');

					if ($access_token) {

						// get user
						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
						$cresult = curl_exec($ch);
						if ($cresult !== FALSE) {
							if ($cresult = json_decode($cresult, TRUE)) {                             
								$firstName  = $cresult['firstName'];
								$middleName = $cresult['middleName']; 
								$lastName   = $cresult['lastName'];
								$usernm = $firstName; 
								if ($middleName) { $usernm .= " $middleName"; }
								if ($lastName) { $usernm .= " $lastName"; }
								print "<p align=\"center\"><b>$usernm at $sitename</b></p>\n";
							} else {
								$resultMessage .= "Could not get user details<br>";
							}
						} else {
							$resultMessage .= "Could not get user details<br>";
						}

						if ($expire AND $passdis) {

							if ($passdis == $_SESSION['pwd']) {

								$expiredate = date("Y-m-d",strtotime("-1 Day"));

								$headers = array(
									'Authorization: bearer '.$access_token,
									'Accept: application/json'
								);
								$postdata = http_build_query(
									array(
										'id'         => $userid,
										'expiryDate' => $expiredate
									)
								);
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
								curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($ch, CURLINFO_HEADER_OUT, true);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
								$u2result = curl_exec($ch);
								$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
								if ($u2result !== FALSE) {
									if ($httpcode == 200) {
										$resultMessage .= "<b>User Expired</b><br>$usernm can no longer access $sitename";
										$now = time();
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Expired','$usernm at $sitename','Client','$now')");
										// email client
										$sql = "SELECT `email`, `aemails`, `compname` FROM `users` WHERE `custref` = '$custref'";
										if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
											$row = $result->fetch_assoc();
											$aemails  = $row['aemails'];
											$email    = $row['email'];
											$compname = $row['compname'];
											if (!$aemails) { $aemails = $email; }

											require "../../PHPMailer/src/SMTP.php";
											require "../../PHPMailer/src/PHPMailer.php";
											require "../../PHPMailer/src/Exception.php";

											$from = "accounts@dephna.com";
											$fromname = "Dephna Support";
											$subject = "User Expired";
			
											// email message
											include("../backoffice/email-header.php");
											$message .= "<p><b>$compname</b></p>\n";
											$message .= "<p><b>$usernm - $sitename</b></p>\n";
											$message .= "<p>You have expired this user.<br>They will no longer have access to the building.</p>\n";
											$message .= "<p>If this was not you, please contact support@dephna.com</p>\n";
											$message .= "<p>Kind Regards<br>Dephna Support</p>\n";
											include("../backoffice/email-footer.php");

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
												$resultMessage .= "Email Sent to $to<br>";

											} catch (Exception $e) {
												$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
											}

											
										} else {
											$resultMessage .= "Customer Not Found<br>" . $dbcnx->error . "<br>";
										}

									} else {
										$resultMessage .= "Error2: $firstName $surname Code $httpcode<br>";
									}
								} else {
									$resultMessage .= "<b>Error</b><br>$usernm Could NOT be expired.<br>Error Code $httpcode<br>";
								}

							} else {
								$resultMessage .= "<b>Error</b><br>Password Incorrect<br>";
							}

						} else {

							print "<p align=\"center\">If a user is expired they will no longer have access to the building.</p>\n";
							print "<form name=\"addform\" method=\"post\" action=\"keyfobs-users-expire.php\">\n";
							print "<input type=\"hidden\" name=\"expire\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
							
							print "<p align=\"center\"><b>Enter Your Password</b> ";
							print "<input name=\"passdis\" type=\"password\" maxlength=\"30\" />";
							print "</p>\n";

							print "<p align=\"center\" style=\"color:red\">ARE YOU SURE?</p>\n";
							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Yes Expire\" /></p>\n";
							print "</form>\n";

						}

					} else {
						$resultMessage .= "Could not get paxton access token for $sitename.<br>";
					}

				} else {
					$resultMessage .= "Could not get site<br>";
				}

			} else {
				$resultMessage .= "Page called without ids<br>";
			}

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage)	{
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"keyfobs.php\">Return</a></p>";
		print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";

	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	
	/* print "<p align=\"left\"><b>Raw Data</b></p>";
	print "<div style=\"text-align:left\"><pre>";
	print_r($_REQUEST);
	print "</pre></div>"; */
	
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>