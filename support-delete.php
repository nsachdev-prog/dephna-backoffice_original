<?php
$page = "Support";
$subpage = "Delete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
		include('support-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"larger\">Delete Ticket</p>\n";

			// get filtering if any
			if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
			if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
			if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
			if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
			if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
			if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  } else { $ty = "Support"; }
			$qString ="se=$se&amp;dp=$dp&amp;fr=$fr&amp;al=$al&amp;ty=$ty";
			if ($fr == "cl") { $returnprog = "closed-list"; } else { $returnprog = "list"; }
			$delete  = $_REQUEST['delete'];
			if ($tid) {
				
				// get ticket
				$sql = "SELECT * FROM support_tickets WHERE ID = '$tid'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$ticketno = $row['ticketno'];
					$custref  = $row['custref'];
					$subject  = $row['subject'];

					print "<p align=\"center\"><b>DST$ticketno - DP$custref<br>$subject</b></p>\n";
					
					if ($delete) {

						$failed = 0;
						// get email attachments
						$sql = "SELECT ID, attfilename FROM support_attachments WHERE tid = '$tid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$aid         = $row['ID'];
								$attname     = $row['attname'];
								$attfilename = $row['attfilename'];
								$filename = "../../dis2_db/sup/$attfilename";
								if (is_file($filename)) {
									if (!unlink($filename)) {
										$resultMessage .= "Attachment $attname Could NOT Be Deleted.<br>\n";
										$failed++;
									} else {
										$resultMessage .= "Attachment $attname Deleted.<br>\n";
									}
								} else {
									$resultMessage .= "Attachment $attfilename NOT FOUND.<br>\n";
								}
								$sql = "DELETE FROM support_attachments WHERE ID = '$aid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Attachment Record Deleted.<br>\n";
								} else {
									$resultMessage .= "Attachment Record($attname) Could NOT Be Deleted.<br>\n";
									$failed++;
								}
							}
						}
						if (!$failed) {
							$sql = "DELETE FROM support_emails WHERE tid = '$tid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Emails Deleted<br>\n";
							} else {
								$resultMessage .= "Emails Could NOT Be Deleted.<br>\n";
							}

							$sql = "DELETE FROM support_notes WHERE tid = '$tid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Notes Deleted<br>\n";
							} else {
								$resultMessage .= "Notes Could NOT Be Deleted<br>\n";
							}

							$sql = "DELETE FROM support_tickets WHERE ID = '$tid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Ticket Deleted<br>\n";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Tkt:$ticketno $subject','$page','$now')");
							} else {
								$resultMessage .= "Ticket Could NOT Be Deleted<br>\n";
							}
						}

					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"support-delete.php?&amp;tid=$tid&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"support-$returnprog.php?$qString\">No DON'T Delete</a>";
						print "</p>\n";
					}
					
				} else {
					$resultMessage = "Ticket Not Found!<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without ID<br />";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"support-$returnprog.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
