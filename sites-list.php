<?php
$page = "Sites";
$subpage = "Sites";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align = \"center\" class=\"heading\">SITES</p>\n";
	
	if ($accessRights[$page]) {
	
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$sql = "SELECT * FROM sites ORDER BY title";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
			
				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>IP Address</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Floors</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Training</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$title     = $row['title'];
					$ipaddress = $row['ipaddress'];
					$floors    = $row['floors'];
					$training  = $row['training'];
					if ($training == "on") { $training = "Yes"; } else { $training = "No"; }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$id</td>\n";
					print "	<td>$ipaddress</td>\n";
					print "	<td>$floors</td>\n";
					print "	<td>$training</td>\n";
					print "	<td><a href=\"sites-view.php?id=$id\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td><a href=\"sites-doors-addedit.php?siteid=$id\">Doors</a></td>\n";
					} else {
						print "	<td><span class=\"noAccess\">Doors</span></td>\n";
					}
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"sites-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"units-view.php?id=$id\">Units</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Units</span></td>\n";
					}
					if ($accessRights[$page] > 9) {
						print "	<td align=\"center\"><a href=\"sites-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";							
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print "<td style=\"border-top:1px solid #999999\" colspan=\"10\" align=\"right\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"sites-addedit.php\">Add Site</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
