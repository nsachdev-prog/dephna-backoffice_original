<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Reports";
$subpage = "Used";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\">$submenustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Parts Used</p>\n";

            // get filtering
            $date = $_REQUEST['date'];
            if (!$date) { $date = date("m-Y"); } 
            list($m,$y) = explode("-",$date);
            $search[] = "h.sdate LIKE '$y-$m%'";
            $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));

            $search[] = "l.lncode = 'PRD1' OR l.lncode = 'PRD2' OR l.lncode = 'PRD3' OR l.lncode = 'PRD4'";
            $searchstr = implode(" AND ",$search);

            // print filter form
            print "<form name=\"addform\" method=\"post\" action=\"coldroom-reports-parts-used.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"coldroom-reports-parts-used.php?date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.2em\"><b>Month</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:3.6em\"><a href=\"coldroom-reports-parts-used.php\"><b>&bull;</b></a></td>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"coldroom-reports-parts-used.php?date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";

            // print month
            print "<p align=\"center\" class=\"larger\"><b>$thismonth</b></p>\n";

            // get data
            $query = "SELECT l.*, h.siteid
                      FROM cr_reports_lines AS l
                      LEFT JOIN cr_reports_headers AS h ON l.hid = h.ID
                      WHERE $searchstr
            ";
            $data = array();
            if ($result = $dbcnx->query($query) AND $result->num_rows > 0) {
                // collate data into array by site and report fields
                while ($row = $result->fetch_assoc()) {
                    $siteid = $row['siteid'];
                    $lncode = $row['lncode'];
                    $afcost = $row['afcost'];
                    $dephnacost = $row['dephnacost'];

                    $data[$siteid][$lncode]['qty']++;
                    $data[$siteid][$lncode]['afcost'] += $afcost;
                    $data[$siteid][$lncode]['dephnacost'] += $dephnacost;
                }
            } else {
                $data['3']['3']['qty']++;
                $data['3']['3']['afcost'] += 65.21;
                $data['3']['3']['dephnacost'] += 65.36;
                $data['3']['3']['qty']++;
                $data['3']['3']['afcost'] += 65.21;
                $data['3']['3']['dephnacost'] += 65.36;
                $data['35']['3']['qty']++;
                $data['35']['3']['afcost'] += 65.21;
                $data['35']['3']['dephnacost'] += 65.36;
                $data['3']['5']['qty']++;
                $data['3']['5']['afcost'] += 125.21;
                $data['3']['5']['dephnacost'] += 125.36;
                $resultMessage = "<span class=\"redtext\"><b>Dummy Data Used</b></span><br>" . $dbcnx->error . "<br>";
            }

            // print data array
            /* print "<pre>";
            print_r($data);
            print "</pre>"; */

                // get site names
                $sql = "SELECT ID, title FROM sites WHERE ID != '2' AND ID != '4' AND ID != '26' AND ID != '27' ORDER BY title";
                if ($sitesresult = $dbcnx->query($sql) AND $sitesresult->num_rows > 0) {
                    while ($srow = $sitesresult->fetch_assoc()) {
                        $thisid = $srow['ID'];
                        $thistitle = $srow['title'];
                        $sitenames[$thisid] = $thistitle;
                    }
                }

                // get parts
                $parts = array();
                $sql = "SELECT `ID`, `group`, `part` FROM `cr_reports_parts` ORDER BY `group`, `part`";
                if ($partsresult = $dbcnx->query($sql) AND $partsresult->num_rows > 0) {
                    while ($prow = $partsresult->fetch_assoc()) {
                        $partid = $prow['ID'];
                        $group = $prow['group'];
                        $part = $prow['part'];
                        $parts[$group][$partid] = $part;
                    }
                } else {
                    $resultMessage = "<span class=\"redtext\"><b>Error Getting Parts</b></span><br>" . $dbcnx->error . "<br>";
                }

                /* print "<pre>";
                print_r($parts);
                print "</pre>"; */


                // print data
                print "<table align=\"center\" width=\"98%\" cellspacing=\"0px\" cellpadding=\"4px\">\n";

                // print site and part data
                $grandtotalqty = 0;
                $grandtotalafcost = 0;
                $grandtotaldephnacost = 0;
                foreach ($parts as $group => $partids) {
                    $thisgroup = "";
                    if ($group != $thisgroup) {
                        print "<tr>\n";
                        print "<td class=\"larger\" style=\"border-bottom:1px solid #999999\"><b>$group</b></td>\n";
                        foreach ($sitenames as $siteid => $sitename) {
                            print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>$unitPrefixes[$sitename]</b></th>\n";
                        }
                        print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Total</b></th>\n";
                        print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Total AFCost</b></th>\n";
                        print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Total DPCost</b></th>\n";
                        print "</tr>\n";
                        $thisgroup = $group;
                    }
                    $totalqty = 0;
                    $totalafcost = 0;
                    $totaldephnacost = 0;
                    foreach ($partids as $partid => $part) {
                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "<td>$part</td>\n";
                        $subtotalqty = 0;
                        $subtotalafcost = 0;
                        $subtotaldephnacost = 0;
                        foreach ($sitenames as $siteid => $sitename) {
                            $qty        = $data[$siteid][$partid]['qty'];
                            $afcost     = $data[$siteid][$partid]['afcost'];
                            $dephnacost = $data[$siteid][$partid]['dephnacost'];
                            $subtotalqty += $qty;
                            $subtotalafcost += $afcost;
                            $subtotaldephnacost += $dephnacost;
                            $totalqty += $qty;
                            $totalafcost += $afcost;
                            $totaldephnacost += $dephnacost;
                            $grandtotalqty += $qty;
                            $grandtotalafcost += $afcost;
                            $grandtotaldephnacost += $dephnacost;
                            print "<td align=\"right\">$qty</td>\n";
                        }
                        print "<td align=\"right\">$subtotalqty</td>\n";
                        print "<td align=\"right\">" . number_format($subtotalafcost,2) . "</td>\n";
                        print "<td align=\"right\">" . number_format($subtotaldephnacost,2) . "</td>\n";
                        print "</tr>\n";
                    }
                    print "<tr>\n";
                    print "<td align=\"right\" style=\"border-top:1px solid #999999\" colspan=\"" . (count($sitenames) + 1) . "\"><b>Section Total</b></td>\n";
                    print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>$totalqty</b></td>\n";
                    print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>" . number_format($totalafcost,2) . "</b></td>\n";
                    print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>" . number_format($totaldephnacost,2) . "</b></td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\" colspan=\"" . (count($sitenames) + 1) . "\"><b>Grand Total</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>$grandtotalqty</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>" . number_format($grandtotalafcost,2) . "</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>" . number_format($grandtotaldephnacost,2) . "</b></td>\n";
                print "</tr>\n";
                print "</table>\n";
                

            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
            print "	});\n";
            print "</script>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
