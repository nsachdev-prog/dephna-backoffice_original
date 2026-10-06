<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Termination";
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
			print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			print "<p align=\"center\" class=\"larger\">Termination</p>\n";

			// get filters
			$custref = $_REQUEST['custref'];
			$action  = $_REQUEST['action'];
			$ppid    = $_REQUEST['ppid'];
			$now = time();

			if ($custref) {
				// get customer details
				$sql = "SELECT `ID`, `username`, `email`, `aemails`, `compname`, `terminationCheck` FROM `users` WHERE `custref`='$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$id   = $crow['ID'];
					$compname = $crow['compname'];
					$custname = $crow['username'];
					$aemails  = $crow['aemails'];
					$email    = $crow['email'];
					$termCheck = $crow['terminationCheck'];
					if (!$aemails) { $aemails = $email; }

					print "<p align=\"center\" class=\"larger\">Termination $action</p>\n";
					print "<p align=\"center\" class=\"larger\">$compname</p>\n";

					// get outstanding invoices
					$sql = "SELECT SUM(outstanding) FROM inv_headers WHERE custref = $custref AND outstanding <> 0";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_row();
						$outstanding = $row[0];

						if ($action == "Warning") {
							$mailout = "TerminationWarning";
							$userField = "terminationWarning";
							$remdate = date("Y-m-d",strtotime("+3 Days"));
							$remid = $_SESSION['user'];
						} elseif ($action == "Notice") {
							$mailout = "TerminationNotice";
							$userField = "terminationNotice";
							$remdate = 0;
							$remid = 0;
						} elseif ($action == "Cancel") {
							$remdate = 0;
							$remid = 0;
						} elseif ($action == "Check") {
							$userField = "terminationCheck";
							$remdate = 0;
							$remid = 0;
						}
						
						if ($_POST['posted']) {

							$mydate = date("Y-m-d");

							if ($_POST['send'] AND $mailout) {

								// process and send email
								$subject = strip_tags($_POST['subject']);
								$body    = strip_tags($_POST['body']);
								$trefs   = $_POST['trefs'];
								$statement = $_POST['statement'];
								$tdate = $row['date'];
								if (!$subject) { $ersubject = "No Subject"; $e++; }
								if (!$body)    { $erbody    = "No Message"; $e++; }
								if (!$e) {

									require "../../PHPMailer/src/SMTP.php";
									require "../../PHPMailer/src/PHPMailer.php";
									require "../../PHPMailer/src/Exception.php";

									// send email to client
									$from     = "accounts@dephna.com";
									$fromname = "Dephna Accounts";
									
									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= nl2br($body);
									$message .= file_get_contents('email-footer.html') . "\r\n";

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

										$mail->send();

										$resultMessage .= "Email Sent to $aemails<br>";
										$mailResult = "<br>Email Sent to $aemails";

									} catch (Exception $e) {
										$resultMessage .= "Email FAILED<br>Mailer Error: {$mail->ErrorInfo}";
									}
									
								}
							} else {
								if ($action == "Check") {
									$mailResult = "<br>" . $_POST['details'];
								} else {
									$resultMessage .= "Email customer not selected<br>";
									$mailResult = "<br>Email customer not selected";
								}
							}

							// update user record
							$mydate = date("Y-m-d");
							if ($action == "Cancel") {
								$sql = "UPDATE users SET terminationNotice = '', terminationWarning = '', terminationCheck = '' WHERE custref = '$custref'";
							} else {
								$sql = "UPDATE users SET $userField = '$mydate' WHERE custref = '$custref'";
							}
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Termination $action Done<br>";
							} else {
								$resultMessage .= "Could NOT update database<br />" . $dbcnx->error . "<br />";
							}

							// add to notes	
							$sql = "INSERT INTO `ad_notes` VALUES (
								null,
								'$mydate',
								'$custref',
								'Termination $action $mailResult',
								'note',
								'',
								'$remdate',
								'$remid',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Note added<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Termination','DP$custref Termination $action','$page','$now')");
							} else {
								$resultMessage .= "Could NOT update notes<br>" . $dbcnx->error . "<br>";
							}

							// mail report
							/* $mailto   = "???";
							$from     = "robot@dephna.com";
							$mailsjt  = "Termination $action DP$custref";
							$mime_boundary = md5(uniqid(time()));
							$headers .= "From: $from\r\n";
							$headers .= "Reply-To: $from\r\n";
							$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
							$headers .= "MIME-Version: 1.0\r\n";
							$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
							$message  = "--".$mime_boundary."\r\n";
							$message .= "Content-type:text/html; charset=utf-8\r\n";
							$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
							$message .= "<p><b>Termination $action DP$custref<br />$compname</b></p>\r\n";
							$message .= "<p>$resultMessage</p>";
							$message .= "<p>By {$_SESSION['username']} - " . date("Y-m-d H:i") . "</p>\r\n";
							$message .= "\r\n\r\n";
							$message .= "--".$mime_boundary."--";
							mail($mailto, $mailsjt, $message, $headers); */

						}
						
						if ((!$resultMessage) OR ($e)) {

							if ($action == "Check") {

								// display the form with details box
								if (!$details) { $details = "Checked with office. Okay to send termination warning."; }
								print "<form name=\"emailform\" method=\"post\" action=\"ad-termination-email.php\">\n";
								print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
								print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
								print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
								print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
								print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";
								print "<tr>\n";
								print "	<td><b>Outstanding Amount</b></td>\n";
								print "	<td>&pound;".number_format($outstanding,2)."</td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "	<td align=\"right\" valign=\"top\"><b>Notes</b></td>\n";
								print "	<td><textarea name=\"details\" rows=\"2\" class=\"element_box_large\">$details</textarea></td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "</table>\n";
								print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
								print "</form>\n";
							
							} else {

								// display the form with outstanding transactions
								print "<form name=\"emailform\" method=\"post\" action=\"ad-termination-email.php\">\n";
								print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
								print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
								print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
								print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
								print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";
								print "<tr>\n";
								print "	<td><b>Outstanding Amount</b></td>\n";
								print "	<td align=\"right\">&pound;".number_format($outstanding,2)."</td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "	<td><b>Send Email To Client</b></td>\n";
								print "	<td><input type=\"checkbox\" name=\"send\" CHECKED /></td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "</table>\n";

								// get email template
								$sql = "SELECT * FROM `mailouts` WHERE `mref` = '$mailout'";
								$tresult = $dbcnx->query($sql);
								if ($tresult->num_rows) {
									$trow  = $tresult->fetch_assoc();
									$subject = $trow['subject'];
									$body    = $trow['html'];

									$search  = array("[[custname]]","[[compname]]","[[amount]]");
									$replace = array($custname,$compname,number_format($outstanding,2));
									$body    = str_replace($search,$replace,$body);

									print "<p align=\"center\"><b>To:</b>$aemails</p>\n";

									print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
									if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
									print "</p>\n";

									if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
									print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

									print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
									print "</form>\n";

								} else {
									$resultMessage .= "Email template $mailout not found<br />" . $dbcnx->error;
								}
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
	print "<a href=\"pplans-view.php?id=$id&amp;ppid=$ppid\">Return</a> | ";
	print "<a href=\"ad-list.php?$qString\">Debtors</a>";
	print "</p>\n";
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

?>
