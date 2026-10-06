<?php
$page = "Fire_Alarms";
$subpage = "Points";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		print "<p align = \"center\" class=\"heading\">Alarm Points</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; $search[] = "siteid='$lo'"; }
			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
			print "<tr>\n";
			
			print "<form name=\"listform\" method=\"post\" action=\"fam-points-list.php\">\n";
			$sql = "SELECT ID, title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);
			print "	<td align=\"right\"><b>Site:</b></td>\n";
			print "	<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option></option>\n";
			while ($row = $sitesresult->fetch_assoc()) {
				$thissiteid   = $row['ID'];
				$thissitename = $row['title'];
				print "		<option value=\"$thissiteid\"";
				if ($thissiteid == $lo) {
					print " SELECTED";
				}
				print ">$thissitename</option>\n";
			}
			print "	</select></td>\n";
			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"fam-points-list.php\"><b>All</b></a></td>\n";
			print "</form>\n";
			
			print "</tr>\n";
			print "</table>\n";
			
			print "<p>&nbsp;</p>\n";
			
			$sql = "SELECT *
					FROM fam_points
					$search
					ORDER BY sitename, title";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$title    = $row['title'];
					$details  = $row['details'];
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</td>\n";
					print "	<td><a href=\"fam-points-view.php?id=$id\">$title</a></td>\n";
					print "	<td>$details</td>\n";
					print "<td align=\"center\"><a href=\"fam-points-view.php?id=$id\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"fam-points-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"fam-points-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\"><b>No Fire Alarm Points Found</b></p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($accessRights[$page] > 1) {
			print "<p align=\"center\"><a href=\"fam-points-addedit.php\">Create New Fire Alarm Point</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
