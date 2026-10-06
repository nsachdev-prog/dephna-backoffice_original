<?php
$page = "Telecoms";
$subpage = "Rentals";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Rental Charges for Active Bookings</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

        $ancid = $_REQUEST['ancid'];
        
        if ($ancid) {

            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                // get ancillary title
                $sql = "SELECT title FROM ancillaries WHERE ID = '$ancid'";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $anctite = $row['title'];
                }

                print "<p align=\"center\" class=\"heading\">$anctite</p>\n";

                // get available charges
                $sql = "SELECT b.custref, b.rate, b.qty, u.compname
                        FROM bookings_an AS b
                        LEFT JOIN ancillaries AS a ON a.title = b.anctitle
                        LEFT JOIN users AS u ON u.custref = b.custref
                        WHERE a.ancgroup = 'Telephone'
                            AND EXISTS (SELECT 1 FROM bookings WHERE custid = b.custref)
                            AND b.ancid = '$ancid'
                        ORDER BY u.compname, b.anctitle
                ";
                $active = "AND EXISTS (SELECT 1 FROM bookings WHERE custid = users.custref)";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                    print "	<tr>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Cust Name</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Qty</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Rate</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Charge</b></td>\n";
                    print "	</tr>\n";

                    # row background colours
                    print "<style>\n";
                    print ".r0 { background-color:#fff }\n";
                    print ".r1 { background-color:#ddd }\n";
                    print "</style>\n";

                    while ($row = $result->fetch_assoc()) {
                        $custref   = $row['custref'];
                        $compname  = $row['compname'];
                        $rate      = $row['rate'];
                        $qty       = $row['qty'];

                        $charge = $qty * $rate;
                        $totcharges += $charge;

                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	    <td>DP$custref</td>\n";
                        print "	    <td>$compname</td>\n";
                        print "	    <td align=\"right\">$qty</td>\n";
                        print "	    <td align=\"right\">".number_format($rate,2)."</td>\n";
                        print "	    <td align=\"right\">".number_format($charge,2)."</td>\n";
                        print "	  </tr>\n";

                    }

                    $totlines = $result->num_rows;

                    print "		<tr>\n";
                    print "		  	<td colspan=\"3\" style=\"border-top:1px solid #666\" align=\"right\"><b>$totlines</b></td>\n";
                    print "		  	<td colspan=\"2\" style=\"border-top:1px solid #666\" align=\"right\"><b>".number_format($totcharges,2)."</b></td>\n";
                    print "		</tr>\n";

                    print "</table>\n";
                    
                } else {
                    print "<p align=\"center\"><b>No calls found</b></p>";
                }
        
                $dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "<b>Page Called Without ANC ID</b><br>";
        }
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
        }
        print "<p align=\"center\"><a href=\"telecoms-rentals.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
