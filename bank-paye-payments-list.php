<?php
$page = "Bank";
$subpage = "Ni and Paye";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $y = $_REQUEST['y'];
            if (!$y) { $y = date("Y"); } 
            $prevy = date("Y",strtotime("$y-06-06 -1 Year"));
            $nexty = date("Y",strtotime("$y-06-06 +1 Year"));
            
            print "<p align=\"center\" class=\"heading\">BANK</p>\n";
		    include('bank-payroll-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Payroll Ni and Paye - $y</p>\n";

            // print filter form
            print "<form name=\"addform\" method=\"post\" action=\"bank-paye-payments-list.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"bank-paye-payments-list.php?y=$prevy\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.1em\"><b>Year</b></td>\n";
            print "<td><input type=\"text\" name=\"y\" value=\"$y\" class=\"element_tiny\" /></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"bank-paye-payments-list.php?y=$nexty\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";
            
            $sql = "SELECT ID, year, month, day, amount, dephnaref, bankref, details, pdf
                    FROM bank
                    WHERE txtype = 'DR' AND costcentre = '55' AND year = '$y'
                    ORDER BY year DESC, month DESC, day DESC
            ";
            
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
                print "	    <td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\"><b>Dephna Ref</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\"><b>Bank Ref</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amount</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" width=\"20\" align=\"centre\">&nbsp;</td>\n";
                print "	</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                while ($row = $result->fetch_assoc()) {
                    $id     = $row['ID'];
                    $year   = $row['year'];
                    $month  = $row['month'];
                    $day    = $row['day'];
                    $amount = $row['amount'];
                    $pdf    = $row['pdf'];
                    $dephnaref = $row['dephnaref'];
                    $bankref = $row['bankref'];
                    $total += $amount;

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$day-$month-$year</td>\n";
                    print "<td>$dephnaref</td>\n";
                    print "<td>$bankref</td>\n";
                    print "<td align=\"right\">".number_format($amount,2)."</td>\n";
                    if ($pdf) {
                        print "	<td width=\"20px\" align=\"center\"><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">Pdf</a></td>\n";
                    } else {
                        print "<td>&nbsp;</td>\n"; 
                    }
                    print "</tr>\n";

                }

                $numrows = $result->num_rows;
                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
                print "	<td colspan=\"3\" align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($total,2)."</b></td>\n";
                print "</tr>\n";

                print "</table>\n";
            } else {
                $resultMessage = "<b>Nothing Found!</b>";
            }
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
