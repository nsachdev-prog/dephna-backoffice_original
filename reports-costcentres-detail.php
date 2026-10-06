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
			if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
            if ($_REQUEST['to']) { $to = $_REQUEST['to']; }
            if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; }
            if ($_REQUEST['mo']) { $mo = $_REQUEST['mo']; }
            if ($_REQUEST['ccid']) { $ccid = $_REQUEST['ccid']; }

            // get costcentre
            $sql = "SELECT title FROM costcentres WHERE ID = '$ccid'";
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $costcentre = $row['title'];
            }

            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">Expenses for $costcentre</span><br><b>{$months[$mo]} $ye</b></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

            $sql = "SELECT l.net, l.vat, l.total,
                            p.year, p.month, p.day, p.txtype, p.dephnaref, p.supplierref,
                            s.suppname
                    FROM purchases_lines AS l
                    LEFT JOIN purchases AS p ON p.ID = l.hid
                    LEFT JOIN suppliers AS s ON s.ID = p.suppid
                    WHERE p.year = '$ye'
                        AND p.month = '$mo'
                        AND (p.txtype = 'I' OR p.txtype = 'C')
                        AND l.ccid = '$ccid'
            ";
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\"><b>Supplier</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\"><b>Type</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\"><b>Our Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\"><b>Supp Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Net</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Vat</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Total</b></td>\n";
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
                    $txtype	   = $row['txtype'];
                    $dephnaref = $row['dephnaref'];
                    $supplierref = $row['supplierref'];
                    $year      = $row['year'];
                    $month     = $row['month'];
                    $day       = $row['day'];
                    $total     = $row['total'];
                    $net       = $row['net'];
                    $vat       = $row['vat'];
                    $suppname  = $row['suppname'];

                    if (strlen($suppname) > 20) { $suppname = substr($suppname, 0, 20); }

                    $totnet += $net;
                    $totvat += $vat;
                    $tottot += $total;
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$year-$month-$day</td>\n";
                    print "	<td>$suppname</td>\n";
                    print "<td>{$txtypes[$txtype]}</td>\n";
                    print "	<td>$dephnaref</td>\n";
                    print "	<td>$supplierref</td>\n";
                    print "	<td align=\"right\">".number_format($net,2)."</td>\n";
                    print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
                    print "	<td align=\"right\">".number_format($total,2)."</td>\n";
                    print "</tr>\n";
                }

                $numrows = $result->num_rows;
                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
				print "<td align=\"right\" style=\"border-top:1px solid #999\"><b>".number_format($totnet,2)."</b></td>\n";
				print "<td align=\"right\" style=\"border-top:1px solid #999\"><b>".number_format($totvat,2)."</b></td>\n";
				print "<td align=\"right\" style=\"border-top:1px solid #999\"><b>".number_format($tottot,2)."</b></td>\n";
				print "</tr>\n";
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
        print "<p align=\"center\"><a href=\"reports-costcentres.php?fr=$fr&amp;to=$to&amp;ye=$ye\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
