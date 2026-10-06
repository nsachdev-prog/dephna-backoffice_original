<?php
$page = "Reporting";
$subpage = "Expenses By Supplier";
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
			if ($_REQUEST['supps']) {
				$supps = $_REQUEST['supps'];
			}
			if ($to < $fr) { $to = "12"; }
			
			foreach ($supps AS $suppid => $v) {
				$search[] = "p.suppid = '$suppid'";
			}

            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">Expenses By Supplier</span><br><b>{$months[$fr]} $ye to {$months[$to]} $ye</b></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			if ($search) {
				// make MySql search string
				$search = "AND (" . implode(" OR ", $search).")";
			}

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"reports-expenses.php\">\n";
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
            
            print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
            print " <td><a href=\"reports-expenses.php\">Reset</a></td>\n";
			print "</tr>";
			print "</table>\n";
            print "</form>\n";

			$sql = "SELECT p.month, SUM(p.total) AS total
                    FROM purchases AS p
                    WHERE CONCAT(p.year,\"-\",p.month) >= '$ye-$fr'
                    AND   CONCAT(p.year,\"-\",p.month) <= '$ye-$to'
                    AND (p.txtype = 'I' OR p.txtype = 'C')
					$search
                    GROUP BY p.month
                    ORDER BY p.month
            ";
			$result = $dbcnx->query($sql);
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				// get highest value and round up to nearst 1000
				// put data in array
				$totals = array();
				while ($row = $result->fetch_assoc()) {
					$thismonth = $row['month'];
					$thistotal = $row['total'];
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
						print "		<td width=\"55px\" align=\"center\" valign=\"bottom\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"9px\" alt=\"\" />";
						print "<img src=\"images/clear.gif\" width=\"55px\" height=\"{$clear}px\" alt=\"\" />";
						print "<img src=\"images/red.gif\" width=\"55px\" height=\"{$percent}px\" alt=\"\" />";
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
				
				// month loop
				
				print "<form name=\"addform\" method=\"post\" action=\"reports-expenses.php\">\n";
				print "<input type=\"hidden\" name=\"fr\" value=\"$fr\" />";
				print "<input type=\"hidden\" name=\"to\" value=\"$to\" />";
				print "<input type=\"hidden\" name=\"ye\" value=\"$ye\" />";
				print "<fieldset style=\"border:none\">\n";

				$all = 0;
                
				foreach ($totals AS $key => $month) {

					$sql = "SELECT p.suppid, SUM(p.net) AS net, SUM(p.vat) AS vat, SUM(p.total) AS tot, s.suppname
                            FROM purchases AS p
						    LEFT JOIN suppliers AS s ON s.ID = p.suppid
                            WHERE p.year = '$ye'
                              AND p.month = '$key'
                              AND (p.txtype = 'I' OR p.txtype = 'C')
							  $search
						    GROUP BY p.suppid
                            ORDER BY s.suppname
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                        $totnet = 0;
                        $totvat = 0;
						$tottot = 0;
						
                        print "<p align=\"center\" class=\"larger\" ><b>{$months[$key]} $ye</b></p>\n";
                        print "<table width=\"\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						if (!$all) {
							print "	<td style=\"border-bottom:1px solid #999999\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
							$all++;
						} else {
							print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						}
                        print "	<td style=\"border-bottom:1px solid #999999\" width=\"250px\">&nbsp;</td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Net</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Vat</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\">&nbsp;</td>\n";
                        print "</tr>\n";
                        while ($row = $result->fetch_assoc()) {
                            $net = $row['net'];
                            $vat = $row['vat'];
							$tot = $row['tot'];
							$suppid = $row['suppid'];
                            $suppname = $row['suppname'];
                            $bar = abs($tot);
							if ($tot) {
								$thispercent = round((($bar/$month)*100),2);
							} else {
								$thispercent = 0;
							}
							$barwidth = ceil($thispercent*2);
							if ($barwidth > 200) { $barwidth = 200; }
							$clear = 200-$barwidth;
							if ($clear > 200) { $clear = 200+$barwidth; }
                            if (!$suppname) { $suppname = "Unknown"; }
							print "<tr>\n";
							print " <tr><td><input type=\"checkbox\" name=\"supps[$suppid]\""; if ($supps[$suppid]) { print " CHECKED"; } print "></td>\n";
                            print "	<td width=\"250px\"><a href=\"reports-expenses-detail.php?fr=$fr&amp;to=$to&amp;ye=$ye&amp;mo=$key&amp;suppid=$suppid\">$suppname</a></td>\n";
                            print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
                            print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
                            print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
                            if ($tot < 0) {
								$thispercent *= -1;
								print "	<td><img src=\"images/green.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
							} else {
								print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
							}
							print "	<td align=\"right\">" . number_format($thispercent,2) . "%</td>\n";
                            print "</tr>\n";
                            $totnet += $net;
                            $totvat += $vat;
							$tottot += $tot;
							$yearnet += $net;
							$yearvat += $vat;
							$yeartot += $tot;

						}
						$numrows = $result->num_rows;
						print "<tr>\n";
						print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totnet,0) . "</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totvat,0) . "</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($tottot,0) . "</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" colspan=\"2\">&nbsp;</td>\n";
                        print "</tr>\n";
                        print "</table>\n";
						
					}
				}

				print "<p align=\"center\">&nbsp;</p>\n";
				print "<table width=\"\" align=\"center\">\n";
				print "<tr>\n";
				print "	<td width=\"250px\">&nbsp;</td>\n";
				print "	<td align=\"right\"><b>Net</b></td>\n";
				print "	<td align=\"right\"><b>Vat</b></td>\n";
				print "	<td align=\"right\"><b>Total</b></td>\n";
				print "	<td width=\"230\">&nbsp;</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td width=\"250px\"><b>{$months[$fr]} $ye to {$months[$to]} $ye Totals</b></td>\n";
				print "	<td align=\"right\"><b>" . number_format($yearnet,0) . "</b></td>\n";
				print "	<td align=\"right\"><b>" . number_format($yearvat,0) . "</b></td>\n";
				print "	<td align=\"right\"><b>" . number_format($yeartot,0) . "</b></td>\n";
				print "	<td width=\"230\">&nbsp;<br></td>\n";
				print "</tr>\n";
				print "</table>\n";

				print "</fieldset>\n";

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

			} else {
				$resultMessage = "Nothing Found<br />" . $dbcnx->error;
			}

			print "	<p align=\"center\">Select and submit suppliers before clicking download.</p>\n";
			
			print "<form name=\"form2\" method=\"post\" target=\"_blank\" action=\"reports-expenses-dnlcsv.php\">\n";
			print "<input type=\"hidden\" name=\"fr\" value=\"$fr\" />";
			print "<input type=\"hidden\" name=\"to\" value=\"$to\" />";
			print "<input type=\"hidden\" name=\"ye\" value=\"$ye\" />";
			if ($supps) {
				foreach ($supps AS $suppid => $v) {
					print "<input type=\"hidden\" name=\"supps2[$suppid]\" />";
				}
			}
			print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Download Csv\"></p>\n";
			print "</form>\n";



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
