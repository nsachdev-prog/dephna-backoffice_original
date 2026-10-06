<?php
$page = "Reporting";
$subpage = "Inputs/Costcentres";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering if any and build search array
			if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; } else { $fr = "01"; }
			if ($_REQUEST['to']) { $to = $_REQUEST['to']; } else { $to = "12"; }
			if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; } else { $ye = date("Y"); }
			if ($to < $fr) { $to = "12"; }
			if ($_REQUEST['tys']) { $tys = $_REQUEST['tys']; }
			if ($_REQUEST['ccs']) { $ccs = $_REQUEST['ccs']; }

			$months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">Inputs Against Costcentres</span><br><b>{$months[$fr]} $ye to {$months[$to]} $ye</b></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			?>
			<!-- <script>
				$('.rotate').css('height', $('.rotate').width());
			</script> -->
			<style>
				.rotateMe {
					width: 25px;
					text-align: center;
					-webkit-transform: rotate(90deg);
					-moz-transform: rotate(90deg);
					-o-transform: rotate(90deg);
					-ms-transform: rotate(90deg);
					transform: rotate(90deg);
				}
			</style>
			<?php

			if ($tys AND $ccs) {

				foreach ($tys AS $ty => $v) {
					$isearch[] = "l.unittype = '$ty'";
				}
				foreach ($ccs AS $cc => $v) {
					$osearch[] = "ccid = '$cc'";   
				}

				if (count($isearch) > 1) {
					$isearch = "(".implode(" OR ",$isearch).")";
				} else {
					$isearch = implode(" OR ",$isearch);
				}
				if (count($osearch) > 1) {
					$osearch = "(".implode(" OR ",$osearch).")";
				} else {
					$osearch = implode(" OR ",$osearch);
				}

				$totins  = array();
				$totouts = array();

				// get total for month
				$sql = "SELECT SUM(l.linetotal) AS tot, h.month AS mmonth
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON h.ID = l.hid
						WHERE h.year='$ye' AND (h.txtype = 'I' OR h.txtype = 'C') AND $isearch
						GROUP BY h.month
						ORDER BY h.month
				";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
					while ($row = $result->fetch_assoc()) {
						$thismonth = $row['mmonth'];
						$thistotal = $row['tot'];
						if ($thistotal > $highest) { $highest = $thistotal; }
						$totins[$thismonth] = $thistotal;
					}
				} else {
					print $dbcnx->error;
				}

				$sql = "SELECT SUM(l.total) AS tot, p.month AS mmonth
						FROM purchases_lines AS l
						LEFT JOIN purchases AS p ON p.ID = l.hid
						LEFT JOIN costcentres AS c ON c.ID = l.ccid
						WHERE p.year = '$ye' AND (p.txtype = 'I' OR p.txtype = 'C') AND $osearch
						GROUP BY p.month
						ORDER BY p.month
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					while ($row = $result->fetch_assoc()) {
						$thismonth = $row['mmonth'];
						$thistotal = $row['tot'];
						if ($thistotal > $highest) { $highest = $thistotal; }
						$totouts[$thismonth] = $thistotal;
					}
				} else {
					print $dbcnx->error;
				}

				$highest = ceil( $highest/1000 ) * 1000;
				$half = $highest/2;
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"0\">\n";
				print "	<tr>\n";
				print "		<td width=\"50px\" align=\"right\" valign=\"bottom\" class=\"smalltext2\">$highest-&nbsp;<br>";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br>";
				print "$half-&nbsp;<br>";
				print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" /><br>";
				print "0-&nbsp;<br>";
				print "<img src=\"images/clear.gif\" width=\"40px\" height=\"50px\" alt=\"\" /><br>";
				print "&nbsp;";
				print "</td>";
				foreach ($months AS $key => $value) {
					if ($totins[$key]) {
						# percentage of highest
						$percent = ceil(($totins[$key]/$highest)*300);
						$clear = 300-$percent;
						print "<td width=\"25px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"{$clear}px\" alt=\"\" />";
						print "<img src=\"images/green.gif\" width=\"25px\" height=\"{$percent}px\" alt=\"\" />";
						print "<br>" . $value . "<br>&nbsp;";
						print "<br><div class=\"rotateMe\">".number_format($totins[$key],0)."</div><br>&nbsp;";
						print "</td>\n";
					} else {
						print "<td width=\"25px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"300px\" alt=\"\" />";
						print "<br>" . $value . "<br>&nbsp;";
						print "</td>\n";
					}
					if ($totouts[$key]) {
						# percentage of highest
						$percent = ceil(($totouts[$key]/$highest)*300);
						$clear = 300-$percent;
						print "<td width=\"25px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"{$clear}px\" alt=\"\" />";
						print "<img src=\"images/red.gif\" width=\"25px\" height=\"{$percent}px\" alt=\"\" />";
						print "<br><br>&nbsp;";
						print "<br><div class=\"rotateMe\">".number_format($totouts[$key],0)."</div><br>&nbsp;";
						print "</td>\n";
					} else {
						print "		<td width=\"25px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"25px\" height=\"300px\" alt=\"\" />";
						print "<br><br>&nbsp;";
						print "</td>\n";
					}
					print "<td width=\"5px\"></td>\n";
				}
				print "	</tr>\n";
				print "</table><br>\n";
				
				foreach ($months AS $key => $month) {
					$tot    = 0;
					$totnet = 0;
					$totvat = 0;
					$tottot = 0;
					$totin  = 0;
					$totout = 0;
					if (($key <= $to) AND ($key >= $fr)) {

						$monthtotal = $totins[$key] + $totouts[$key];

						print "<p align=\"center\" class=\"larger\">$month $ye</p>";
						print "<table width=\"\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #ccc\" width=\"200px\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #ccc\" align=\"right\"><b>Net</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #ccc\" align=\"right\"><b>Vat</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #ccc\" align=\"right\"><b>Total</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #ccc\" colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";
						// get inputs
						$sql = "SELECT SUM(l.linetotal) AS tot, SUM(l.vattotal) AS vat, SUM(l.linenet) AS net, l.unittype
								FROM inv_lines AS l
								LEFT JOIN inv_headers AS h ON h.ID = l.hid
								WHERE h.year='$ye' AND h.month = '$key' AND (h.txtype = 'I' OR h.txtype = 'C') AND $isearch
								GROUP BY l.unittype
						";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
							while ($row = $result->fetch_assoc()) {
								$tot = $row['tot'];
								$vat = $row['vat'];
								$net = $row['net'];
								$totnet += $net;
								$totvat += $vat;
								$tottot += $tot;

								$unittype = $row['unittype'];
								$bar = abs($tot);
								if ($tot) {
									$thispercent = round((($bar/$monthtotal)*100),2);
								} else {
									$thispercent = 0;
								}
								$barwidth = ceil($thispercent*2);
								if ($barwidth > 200) { $barwidth = 200; }
								$clear = 200-$barwidth;
								if ($clear > 200) { $clear = 200+$barwidth; }
								print "<tr>\n";
								print "	<td width=\"200px\">$unittype</td>\n";
								print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
								if ($tot < 0) {
									$thispercent *= -1;
									print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								} else {
									print "	<td><img src=\"images/green.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								}
								print "	<td align=\"right\">" . number_format($thispercent,2) . "%</td>\n";
								print "</tr>\n";
							}
						} else {
							print $dbcnx->error;
						}
						// get outputs
						$sql = "SELECT SUM(l.net) AS net, SUM(l.vat) AS vat, SUM(l.total) AS tot, c.title AS costcentre
								FROM purchases_lines AS l
								LEFT JOIN purchases AS p ON p.ID = l.hid
								LEFT JOIN costcentres AS c ON c.ID = l.ccid
								WHERE p.year = '$ye' AND p.month = '$key' AND (p.txtype = 'I' OR p.txtype = 'C') AND $osearch
								GROUP BY costcentre
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

							while ($row = $result->fetch_assoc()) {
							
								$net = $row['net'] *-1;
								$vat = $row['vat'] *-1;
								$tot = $row['tot'] *-1;
								$totnet += $net;
								$totvat += $vat;
								$tottot += $tot;
								$ccid = $row['ccid'];
								$costcentre = $row['costcentre'];
								$bar = abs($tot);
								if ($tot) {
									$thispercent = round((($bar/$monthtotal)*100),2);
								} else {
									$thispercent = 0;
								}
								$barwidth = ceil($thispercent*2);
								if ($barwidth > 200) { $barwidth = 200; }
								$clear = 200-$barwidth;
								if ($clear > 200) { $clear = 200+$barwidth; }
								print "<tr>\n";
								print "	<td width=\"200px\">$costcentre</a></td>\n";
								print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
								if ($tot < 0) {
									// $thispercent *= -1;
									print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								} else {
									print "	<td><img src=\"images/green.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
								}
								print "	<td align=\"right\">" . number_format($thispercent,2) . "%</td>\n";
								print "</tr>\n";
							}

						}
						print "<tr>\n";
						print "	<td style=\"border-top:1px solid #ccc\" width=\"200px\"><b>$numrows</b></td>\n";
						print "	<td style=\"border-top:1px solid #ccc\" align=\"right\"><b>" . number_format($totnet,0) . "</b></td>\n";
						print "	<td style=\"border-top:1px solid #ccc\" align=\"right\"><b>" . number_format($totvat,0) . "</b></td>\n";
						print "	<td style=\"border-top:1px solid #ccc\" align=\"right\"><b>" . number_format($tottot,0) . "</b></td>\n";
						print "	<td style=\"border-top:1px solid #ccc\" colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					}
				}

				print "<p align=\"center\"><a href=\"reports-inout-cc.php?fr=$fr&amp;to=$to&amp;ye=$ye\">Return</p>\n";

			} else {

				// filter form
				print "<form name=\"addform\" method=\"post\" action=\"reports-inout-cc.php\">\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";

				// get available years
				$sql = "SELECT year FROM purchases GROUP BY year ORDER BY year DESC";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "		<td align=\"right\"><b>Year</b></td>\n";
					print "		<td><select name=\"ye\" class=\"element_smaller\">\n";
					while ($row = $result->fetch_assoc()) {
						$year = $row['year'];
						print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
					}
					print "		</select>";
					print "		</td>\n";
				}

				print "		<td align=\"right\"><b>From</b></td>\n";
				print "		<td><select name=\"fr\" class=\"element_smaller\">\n";
				foreach ($months AS $key => $value) {
					print "		<option value=\"$key\""; if ($fr == $key) { print " SELECTED"; } print ">$value</option>\n";
				}
				print "		</select>";
				print "		</td>\n";

				print "		<td align=\"right\"><b>To</b></td>\n";
				print "		<td><select name=\"to\" class=\"element_smaller\">\n";
				foreach ($months AS $key => $value) {
					print "		<option value=\"$key\""; if ($to == $key) { print " SELECTED"; } print ">$value</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
				
				print " <td><a href=\"reports-expenses.php\">Reset</a></td>\n";
				print "</tr>";
				print "</table>\n";

				// print Unittypes And Costcentres

				print "<table align=\"center\">\n"; // outer
				print "	<tr>\n";
				print " <td valign=\"top\">\n";

				print "<fieldset style=\"border:none\">\n";
				print "<table align=\"center\">\n";  // inner
				print "<tr><td colspan=\"2\" align=\"center\"><b>Inputs</b></td></tr>";
				print "<tr><td><input type=\"checkbox\" class=\"checkall\"><td>All</td></tr>\n";
				$result = $dbcnx->query("SELECT unittype FROM inv_lines GROUP BY unittype ORDER BY unittype");
				if (($result) && ($result->num_rows)) {
					while ($row = $result->fetch_assoc()) {
						$unittype = $row['unittype'];
						if ($unittype) {
							print " <tr><td><input type=\"checkbox\" name=\"tys[$unittype]\""; if ($tys[$unittype]) { print " CHECKED"; } print "></td><td>$unittype</td></tr>\n";
						}
					}
				}
				print "</table>\n";
				print "</fieldset>\n";

				print "<td width=\"20\">&nbsp;</td>\n";

				print "</td>\n";

				print "<td valign=\"top\">\n";

				print "<fieldset style=\"border:none\">\n";
				print "<table align=\"center\">\n";  // inner
				print "<tr><td colspan=\"2\" align=\"center\"><b>Outputs</b></td></tr>";
				print "<tr><td><input type=\"checkbox\" class=\"checkall\"><td>All</td></tr>\n";
				$sql = "SELECT l.ccid AS ccid, c.title AS title
						FROM purchases_lines AS l
						LEFT JOIN costcentres AS c ON c.ID = l.ccid
						GROUP BY l.ccid
						ORDER BY c.title
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					while ($row = $result->fetch_assoc()) {
						$ccid   = $row['ccid'];
						$ttitle = $row['title'];
						print " <tr><td><input type=\"checkbox\" name=\"ccs[$ccid]\""; if ($ccs[$ccid]) { print " CHECKED"; } print "></td><td>$ttitle</td></tr>\n";
					}
				}
				print "</table>\n";
				print "</fieldset>\n";

				print "</td>\n";
				print "</tr>\n";
				print "</table>\n";

				print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
				print "</form>\n";

				// select all toggle
				print "<script type=\"text/javascript\">\n";
				print "$(function () {\n";
				print "    $('.checkall').on('click', function () {\n";
				print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
				print "    });\n";
				print "});\n";
				print "</script>\n";

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
