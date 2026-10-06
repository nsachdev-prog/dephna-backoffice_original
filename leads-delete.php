<?php
$page = "Leads";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering if any
			if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
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
			print "<p align=\"center\" class=\"larger\">Delete Lead</p>\n";

			if ($fr == "arc") {
				$returnto = "leads-archive";
			} else {
				$returnto = "leads";
			}

			$delete  = $_REQUEST['delete'];
			if ($tid) {
				
				// get ticket
				$sql = "SELECT * FROM leads_tickets WHERE ID = '$tid'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$ticketno = $row['ticketno'];
					$fullname = $row['fullname'];
                    $compname = $row['compname'];
					$subject  = $row['subject'];

					print "<p align=\"center\"><b>Lead:$ticketno - $fullname - $compname<br>$subject</b></p>\n";
					
					if ($delete) {

						$failed = 0;
						// get email attachments
						$sql = "SELECT ID, attfilename FROM leads_attachments WHERE tid = '$tid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$aid         = $row['ID'];
								$attname     = $row['attname'];
								$attfilename = $row['attfilename'];
								$filename = "../../dis2_db/lds/$attfilename";
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
								$sql = "DELETE FROM leads_attachments WHERE ID = '$aid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Attachment Record Deleted.<br>\n";
								} else {
									$resultMessage .= "Attachment Record($attname) Could NOT Be Deleted.<br>\n";
									$failed++;
								}
							}
						}
						if (!$failed) {
							$sql = "DELETE FROM leads_emails WHERE tid = '$tid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Emails Deleted<br>\n";
							} else {
								$resultMessage .= "Emails Could NOT Be Deleted.<br>\n";
							}

							$sql = "DELETE FROM leads_notes WHERE tid = '$tid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Notes Deleted<br>\n";
							} else {
								$resultMessage .= "Notes Could NOT Be Deleted<br>\n";
							}

							$sql = "DELETE FROM leads_tickets WHERE ID = '$tid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Lead Deleted<br>\n";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Tkt:$ticketno $subject','$page','$now')");
							} else {
								$resultMessage .= "Lead Could NOT Be Deleted<br>\n";
							}
						}

					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"leads-delete.php?&amp;tid=$tid&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"$returnto-list.php?$qString\">No DON'T Delete</a>";
						print "</p>\n";
					}
					
				} else {
					$resultMessage = "Lead Not Found!<br />" . $dbcnx->error;
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
		print "<p align=\"center\"><a href=\"$returnto-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
