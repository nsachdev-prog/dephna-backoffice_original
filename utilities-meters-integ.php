<?php
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Meters - Delete From Manual</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT m.*, g.title AS gateway, t.title AS metertype, s.title AS sitename
					FROM UtlMeters AS m
					LEFT JOIN UtlGateways AS g ON g.ID = m.gatewayid
					LEFT JOIN UtlTypes AS t ON t.ID = m.typeid
					LEFT JOIN sites AS s ON s.ID = m.siteid
					ORDER BY sitename, unitname
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$unitid    = $row['unitid'];
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$gateway   = $row['gateway'];
					$deviceid  = $row['deviceid'];
					$metertype = $row['metertype'];
					$unitname  = $row['unitname'];
					$commodity = $row['commodity'];
					$custref   = $row['custref'];

					$sql = "SELECT ID, unittype, title FROM units WHERE ID = '$unitid' ";
					if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
						$urow = $uresult->fetch_assoc();
						$unittype  = $urow['unittype'];
						$title     = $urow['title'];
						list($pre,$title) = explode(" ",$title);
					} else {
                        $unittype  = "Not Found";
						$title     = "Not Found";
                    }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</a></td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$title</td>\n";
					print "	<td>$unittype</td>\n";
					print "	<td>$commodity</td>\n";
					print "</tr>\n";

				}
				
				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
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
