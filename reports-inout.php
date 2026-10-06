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

			if ($tys AND $ccs) {

                foreach ($tys AS $ty => $v) {
                    $isearch[] = "l.unittype = '$ty'";
                }
                foreach ($ccs AS $cc => $v) {
                    $result = $dbcnx->query("SELECT title FROM costcentres WHERE ID = '$cc'");
                    if (($result) && ($result->num_rows)) {
                        while ($row = $result->fetch_assoc()) {
                            $output = $row['title'];
                            $outputs[] = $output;
                            $osearch[] = "ccid = '$cc'";
                        }
                    }
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

                foreach ($months AS $key => $month) {
                    $totnet = 0;
                    $totvat = 0;
                    $tottot = 0;
                    if (($key <= $to) AND ($key >= $fr)) {
                        print "<p align=\"center\" class=\"larger\">$month $ye</p>";
                        print "<table width=\"\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "	<td width=\"130px\">&nbsp;</td>\n";
                        print "	<td align=\"right\"><b>Net</b></td>\n";
                        print "	<td align=\"right\"><b>Vat</b></td>\n";
                        print "	<td align=\"right\"><b>Total</b></td>\n";
                        print "	<td colspan=\"2\">&nbsp;</td>\n";
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
								if ($typetotal) {
									$thispercent = round((($typetotal/$monthtotal)*100),2);
								} else {
									$thispercent = 0;
								}
								$barwidth = ceil($thispercent*4);
								$clear = 200-$barwidth;
								if ($clear > 200) { $clear = 200+$barwidth; }
								print "<tr>\n";
								print "	<td width=\"130px\">$unittype</td>\n";
								print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
								print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
								print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
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
                                if ($tot) {
                                    $thispercent = round((($tot/$totals[$key])*100),2);
                                } else {
                                    $thispercent = 0;
                                }
                                $barwidth = ceil($thispercent*2);
                                $clear = 200-$barwidth;
                                if ($clear > 200) { $clear = 200+$barwidth; }
                                print "<tr>\n";
                                print "	<td width=\"130px\">$costcentre</a></td>\n";
                                print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
                                print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
                                print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
                                print "	<td><img src=\"images/red.gif\" width=\"{$barwidth}px\" height=\"10px\" alt=\"\" /><img src=\"images/lightgrey.gif\" width=\"{$clear}px\" height=\"10px\" alt=\"\" /></td>\n";
                                print "	<td align=\"right\">" . number_format($thispercent,2) . "%</td>\n";
                                print "</tr>\n";
                            }
                            print "<tr>\n";
                            print "	<td  style=\"border-top:1px solid #999999\" width=\"130px\"><b>$numrows</b></td>\n";
                            print "	<td  style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totnet,0) . "</b></td>\n";
                            print "	<td  style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totvat,0) . "</b></td>\n";
                            print "	<td  style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($tottot,0) . "</b></td>\n";
                            print "	<td  style=\"border-top:1px solid #999999\" colspan=\"2\">&nbsp;</td>\n";
                            print "</tr>\n";

                        } else {
                            print $dbcnx->error;
                        }
                        print "</table>\n";
                    }
                }

                print "<p align=\"center\"><a href=\"reports-inout.php?fr=$fr&amp;to=$to&amp;ye=$ye\">Return</p>\n";


            } else {

                // filter form
                print "<form name=\"addform\" method=\"post\" action=\"reports-inout.php\">\n";
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

                print "<table align=\"center\">\n";  // inner
                print "<tr><td colspan=\"2\" align=\"center\"><b>Inputs</b></td></tr>";
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

                print "<td width=\"20\">&nbsp;</td>\n";

                print "</td>\n";

                print "<td valign=\"top\">\n";

                print "<table align=\"center\">\n";  // inner
                print "<tr><td colspan=\"2\" align=\"center\"><b>Outputs</b></td></tr>";
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

                print "</td>\n";
                print "</tr>\n";
                print "</table>\n";

                print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
                print "</form>\n";

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
