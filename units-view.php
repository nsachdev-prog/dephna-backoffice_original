<?php
$page = "Sites";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
$id = $_REQUEST['id'];
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				// get site title
				$sql = "SELECT title FROM sites WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				print "<p align=\"center\"class=\"heading\">SITES</p>\n";
				print "<p align=\"center\"class=\"larger\">Units - $sitetitle</p>\n";

				$sql = "SELECT * FROM units WHERE siteid = '$id' ORDER BY title";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>ID</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Unit Type</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Status</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Floor</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Air-con</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Windows</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Lock ID</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
					print "</tr>\n";

					while ($row = $result->fetch_assoc()) {
						$unitid   = $row['ID'];
						$title    = $row['title'];
						$unittype = $row['unittype'];
						$status   = $row['status'];
						$floor    = $row['floor'];
						$aircon   = $row['aircon'];
						$windows  = $row['windows'];
						$lockid   = $row['lockid'];
						$fpwidth  = $row['fpwidth'];
						$fpheight = $row['fpheight'];
						if ($floor == 0) { $floor = "G"; }
						if ($aircon) { $aircon = "Yes"; } else { $aircon = "&nbsp;"; }
						if ($windows) { $windows = "Yes"; } else { $windows = "&nbsp;"; }
						if ($lockid == 0) { $lockid = "&nbsp;"; }
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$unitid</td>\n";
						print "	<td>$title</td>\n";
						print "	<td>$unittype</td>\n";
						print "	<td>$status</td>\n";
						print "	<td>$floor</td>\n";
						print "	<td>$aircon</td>\n";
						print "	<td>$windows</td>\n";
						print "	<td>$lockid</td>\n";
						if ($accessRights[$page] > 1) {
							print "	<td align=\"center\"><a href=\"units-addedit.php?id=$id&amp;unitid=$unitid\">Edit</a></td>\n";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						}
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\"><a href=\"units-delete.php?id=$unitid\">Delete</a></td>\n";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
	                print " <td style=\"border-top:1px solid #666\" colspan=\"19\">&nbsp;</td>\n";
	                print "</tr>\n";

					print "</table>\n";
				} else {
					print "<p align=\"center\">No Units Added!</p>\n";
				}

			} else {
				$resultMessage = "Page called without site id";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"sites-list.php\">Sites</a>&nbsp;|&nbsp;<a href=\"units-addedit.php?id=$id\">Add Unit</a></p>\n";
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
