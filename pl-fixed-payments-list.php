<?php
$page = "Purchases";
$subpage = "Fixed Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
            
            print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
		    include('pl-fixed-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Fixed Payments</p>\n";

            
            $sql = "SELECT f.*, s.suppname, c.title AS costcentre
                    FROM fixedpmts AS f
                    LEFT JOIN suppliers AS s ON s.ID = f.suppid
                    LEFT JOIN costcentres AS c ON c.ID = f.ccid
                    ORDER BY day, suppname
            ";
            
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
                print "	    <td style=\"border-bottom:1px solid #999\"><b>Day</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\"><b>Supplier/CC</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\"><b>Details</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\"><b>Invoice</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Net</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Vat</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Total</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\" width=\"24\">&nbsp;</td>\n";
                print "	    <td style=\"border-bottom:1px solid #999\" width=\"30\">&nbsp;</td>\n";
                print "	</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                while ($row = $result->fetch_assoc()) {
                    $id         = $row['ID'];
                    $day        = $row['day'];
                    $ledger     = $row['ledger'];
                    $suppname   = $row['suppname'];
                    $costcentre = $row['costcentre'];
                    $details    = $row['details'];
                    $net        = $row['net'];
                    $vat        = $row['vat'];
                    $total      = $row['total'];
                    $make_inv   = $row['make_inv'];
                    $suppid      = $row['suppid'];
                    $pagetotal += $total;
                    if ($ledger == "BK") { $suppname = $costcentre; }
                    if (strlen($suppname) > 20) { $suppname = substr($suppname, 0, 20); }
                    if (strlen($details) > 20) { $details = substr($details, 0, 20); }
                    if ($make_inv) { $make_inv = "Yes"; } else { $make_inv = "No"; }
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$day</td>\n";
                    print "<td>$ledger</td>\n";
                    print "<td><a href=\"pl-ledger.php?id=$suppid&from=ledgers\">$suppname</a></td>\n";
                    print "<td>$details</td>\n";
                    print "<td>$make_inv</td>\n";
                    print "<td align=\"right\">".number_format($net,2)."</td>\n";
                    print "<td align=\"right\">".number_format($vat,2)."</td>\n";
                    print "<td align=\"right\">".number_format($total,2)."</td>\n";
                    print "<td><a href=\"pl-fixed-payments-addedit.php?id=$id\">Edit</a></td>\n";
                    print "<td><a href=\"pl-fixed-payments-delete.php?id=$id\">Delete</a></td>\n";
                    print "</tr>\n";

                }

                $numrows = $result->num_rows;
                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
                print "	<td colspan=\"7\" align=\"right\" style=\"border-top:1px solid #999\"><b>".number_format($pagetotal,2)."</b></td>\n";
                print "	<td colspan=\"2\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
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
    print "<p align=\"center\"><a href=\"pl-fixed-payments-addedit.php\">Add New Fixed Payment</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
