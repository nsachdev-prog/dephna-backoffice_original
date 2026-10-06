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
	if ($_REQUEST['eid']) { $eid = $_REQUEST['eid']; }
	print "<p align=\"center\" class=\"larger\">Close Ticket</p>\n";

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
				$subject    = $row['subject'];
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

				print "<tr>\n";
				print "	<td><b>Subject</b></td>\n";
				print "	<td>$subject</td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">Ticket Not Found!</p>\n";
			}


			if ($_POST['posted']) {
				$tos     = strip_tags($_POST['tos']);
				$ccs     = strip_tags($_POST['ccs']);
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

				}

				if (!$e) {

					$failed = 0;

					// delete attachments
					$sql = "SELECT ID, attname, attfilename FROM support_attachments WHERE tid = '$tid'";
					if ($eresult = $dbcnx->query($sql) AND $eresult->num_rows) {
						while ($erow = $eresult->fetch_assoc()) {
							$aid         = $erow['ID'];
							$attname     = $erow['attname'];
							$attfilename = $erow['attfilename'];
							$filename = "../../dis2_db/sup/$attfilename";
							if (file_exists($filename)) {
								if (!unlink($filename)) {
									$resultMessage .= "Attachment $attname Could NOT Be Deleted.<br>\n";
									$failed++;
								} else {
									$resultMessage .= "Attachment $attname Deleted.<br>\n";
								}
							}
							if (!$failed) {
								$sql = "DELETE FROM support_attachments WHERE ID = '$aid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Attachment ($attname) Record Deleted.<br>\n";
								} else {
									$resultMessage .= "Attachment Record($attname) Could NOT Be Deleted.<br>\n";
									$failed++;
								}
							}
						}
					}

					if (!$failed) {

						$now = time();
						$sql = "UPDATE `support_tickets` SET `status` = 'Closed', `pending` = '0', `flagged` = '0', `lastupd` = '$now', `updby` = '{$_SESSION['username']}' WHERE `ID` = '$tid'";
						if ($dbcnx->query($sql)) {

							if ($mailit) {

								$subject = "Re: " . $subject;

								require "../../PHPMailer/src/SMTP.php";
								require "../../PHPMailer/src/PHPMailer.php";
								require "../../PHPMailer/src/Exception.php";

								$from     = "support@dephna.com";
								$fromname = "Dephna Support";
								
								$body     = "<p>This support ticket has been closed now that the issue is resolved</p>\r\n";
								$body    .= "<p>Tkt No: $ticketno<br>$subject</p>\r\n";
								$body    .= "<p>Kind Regards<br>Dephna Support Team</p>\r\n";
								$body    .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DST]]{$ticketno}[[/DST]]</span>";
								
								$message  = file_get_contents('email-header.html') . "\r\n";
								$message .= $body . "\r\n";
								$message .= file_get_contents('email-footer.html') . "\r\n";

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
										$bccs = implode(",",$bccs);// get template body
									}
									$mail->isHTML(true);
									$mail->Subject = $subject;
									$mail->Body    = $message;

									$mail->send();

									$now = time();
									$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Closure email sent to $tos')");

								} catch (Exception $e) {
									$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
									$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','$resultMessage')");
								}

							}

							/* // send whatsapp message
							if (strlen($subject) > 20) { $subject = substr($subject,0,20); }
							if (strlen($compname) > 20) { $compname = substr($compname,0,20); }

							// get assiged to name
							$sql = "SELECT username FROM staff WHERE ID = '$assignedto'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$assigned = $row['username'];
								if (strlen($assigned) > 20) { $assigned = substr($assigned,0,20); }
							} else {
								$assigned = "Unassigned";
							}

							// build whatsapp message
							$wanote = "Sup Tkt:$ticketno Closed\nClient:$compname\n$subject\nAsgnd:$assigned";

							// send to whatsapp
							$postData = array(
								'group_admin' => $WA_GROUP_ADMIN,
								'group_name' => $WA_GROUP_NAME,
								'message' => $wanote
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
							curl_close($ch);
							$resultMessage .= "WhatsApp Responded...<br>" . $response . "<br>\n"; */

							$now = time();
							$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Ticket Closed...<br>$resultMessage')");
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Closed','Tkt:$ticketno $subject','$page','$now')");
							$resultMessage .= "Ticket Marked As Closed.<br>\n";

						} else {
							$now = time();
							$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Ticket Could Not Be Closed....<br>$resultMessage')");
							$resultMessage .= "Ticket Could Not Be Closed.<br>\n";
						}
					}
				}

			} else {

				$tos = $aemails;

			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"support-close.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
					print "<input type=\"hidden\" name=\"eid\" value=\"$eid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Email Client</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
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
print "<p align=\"center\"><a href=\"support-list.php?$qString\">Return</a></p>\n";
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
