<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "Close All";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	include 'support-submenu.php';
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($_REQUEST['ty'])  { $ty = $_REQUEST['ty']; }
	$qString = "ty=$ty";
	print "<p align=\"center\" class=\"larger\">Close All Open Camilla Tickets</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($ty == "Camilla") {

				if ($_GET['closeall']) {

					$sql = "SELECT `ID`, `ticketno`, `subject` FROM `support_tickets` WHERE `status` = 'Open' AND `type` = 'Camilla'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						while ($row = $result->fetch_assoc()) {
							$tid      = $row['ID'];
							$ticketno = $row['ticketno'];
							$subject  = $row['subject'];
							$resultMessage .= "Ticket: $ticketno Subject: $subject<br>\n";
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
											$resultMessage .= "<span class=\"redtext\">Attachment $attname Could NOT Be Deleted.</span><br>\n";
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
											$resultMessage .= "<span class=\"redtext\">Attachment Record($attname) Could NOT Be Deleted.</span><br>\n";
											$failed++;
										}
									}
								}
							}
							if (!$failed) {
								$now = time();
								$sql = "UPDATE `support_tickets` SET `status` = 'Closed', `pending` = '0', `flagged` = '0', `lastupd` = '$now', `updby` = '{$_SESSION['username']}' WHERE `ID` = '$tid'";
								if ($dbcnx->query($sql)) {
									$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Ticket Closed')");
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Closed','Tkt:$ticketno $subject','$page','$now')");
									$resultMessage .= "Ticket Marked As Closed.<br>\n";
								} else {
									$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Ticket Could Not Be Closed')");
									$resultMessage .= "<span class=\"redtext\">Ticket Could Not Be Closed.</span><br>\n";
								}
							} else {
								$resultMessage .= "<span class=\"redtext\">Ticket Could Not Be Closed.</span><br>\n";
							}
						}
					} else {
						$resultMessage .= "<span class=\"redtext\">No Tickets Found to Delete.</span><br>\n";
					}
				} else {
					print "<p align=\"center\">Are you sure you want to CLOSE ALL open Camilla Tickets?</p>\n";
					print "<p align=\"center\"><a href=\"support-close-all.php?$qString&closeall=1\">Yes Close All</a> | <a href=\"support-list.php?$qString\">NO DON'T CLOSE</a></p>\n";
				}

			} else {
				$resultMessage .= "<b>Invalid Type. Not Camilla</b>\n";
			}
			
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>\n";
	}
} else {
	include 'loggedout.php';
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"support-list.php?$qString\">Return</a></p>\n";
print "</td>\n";
include 'footer.php';
?>
