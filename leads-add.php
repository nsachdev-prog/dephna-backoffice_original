<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Leads";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">LEADS</p>\n";
	print "<p align=\"center\" class=\"larger\">Add A New Lead</p>\n";
	print "<p align=\"center\">Submitting this form will add a web lead to the database and send an email with brochures (if requested) to the applicant.</p>\n";
	if ($accessRights[$page]) {
		?>
		<script>
			$(document).ready(function() {
				$("#depForm").validationEngine()
			})
		</script>
		<?php

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {
	if ($_POST['posted']) {

		$fullname = strip_tags($_POST['fullname']);
		$phone    = strip_tags($_POST['phone']);
		$mobile   = strip_tags($_POST['mobile']);
		$email    = strip_tags($_POST['email']);
		$compname = strip_tags($_POST['compname']);
		$details  = strip_tags($_POST['details']);
		$subject  = strip_tags($_POST['subject']);

		$unittypes = array();
		$sites = array();

		if ($_POST['office']     == "on") { $unittypes[] = "Office"; }
		if ($_POST['kitchen']    == "on") { $unittypes[] = "Kitchen"; }
		if ($_POST['storage']    == "on") { $unittypes[] = "Storage"; }
		if ($_POST['coldroom']   == "on") { $unittypes[] = "Cold Room"; }

		$emailclient = $_POST['emailclient'];
		if ($emailclient == "on") {
			if (!$subject) { $ersubject = "**"; $e++; }
		}

		if (!$fullname) { $erfullname = "**"; $e++; }
		if (!$phone)    { $erphone = "**"; $e++; }
		if (!$email)    { $eremail = "**"; $e++; }
		else {
			$email = strtolower($email);
			if (!validate_email($email)) { $eremail = "**"; $e++; }
		}

		if (!$e) {
			if ($unittypes) { $unittypes = implode("|",$unittypes); }
			$fullname = addslashes($fullname);
			$phone    = addslashes($phone);
			$email    = addslashes($email);
			$compname = addslashes($compname);
			$details  = addslashes($details);

			$now = time();
			// get next lead ticket number.
			$sql = "SELECT `ticketno` FROM `leads_tickets` ORDER BY `ticketno` DESC LIMIT 0, 1";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$ticketno = $row['ticketno'];
				$ticketno++;
			} else {
				$ticketno = 1001;
			}

			$datetime = date("Y-m-d H:i");

			$sql = "INSERT INTO leads_tickets VALUES (
				null,
				'$datetime',
				'$ticketno',
				'$fullname',
				'$phone',
				'',
				'$compname',
				'$email',
				'',
				'',
				'$unittypes',
				'Added From CAM',
				'0',
				'1',
				'Open',
				'0',
				'',
				'$now',
				'Staff',
				'0',
				'',
				'$now',
				'{$_SESSION['username']}'
			)";
			if ($dbcnx->query($sql)) {
				$tid = $dbcnx->insert_id;
				$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','New Lead:$ticketno','Leads','$now')");
				if ($details) {
					str_replace('\n'," ",$details);
					$details = "<br>" . $details;
				}
				$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Created new lead{$details}')");
				$resultMessage .= "Lead posted in new table<br />";

				if ($emailclient == "on") {
					// get template
					$sql = "SELECT * FROM leads_templates WHERE tref = 'NewLead'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row  = $result->fetch_assoc();
						$html = $row['body'];

						// replace fields
						$search  = array("[[fullname]]");
						$replace = array($fullname);
						$html    = str_replace($search,$replace,$html);

						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";

						$from     = "sales@dephna.com";
						$fromname = "Dephna Sales";

						$message  = file_get_contents('email-header.html') . "\r\n";
						$message .= nl2br($html);
						$message .= file_get_contents('email-footer.html') . "\r\n";
						$message .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DLT]]{$ticketno}[[/DLT]]</span>";

						$mail = new PHPMailer(true);

						try {

							include('../../cron/SMTP.php');

							$mail->setFrom($from, $fromname);
							$mail->addAddress($email,$fullname);
							$mail->addReplyTo($from, $fromname);
							$mail->addBCC($from, $fromname);
							$mail->isHTML(true);
							$mail->Subject = $subject;
							$mail->Body    = $message;
					
							if (is_file("pdf/Dephna-Brochure.pdf")) {
								$note = "Dephna-Brochure.pdf attached";
								$resultMessage .= "Dephna-Brochure.pdf attached<br />";
								$fileatt = file_get_contents("pdf/Dephna-Brochure.pdf");
								$mail->AddStringAttachment($fileatt, "Dephna-Brochure.pdf", "base64", "application/pdf"); 
							} else {
								$resultMessage .= "Dephna-Brochure.pdf not found<br />"; $e++;
								$note = "Dephna-Brochure.pdf NOT FOUND";
							}

							$mail->send();

							$resultMessage .= "Email Sent to $email<br />";
							$sql = "UPDATE `olm_wleads` SET `status` = 'Live' WHERE `ID` = '$lid'";
							$dbcnx->query($sql);
							$now = time();
							$message = nl2br($html);
							$sql = "INSERT INTO olm_wactions VALUES (null,'$lid','Emailed','$note','$message','$now','{$_SESSION['username']}','{$_SESSION['user']}','0')";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Note added<br />";
							} else {
								$resultMessage .= "Could NOT add note<br />";
							}

							// save the email
							$sql = "INSERT INTO leads_emails VALUES (
								null,
								'$tid',
								'$datetime',
								'sales@dephna.com',
								'$email',
								'',
								'',
								'Dephna',
								'$subject',
								'$message',
								'None',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$eid = $dbcnx->insert_id;
							} else {
								$resultMessage .= "Could NOT Save lead email<br>" . $dbcnx->error . "<br>";
							}

						} catch (Exception $e) {
							$resultMessage .= "Email FAILED to $email Mailer Error: {$mail->ErrorInfo}";
							$failed++;
						}

					} else {
						$resultMessage .= "Could not get email template NewLead<br />\n" . $dbcnx->error;
						$failed++;
					}
				} else {
					$resultMessage .= "Email Client was not selected.<br />";
				}
			
			}


		} else {
			$resultMessage .= "Could not add new record to database" . $dbcnx->error;
		}

		// mail report to admin.
		/* $subject = "Web Lead";
		$from = "robot@dephna.com";
		$to = "sales@dephna.com";
		$mime_boundary = md5(uniqid(time()));
		$headers = "From: $from\r\n";
		$headers .= "Reply-To: $from\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
		$message = "--".$mime_boundary."\r\n";
		$message .= "Content-type:text/html; charset=utf-8\r\n";
		$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

		$message .= "<p><b>Web Lead Created by {$_SESSION['username']}</b></p>";
		$message .= "<p>From: $fullname<br />Email: $email</p>";
		$message .= "<p>$resultMessage</p>";
		$message .= "\r\n\r\n";

		$message .= "--".$mime_boundary."--";
		mail($to, $subject, $message, $headers); */

	}

	if ((!$resultMessage) || ($e)) {
		print "<form name=\"msgForm\" action=\"leads-add.php\" method=\"post\" id=\"depForm\">\n";
		print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
		print "<table width=\"420\" border=\"0\" cellpadding=\"0\" cellspacing=\"8\" class=\"webleads\" align=\"center\">\n";

		print "	<tr>\n";
		print "		<td height=\"10\" colspan=\"2\">Full Name *<br />\n";
		print "			<input class=\"validate[required] border1\" name=\"fullname\" type=\"text\" id=\"name\" value=\"$fullname\" />";
		if ($erfullname) { print "<span class=\"redtext\">$erfullname</span>"; }
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td height=\"10\">Telephone *<br />\n";
		print "			<input class=\"validate[required] border1\" name=\"phone\" type=\"text\" id=\"telephone\" value=\"$phone\" />";
		if ($erphone) { print "<span class=\"redtext\">$erphone</span>"; }
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td height=\"10\" colspan=\"2\">Email *<br />\n";
		print "			<input class=\"validate[required] border1\" name=\"email\" type=\"text\" id=\"email\" value=\"$email\" />";
		if ($eremail) { print "<span class=\"redtext\">$eremail</span>"; }
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td height=\"10\" colspan=\"2\">Company Name<br />\n";
		print "			<input name=\"compname\" type=\"text\" class=\"border1\" id=\"company\" value=\"$compname\" />\n";
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td align=\"left\" valign=\"top\" colspan=\"2\">Unit Type<br />\n";
		print "			<div id=\"filters\">\n";
		print "				<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"2\">\n";
		print "					<tr>\n";
		print "						<td><label><input type=\"checkbox\" name=\"office\"   class=\"office1\">Office</label></td>\n";
		print "						<td><label><input type=\"checkbox\" name=\"kitchen\"  class=\"kitchen1\">Kitchen</label></td>\n";
		print "					</tr>\n";
		print "					<tr>\n";
		print "						<td><label><input type=\"checkbox\" name=\"coldroom\" class=\"coldroom1\">Cold Room</label></td>\n";
		print "						<td><label><input type=\"checkbox\" name=\"storage\"  class=\"storage1\">Storage</label></td>\n";
		print "					</tr>\n";
		print "				</table>\n";
		print "			</div>\n";
		print "		</td>\n";
		print "	</tr>\n"; 

		print "	<tr>\n";
		print "		<td align=\"left\" valign=\"top\" colspan=\"2\">Other Information<br />\n";
		print "			<textarea name=\"details\" cols=\"30\" rows=\"3\" class=\"border1\" id=\"message\">$details</textarea>\n";
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td align=\"left\" colspan=\"2\" valign=\"top\">Email Client:&nbsp;\n";
		print "			<input name=\"emailclient\" type=\"checkbox\" />\n";
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td height=\"10\" colspan=\"2\">Email Subject<br />\n";
		print "			<input name=\"subject\" type=\"text\" class=\"border1\" id=\"subject\" value=\"$subject\" />\n";
		if ($ersubject) { print "<span class=\"redtext\">$ersubject</span>"; }
		print "		</td>\n";
		print "	</tr>\n";

		print "	<tr>\n";
		print "		<td align=\"left\" valign=\"top\" colspan=\"2\">\n";
		print "			<input type=\"submit\" name=\"submit\" value=\"Submit\">\n";
		print "		</td>\n";
		print "	</tr>\n";
		print "</table>\n";
		print "</form>\n";
	}
	$dbcnx->close();
} else {
	$resultMessage .= "Could not connect to database<br />\n" . $dbcnx->connect_error;
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"leads-list.php?$qString\">Return</a></p>\n";
?>

<script type="text/javascript">

var getFilter = function(category) {
	var filter = $("#filters ." + category + ":checked").map(function() {
		return "." + this.name;
	}).get().join(",");
	filter = (filter.length > 0) ? filter : "*";
	console.log(filter);
	return filter;
}

$("#filters :checkbox").click(function() {
	var all = $(".filterme");
	var tgts = all.filter(getFilter("office1")).filter(getFilter("kitchen1")).filter(getFilter("kitchen2")).filter(getFilter("storage1")).filter(getFilter("coldroom1")).filter(getFilter("virtual1"));
	all.not(tgts).hide(150);
	tgts.show(150);
});
</script>

		</div>
	</div>
<?php
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");

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