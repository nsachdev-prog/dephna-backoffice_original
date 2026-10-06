<?php
$page = "Bank";
$subpage = "Pending";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">BANK</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('bank-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Pending Bank Transactions</p>\n";

            $sql = "SELECT ID, txtype, year, month, day, amount, dephnaref, details, bankref
					FROM bank
					WHERE BINARY txtype = 'dr' OR BINARY txtype = 'cr'
					ORDER BY year, month, day
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Dephna Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Bank Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\">&nbsp;</td>\n";
				print "</tr>\n";
				
				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				$types = array(
					"dr"=>"Debit (p)",
					"cr"=>"Credit (p)"
				);

				while ($row = $result->fetch_assoc()) {

                    $id       = $row['ID'];
                    $year     = $row['year'];
                    $month    = $row['month'];
                    $day      = $row['day'];
                    $amount   = $row['amount'];
					$details  = $row['details'];
                    $txnnum   = $row['dephnaref'];
                    $bankref  = $row['bankref'];
					$txtype   = $row['txtype'];
					$total += $amount;

                    if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
                    
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$day-$month-$year</td>\n";
                    print " <td>{$types[$txtype]}</td>\n";
                    print "	<td>$txnnum</td>\n";
					print "	<td>$bankref</td>\n";
					print " <td>$details</td>\n";
					print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
                    print "	<td align=\"center\"><a href=\"bank-cleared.php?id=$id\">Cleared</a></td>\n";
                    print "</tr>\n";
                    
                }
                
				$numrows = $result->num_rows;
                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
				print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($total,2)."</b></td>\n";
				print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">No Pending Transactions Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
