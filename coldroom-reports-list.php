<?php
$page = "Coldroom_Reports";
$subpage = "List";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
$id = $_REQUEST['id'];
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];

            print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"heading\">Reports</p>\n";

            if ($siteid AND $unitid) {

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

				$sql = "SELECT * FROM cr_reports_headers WHERE siteid = '$siteid' AND unitid = '$unitid' ORDER BY sdate DESC";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Report No</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Type</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Engineer</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
					print "</tr>\n";

					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print ".r0:hover { background-color: #e8ff80; }\n";
					print ".r1:hover { background-color: #e8ff80; }\n";
					print ".notviewed { font-weight: bold; }\n";
					print "</style>\n";

					while ($row = $result->fetch_assoc()) {
						$id       = $row['ID'];
						$type     = $row['type'];
						$sdate    = $row['sdate'];
						$reportno = $row['reportno'];
						$engname  = $row['engname'];

						list($y,$m,$d) = explode("-",$sdate);
						
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$y-$m-$d</td>\n";
						print "	<td>$reportno</td>\n";
						print "	<td>$type</td>\n";
						print "	<td>$engname</td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-view.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">View</a></td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-pdf.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\" target=\"_blank\">Pdf</a></td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-email.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">Email</a></td>\n";
						if ($accessRights[$page] > 1) {
							print "	<td align=\"center\"><a href=\"coldroom-reports-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">Edit</a></td>\n";
						} else {
							print "	<td align=\"center\" class=\"noAccess\">Edit</td>\n";
						}
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\"><a href=\"coldroom-reports-delete.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">Delete</a></td>\n";
						} else {
							print "	<td align=\"center\" class=\"noAccess\">Delete</td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
					print " <td style=\"border-top:1px solid #666\" colspan=\"9\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";
				} else {
					print "<p align=\"center\">No Reports Found!</p>\n";
				}
				if ($accessRights[$page] > 1) {
					print "	<p align=\"center\"><a href=\"coldroom-reports-addedit.php?siteid=$siteid&amp;unitid=$unitid\">Add New Report</a></p>\n";
				} else {
					print "	<p align=\"center\" class=\"noAccess\">Add New Report</p>\n";
				}

			} else {
				print "<p align=\"center\">Page called without either siteid or unitid</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldroom-reports-sites.php?siteid=$siteid\">Return</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
