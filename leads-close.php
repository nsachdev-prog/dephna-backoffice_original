<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Leads";
$subpage = "Close";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
	if ($_REQUEST['close']) { $close = $_REQUEST['close']; }

	if ($accessRights[$page] > 1) {

		if ($tid) {

			if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
			if ($_REQUEST['st'])  { $st  = $_REQUEST['st'];  }
			if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
			if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
			if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
			if ($_REQUEST['rp'])  { $rp  = $_REQUEST['rp'];  } else { $rp = 0; }
			$qString ="se=$se&amp;st=$st&amp;al=$al&amp;fr=$fr&amp;ty=$ty&amp;rp=$rp";

			if ($ty == "Offices") {
				$heading = "OFFICE LEADS";
				$subpage = "Offices";
			} else {
				$heading = "LEADS";
				$subpage = "Leads";
			}
		
			print "<p align=\"center\" class=\"heading\">$heading</p>\n";
			include('leads-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Archive Lead</p>\n";

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				// get ticket details
				$sql = "SELECT t.*, s.username AS assigned
						FROM leads_tickets AS t
						LEFT JOIN staff AS s ON s.ID = t.assignedto
						WHERE t.ID = '$tid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					
					$row = $result->fetch_assoc();
					$ticketno   = $row['ticketno'];
					$fullname   = $row['fullname'];
					$compname   = $row['compname'];
					$subject    = $row['subject'];
					$aemails    = $row['aemails'];

					if (!$compname) { $compname = "Unknown"; }
					if (!$assignedto) { $assigned = "Unassigned"; }
					if (!$fullname) { $fullname = $aemails; }
					
					print "<tr>\n";
					print "	<td><b>Lead No</b></td>\n";
					print "	<td>$ticketno</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Name</b></td>\n";
					print "	<td>$fullname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Company</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Subject</b></td>\n";
					print "	<td>$subject</td>\n";
					print "</tr>\n";

					print "</table>\n";

				} else {
					print "<p align=\"center\">Lead Not Found!</p>\n";
				}


				if ($close) {
					
					// delete attachments
					$failed = 0;
					$sql = "SELECT ID, attname, attfilename FROM leads_attachments WHERE tid = '$tid'";
					if ($eresult = $dbcnx->query($sql) AND $eresult->num_rows) {
						while ($erow = $eresult->fetch_assoc()) {
							$aid         = $erow['ID'];
							$attname     = $erow['attname'];
							$attfilename = $erow['attfilename'];
							$filename = "../../dis2_db/lds/$attfilename";
							if (!unlink($filename)) {
								$resultMessage .= "Attachment $attname Could NOT Be Deleted.<br>\n";
								$failed++;
							} else {
								$resultMessage .= "Attachment $attname Deleted.<br>\n";
								$sql = "DELETE FROM leads_attachments WHERE ID = '$aid'";
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
						$sql = "UPDATE `leads_tickets` SET `status` = 'Closed', `pending` = '0', `flagged` = '0', `lastupd` = '$now', `updby` = '{$_SESSION['username']}' WHERE `ID` = '$tid'";
						if ($dbcnx->query($sql)) {
							$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Lead Archived...<br>$resultMessage')");
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Archived','Tkt:$ticketno $subject','$page','$now')");
							$resultMessage .= "Lead Archived.<br>\n";
						} else {
							$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Lead Could Not Be Archived....<br>$resultMessage')");
							$resultMessage .= "Lead Could Not Be Archived.<br>\n";
						}
					}

				} else {
					print "<p align=\"center\"><b>Are You Sure?</b></p>";
					print "<p align=\"center\">";
					print "	<a href=\"leads-close.php?&amp;tid=$tid&amp;close=1&amp;$qString\">Yes Archive</a>&nbsp;|&nbsp;<a href=\"leads-view.php?tid=$tid&amp;$qString\">No DON'T Archive</a>";
					print "</p>\n";
				}
				
				$dbcnx->close();

			} else {
				$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without id.\n";
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
print "<p align=\"center\"><a href=\"leads-view.php?tid=$tid&amp;$qString\">Return</a></p>\n";
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
