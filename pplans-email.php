<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Payment Plans";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
	include('ad-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az";

			$ppid = $_REQUEST['ppid'];

			if ($ppid) {
			
				print "<p align=\"center\" class=\"larger\">Payment Plan Email</p>\n";

				$sql = "SELECT p.*, c.compname, c.username AS custname
							FROM pplans_headers AS p
							LEFT JOIN users AS c ON c.custref = p.custref
							WHERE p.ID = '$ppid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$txndate  = $row['txndate'];
					$custref  = $row['custref'];
					$compname = $row['compname'];
					$custname = $row['custname'];
					$aemails  = $row['aemails'];
					$dispemails = str_replace(",","<br>",$aemails);

					print "<p align=\"center\" class=\"larger\">$compname</p>\n";

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Agreement Date</b></td>\n";
					print "	<td>$txndate</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>To</b></td>\n";
					print "	<td>$dispemails</td>\n";
					print "</tr>\n";

					// get current plan
					$sql = "SELECT * FROM pplans_lines WHERE ppid = '$ppid' AND txtype = 'DR' ORDER BY duedate";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Due Date</b></td>";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>";
						print "</tr>\n";
						$plan = "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\r\n";
						$plan .= "<tr>\r\n";
						$plan .= "<td><b>Due Date</b></td>\r\n";
						$plan .= "<td align=\"right\"><b>Amount</b></td>\r\n";
						$plan .= "</tr>\r\n";
						while ($row = $result->fetch_assoc()) {
							$lid     = $row['ID'];
							$duedate = $row['duedate'];
							$amount  = $row['amount'];
							list($y,$m,$d) = explode("-",$duedate);
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$d-$m-$y</td>";
							print "	<td align=\"right\">$amount</td>\n";
							print "</tr>\n";
							$plan .= "<tr>\r\n";
							$plan .= "<td>$d-$m-$y</td>\r\n";
							$plan .= "<td align=\"right\">$amount</td>\r\n";
							$plan .= "</tr>\r\n";
						}
						print "<tr>\n";
						print "	<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
						$plan .= "</table>";
					}
				
					if ($_POST['posted']) {

						$subject = strip_tags($_POST['subject']);
						$body    = strip_tags($_POST['body']);

						if (!$subject) {
							$ersubject = "Enter A Subject"; $e++;
						}

						if (!$body) {
							$erbody = "Enter A Message"; $e++;
						}
						
						if (!$e) {

							// send email
							$body    = nl2br($body);
							$search  = array("[[custname]]","[[plan]]");
							$replace = array($custname,$plan);
							$body    = str_replace($search,$replace,$body);

							$from     = "accounts@dephna.com";
							$fromname = "Dephna Accounts";
							$now = time();

							// email message
							include("email-header.php");
							$message .= "<p>$body</p>\n";
							include("email-footer.php");

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							// send email(s)
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
								$resultMessage .= "Email Sent to $aemails<br>";
								// mark as sent
								$sql = "UPDATE `pplans_headers` SET `status` = '1' WHERE ID = '$ppid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Plan Marked As Sent<br>\n";
								} else {
									$resultMessage .= "Plan Could NOT Be Marked As Sent<br>\n";
								}

								$body = addslashes($body);
								$aemails = str_replace(",","<br>",$aemails);
								$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Plan Emailed To:<br />$aemails','$body')");

							} catch (Exception $e) {
								$body = addslashes($body);
								$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
								$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','One or more addresses FAILED<br>Mailer Error: {$mail->ErrorInfo}','$body')");
							}
						}

					} else {
						// get email template
						$sql = "SELECT * FROM mailouts WHERE mref='payplansend'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];
						}
					}

					if (!$resultMessage OR $e) {
								
						print "<form name=\"addform\" method=\"post\" action=\"pplans-email.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Subject</b></td>";
						print "<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
						if ($ersubject) { print "<br /><span class=\"redtext\">$ersubject</span>"; }
						print "</td>";
						print "</tr>\n";

						print "</table>\n";

						print "<p align=\"center\">Do not change the [[fields]] below, they will be replaced automatically.<br>Email templates can be updated in the Mailouts section.</p>\n";

						if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
						print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
						print "</form>\n";

					}

				} else {
					$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br>" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page Called Without ppid<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		if ($ppid) {
			print "<a href=\"pplans-view.php?ppid=$ppid\">View Plan</a> | ";
			print "<a href=\"pplans-addedit.php?ppid=$ppid\">Update Plan</a> | ";
		}
		print "<a href=\"ad-list.php?$qString\">Debtors</a> | ";
		print "<a href=\"pplans-list.php?$qString\">Plans</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
