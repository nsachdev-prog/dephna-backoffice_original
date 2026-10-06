<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Support";
$subpage = "Close";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	include('support-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
	print "<p align=\"center\" class=\"larger\">Move Ticket To Support</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get ticket details
			$sql = "SELECT t.*, u.compname, s.username AS assigned, u.username AS custname
					FROM support_tickets AS t
					LEFT JOIN users AS u ON u.custref = t.custref
					LEFT JOIN staff AS s ON s.ID = t.assignedto
					WHERE t.ID = '$tid'
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				
				$row = $result->fetch_assoc();
				$ticketno   = $row['ticketno'];
				$custref    = $row['custref'];
				$compname   = $row['compname'];
				$custname   = $row['custname'];
				$aemails    = $row['aemails'];

				if (!$compname) { $compname = "Unknown"; }
				if (!$assignedto) { $assigned = "Unassigned"; }
				if (!$custname) { $custname = $aemails; }
				
				print "<tr>\n";
				print "	<td><b>Ticket No</b></td>\n";
				print "	<td>$ticketno</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td><b>Customer</b></td>\n";
				print "	<td>$compname</td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">Ticket Not Found!</p>\n";
			}


			if ($_POST['posted']) {
				$tos     = strip_tags($_POST['tos']);
				$ccs     = strip_tags($_POST['ccs']);
                $subject = strip_tags($_POST['subject']);
				$body    = strip_tags($_POST['body']);

				$mailit = $_POST['mailit'];

				if ($mailit) {

					if (!$tos) {
						$ertos  = "Please enter a recipient"; $e++;
					} else {
						$tos = explode(",",$tos);
						foreach ($tos AS $to) {
							$to = trim($to);
							if (!validate_email($to)) { $ertos = "Invalid email address"; $e++; }
						}
						$tos = implode(",",$tos);
					}
					if ($ccs) {
						$ccs = explode(",",$ccs);
						foreach ($ccs AS $cc) {
							$cc = trim($cc);
							if (!validate_email($cc)) { $erccs = "Invalid email address"; $e++; }
						}
						$ccs = implode(",",$ccs);
					}
					if ($bccs) {
						$bccs = explode(",",$bccs);
						foreach ($bccs AS $bcc) {
							$bcc = trim($bcc);
							if (!validate_email($bcc)) { $erbccs = "Invalid email address"; $e++; }
						}
						$bccs = implode(",",$bccs);
					}
                    if (!$subject) { $erbody = "No Subject entered"; $e++; }
					if (!$body) { $erbody = "No bodytext entered"; $e++; }

				}

				if (!$e) {

                    $sql = "UPDATE `support_tickets` SET `section` = '1-Support' WHERE `ID` = '$tid'";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "Moved To Support<br>";
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Moved','DP$custref Ticket $ticketno','$page','$now')");

						if ($mailit) {

							$subject = "Re: [DST$ticketno]: " . $subject;

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							$body = nl2br($body);

							$from = "support@dephna.com";				
							$fromname = "Dephna Support";
							$message .= "<p>$body</p>\n";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addReplyTo($from, $fromname);
								$tos = explode(",",$tos);
								foreach ($tos AS $to) {
									$mail->addAddress($to);
								}
								$tos = implode(",",$tos);
								if ($ccs) {
									$ccs = explode(",",$ccs);
									foreach ($ccs AS $cc) {
										$mail->addCC($cc);
									}
									$ccs = implode(",",$ccs);
								}
								if ($bccs) {
									$bccs = explode(",",$bccs);
									foreach ($bccs AS $bcc) {
										$mail->addBCC($bcc);
									}
									$bccs = implode(",",$bccs);
								}
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$mail->send();
								$resultMessage .= "Email Sent to $tos<br>";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
							}

						} else {
							$resultMessage .= "Email customer not selected<br>";
						}

					} else {
						$resultMessage .= "Could NOT Close Ticket<br>";
					}
					
				}

			} else {

				$subject = $row['subject'];
				// get template body
				$sql = "SELECT body FROM support_templates WHERE tref = 'MoveToSupport'";
				if ($tresult = $dbcnx->query($sql) AND $tresult->num_rows) {
					$trow = $tresult->fetch_assoc();
					$body = $trow['body'];
					$body = html_entity_decode($body, ENT_QUOTES);
					if (!$custname) { $custname = $aemails; }
					$ticketdetails .= "Ticket ID: DST$ticketno\nSubject: $subject\nStatus: Open\n";
					$search  = array("[[ticketno]]","[[subject]]","[[custname]]","[[staffname]]","[[ticketdetails]]");
					$replace = array($ticketno,$subject,$custname,$_SESSION['username'],$ticketdetails);
					$body    = str_replace($search,$replace,$body);
					$body    = str_replace("<br>","\n",$body);
				}
				$tos = $aemails;
				$mailit = "on";
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"support-move.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Email Client</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>Subject</b></td>\n";
					print "	<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
					if ($ersubject) { print "<br><span class=\"redtext\">$ersubject</span>"; }
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>To</b></td>\n";
					print "	<td><input name=\"tos\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$tos\" />";
					if ($ertos) { print "<br><span class=\"redtext\">$ertos</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Cc</b></td>\n";
					print "	<td><input name=\"ccs\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$ccs\" />";
					if ($erccs) { print "<br><span class=\"redtext\">$erccs</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Bcc</b></td>\n";
					print "	<td><input name=\"bccs\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$bccs\" />";
					if ($erbccs) { print "<br><span class=\"redtext\">$erbccs</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
					print "<p align=\"center\"><textarea id=\"body\" name=\"body\" rows=\"24\" cols=\"8\" class=\"element_box_large\">$body</textarea></p>\n";

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "</table>\n";

					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

					print "</form>\n";

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";
				}
			}
			
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>\n";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">Return</a></p>\n";
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
