<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Escalation";
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
			print "<p align=\"center\" class=\"heading\">DEBTOR ESCALATION</p>\n";
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
				$sql = "SELECT ID, compname, escalation FROM users WHERE custref = '$custref'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$clientid   = $row['ID'];
					$compname   = $row['compname'];
					$escalation = $row['escalation'];

					// restriction toggle
					if ($escalation == "Yes") {
						$action = "Deescalate";
					} else {
						$action = "Escalate";
					}

					print "<p align=\"center\" class=\"larger\">$compname</p>\n";
					print "<p align=\"center\" class=\"larger\">$action</p>\n";

					if ($_POST['posted']) {

						$action = $_POST['action'];

						if ($action == "Escalate") {
							$escalation = "Yes";
							$escstaffid = $_POST['new_escstaffid'];
						} else {
							$escalation = "No";
							$escstaffid = 0;
						}

						// update user
						$sql = "UPDATE users SET escalation = '$escalation', escstaffid = '$escstaffid' WHERE custref = '$custref'";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "$action Done<br />";
							// add to notes
							$mydate = date("Y-m-d");
							$now = time();
							$sql = "INSERT INTO `ad_notes` VALUES (
								null,
								'$mydate',
								'$custref',
								'$action',
								'note',
								'',
								'',
								'',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Access','DP$custref $action','$page','$now')");

								if ($action == "Escalate" AND $escstaffid) {
									// email staff

									$sql = "SELECT email, username FROM staff WHERE ID = '$escstaffid'";
									if ($result = $dbcnx->query($sql) AND $result->num_rows) {
										$row = $result->fetch_assoc();
										$staffemail = $row['email'];
										$staffname  = $row['username'];

										require "../../PHPMailer/src/SMTP.php";
										require "../../PHPMailer/src/PHPMailer.php";
										require "../../PHPMailer/src/Exception.php";
										
										$esubject = "Debt Escalation Assignment";
										$body     = "<p>A client debt has allocated to you by {$_SESSION['username']} for collection.</p>\r\n";
										$body    .= "<p>$compname</p>\r\n";
										$body    .= "<p><a href=\"https://dephna.net/backoffice/sl-ledger.php?id=$clientid\">View Client</a></p>\r\n";
										$from     = "robot@dephna.com";
										$fromname = "Dephna Robot";
										$message  = file_get_contents('email-header.html') . "\r\n";
										$message .= $body . "\r\n";
										$message .= file_get_contents('email-footer.html') . "\r\n";

										$mail = new PHPMailer(true);

										try {
											
											include('../../cron/SMTP.php');

											$mail->CharSet = 'UTF-8';
											$mail->setFrom($from, $fromname);
											$mail->addReplyTo($from, $fromname);
											$mail->addAddress($staffemail);
											
											$mail->isHTML(true);
											$mail->Subject = $esubject;
											$mail->Body    = $message;

											$mail->send();

											$notemessage = "$staffname assigned and emailed";
											$resultMessage .= "$staffname assigned<br>Email sent to $staffemail<br>";


										} catch (Exception $e) {
											$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
											$notemessage = "Assignment Email FAILED to $staffemail";
										}

										// add to notes
										$sql = "INSERT INTO `ad_notes` VALUES (
											null,
											'$mydate',
											'$custref',
											'$notemessage',
											'note',
											'',
											'',
											'',
											'$now',
											'{$_SESSION['username']}'
										)";
										$dbcnx->query($sql);
										
									} else {
										$resultMessage .= "Could not get staff member record.<br>";
									}

								}
							}
						} else {
							$resultMessage .= "Could NOT update database<br />" . $dbcnx->error . "<br />";
						}

						// mail report
						$mailto   = "david@dsi3.net,rajive@dephna.com";
						$from     = "robot@dephna.com";
						$mailsjt  = "Escalation Result DP$custref";
						$mime_boundary = md5(uniqid(time()));
						$headers .= "From: $from\r\n";
						$headers .= "Reply-To: $from\r\n";
						$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
						$message  = "--".$mime_boundary."\r\n";
						$message .= "Content-type:text/html; charset=utf-8\r\n";
						$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
						$message .= "<p><b>Escalation Result DP$custref<br />$compname</b></p>\r\n";
						$message .= "<p>$resultMessage</p>";
						$message .= "<p>By {$_SESSION['username']} - " . date("Y-m-d H:i") . "</p>\r\n";
						$message .= "\r\n\r\n";
						$message .= "--".$mime_boundary."--";
						mail($mailto, $mailsjt, $message, $headers);

					}
                        
					if ((!$resultMessage) OR ($e)) {

						print "<form name=\"emailform\" method=\"post\" action=\"ad-escalation.php?$qString\">\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

						print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";

						if ($action == "Escalate") {

							print "<tr>\n";
							print "	<td align=\"right\"><b>Assign To:</b></td>\n";
							print "	<td><select name=\"new_escstaffid\" class=\"element\">\n";
							print "	<option value=\"Unassigned\""; if ($escstaffid == 0) { print " SELECTED"; } print ">Unassigned</option>\n";
							$sql = "SELECT ID, username FROM staff WHERE onsupport = 'on' ORDER BY username";
							$staffresult = $dbcnx->query($sql);
							while ($staffrow = $staffresult->fetch_assoc()) {
								$thisstaffname = $staffrow['username'];
								$thisstaffid   = $staffrow['ID'];
								print "		<option value=\"$thisstaffid\""; if ($thisstaffid == $escstaffid) { print " SELECTED"; } print ">$thisstaffname</option>\n";
							}
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Escalate\" class=\"button\" /></td>\n";
							print "</tr>\n";

						} else {

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Deescalate\" class=\"button\" /></td>\n";
							print "</tr>\n";

						}

						print "</table>\n";
						print "</form>\n";
						
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
	
	print "<p align=\"center\"><a href=\"ad-list.php?$qString\">Return</a></p>\n";
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

?>
