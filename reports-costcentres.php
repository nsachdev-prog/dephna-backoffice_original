<?php
$page = "Reporting";
$subpage = "Expenses By Costcentre";
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

            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">Expenses By Costcentre</span><br><b>{$months[$fr]} $ye to {$months[$to]} $ye</b></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			if ($search) {
				// make MySql search string
				$search = "WHERE " . implode(" AND ", $search);
			}

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"reports-costcentres.php\">\n";
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

			$sql = "SELECT month, SUM(total) AS total
                    FROM purchases
                    WHERE CONCAT(year,\"-\",month) >= '$ye-$fr'
                    AND   CONCAT(year,\"-\",month) <= '$ye-$to'
                    AND (txtype = 'I' OR txtype = 'C')
                    GROUP BY month
                    ORDER BY month
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
                
				foreach ($totals AS $key => $month) {

					$sql = "SELECT SUM(l.net) AS net, SUM(l.vat) AS vat, SUM(l.total) AS tot, l.ccid, c.title AS costcentre
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
						    LEFT JOIN costcentres AS c ON c.ID = l.ccid
                            WHERE p.year = '$ye'
                              AND p.month = '$key'
                              AND (p.txtype = 'I' OR p.txtype = 'C')
						    GROUP BY c.title
                            ORDER BY c.title
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                        $totnet = 0;
                        $totvat = 0;
                        $tottot = 0;
						
                        print "<p align=\"center\" class=\"larger\"><b>{$months[$key]} $ye</b></p>\n";
                        print "<table width=\"\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
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
							$ccid = $row['ccid'];
                            $costcentre = $row['costcentre'];
                            if ($tot) {
                                $thispercent = round((($tot/$totals[$key])*100),2);
                            } else {
                                $thispercent = 0;
                            }
                            $barwidth = ceil($thispercent*2);
                            $clear = 200-$barwidth;
                            if ($clear > 200) { $clear = 200+$barwidth; }
                            if (!$costcentre) { $costcentre = "Unknown"; }
                            print "<tr>\n";
                            print "	<td width=\"250px\"><a href=\"reports-costcentres-detail.php?fr=$fr&amp;to=$to&amp;ye=$ye&amp;mo=$key&amp;ccid=$ccid\">$costcentre</a></td>\n";
                            print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
                            print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
                            print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
                            print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
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
                        print "	<td  style=\"border-top:1px solid #999999\" width=\"250px\"><b>$numrows</b></td>\n";
                        print "	<td  style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totnet,0) . "</b></td>\n";
                        print "	<td  style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totvat,0) . "</b></td>\n";
                        print "	<td  style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($tottot,0) . "</b></td>\n";
                        print "	<td  style=\"border-top:1px solid #999999\" colspan=\"2\">&nbsp;</td>\n";
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
