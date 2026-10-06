<?php
$page = "Bank";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"heading\">BANK</p>\n";
			include('bank-payroll-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Payroll Ledgers</p>\n";

			
			$sql = "SELECT ID, username
					FROM staff
					ORDER BY username
			";
			
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Last Paid</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amount</b></td>\n";
				print "	</tr>\n";

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id     = $row['ID'];
					$staffname = $row['username'];

					// get last paid
					$sql = "SELECT l.amount, h.year, h.month, h.day
							FROM bank_payroll_lines AS l
							LEFT JOIN bank_payroll_headers AS h ON h.ID = l.hid
							WHERE l.sid = '$id'
							ORDER BY h.year DESC, h.month DESC, h.day DESC
							LIMIT 0,1
					";
					if ($aresult = $dbcnx->query($sql) AND $aresult->num_rows) {
						$arow   = $aresult->fetch_assoc();
						$year   = $arow['year'];
						$month  = $arow['month'];
						$day    = $arow['day'];
						$amount = $arow['amount'];
						$thisdate = "$day-$month-$year";

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "<td><a href=\"bank-payroll-ledger.php?id=$id\">$staffname</a></td>\n";
						print "<td>$thisdate</td>\n";
						print "<td align=\"right\">".number_format($amount,2)."</td>\n";
						print "</tr>\n";

					}

				}

				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"3\">&nbsp;</td>\n";
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
