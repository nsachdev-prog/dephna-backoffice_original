<?php
$page = "Reporting";
$subpage = "Units By Space";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading and submenu
			print "<p align=\"center\" class=\"heading\">$subpage</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			# get filtering if any and build search array
			if ($_REQUEST['ye'] != "All") { $ye = $_REQUEST['ye']; } # Year
			if (!$ye) { $ye = date("Y"); }
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; } # Type

			if ($ye) { $search[] = "inv_headers.year='$ye'"; }
			if ($lo) { $search[] = "inv_lines.location='$lo'"; $unitsearch[] = "sites.title='$lo'"; }
			if ($ty) { $search[] = "inv_lines.unittype='$ty'"; $unitsearch[] = "units.unittype='$ty'"; }

			if ($unitsearch) { $unitsearch = "WHERE " . implode(" AND ", $unitsearch); }

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-space.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			# get available years
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

			print "		<td align=\"right\"><b>Location</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$lresult = $dbcnx->query($sql);
			if (($lresult) && ($lresult->num_rows)) {
				while ($lrow = $lresult->fetch_assoc()) {
					$ltitle  = $lrow['title'];
					print "		<option value=\"$ltitle\""; if ($lo == $ltitle) { print " SELECTED"; } print ">$ltitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td align=\"right\"><b>Type</b></td>\n";
			print "		<td><select name=\"ty\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$tresult = $dbcnx->query("SELECT title FROM types ORDER BY title");
			if (($tresult) && ($tresult->num_rows)) {
				while ($trow = $tresult->fetch_assoc()) {
					$ttitle  = $trow['title'];
					if ($_REQUEST['ty'] == "All") { $alltypes[] = "inv_lines.unittype='$ttitle'"; }
					print "		<option value=\"$ttitle\""; if ($ty == $ttitle) { print " SELECTED"; } print ">$ttitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			# get total units
			$sql = "SELECT COUNT(*) AS numrows FROM units
					JOIN sites ON units.siteid = sites.ID
					$unitsearch
			";
			$uresult = $dbcnx->query($sql);
			if (($uresult) && ($uresult->num_rows)) {
				$urow = $uresult->fetch_assoc();
				$totalunits = $urow['numrows'];
				$half = $totalunits/2;
			} else {
				$totalunits = 0;
				$half = 0;
			}
			if ($alltypes) { $search[] = "(" . implode(" OR " , $alltypes) . ")"; }
			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			# get results
			$sql = "SELECT inv_headers.month, COUNT(DISTINCT office,location) AS ctotal FROM inv_lines
							JOIN inv_headers ON inv_lines.hid = inv_headers.ID
							$search
							GROUP BY inv_headers.month
							ORDER BY inv_headers.month
			";

			$rresult = $dbcnx->query($sql);
			if (($rresult) && ($rresult->num_rows)) {
				while ($rrow = $rresult->fetch_assoc()) {
					$thismonth = $rrow['month'];
					$thistotal = $rrow['ctotal'];
					$totals[$thismonth] = $thistotal;
				}
				print "<table align=\"center\">\n";
				print "	<tr>\n";
				print "		<td width=\"50px\" align=\"right\" valign=\"top\" class=\"smalltext2\">$totalunits-<br />";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br />";
				print "$half-<br />";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br />";
				print "0-";
				print "</td>";
				foreach ($months AS $key => $value) {
					if ($totals[$key]) {
						# percentage of highest
						$percent = ceil(($totals[$key]/$totalunits)*300);
						$clear = 300-$percent;
						print "		<td width=\"55px\" align=\"center\" valign=\"top\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"{$clear}px\" alt=\"\" />";
						print "<img src=\"images/red.gif\" width=\"55px\" height=\"{$percent}px\" alt=\"\" />";
						print "<br />" . $value . "<br />" . $totals[$key] . "/" . $totalunits;
						print "</td>\n";
					} else {
						print "		<td width=\"55px\" align=\"center\" valign=\"top\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"300px\" alt=\"\" />";
						print "<br />" . $value . "<br />&nbsp;";
						print "</td>\n";
					}
				}
				print "	</tr>\n";
				print "</table>\n";
			} else {
				$resultMessage = "Nothing Found<br />" . $dbcnx->error;
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
	print "<p align=\"center\"<b>You are not logged in as an administrator</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
