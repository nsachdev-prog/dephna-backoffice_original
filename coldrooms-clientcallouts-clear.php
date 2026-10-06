<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Coldroom_Callouts";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">COLD ROOM CALLOUTS</p>\n";
	print "<p align=\"center\" class=\"larger\">Clear Callout</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$ccoid = $_REQUEST['ccoid'];

			if ($ccoid) {
				
				// get callout
				$sql = "SELECT c.*, s.title AS sitename, u.compname
						FROM ClientCallouts AS c
						LEFT JOIN sites AS s ON s.ID = c.siteid
						LEFT JOIN users AS u ON u.custref = c.custref
						WHERE c.ID = '$ccoid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$custref  = $row['custref'];
					$siteid   = $row['siteid'];
					$unitid   = $row['unitid'];
					$unitname = $row['unitname'];
				
					print "<p align=\"center\" class=\"larger\"><b>$compname - $unitname</b></p>\n";
			
					if ($_POST['posted']) {

						$mailit        = strip_tags($_POST['mailit']);
						$ColdRoomEmail = strip_tags($_POST['ColdRoomEmail']);
						$subject       = strip_tags($_POST['subject']);
						$html          = strip_tags($_POST['html']);
						$details       = strip_tags($_POST['details']);

						if ($mailit == "on") {
							if ($ColdRoomEmail) {
								$aemails = explode(",",$ColdRoomEmail);
								foreach ($aemails AS $email) {
									$email = trim($email);
									if (!validate_email($email)) { $erColdRoomEmail = "Invalid email address"; $e++; }
								}
								$aemails = implode(",",$aemails);
							} else {
								$erColdRoomEmail = "Enter email address(es <i>Comma Separated</i>)"; $e++;
							}
						}

						if (!$e) {
						
							if ($details) {
								$details = nl2br($details);
								$details = addslashes($details);
							}
							$now = time();
									
							$datetime = date("Y-m-d H:i");
							$sql = "UPDATE ClientCallouts SET 
								clearedby='{$_SESSION['username']}',
								cleared='$datetime'
								WHERE ID = '$ccoid'
							";
							if ($result = $dbcnx->query($sql)) {

								// email client
								if ($mailit == "on") {

									$html = nl2br($html);

									// email message
									include("email-header.php");
									$message .= "<p>$html</p>\n";
									include("email-footer.php");

									// prepare email
									$from     = "support@dephna.com";
									$fromname = "Dephna Support";

									require "../../PHPMailer/src/SMTP.php";
									require "../../PHPMailer/src/PHPMailer.php";
									require "../../PHPMailer/src/Exception.php";

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
										$mailnote = "Email Sent to $aemails";

									} catch (Exception $e) {
										$resultMessage .= "Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
										$mailnote = "Email FAILED to $aemails";
									}

								} else {
									$resultMessage .= "Email Customer Not Selected<br />";
									$mailnote = "Email Customer Not Selected";
									$html = "";
								}

								$resultMessage .= "Cleared Date Added<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cleared','Cold Room Callout DP$custref $unitname','$page','$now')");
								// add history note
								if ($details) {
									$details = "$mailnote<br>" . $details;
								} else {
									$details = "$mailnote";
								}
								$sql = "INSERT INTO ClientCalloutsHistory VALUES (
									null,
									'$ccoid',
									'Cleared',
									'$details',
									'$html',
									'$datetime',
									'{$_SESSION['username']}',
									'{$_SESSION['user']}',
									'0'
								)";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "History Updated $unitname DP$custref<br>";
								} else {
									$resultMessage .= "Could NOT update history $unitname DP$custref<br>" . $dbcnx->error . "<br>";
								}
							} else {
								$resultMessage .= "Could NOT Update Callout Record<br />" . $dbcnx->error . "<br>";
							}
						}

					} else {

						$sql = "SELECT ColdRoomContact, ColdRoomEmail, username, email
								FROM users
								WHERE custref = '$custref'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$ColdRoomContact   = $row['ColdRoomContact'];
							$ColdRoomEmail     = $row['ColdRoomEmail'];
							$username          = $row['username'];
							$email             = $row['email'];
							if (!$ColdRoomContact) { $ColdRoomContact = $username; }
							if (!$ColdRoomEmail) { $ColdRoomEmail = $email; }

							// get the email mail template
							$sql = "SELECT `subject`, `html` FROM mailouts WHERE mref = 'ClientCallOutClear'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$subject = $row['subject'];
								$html    = $row['html'];
								// replace fields in template with data
								$search  = array("[[custname]]","[[unitname]]");
								$replace = array($ColdRoomContact,$unitname);
								$html    = str_replace($search,$replace,$html);
							} else {
								$resultMessage .= "Mailout Template ClientCallOutClear Not Found<br>";
							}
						}
					}
				
					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							$mailit = "on";
							
							print "<form name=\"addform\" method=\"post\" action=\"coldrooms-clientcallouts-clear.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"ccoid\" value=\"$ccoid\" />\n";

							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Email Customer</b></td>\n";
							print "	<td><input type=\"checkbox\" name=\"mailit\" value=\"on\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Email Address(es <i>Comma Separated</i>)</b></td>\n";
							print "	<td><input name=\"ColdRoomEmail\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$ColdRoomEmail\" />";
							if ($erColdRoomEmail) { print "<br /><span class=\"redtext\">$erColdRoomEmail</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\"><b>Subject:</b></td>\n";
							print "<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
							if ($ersubject) { print "<br /><span class=\"redtext\">$ersubject</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "</table>\n";

							if ($erhtml) { print "<p align=\"center\"><span class=\"redtext\">$erhtml</span></p>"; }
							print "<p align=\"center\"><textarea name=\"html\" rows=\"24\" class=\"element_box_large\">$html</textarea></p>\n";
							
							print "<p align=\"center\"><b>Notes (Optional)</b></p>";
							print "<p align=\"center\"><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box_large\">$details</textarea></p>\n";
							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
							
							print "</form>\n";

						}
						
					}

				} else {
					$resultMessage .= "Record Not Found!<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage = "Page called without ID<br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-clientcallouts-list.php\">List</a> | ";
		print "<a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid\">View</a> | ";
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
function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
?>
