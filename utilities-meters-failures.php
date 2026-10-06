<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Failures";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	print "<div id=\"LoadingOnClick\">\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Failures</p>\n";
    print "<p align=\"center\">Meters that failed to import last night.</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT f.*, g.title AS gateway, t.title AS metertype, s.title AS sitename
					FROM UtlFailures AS f
					LEFT JOIN UtlGateways AS g ON g.ID = f.gatewayid
					LEFT JOIN UtlTypes AS t ON t.ID = f.typeid
					LEFT JOIN sites AS s ON s.ID = f.siteid
					ORDER BY sitename, unitname, xdatetime
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

				$lastsitename = "";
				$totalfailures = $result->num_rows;

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
                    $xdatetime = $row['xdatetime'];
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$gateway   = $row['gateway'];
					$deviceid  = $row['deviceid'];
					$metertype = $row['metertype'];
					$unitname  = $row['unitname'];
					$commodity = $row['commodity'];
					$reason    = $row['reason'];

                    $date = date_create($xdatetime);
                    $xdatetime = date_format($date, 'd-m-Y H:i:s');

					if ($sitename != $lastsitename) {
						if ($lastsitename) {
							print "<tr>\n";
							print " <td style=\"border-top:1px solid #666\" colspan=\"7\"><b>Total: $sitetotal</b></td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print " <td class=\"larger\" colspan=\"7\"><br>$sitename<br></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Date Time</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Gateway</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Device ID</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Meter Type</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Reason</b></td>\n";
						print "</tr>\n";
                        $lastsitename = $sitename;
						$sitetotal = 0;
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$xdatetime</td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$commodity</td>\n";
					print "	<td>$gateway</td>\n";
					print "	<td>$deviceid</td>\n";
					print "	<td>$metertype</td>\n";
					print "	<td>$reason</td>\n";
					print "</tr>\n";

					$sitetotal++;

				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"7\"><b>Total: $sitetotal</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print " <td colspan=\"7\"><b>All Sites $totalfailures</b></td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">No Failures.</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Wait! Collecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
