<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $six = date("Y-m-d H:i",strtotime("-6 Minutes"));

			print "<p align=\"center\"><span class=\"larger\">More Than 3&deg; Above Setpoint</p>\n";

			$sql = "SELECT h.*, u.compname
					FROM xweb_history AS h
                    LEFT JOIN users AS u ON u.custref = h.custref
					WHERE ABS(h.setpoint - h.temperature) > '3'
                      AND h.xdatetime > '$six'
					ORDER BY h.sitename, h.unitname, h.xdatetime DESC
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Setp</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Temp</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$datetime  = $row['xdatetime'];
                    $siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$unitid    = $row['unitid'];
					$unitname  = $row['unitname'];
					$compname  = $row['compname'];
                    $setpoint  = $row['setpoint'];
                    $temperature = $row['temperature'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print " <td>$datetime</td>\n";
					print " <td>$sitename</td>\n";
					print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print " <td>$compname</td>\n";
					print " <td align=\"right\">$setpoint</td>\n";
                    print " <td align=\"right\">$temperature</td>\n";
					print "</tr>\n";

				}
				print "<tr>\n";
				print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found</p>";
			}

            print "<p align=\"center\"><span class=\"larger\">More Than 3&deg; Below Setpoint</p>\n";

			$sql = "SELECT h.*, u.compname
					FROM xweb_history AS h
                    LEFT JOIN users AS u ON u.custref = h.custref
					WHERE ABS(h.setpoint - h.temperature) < '-3'
                      AND h.xdatetime > '$six'
					ORDER BY h.sitename, h.unitname, h.xdatetime DESC
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Setp</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Temp</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$datetime  = $row['xdatetime'];
                    $siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$unitid    = $row['unitid'];
					$unitname  = $row['unitname'];
					$compname  = $row['compname'];
                    $setpoint  = $row['setpoint'];
                    $temperature = $row['temperature'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print " <td>$datetime</td>\n";
					print " <td>$sitename</td>\n";
					print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print " <td>$compname</td>\n";
					print " <td align=\"right\">$setpoint</td>\n";
                    print " <td align=\"right\">$temperature</td>\n";
					print "</tr>\n";

				}
				print "<tr>\n";
				print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found</p>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
		}

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
