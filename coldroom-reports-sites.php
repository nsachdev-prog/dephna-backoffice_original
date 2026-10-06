<?php
$page = "Coldroom_Reports";
$subpage = "List";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			if (!$siteid) { $siteid = 28; }

			print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";

			// get site title
			$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$sitename = $row['title'];
			} else {
				$resultMessage = "Site Not Found<br />" . $dbcnx->error;
			}

			print "<p align=\"center\"class=\"larger\">$sitename</p>\n";

			$sql = "SELECT * FROM units WHERE siteid = '$siteid' AND unittype = 'Cold Room' ORDER BY title";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Latest Report</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Engineer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"70px\">&nbsp;</td>\n";
				print "</tr>\n";

				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print ".r0:hover { background-color: #e8ff80; }\n";
				print ".r1:hover { background-color: #e8ff80; }\n";
				print ".notviewed { font-weight: bold; }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$unitid   = $row['ID'];
					$unitname = $row['title'];
					list($junk,$unitname) = explode(" ",$unitname);

					$sql = "SELECT ID, sdate, `type`, engname
							FROM cr_reports_headers
							WHERE siteid = $siteid
							  AND unitid = $unitid
							ORDER BY sdate DESC
							LIMIT 0,1
					";
					if (($uresult = $dbcnx->query($sql)) && ($uresult->num_rows)) {
						$urow = $uresult->fetch_assoc();
						$id      = $urow['ID'];
						$sdate   = $urow['sdate'];
						$type    = $urow['type'];
						$engname = $urow['engname'];
						list($y,$m,$d) = explode("-",$sdate);
						$sdate = "$d-$m-$y";
					} else {
						$sdate = "None";
					}

					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$sdate</td>\n";
					if ($sdate != "None") {
						print "	<td>$type</td>\n";
						print "	<td>$engname</td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-view.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">View</a></td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-pdf.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\" target=\"_blank\">Pdf</a></td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-email.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">Email</a></td>\n";
						print "	<td align=\"center\"><a href=\"coldroom-reports-list.php?siteid=$siteid&amp;unitid=$unitid\">List All</a></td>\n";

					} else {
						print "	<td>&nbsp;</td>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td align=\"center\" class=\"noAccess\">View</td>\n";
						print "	<td align=\"center\" class=\"noAccess\">Pdf</td>\n";
						print "	<td align=\"center\" class=\"noAccess\">Email</td>\n";
						print "	<td align=\"center\" class=\"noAccess\">List All</td>\n";
					}
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"coldroom-reports-addedit.php?siteid=$siteid&amp;unitid=$unitid\">New Report</a></td>\n";
					} else {
						print "	<td align=\"center\" class=\"noAccess\">New Report</td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\">". $result->num_rows ."</td>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"8\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";
			} else {
				print "<p align=\"center\">No Cold Rooms Found!</p>\n";
			}

			/* print "<p align=\"center\">";
			if ($accessRights[$page] > 1) {
				print "<a href=\"coldroom-reports-yearly.php\">Generate Yearly Reports for All Sites</a>";
			}
			print "</p>\n"; */

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
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
