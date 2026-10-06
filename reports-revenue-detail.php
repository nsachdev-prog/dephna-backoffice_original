<?php
$page = "Reporting";
$subpage = "Units By Revenue";
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
            if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; }
            if ($_REQUEST['mo']) { $mo = $_REQUEST['mo']; }
            if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; $search = "AND l.location = '$lo'"; }
            if ($_REQUEST['ut']) { $ut = $_REQUEST['ut']; }
            if ($lo) { $loc = $lo; } else { $loc = "All Locations"; }

            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">$ut - $loc</span><br><b>{$months[$mo]} $ye</b></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

            $sql = "SELECT SUM(l.linetotal) AS tot, SUM(l.vattotal) AS vat, SUM(l.linenet) AS net, h.txtype, h.invnumber, h.year, h.month, h.day, u.compname
                    FROM inv_lines AS l
                    LEFT JOIN inv_headers AS h ON l.hid = h.ID
                    LEFT JOIN users AS u ON u.custref = h.custref
                    WHERE h.year = '$ye' AND h.month = '$mo' AND l.unittype = '$ut' $search
                    GROUP BY h.invnumber
                    ORDER BY h.year, h.month, h.day
            ";
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                print "<table width=\"90%\" class=\"table1\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Net</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Vat</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
                print "</tr>\n";

                # row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                $txtypes = array(
                    "I"=>"Invoice",
                    "C"=>"Credit Note",
                    "P"=>"Payment",
                    "R"=>"Refund",
                    "p"=>"Payment (p)",
                    "BR"=>"Bank Receipt",
                    "BP"=>"Bank Payment",
                    "bp"=>"Bank Payment (p)"
                );

                while ($row = $result->fetch_assoc()) {

                    $invnumber= $row['invnumber'];
                    $txtype   = $row['txtype'];
                    $compname = $row['compname'];
                    $year     = $row['year'];
                    $month    = $row['month'];
                    $day      = $row['day'];
                    $net      = $row['net'];
                    $vat      = $row['vat'];
                    $total    = $row['tot'];

                    if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }

                    $totnet += $net;
                    $totvat += $vat;
                    $tottot += $total;

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$day-$month-$year</td>\n";
                    print "	<td>{$txtypes[$txtype]}</td>\n";
                    print "	<td><a href=\"customer-pdf-invoice.php?inv=$invnumber\" target=\"_blank\">$invnumber</a></td>\n";
                    print "	<td>$compname</td>\n";
                    print "	<td align=\"right\">".number_format($net,2)."</td>\n";
                    print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
                    print "	<td align=\"right\">".number_format($total,2)."</td>\n";
                    print "</tr>\n";

                }

                $numrows = $result->num_rows;
                print "<tr>\n";
                print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
                print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totnet,2)."</b></td>\n";
                print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totvat,2)."</b></td>\n";
                print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
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
        print "<p align=\"center\"><a href=\"reports-revenue.php?ye=$ye&amp;lo=$lo\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
