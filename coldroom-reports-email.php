<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Coldroom_Reports";
$subpage = "Email";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
	
			$id     = $_REQUEST['id']; 
			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];
			$date   = $_REQUEST['date'];

			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Email Service Report</p>\n";

			if ($id AND $siteid AND $unitid) {

				// get sitename
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				// get unitname
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
					list($junk,$unitname) = explode(" ",$unitname);
				} else {
					$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
				}

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

				// get report header
				$sql = "SELECT * FROM cr_reports_headers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$reportno  = $row['reportno'];
					$type      = $row['type'];
					$sdate     = $row['sdate'];
					list($y,$m,$d) = explode("-",$sdate);
				} else {
					$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
				}

				print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Report Number</b></td>\n";
				print "	<td>$reportno</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Report Type</b></td>\n";
				print "	<td>$type</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Date</b></td>\n";
				print "	<td>$d-$m-$y</td>\n";
				print "</tr>\n";

				print "</table>\n";
		
				if ($_POST['posted']) {

					$subject = strip_tags($_POST['subject']);
					$aemails = strip_tags($_POST['aemails']);
					$body    = strip_tags($_POST['body']);

					if (!$subject) { $ersubject = "Type a subject or accept default";   $e++; }
					if (!$body)    { $erbody    = "Type a message or accept default"; $e++; }
					if ($aemails) {
						$emailarray = explode(",",$aemails);
						foreach ($emailarray AS $thisemail) {
							$thisemail = trim($thisemail);
							$trimmedemailarray[] = $thisemail;
							if (!validate_email($thisemail)) { $ee++; }
						}
						if ($ee) {
							$eraemails = "Contains invalid email address"; $e++;
						} else {
							$aemails = implode(",",$trimmedemailarray);
						}
					} else {
						$eraemails = "Please give an email address"; $e++;
					}

					if (!$e) {

						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";

						$body     = nl2br($body);
						$from     = "support1@dephna.com";
						$fromname = "Dephna Support";

						// email message
						include("email-header.php");
						$message .= "<p>$body</p>\n";
						include("email-footer.php");

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

							$byemail = 1;
							$rid = $id;
							$fileatt = "";
							include("coldroom-reports-pdf.php");
							if ($fileatt) {
								$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
							}

							$mail->send();
							$resultMessage .= "Email Sent to $aemails<br>";
							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Siteid:$siteid Unit:$unitname ID:$id Report Emailed','$page','$now')");
							$sql = "INSERT INTO cr_reports_notes VALUES (
								null,
								'$id',
								'$now',
								'{$_SESSION['username']}',
								'Report emailed to $aemails',
								'$body'
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Note Added<br>";
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add note to database</b></span><br>" . $dbcnx->error . "<br>";
							}

						} catch (Exception $e) {
							$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
						} 
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
							
						print "<form name=\"addform\" method=\"post\" action=\"coldroom-reports-email.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						if (!$subject) { $subject = "Cold Room Service Report"; }
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Subject</b></td>\n";
						print "	<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$subject\" />";
						if ($ersubject) { print "<br><span class=\"redtext\">$ersubject</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Email(,s)</b></td>\n";
						print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$aemails\" />";
						if ($eraemails) { print "<br><span class=\"redtext\">$eraemails</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if (!$body) { $body = "Dear Client,\n\nPlease find attached your service report as requested.\n\nKind Regards\nDephna Support\n"; }
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Message</b></td>";
						print "	<td><textarea name=\"body\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$body</textarea>";
						if ($erbody) { print "<br><span class=\"redtext\">$erbody</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
						print "</form>\n";

					}
				}
				$dbcnx->close();

			} else {
				$resultMessage .= "Page called without either id, siteid or unitid";
			}

		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		if ($date) {
			print "<a href=\"coldroom-reports-summary.php?date=$date\">Summary</a> | ";
		}
		print "<a href=\"coldroom-reports-list.php?siteid=$siteid&amp;unitid=$unitid\">List</a> | ";
		print "<a href=\"coldroom-reports-sites.php?siteid=$siteid\">Site</a>";
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
