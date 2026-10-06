<?php
$page = "Sites";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
$siteid = $_REQUEST['siteid'];
$unitid = $_REQUEST['unitid'];
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($siteid AND $unitid) {

				// get site title
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				// get unit title
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unittitle = $row['title'];
					list($prefix,$unittitle) = explode(" ",$unittitle);
				} else {
					$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
				}

				print "<p align=\"center\"class=\"heading\">$sitetitle - $unittitle</p>\n";
				print "<p align=\"center\"class=\"heading\">Meters</p>\n";

				// get meters
				$sql = "SELECT *
						FROM UtlCollectors
						WHERE siteid = '$siteid' AND unit = '$unittitle'
				";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Collector</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Counter</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" colspan=\"2\" align=\"center\"><b>Last Reading</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Custref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
					print "</tr>\n";

					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";

					while ($row = $result->fetch_assoc()) {
						$meterid   = $row['ID'];
						$collector = $row['collector'];
						$counter   = $row['counter'];
						$commodity = $row['commodity'];

						// get last meter reading
						$sql = "SELECT u.meter, u.year, u.month, u.day, u.custref
								FROM UtlUsedNew AS u
								WHERE u.siteid = '$siteid'
								  AND u.unit = '$unittitle'
								  AND collector = '$collector'
								  AND `counter` = '$counter'
								  AND commodity = '$commodity'
								ORDER BY u.year DESC, u.month DESC, u.day DESC
								LIMIT 0,1
						";
						$uresult = $dbcnx->query($sql);
						if (($uresult) && ($uresult->num_rows)) {
							$urow = $uresult->fetch_assoc();
							$meter     = $urow['meter'];
							$y         = $urow['year'];
							$m         = $urow['month'];
							$d         = $urow['day'];
							$custref   = "DP".$urow['custref'];
						} else {
							$meter     = "-";
							$y         = "-";
							$m         = "-";
							$d         = "-";
							$custref   = "-";
						}
						
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$collector</td>\n";
						print "	<td>$counter</td>\n";
						print "	<td>$commodity</td>\n";
						print "	<td align=\"right\">$meter</td>\n";
						print "	<td>$d-$m-$y</td>\n";
						print "	<td>$custref</td>\n";
						/* if ($accessRights[$page] > 1) {
							print "	<td align=\"center\"><a href=\"units-meters-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;meterid=$meterid\">Edit</a></td>\n";
						} else { */
							print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						/* }
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\"><a href=\"units-meters-delete.php?siteid=$siteid&amp;unitid=$unitid&amp;meterid=$meterid\">Delete</a></td>\n";
						} else { */
							print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						/* } */
						print "</tr>\n";
					}
					print "<tr>\n";
					print " <td style=\"border-top:1px solid #666\" colspan=\"8\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";
				} else {
					print "<p align=\"center\">No Meters Found</p>\n";
				}

			} else {
				$resultMessage = "Page called without id";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"sites-list.php\">Sites</a>&nbsp;|&nbsp;<a href=\"units-view.php?id=$siteid\">Return</a></p>\n";
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
