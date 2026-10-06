<?php
$page = "Reporting";
$subpage = "Unpaid Deposits";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['yr']) { $yr = $_REQUEST['yr']; }
            if (!$yr) {
                $yr = date("Y") - 1;
            }

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">$subpage At Year End</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

            $sql = "SELECT h.*, c.compname, oh.ID AS invid, c.ID AS custid
                    FROM 2021_inv_headers AS h
                    LEFT JOIN inv_headers AS oh ON oh.invnumber = h.invnumber
                    LEFT JOIN inv_lines AS l ON l.hid = oh.ID
                    LEFT JOIN users AS c ON c.custref = h.custref
                    WHERE h.outstanding > '0'
                      AND l.unittype = 'Deposit'
                    GROUP BY h.invnumber
                    ORDER BY h.year, h.month, h.day
            ";
            
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"2\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Inv Number</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Amount</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Outstanding</b></td>\n";
                print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
                    $y   = $row['year'];
                    $m   = $row['month'];
                    $d   = $row['day'];
                    $compname  = $row['compname'];
                    $invid     = $row['invid'];
                    $custid    = $row['custid'];
                    $invnumber = $row['invnumber'];
                    $invtotal  = $row['invtotal'];
                    $outstanding  = $row['outstanding'];
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$d-$m-$y</td>\n";
                    print "	<td><a href=\"customer-pdf-invoice.php?inv=$invnumber\" target=\"_blank\">$invnumber</a></td>\n";
                    print "	<td><a href=\"sl-ledger.php?id=$custid&amp;yr=2021\">$compname</a></td>\n";
                    print "	<td>$invtotal</td>\n";
					print "	<td>$outstanding</td>\n";
					print "</tr>\n";
					
				}

                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                
				print "</table>\n";

			} else {
				print "	<p align=\"center\">No Customers Found<br>" . $dbcnx->error . "</p>\n";
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
