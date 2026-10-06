<?php
$page = "Reporting";
$subpage = "Revenue By Office";
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
			$date = $_REQUEST['date'];
            if (!$date) { $date = date("m-Y"); } 
            list($m,$y) = explode("-",$date);
            $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));
            if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
            if (!$lo) { $lo = "26 Chase Road"; }
            /* if ($_REQUEST['ord']) { $ord = $_REQUEST['ord']; }
            if (!$ord) { $ord = "ASC"; } */
            $search[] = "h.year = '$y'";
            $search[] = "h.month = '$m'";
            $search[] = "l.location = '$lo'";
            $search[] = "l.office != 'Kitchen'";
            $search[] = "l.office NOT LIKE '%Virtual%'";
            $search[] = "l.office NOT LIKE '%Charges%'";
            $search[] = "l.unittype != 'Deposit'";
            $search[] = "l.unittype != 'Utilities'";
            $search[] = "l.unittype != 'Telephone'";
            $search[] = "l.unittype != 'Fitout/Sundry'";
            $search[] = "l.unittype NOT LIKE '%Parking%'";
			$search = "WHERE " . implode(" AND ", $search);

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-revenue-by-office.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"reports-revenue-by-office.php?date=$prevmon&amp;lo=$lo&amp;ord=$ord\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.1em\"><b>Month</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
            print "<td align=\"right\"><b>Site</b></td>\n";
			print "<td><select name=\"lo\" class=\"element_small\">\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$lresult = $dbcnx->query($sql);
			if (($lresult) && ($lresult->num_rows)) {
				while ($lrow = $lresult->fetch_assoc()) {
					$ltitle  = $lrow['title'];
					print "		<option value=\"$ltitle\""; if ($lo == $ltitle) { print " SELECTED"; } print ">$ltitle</option>\n";
				}
			}
			print "</select>";
            print "</td>\n";
            /* print "<td align=\"right\"><b>Order</b></td>\n";
            print "<td><select name=\"ord\" class=\"element_small\">\n";
            print "<option value=\"ASC\""; if ($ord == "ASC") { print " SELECTED"; } print ">Acsending</option>\n";
            print "<option value=\"DESC\""; if ($ord == "DESC") { print " SELECTED"; } print ">Descending</option>\n";
            print "</select>";
            print "</td>\n"; */
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"reports-revenue-by-office.php?date=$nextmon&amp;lo=$lo&amp;ord=$ord\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";

            print "<p align=\"center\" class=\"heading\">$lo - $thismonth</p>\n";

			$sql = "SELECT h.month, l.office, SUM(l.linenet) AS net, SUM(l.vattotal) AS vat, SUM(l.linetotal) AS tot
                    FROM inv_lines AS l
					LEFT JOIN inv_headers AS h ON l.hid = h.ID
					$search
					GROUP BY l.office
					ORDER BY l.office
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                print "<table align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Office</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" width=\"60\" align=\"right\"><b>Net</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" width=\"60\" align=\"right\"><b>Vat</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" width=\"60\" align=\"right\"><b>Total</b></td>\n";
                print "</tr>\n";

                // row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                while ($row = $result->fetch_assoc()) {

                    $office = $row['office'];
                    $net = $row['net'];
                    $vat = $row['vat'];
                    $tot = $row['tot'];

                    $totnet += $net;
                    $totvat += $vat;
                    $tottot += $tot;
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td><a href=\"reports-revenue-by-office-detail.php?date=$date&amp;lo=$lo&amp;of=$office\">$office</a></td>\n";
                    print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
                    print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
                    print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\" width=\"130px\">&nbsp;</td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totnet,0) . "</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totvat,0) . "</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($tottot,0) . "</b></td>\n";
                print "</tr>\n";
                print "</table>\n";

                print "<p align=\"center\"><a href=\"reports-revenue-by-office-dnl.php?date=$date&amp;lo=$lo&amp;ord=$ord\" target=\"_blank\">Download Csv</a></p>\n";
                
                // jquery date picker script
                print "<script type=\"text/javascript\">\n";
                print "	$( document ).ready(function() {\n";
                print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
                print "	});\n";
                print "</script>\n";

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
