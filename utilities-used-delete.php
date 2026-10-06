<?php
$page = "Utilities";
$subpage = "Imported";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	# print heading
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	if ($accessRights[$page] > 2) {
		# print submenu
		include('utilities-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Delete Reading</p>\n";

		$uid     = $_REQUEST['uid'];
		$delete  = $_REQUEST['delete'];
		$comm    = $_REQUEST['comm'];
		$site    = $_REQUEST['site'];
		$type    = $_REQUEST['type'];
		$comm    = $_REQUEST['comm'];
		$fmonth  = $_REQUEST['fmonth'];
		$fyear   = $_REQUEST['fyear'];

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$result = $dbcnx->query("SELECT * FROM UtlUsedNew WHERE ID = '$uid'");
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$sitename  = $row['sitename'];
				$custref   = $row['custref'];
				$unit      = $row['unit'];
				$commodity = $row['commodity'];
				$meter     = $row['meter'];
				$previous  = $row['previous'];
				$used      = $row['used'];
				$rate      = $row['rate'];
				$charge    = $row['charge'];

				$custresult = $dbcnx->query("SELECT compname FROM users WHERE custref='$custref'");
				$custrow  = $custresult->fetch_assoc();
				$compname = $custrow['compname'];

				if ($delete) {
					$sql = "DELETE FROM UtlUsedNew WHERE ID='$uid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage .= "Charge Successfully Deleted<br />";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref {$unitPrefixes[$sitename]} $unit $commodity Charge','$page','$now')");
					} else {
						$resultMessage .= "Could not delete charge<br />" . $dbcnx->error;
					}

				} else {
					print "<table align=\"center\" cellspacing=\"4\" cellpadding=\"0\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Company Name:</td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Unit:</td>\n";
					print "	<td>$unit</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Commodity:</td>\n";
					print "	<td>$commodity</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Previous Reading:</td>\n";
					print "	<td>$previous</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Current Reading:</td>\n";
					print "	<td>$meter</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Rate:</td>\n";
					print "	<td>$rate</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\"><a href=\"utilities-used-delete.php?uid=$uid&amp;delete=1&amp;comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;com=$com&amp;type=$type\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"utilities-itemised-rayleigh.php?comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;com=$com&amp;type=$type\">No DON'T Delete</a></p>\n";
				}

			} else {
				print "<p align=\"center\"><strong>Record Not Found</strong></p>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"utilities-itemised-rayleigh.php?comm=$comm&amp;site=$site&amp;custref=$custref&amp;type=$type\">Return to DP$custref</a>";
		print "</p>";
		print "<p align=\"center\">";
		print "<a href=\"utilities-itemised-rayleigh.php?comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;type=$type\">Return to Top</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
