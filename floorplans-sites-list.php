<?php
$page = "Floorplans";
$subpage = "Sites";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">FLOORPLANS</p>\n";
	print "<p align=\"center\" class=\"larger\">Sites</p>\n";
	
	if ($accessRights[$page]) {
	
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$sql = "SELECT ID, title FROM sites WHERE ID != '26' AND ID != '27' ORDER BY title";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
			
				print "<table width=\"40%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$siteid = $row['ID'];
					$title  = $row['title'];
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$siteid</td>\n";
					print "	<td class=\"noAccess\">Draw</td>\n";
					print "	<td><a href=\"floorplans-units-list.php?siteid=$siteid\">Units</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "<td style=\"border-top:1px solid #999999\" colspan=\"10\" align=\"right\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Sites Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
