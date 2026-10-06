<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Support";
$subpage = "Reopen";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	include('support-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
	if ($_REQUEST['reopen']) { $reopen = $_REQUEST['reopen']; }
	if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
	if ($_REQUEST['al'] != "All") { $al = $_REQUEST['al']; }
	$qString ="se=$se&amp;al=$al";
	print "<p align=\"center\" class=\"larger\">Reopen Ticket</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get ticket details
			$sql = "SELECT t.*, u.compname
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
				$compname   = $row['compname'];
				$subject    = $row['subject'];

				if (!$compname) { $compname = "Unknown"; }
				
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

			if ($reopen) {

				$now = time();
				$sql = "UPDATE `support_tickets` SET `status` = 'Open', `lastupd` = '$now', `updby` = '{$_SESSION['username']}' WHERE `ID` = '$tid'";
				if ($dbcnx->query($sql)) {
					$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Ticket Reopened')");
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Reopened','Tkt:$ticketno $subject','$page','$now')");
					$resultMessage .= "Ticket Marked As Reopened.<br>\n";
				} else {
					$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Ticket Could Not Be Reopened')");
					$resultMessage .= "Ticket Could Not Be Reopened.<br>\n";
				}

			} else {

				print "<p align=\"center\"><b>Are You Sure?</b></p>";
				print "<p align=\"center\">";
				print "	<a href=\"support-reopen.php?tid=$tid&amp;reopen=1&amp;$qString\">Yes Unarchive</a>&nbsp;|&nbsp;<a href=\"support-closed-list.php?$qString\">No DON'T Reopen</a>";
				print "</p>\n";

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
print "<p align=\"center\"><a href=\"support-closed-list.php?$qString\">Return</a></p>\n";
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
