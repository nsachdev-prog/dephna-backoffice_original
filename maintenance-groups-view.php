<?php
$page = "Maintenance";
$subpage = "Groups";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MAINTENANCE - GROUPS &amp; TYPES</p>\n";
	include('maintenance-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM mnt_groups";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">Groups</td>\n";
				print "	<td class=\"colHead\" width=\"50px\">&nbsp;</td>\n";
				print "	<td class=\"colHead\" width=\"50px\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Types</td>\n";
				print "	<td class=\"colHead\" width=\"50px\">&nbsp;</td>\n";
				print "	<td class=\"colHeadEnd\" width=\"50px\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$gid     = $row['ID'];
					$grtitle = $row['title'];
					print "<tr>\n";
					print "	<td class=\"col\">$grtitle</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td class=\"col\" align=\"center\"><a href=\"maintenance-groups-addedit.php?gid=$gid\">Edit</a></td>\n";
					} else {
						print "	<td class=\"col\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td class=\"col\" align=\"center\"><a href=\"maintenance-groups-delete.php?gid=$gid\">Delete</a></td>\n";
					} else {
						print "	<td class=\"col\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					$sql = "SELECT * FROM mnt_types WHERE gid='$gid'";
					$tresult = $dbcnx->query($sql);
					if (($tresult) && ($tresult->num_rows)) {
						$t = 0;
						while ($trow = $tresult->fetch_assoc()) {
							$tid     = $trow['ID'];
							$tytitle = $trow['title'];
							if ($t > 0) {
								print "<tr>\n";
								print "<td class=\"col\" colspan=\"3\">\n";
							}
							print "	<td class=\"col\">$tytitle</td>\n";
							if ($accessRights[$page] > 1) {
								print "	<td class=\"col\" align=\"center\"><a href=\"maintenance-types-addedit.php?gid=$gid&amp;tid=$tid\">Edit</a></td>\n";
							} else {
								print "	<td class=\"col\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "	<td class=\"colEnd\" align=\"center\"><a href=\"maintenance-types-delete.php?gid=$gid&amp;tid=$tid\">Delete</a></td>\n";
							} else {
								print "	<td class=\"colEnd\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
							}
							print "</tr>\n";
							$t++;
						}

					} else {
						print "<td class=\"colEnd\" colspan=\"3\">\n";
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td class=\"colEndGrey\" colspan=\"6\" align=\"right\"><a href=\"maintenance-types-addedit.php?gid=$gid\">Add New Type to $grtitle</a></td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Groups Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"maintenance-groups-addedit.php\">Add New Group</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Group</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
