<?php
$page = "Reporting";
$subpage = "Converted";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">$subpage</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			$years = array();

			print "	<p align=\"center\"><b>New Customers each year for each Unittype</b></p>\n";

			$sql = "SELECT custref FROM users ORDER BY compname";
			if ($cresult = $dbcnx->query($sql) AND $cresult->num_rows) {
				while ($crow = $cresult->fetch_assoc()) {
					$custref = $crow['custref'];

					$sql = "SELECT ID, `year`
							FROM inv_headers
							WHERE custref = '$custref' AND `day` = '01'
							ORDER BY `year`
							LIMIT 0,1
					";
					if ($iresult = $dbcnx->query($sql) AND $iresult->num_rows) {
						$irow = $iresult->fetch_assoc();
						$hid  = $irow['ID'];
						$year = $irow['year'];

						$sql = "SELECT unittype
							FROM inv_lines
							WHERE hid = '$hid'
						";
						if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
							while ($lrow = $lresult->fetch_assoc()) {
								$unittype = $lrow['unittype'];
								$years[$year][$unittype]++;
							}

						}

					}

				}

				print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"2\">\n";

				ksort($years);
				
				foreach($years AS $year => $unittypes) {

					if ($year > 2010) {

						print "<tr>\n";
						print "	<td colspan=\"2\"><p><b>$year</b></p></td>\n";
						print "</tr>\n";

						ksort($unittypes);

						foreach($unittypes AS $unittype => $value) {
							
							print "<tr>\n";
							print "	<td>$unittype</td>\n";
							print "	<td>$value</td>\n";
							print "</tr>\n";

						}

					}

				}
				
				print "</table>\n";

			} else {
				print "	<p align=\"center\">No Customers Found<br>" . $dbcnx->error . "</p>\n";
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

print "</td>\n";
include('footer.php');

?>
