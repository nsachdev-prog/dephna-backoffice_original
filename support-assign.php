<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Support";
$subpage = "View";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];
			if ($_REQUEST['custref']) {

				// get filtering if any
				if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
				if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
				if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
				if ($_REQUEST['st'])  { $st  = $_REQUEST['st'];  }
				if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
				$qString ="se=$se&amp;dp=$dp&amp;st=$st&amp;al=$al";

				if ($tid) {

					if ($_POST['posted']) {
						$assignedto = $_POST['assignedto'];
						$oldassignedto = $_POST['oldassignedto'];
						$pending    = $_POST['pending'];
						$flagged    = $_POST['flagged'];
						$custref    = $_POST['custref'];
						$oldcustref = $_POST['oldcustref'];
						if ($custref != $oldcustref) {
							// get email addresses
							$sql = "SELECT email, aemails FROM users WHERE custref = '$custref'";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$email   = $row['email'];
								$aemails = $row['aemails'];
								if (!$aemails) { $aemails = $email; }
							}
						} else {
							$aemails  = $_POST['aemails'];
							if ($aemails) {	
								$aemails = explode(",",$aemails);
								foreach ($aemails AS $email) {
									$email = trim($email);
									if (!validate_email($email)) { $eremail = "Invalid email address"; $e++; }
								}
								$aemails = implode(",",$aemails);
							} else {
								$eremail = "Enter email address(,es)"; $e++;
							}
						}
						
						if (!$e) {

							if ($flagged) {
								$pending = 0;
							}
						
							$now = time();
							$sql = "UPDATE `support_tickets` SET
								`custref`    = '$custref',
								`aemails`    = '$aemails',
								`pending`    = '$pending',
								`flagged`    = '$flagged',
								`assignedto` = '$assignedto',
								`lastupd`    = '$now',
								`updby`      = '{$_SESSION['username']}'
								WHERE `ID` = '$tid'
							";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Ticket Updated<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref Ticket,'$page','$now')");
							} else {
								$resultMessage .= "Could NOT Update Details<br>" . $dbcnx->error . "<br>";
							}

							if (($assignedto != $oldassignedto) AND ($assignedto != "Unassigned")) {
								// email staff member
								$sql = "SELECT email FROM staff WHERE onsupport = 'on' ORDER BY username";
								if ($result = $dbcnx->query($sql) AND $result->num_rows) {
									$row = $result->fetch_assoc();
									$staffemail = $row['email'];

									require "../../PHPMailer/src/SMTP.php";
									require "../../PHPMailer/src/PHPMailer.php";
									require "../../PHPMailer/src/Exception.php";
									
									$subject  = "Support Notification";
									$body     = "<p>A support ticket has allocated to you.</p>";
									$body    .= "<p><a href=\"https://dephna.net/backoffice/support-view.php?tid=$tid&amp;custref=$custref\">View Support Ticket</a></p>";
									$from     = "rajive@dephna.com";
									$fromname = "Rajive Sachdev";
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

									} catch (Exception $e) {
										$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
									}

								}
							}
						}
					}

					$sql = "SELECT t.*, u.compname, s.username AS assigned
							FROM support_tickets AS t
							LEFT JOIN users AS u ON u.custref = t.custref
							LEFT JOIN staff AS s ON s.ID = t.assignedto
							WHERE t.ID = '$tid'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						
						$row = $result->fetch_assoc();
						$tdatetime  = $row['tdatetime'];
						$ticketno   = $row['ticketno'];
						$custref    = $row['custref'];
						$compname   = $row['compname'];
						$subject    = $row['subject'];
						$assignedto = $row['assignedto'];
						$assigned   = $row['assigned'];

						if (!$compname) { $compname = "Unknown"; }
						if (!$assignedto) { $assigned = "Unassigned"; }
						$subject = html_entity_decode($subject, ENT_QUOTES);

                        print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						
						print "<tr>\n";
						print "	<td><b>Ticket No</b></td>\n";
						print "	<td>$ticketno</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Created</b></td>\n";
						print "	<td>$tdatetime</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Customer</b></td>\n";
						print "	<td>$compname</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Subject</b></td>\n";
						print "	<td>$subject</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Assigned To</b></td>\n";
						print "	<td>$assigned</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\">Ticket Not Found!</p>\n";
					}

					if ($status == "Open") {

						print "<td valign=\"top\">\n";

						print "<form name=\"addform\" method=\"post\" action=\"support-assign.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
						print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Assigned To:</b></td>\n";
						print "	<td><select name=\"assignedto\" class=\"element\">\n";
						print "	<option value=\"Unassigned\""; if ($thisstaffid == 0) { print " SELECTED"; } print ">Unassigned</option>\n";
						$sql = "SELECT ID, username FROM staff WHERE onsupport = 'on' ORDER BY username";
						$staffresult = $dbcnx->query($sql);
						while ($staffrow = $staffresult->fetch_assoc()) {
							$thisstaffname = $staffrow['username'];
							$thisstaffid   = $staffrow['ID'];
							print "		<option value=\"$thisstaffid\""; if ($thisstaffid == $assignedto) { print " SELECTED"; } print ">$thisstaffname</option>\n";
						}
						print "	</select>";
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

					}

				} else {
					print "<p align=\"center\">Page called without ticket ID</p>\n";
				}
				print "<p align=\"center\"><a href=\"support-list.php?$qString\">Return</a></p>\n";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
?>
<script>
var state = 0;
$('.tglthis').click(function() {
	var eid = $(this).data("eid");
	$('.tgl' + eid).toggle();
	return false;
});
$('.tglall').click(function() {
	if (state == 0) {
		$('.sup-sub').show();
		$('.sup-body').show();
		$('.sup-not').show();
		$('.expand').text("Collapse All");
		state = 1;
	} else {
		$('.sup-sub').hide();
		$('.sup-body').hide();
		$('.sup-not').hide();
		$('.expand').text("Expand All");
		state = 0;
	}
	return false;
});
</script>
<?php
print "<script>\n";
print "	$(\".tgl$first\").toggle();\n";
print "</script>\n";
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
