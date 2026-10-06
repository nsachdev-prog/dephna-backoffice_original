<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Accounts";
$subpage = "Occupancy";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	// print headings
    print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";
    print "<p align=\"center\" class=\"larger\">Occupancy by Month</p>\n";
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering if any and build search array
			if ($_REQUEST['ye'] != "All") { $ye = $_REQUEST['ye']; } # Year
			if (!$ye) { $ye = date("Y"); }

			// filter form
			print "<form name=\"yearform\" method=\"post\" action=\"acc-reports-occupancy.php\">\n";
			print "<table align=\"center\">\n";
			print "	<tr>\n";

			// get available years
			$sql = "SELECT DISTINCT year FROM inv_headers ORDER BY year DESC";
			$yresult = $dbcnx->query($sql);
			if (($yresult) && ($yresult->num_rows)) {
				print "		<td align=\"right\"><b>Year</b></td>\n";
				print "		<td><select name=\"ye\" class=\"element_date\" onchange=\"document.yearform.submit();\">\n";
				while ($yrow = $yresult->fetch_assoc()) {
					$year = $yrow['year'];
					print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "	</tr>\n";
			print "</table>\n";

			print "</form>\n";

			print "<p align=\"center\" class=\"larger\"><b>$ye</b></p>\n";

			// get sites
			$sql = "SELECT ID, title FROM sites WHERE ID != '26' AND ID != '27' AND ID != '36' ORDER BY title";
			if (($sresult = $dbcnx->query($sql)) && ($sresult->num_rows)) {
				$months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");
				print "<table width=\"50%\" align=\"center\">\n";
				while ($srow = $sresult->fetch_assoc()) {
					$siteid   = $srow['ID'];
					$sitename = $srow['title'];

					// get units per site
					$sql = "SELECT ID FROM units WHERE siteid = '$siteid' AND unittype != 'Car Parking'";
					if (($uresult = $dbcnx->query($sql)) && ($uresult->num_rows)) {
						$unitsqty = $uresult->num_rows;
					} else {
						$unitsqty = 0;
					}

					print "<tr>\n";
					print "	<td colspan=\"4\" class=\"larger\">$sitename</td>\n";
					print "</tr>\n";

					// get units occupied in given year group by site
					$sql = "SELECT h.month, COUNT(*) AS occqty
							FROM inv_lines AS l
							LEFT JOIN inv_headers AS h ON h.ID = l.hid
							WHERE h.year = '$ye'
							  AND l.location = '$sitename'
							  AND (l.unittype = 'Office'
							   OR  l.unittype = 'Office V'
							   OR  l.unittype = 'Kitchen'
							   OR  l.unittype = 'Kitchen V'
							   OR  l.unittype = 'Storage'
							   OR  l.unittype = 'Cold Room'
							   OR  l.unittype = 'Flat'
							   OR  l.unittype = 'Studio'
							   OR  l.unittype = 'Shop')
							GROUP BY h.month
							ORDER BY h.month
					";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {

						print "<tr>\n";
						print "	<td><b>Month</b></td>\n";
						print "	<td align=\"right\"><b>Total</b></td>\n";
						print "	<td align=\"right\"><b>Occupied</b></td>\n";
						print "	<td align=\"right\"><b>Percent</b></td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$month  = $row['month'];
							$occqty = $row['occqty'];

							if ($siteid == 2 OR $siteid == 4) {
								$occqty = $unitsqty;
							}
							if ($occqty > $unitsqty) { $occqty = $unitsqty; }

							$percent = $occqty*100/$unitsqty;
							
							print "<tr>\n";
							print "	<td>{$months[$month]}</td>\n";
							print "	<td align=\"right\">$unitsqty</td>\n";
							print "	<td align=\"right\">$occqty</td>\n";
							print "	<td align=\"right\">" . number_format($percent,2) . "</td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "	<td colspan=\"4\">&nbsp;</td>\n";
						print "</tr>\n";
					} else {
						print "<tr>\n";
						print "	<td colspan=\"4\">&nbsp;</td>\n";
						print "</tr>\n";
					}
				}
				print "</table>\n";

			} else {
				$resultMessage = "Sites Not Found<br />" . $dbcnx->error;
			}
			$dbcnx->close();

		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "<p align=\"center\"><a href=\"acc-periods-list.php\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
?>
