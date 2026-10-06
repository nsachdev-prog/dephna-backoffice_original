<?php
$page = "Reporting";
$subpage = "HK By Revenue";
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
			print "<p align=\"center\" class=\"heading\">Hourly Kitchen By Revenue</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			# get filtering if any and build search array
			if ($_REQUEST['ye'] != "All") { $ye = $_REQUEST['ye']; } # Year
			if (!$ye) { $ye = date("Y"); }
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; } # Type

			if ($ye) { $search[] = "year='$ye'"; }
			if ($ty) { $search[] = "details LIKE '%$ty%'"; }

			if ($search) {
				# make MySql search string
				$search = "WHERE " . implode(" AND ", $search);
			}

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-hk-revenue.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			# get available years
			$sql = "SELECT year FROM kinv_lines GROUP BY year ORDER BY year DESC";
			$yresult = $dbcnx->query($sql);
			if (($yresult) && ($yresult->num_rows)) {
				print "		<td align=\"right\"><b>Year</b></td>\n";
				print "		<td><select name=\"ye\" class=\"element_date\">\n";
				while ($yrow = $yresult->fetch_assoc()) {
					$yrow = $yresult->fetch_assoc();
					$year = $yrow['year'];
					print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "		<td align=\"right\"><b>Type</b></td>\n";
			print "		<td><select name=\"ty\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"Kitchen Hire\"";  if ($ty == "Kitchen Hire")    { print " SELECTED"; } print "><i>Kitchen Hire</i></option>\n";
			print "		<option value=\"Cold Rooms\""; if ($ty == "Cold Rooms") { print " SELECTED"; } print "><i>Cold Rooms</i></option>\n";
			print "		<option value=\"Storage\""; if ($ty == "Storage") { print " SELECTED"; } print "><i>Storage</i></option>\n";
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$months = array("1" => "Jan","2" => "Feb","3" => "Mar","4" => "Apr","5" => "May","6" => "Jun","7" => "Jul","8" => "Aug","9" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			$sql = "SELECT month, SUM(price) AS price, SUM(vat) AS vat FROM kinv_lines
							$search
							GROUP BY month
							ORDER BY month
			";
			$rresult = $dbcnx->query($sql);
			if (($rresult) && ($rresult->num_rows)) {

				# get highest value and round up to nearst 1000
				# put data in array
				$totals = array();
				while ($rrow = $rresult->fetch_assoc()) {
					$thismonth = $rrow['month'];
					$nettotal  = $rrow['price'];
					$vattotal  = $rrow['vat'];
					$thistotal = $nettotal + $vattotal;
					if ($thistotal > $highest) { $highest = $thistotal; }
					$totals[$thismonth] = $thistotal;
				}
				$highest = ceil( $highest/1000 ) * 1000;
				$half = $highest/2;
				print "<table align=\"center\">\n";
				print "	<tr>\n";
				print "		<td width=\"50px\" align=\"right\" valign=\"top\" class=\"smalltext2\">$highest-<br />";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br />";
				print "$half-<br />";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br />";
				print "0-";
				print "</td>";
				foreach ($months AS $key => $value) {
					if ($totals[$key]) {
						# percentage of highest
						$percent = ceil(($totals[$key]/$highest)*300);
						$clear = 300-$percent;
						print "		<td width=\"55px\" align=\"center\" valign=\"top\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"{$clear}px\" alt=\"\" />";
						print "<img src=\"images/red.gif\" width=\"55px\" height=\"{$percent}px\" alt=\"\" />";
						print "<br />" . $value . "<br />" . number_format($totals[$key],0);
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

				# breakdown section
				if ($ye) { $search = "year='$ye'"; }
				# month loop
				foreach ($months AS $key => $month) {
					# get total
					$sql = "SELECT month, SUM(price) AS price, SUM(vat) AS vat FROM kinv_lines
						WHERE $search AND month='$key'
						GROUP BY month
					";
					$aresult = $dbcnx->query($sql);
					if (($aresult) && ($aresult->num_rows)) {
						$arow = $aresult->fetch_assoc();
						$nettotal = $arow['price'];
						$vattotal = $arow['vat'];
						$monthtotal = $nettotal + $vattotal;

						$sql = "SELECT month, details, SUM(price) AS price, SUM(vat) AS vat FROM kinv_lines
							WHERE $search AND month='$key'
							GROUP BY details
							ORDER BY details
						";
						$bresult = $dbcnx->query($sql);
						if (($bresult) && ($bresult->num_rows)) {
							print "<p align=\"center\"><b>$month $ye</b></p>\n";
							print "<table width=\"\" align=\"center\">\n";
							while ($brow = $bresult->fetch_assoc()) {
								$nettotal = $brow['price'];
								$vattotal = $brow['vat'];
								$typetotal = $nettotal + $vattotal;
								$thistype  = $brow['details'];
								$thistype = str_replace(" x 1","",$thistype);
								if ($typetotal) {
									$thispercent = round((($typetotal/$monthtotal)*100),2);
								} else {
									$thispercent = 0;
								}
								$barwidth = ceil($thispercent*4);
								$clear = 400-$barwidth;
								if (!$thistype) { $thistype = "Unknown"; }
								print "<tr>\n";
								print "	<td width=\"150px\">$thistype</td>\n";
								print "	<td align=\"right\">" . number_format($typetotal,0) . "</td>\n";
								print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								print "	<td align=\"right\">" . number_format($thispercent,2) . "%</td>\n";
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td width=\"150px\">&nbsp;</td>\n";
							print "	<td align=\"right\"><b>" . number_format($monthtotal,0) . "</b></td>\n";
							print "	<td colspan=\"2\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";
						}
					}
				}

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
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "<p>&nbsp;</p>";
print "</td>\n";
include('footer.php');
?>
