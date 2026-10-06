<?php
$page = "Reporting";
$subpage = "Customer by Ancillary";
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

			// get filtering if any and build search array
			if ($_REQUEST['ye'] != "All") { $ye = $_REQUEST['ye']; } # Year
			if (!$ye) { $ye = date("Y"); }
			if ($_REQUEST['tys']) {
				$tys = $_REQUEST['tys'];
				$collect = 0;
			} else { 
				$collect = 1;
				$tys = array();
			}

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-revenue3.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			// get available years
			$sql = "SELECT year FROM inv_headers GROUP BY year ORDER BY year DESC";
			$yresult = $dbcnx->query($sql);
			if (($yresult) && ($yresult->num_rows)) {
				print "		<td align=\"right\"><b>Year</b></td>\n";
				print "		<td><select name=\"ye\" class=\"element_date\">\n";
				while ($yrow = $yresult->fetch_assoc()) {
					$year = $yrow['year'];
					print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "	</tr>\n";
			print "</table>\n";

			print "<fieldset style=\"border:none\">\n";
			print "<table align=\"center\">\n";
			print "<tr><td><input type=\"checkbox\" class=\"checkall\">&nbsp;All</td></tr>\n";
			$result = $dbcnx->query("SELECT title FROM ancillaries WHERE ancgroup != 'Utilities' ORDER BY title");
			if (($result) && ($result->num_rows)) {
				$rowcount = 1;
				print "<tr>\n";
				print "<td valign=\"top\">\n";
				while ($row = $result->fetch_assoc()) {
					$anctitle = $row['title'];
					if ($anctitle) {
						if ($collect) {
							$tys[$anctitle] = "on";
						}
						print "<input type=\"checkbox\" name=\"tys[$anctitle]\""; if ($tys[$anctitle]) { print " CHECKED"; } print ">&nbsp;$anctitle<br>\n";
					}
					if ($rowcount == 9) {
						print "</td>\n";
						print "<td valign=\"top\">\n";
						$rowcount = 1;
					}
					$rowcount++;
				}
				print "</td>\n";
				print "</tr>\n";
			}
			print "</table>\n";
			print "</fieldset>\n";

			foreach ($tys AS $ty => $v) {
				$cases[] = "WHEN l.details2 LIKE '%$ty%' THEN '$ty'";
				$isearch[] = "l.unittype LIKE '%$ty%'";
			}
			if ($cases) {
				$cases = "(CASE " . implode(" ", $cases) . " END)";
				if (count($isearch) > 1) {
					$isearch = "(".implode(" OR ",$isearch).")";
				} else {
					$isearch = implode(" OR ",$isearch);
				}
				$isearch = "AND $isearch";
			}

			print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";

			print "</form>\n";

			$sql = "SELECT $cases AS anc,
							u.compname, SUM(l.linetotal) AS total, COUNT(l.linetotal) AS anctotal
					FROM inv_lines AS l
					LEFT JOIN inv_headers AS h ON l.hid = h.ID
					LEFT JOIN users AS u ON u.custref = h.custref
					WHERE h.year = '$ye'
					GROUP BY u.compname, $cases
					ORDER BY u.compname
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				$lastcust = "";
				$custcount = 0;
				$grandtotal = 0;

				print "<p align=\"center\" class=\"larger\"><b>$ye</b></p>\n";

				print "<table width=\"\" align=\"center\">\n";
				
				while ($row = $result->fetch_assoc()) {
					$compname = $row['compname'];
					$anc      = $row['anc'];
					$total    = $row['total'];
					$anctotal = $row['anctotal'];

					if ($anc) {

						$grandtotal += $total;

						if ($lastcust != $compname) {
							$lastcust = $compname;
							$custcount++;
						} 
						
						print "<tr>\n";
						print "	<td>$compname</td>\n";
						print "	<td>$anc</td>\n";
						print "	<td align=\"right\">$anctotal</td>\n";
						print "	<td align=\"right\">" . number_format($total,2) . "</td>\n";
						print "</tr>\n";
					}
				}
				print "<tr>\n";
				print "	<td colspan=\"4\">&nbsp;</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td colspan=\"3\"><b>Customers: $custcount</b></td>\n";
				print "	<td align=\"right\"><b>" . number_format($grandtotal,2) . "</b></td>\n";
				print "</tr>\n";
				print "</table>\n";

				// print "<pre>" . print_r($tys,TRUE) . "</pre>";

			} else {
				$resultMessage = "Nothing Found<br />" . $dbcnx->error;
			}
			$dbcnx->close();

			// select all toggle
			print "<script type=\"text/javascript\">\n";
			print "$(function () {\n";
			print "    $('.checkall').on('click', function () {\n";
			print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
			print "    });\n";
			print "});\n";
			print "</script>\n";

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
