<?php
$page = "Reporting";
$subpage = "Revenue By Unittype";
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
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; } # Type

			if ($ye) { $search[] = "inv_headers.year='$ye'"; }
			if ($lo) { $search[] = "inv_lines.location='$lo'"; }
			if ($ty) { $search[] = "inv_lines.unittype='$ty'"; }

			if ($search) {
				// make MySql search string
				$search = "WHERE " . implode(" AND ", $search);
			}

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-revenue.php\">\n";
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
					$types[] = $ttitle;
					print "		<option value=\"$ttitle\""; if ($ty == $ttitle) { print " SELECTED"; } print ">$ttitle</option>\n";
				}
			}
			$tresult = $dbcnx->query("SELECT ancgroup FROM ancillaries GROUP BY ancgroup ORDER BY ancgroup");
			if (($tresult) && ($tresult->num_rows)) {
				while ($trow = $tresult->fetch_assoc()) {
					$ttitle  = $trow['ancgroup'];
					$types[] = $ttitle;
					print "		<option value=\"$ttitle\""; if ($ty == $ttitle) { print " SELECTED"; } print "><i>$ttitle</i></option>\n";
				}
			}
			print "		<option value=\"Car Parking\"";    if ($ty == "Car Parking")    { print " SELECTED"; } print "><i>Car Parking</i></option>\n";
			print "		<option value=\"Virtual Office\""; if ($ty == "Virtual Office") { print " SELECTED"; } print "><i>Virtual Office</i></option>\n";
			print "		</select>";
			print "		</td>\n";

			$types[] = "Car Parking";
			$types[] = "Virtual Office";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			$sql = "SELECT inv_headers.month, SUM(linetotal) FROM inv_lines
					LEFT JOIN inv_headers ON inv_lines.hid = inv_headers.ID
					$search
					GROUP BY inv_headers.month
					ORDER BY inv_headers.month
			";
			$rresult = $dbcnx->query($sql);
			if (($rresult) && ($rresult->num_rows)) {

				// get highest value and round up to nearst 1000
				// put data in array
				$totals = array();
				while ($rrow = $rresult->fetch_assoc()) {
					$thismonth = $rrow['month'];
					$thistotal = $rrow['SUM(linetotal)'];
					if ($thistotal > $highest) { $highest = $thistotal; }
					$totals[$thismonth] = $thistotal;
				}
				$highest = ceil( $highest/1000 ) * 1000;
				$half = $highest/2;
				print "<table align=\"center\">\n";
				print "	<tr>\n";
				print "		<td width=\"50px\" align=\"right\" valign=\"bottom\" class=\"smalltext2\">$highest-<br />";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br />";
				print "$half-<br />";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br />";
				print "0-";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"10px\" alt=\"\" /><br />";
				print "&nbsp;";
				print "</td>";
				foreach ($months AS $key => $value) {
					if ($totals[$key]) {
						# percentage of highest
						$percent = ceil(($totals[$key]/$highest)*300);
						$clear = 300-$percent;
						print "		<td width=\"55px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"{$clear}px\" alt=\"\" />";
						print "<img src=\"images/green.gif\" width=\"55px\" height=\"{$percent}px\" alt=\"\" />";
						print "<br />" . $value . "<br />" . number_format($totals[$key],0);
						print "</td>\n";
					} else {
						print "		<td width=\"55px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"300px\" alt=\"\" />";
						print "<br />" . $value . "<br />&nbsp;";
						print "</td>\n";
					}
				}
				print "	</tr>\n";
				print "</table>\n";

				// breakdown section
				$search = array();
				if ($ye) { $search[] = "inv_headers.year='$ye'"; }
				if ($lo) { $search[] = "inv_lines.location='$lo'"; }
				$search = implode(" AND ", $search);
				if ($lo) {
					$loc = $lo;
				} else {
					$loc = "All Locations";
				}
				// month loop
				foreach ($months AS $key => $month) {
					// get total
					$sql = "SELECT SUM(linetotal) AS linetotal, SUM(vattotal) AS vattotal, SUM(linenet) AS linenet FROM inv_lines
							LEFT JOIN inv_headers ON inv_lines.hid = inv_headers.ID
							WHERE $search AND inv_headers.month='$key'
							GROUP BY inv_headers.month
					";
					$aresult = $dbcnx->query($sql);
					if (($aresult) && ($aresult->num_rows)) {
						$arow = $aresult->fetch_assoc();
						$monthtotal    = $arow['linetotal'];
						$monthvattotal = $arow['vattotal'];
						$monthnettotal = $arow['linenet'];
						$yeartotal    += $monthtotal;
						$yearvattotal += $monthvattotal;
						$yearnettotal += $monthnettotal;

						$sql = "SELECT inv_lines.unittype, SUM(linetotal) AS linetotal, SUM(vattotal) AS vattotal, SUM(linenet) AS linenet FROM inv_lines
								LEFT JOIN inv_headers ON inv_lines.hid = inv_headers.ID
								WHERE $search AND inv_headers.month='$key'
								GROUP BY inv_lines.unittype
								ORDER BY inv_lines.unittype
						";
						$bresult = $dbcnx->query($sql);
						if (($bresult) && ($bresult->num_rows)) {
							print "<p align=\"center\"><b>$month - $ye - $loc</b></p>\n";
							print "<table width=\"\" align=\"center\">\n";
							print "<tr>\n";
							print "	<td width=\"130px\">&nbsp;</td>\n";
							print "	<td align=\"right\"><b>Net</b></td>\n";
							print "	<td align=\"right\"><b>Vat</b></td>\n";
							print "	<td align=\"right\"><b>Total</b></td>\n";
							print "	<td colspan=\"2\">&nbsp;</td>\n";
							print "</tr>\n";
							while ($brow = $bresult->fetch_assoc()) {
								$typetotal    = $brow['linetotal'];
								$typevattotal = $brow['vattotal'];
								$typenettotal = $brow['linenet'];
								$thistype     = $brow['unittype'];
								$bar = abs($typetotal);
								if ($bar) {
									$thispercent = round((($bar/$monthtotal)*100),2);
								} else {
									$thispercent = 0;
								}
								$barwidth = ceil($thispercent*2);
								if ($barwidth > 200) { $barwidth = 200; }
								$clear = 200-$barwidth;
								if ($clear > 200) { $clear = 200+$barwidth; }
								if (!$thistype) { $thistype = "Unknown"; }
								print "<tr>\n";
								print "	<td width=\"130px\"><a href=\"reports-revenue-detail.php?fr=$fr&amp;to=$to&amp;ye=$ye&amp;mo=$key&amp;lo=$lo&amp;ut=$thistype\">$thistype</td>\n";
								print "	<td align=\"right\">" . number_format($typenettotal,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($typevattotal,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($typetotal,0) . "</td>\n";
								if ($typetotal < 0) {
									$thispercent *= -1;
									print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								} else {
									print "	<td><img src=\"images/green.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								}
								print "	<td align=\"right\">" . number_format($thispercent,2) . "%</td>\n";
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td width=\"130px\">&nbsp;</td>\n";
							print "	<td align=\"right\"><b>" . number_format($monthnettotal,0) . "</b></td>\n";
							print "	<td align=\"right\"><b>" . number_format($monthvattotal,0) . "</b></td>\n";
							print "	<td align=\"right\"><b>" . number_format($monthtotal,0) . "</b></td>\n";
							print "	<td colspan=\"2\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";
						}
					}
				}

				print "<p align=\"center\"><b>$ye - $loc</b></p>\n";
				print "<table width=\"\" align=\"center\">\n";
				print "<tr>\n";
				print "	<td width=\"130px\">&nbsp;</td>\n";
				print "	<td align=\"right\"><b>Net</b></td>\n";
				print "	<td align=\"right\"><b>Vat</b></td>\n";
				print "	<td align=\"right\"><b>Total</b></td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td width=\"130px\">Year Totals</td>\n";
				print "	<td align=\"right\"><b>" . number_format($yearnettotal,0) . "</b></td>\n";
				print "	<td align=\"right\"><b>" . number_format($yearvattotal,0) . "</b></td>\n";
				print "	<td align=\"right\"><b>" . number_format($yeartotal,0) . "</b></td>\n";
				print "</tr>\n";
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
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
