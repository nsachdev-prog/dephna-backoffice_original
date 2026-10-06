<?php
$page = "Utilities";
$subpage = "Gateways";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Gateways</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT g.*, s.title AS sitename
					FROM UtlGateways AS g
                    LEFT JOIN sites AS s ON s.ID = g.siteid
					ORDER BY sitename, g.ipaddress, g.port
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>IP Address</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Port</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Mac Address</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Location</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
                print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "</tr>\n";

				// row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$sitename  = $row['sitename'];
                    $title     = $row['title'];
					$ipaddress = $row['ipaddress'];
					$port      = $row['port'];
					$mac       = $row['mac'];
					$location  = $row['location'];

					if (strlen($location) > 20) { $location = substr($location, 0, 20); }

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</a></td>\n";
                    print "	<td>$title</a></td>\n";
					print "	<td>$id</td>\n";
					print "	<td>$ipaddress</td>\n";
					print "	<td>$port</td>\n";
					print "	<td>$mac</td>\n";
					print "	<td>$location</td>\n";
					print "	<td align=\"center\"><a href=\"utilities-gateways-ping.php?id=$id\">Ping</a></td>\n";
                    print "	<td align=\"center\"><a href=\"utilities-gateways-modbus.php?id=$id\">Modbus</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"utilities-gateways-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"utilities-gateways-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"11\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"utilities-gateways-addedit.php\">Add New Gateway</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Server</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
