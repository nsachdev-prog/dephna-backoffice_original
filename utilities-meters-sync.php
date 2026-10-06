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
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Gateway</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Device ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Meter Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Result</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$gateway   = $row['gateway'];
					$deviceid  = $row['deviceid'];
					$metertype = $row['metertype'];
					$unitname  = $row['unitname'];
					$commodity = $row['commodity'];
					$custref   = $row['custref'];

					$sql = "DELETE FROM UtlCollectors
							WHERE siteid = '$siteid'
							  AND unit = '$unitname'
							  AND commodity = '$commodity'
					";
					if ($dbcnx->query($sql)) {
						$res = $dbcnx->affected_rows;
						if ($res) {
							$restext = "<span style=\"color:red\">Deleted</span>";
						} else {
							$restext = "Not Found";
						}
					} else {
						$restext = "Error";
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</a></td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$commodity</td>\n";
					print "	<td>$gateway</td>\n";
					print "	<td>$deviceid</td>\n";
					print "	<td>$metertype</td>\n";
					print "	<td align=\"center\">$restext</td>\n";
					print "</tr>\n";

				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"7\">&nbsp;</td>\n";
				print "</tr>\n";
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
