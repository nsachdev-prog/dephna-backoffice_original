<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "Email";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	include('support-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	// get filtering if any
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
	if ($_REQUEST['eid']) { $eid = $_REQUEST['eid']; }
	if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
	if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
	if ($_REQUEST['st'])  { $st  = $_REQUEST['st'];  }
	if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
	$qString ="se=$se&amp;dp=$dp&amp;st=$st&amp;al=$al";
	print "<p align=\"center\" class=\"larger\">Delete Email</p>\n";

	if ($accessRights[$page] > 1) {

		if ($tid AND $eid) {

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

					if ($pending) { $pendingtxt = "Yes"; } else { $pendingtxt = "No"; }
					if (!$compname) { $compname = "Unknown"; }
					if (!$assignedto) { $assigned = "Unassigned"; }
					
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

					if ($_REQUEST['delete']) {

                        $failed = 0;
						// get email attachments
						$sql = "SELECT ID, attname, attfilename FROM support_attachments WHERE eid = '$eid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$aid         = $row['ID'];
								$attname     = $row['attname'];
								$attfilename = $row['attfilename'];
								$filename = "../../dis2_db/sup/$attfilename";
								if (!unlink($filename)) {
									$resultMessage .= "Attachment $attname Could NOT Be Deleted.<br>\n";
									$failed++;
								} else {
									$resultMessage .= "Attachment $attname Deleted.<br>\n";
									$sql = "DELETE FROM support_attachments WHERE ID = '$aid'";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Attachment Record Deleted.<br>\n";
									} else {
										$resultMessage .= "Attachment Record($attname) Could NOT Be Deleted.<br>\n";
										$failed++;
									}
								}
							}
						}
						if (!$failed) {

							$sql = "DELETE FROM support_notes WHERE eid = '$eid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Notes Deleted<br>\n";
							} else {
								$resultMessage .= "Notes Could NOT Be Deleted<br>\n";
							}

							$sql = "DELETE FROM support_emails WHERE ID = '$eid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Email Deleted<br>\n";
                                $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref Email $tid/$eid','$page','$now')");
							} else {
								$resultMessage .= "Email Could NOT Be Deleted.<br>\n";
								$failed++;
							}

						}

                        // update replied status of ticket in case it's changed
                        $sql = "SELECT origin FROM support_emails WHERE tid = '$tid' ORDER BY edatetime DESC LIMIT 0,1";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                            $row = $result->fetch_assoc();
							$origin = $row['origin'];
                            if ($origin == "Dephna") {
                                $replied = 1;
                            } else {
                                $replied = 0;
                            }
                        } else {
                            $replied = 1;
                        }
                        $sql = "UPDATE support_tickets SET replied = '$replied' WHERE ID = '$tid'";
                        $dbcnx->query($sql);

					} else {

					    // get original email from and body
						$sql = "SELECT * FROM support_emails WHERE ID = '$eid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {

							?>
							<style>
							.sub-tbl {
								border-bottom: 1px solid #fff;
								width: 98%;
							}
							.sup-sub {
								padding: 4px;
								background-color: #eee;
								color: #000;
							}
							.sup-body {
								padding: 4px;
								background-color: #fff;
								border: 1px solid #eee;
								color: #333;
							}
							</style>
							<?php

							$row  = $result->fetch_assoc();
							$tos  = $row['froms'];
							$body = $row['body'];

							print "<table class=\"sub-tbl\" align=\"center\" cellspacing=\"0\" cellpadding=\"0\">\n";
							print "<tr><td class=\"sup-sub\" align=\"left\">To: $tos</td></tr>\n";
							print "<tr><td class=\"sup-body\">$body</td></tr>\n";
							print "</table>\n";

							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"support-email-delete.php?&amp;tid=$tid&amp;eid=$eid&amp;delete=1&amp;custref=$custref&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">No DON'T Delete</a>";
							print "</p>\n";

						} else {

						}

					}
				
				} else {
					
				}
				$dbcnx->close();

			} else {
				$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page call without ids\n";
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
?>
