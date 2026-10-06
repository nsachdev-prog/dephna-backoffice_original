<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Warnings";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM WARNINGS</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"larger\">Last 24 Hours</p>\n";
			print "<p align=\"center\" class=\"larger\">Chiller > 2&deg; Above<br>Freezer > 4&deg; Above</p>\n";

			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; }
			if ($lo) {
				$search = "AND h.siteid = '$lo'";
				$search1 = "AND siteid = '$lo'";
			} else {
				$search = "";
				$search1 = "";
			}

			$twentyfour = date("Y-m-d H:i",strtotime("-24 Hours"));

			print "<form name=\"form1\" method=\"post\" action=\"coldrooms-warnings.php\">\n";
			print "<table align=\"center\">\n";
			print "	<tr>\n";
			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT ID, title FROM sites ORDER BY title";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$thissiteid   = $row['ID'];
					$thissitename = $row['title'];
					print "		<option value=\"$thissiteid\""; if ($lo == $thissiteid) { print " SELECTED"; } print ">$thissitename</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";
			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form><br>\n";

			$sql = "SELECT h.*, u.compname
					FROM xweb_history1 AS h
					LEFT JOIN users AS u ON u.custref = h.custref
					WHERE h.xdatetime > '$twentyfour'
					  AND h.custref > '0'
					  AND ((h.setpoint < '-5' AND h.temperature > h.setpoint + '4')
					   OR  (h.setpoint > '-6' AND h.temperature > h.setpoint + '2'))
					  $search
					GROUP BY h.unitid
					ORDER BY h.sitename, h.unitname
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Setp</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Temp</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Defrost</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Door</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$unitid    = $row['unitid'];
					$unitname  = $row['unitname'];
					$compname  = $row['compname'];
					$setpoint  = $row['setpoint'];
					$temperature = $row['temperature'];
					$dooropen    = $row['dooropen'];
					$defrost     = $row['defrost'];
					$doorswitch  = $row['doorswitch'];

					// has if been close
					$sql = "SELECT *
							FROM xweb_history1
							WHERE xdatetime > '$twentyfour'
							  AND siteid = $siteid
							  AND unitid = $unitid
							  AND ((setpoint < '-5' AND temperature < setpoint + '4')
							   OR  (setpoint > '-6' AND temperature < setpoint + '2'))
							  $search1
					";
					if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {

						// Okay it's been close
						// there must be a better way of doing this!!
						
					} else {

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print " <td>$sitename</td>\n";
						print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
						print " <td>$compname</td>\n";
						print " <td align=\"right\">$setpoint</td>\n";
						print " <td align=\"right\">$temperature</td>\n";
						if ($defrost) {
							print " <td><span class=\"redtext\">ON</span></td>\n";
						} else {
							print " <td>OFF</td>";
						}
						if ($doorswitch) {
							print " <td><span class=\"redtext\">OPEN</span></td>\n";
						} else {
							print " <td>Closed</td>";
						}
						print "</tr>\n";

					}

				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"7\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found</p>" . $dbcnx->error;
			}


			print "<p align=\"center\" class=\"larger\">Call Out Delay > 2</p>\n";

			$sql = "SELECT h.*, u.compname
					FROM xweb_history1 AS h
					LEFT JOIN users AS u ON u.custref = h.custref
					WHERE h.custref > '0'
					  AND h.CODelay > '2'
					  AND h.xdatetime > '$twentyfour'
					ORDER BY h.xdatetime DESC
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
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Defrost</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Door</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$unitid    = $row['unitid'];
					$unitname  = $row['unitname'];
					$compname  = $row['compname'];
					$setpoint  = $row['setpoint'];
					$temperature = $row['temperature'];
					$dooropen    = $row['dooropen'];
					$defrost     = $row['defrost'];
					$doorswitch  = $row['doorswitch'];
					$xdatetime   = $row['xdatetime'];

					$xdatetime = date("d-m-Y H:i", strtotime($xdatetime));

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print " <td>$xdatetime</td>\n";
					print " <td>$sitename</td>\n";
					print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print " <td>$compname</td>\n";
					print " <td align=\"right\">$setpoint</td>\n";
					print " <td align=\"right\">$temperature</td>\n";
					if ($defrost) {
						print " <td><span class=\"redtext\">ON</span></td>\n";
					} else {
						print " <td>OFF</td>";
					}
					if ($doorswitch) {
						print " <td><span class=\"redtext\">OPEN</span></td>\n";
					} else {
						print " <td>Closed</td>";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"8\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";


			} else {
				print "<p align=\"center\">Nothing Found</p>" . $dbcnx->error;
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
