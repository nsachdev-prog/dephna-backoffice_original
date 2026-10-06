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
			print "<p align=\"center\" class=\"heading\">$subpage Detail</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			// get filtering if any and build search array
			$date = $_REQUEST['date'];
            list($m,$y) = explode("-",$date);
            $thismonth = date("F Y",strtotime("$y-$m-01"));
            $lo = $_REQUEST['lo'];
            $of = $_REQUEST['of'];
            $search[] = "h.year = '$y'";
            $search[] = "h.month = '$m'";
            $search[] = "l.location = '$lo'";
            $search[] = "l.office = '$of'";
            $search[] = "l.unittype != 'Deposit'";
            $search[] = "l.unittype != 'Utilities'";
            $search[] = "l.unittype != 'Telephone'";
            $search[] = "l.unittype != 'Fitout/Sundry'";
            $search[] = "l.unittype NOT LIKE '%Parking%'";
			$search = "WHERE " . implode(" AND ", $search);

            print "<p align=\"center\" class=\"heading\">$lo - $of - $thismonth</p>\n";

			$sql = "SELECT h.year, h.month, h.day, h.invnumber, l.details1, l.linenet AS net, l.vattotal AS vat, l.linetotal AS tot
                    FROM inv_lines AS l
					LEFT JOIN inv_headers AS h ON l.hid = h.ID
					$search
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                print "<table width=\"80%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
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

                    $year     = $row['year'];
                    $month    = $row['month'];
                    $day      = $row['day'];
                    $ref      = $row['invnumber'];
                    $details1 = $row['details1'];
                    $net      = $row['net'];
                    $vat      = $row['vat'];
                    $tot      = $row['tot'];

                    $totnet += $net;
                    $totvat += $vat;
                    $tottot += $tot;
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$year-$month-$day</td>\n";
                    print "	<td>$ref</td>\n";
                    print "	<td>$details1</td>\n";
                    print "	<td align=\"right\">" . number_format($net,0) . "</td>\n";
                    print "	<td align=\"right\">" . number_format($vat,0) . "</td>\n";
                    print "	<td align=\"right\">" . number_format($tot,0) . "</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\" colspan=\"3\">&nbsp;</td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totnet,0) . "</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totvat,0) . "</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($tottot,0) . "</b></td>\n";
                print "</tr>\n";
                print "</table>\n";

			} else {
				$resultMessage = "Nothing Found<br />" . $dbcnx->error;
            }
            print "<p align=\"center\"><a href=\"reports-revenue-by-office.php?date=$date&amp;lo=$lo\">Return</a></p>\n";
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
