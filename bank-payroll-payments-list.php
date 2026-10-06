<?php
$page = "Bank";
$subpage = "Payments";
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
			print "<p align=\"center\" class=\"larger\">Payroll Payments - $y</p>\n";

			// print filter form
			print "<form name=\"addform\" method=\"post\" action=\"bank-payroll-payments-list.php\">\n";
			print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
			print "<tr>\n";
			print "<td style=\"font-size:1.6em\"><a href=\"bank-payroll-payments-list.php?y=$prevy\"><b>&lt;&lt;</b></a></td>\n";
			print "<td style=\"font-size:1.1em\"><b>Year</b></td>\n";
			print "<td><input type=\"text\" name=\"y\" value=\"$y\" class=\"element_tiny\" /></td>\n";
			print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "<td style=\"font-size:1.6em\"><a href=\"bank-payroll-payments-list.php?y=$nexty\"><b>&gt;&gt;</b></a></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
  
			$sql = "SELECT h.ID, h.year, h.month, h.day, h.amount, h.ccid, c.title AS cctitle
					FROM bank_payroll_headers AS h
					LEFT JOIN costcentres AS c ON c.ID = h.ccid
					WHERE h.ccid = '66' AND h.year = '$y'
					ORDER BY h.year DESC, h.month DESC, h.day DESC
			";
			
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Type</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amount</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\" width=\"66\">&nbsp;</td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\" width=\"40\">&nbsp;</td>\n";
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
					$cctitle = $row['cctitle'];
					$total += $amount;

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "<td><a href=\"bank-payroll-payment.php?id=$id&amp;y=$y\">$day-$month-$year</a></td>\n";
					print "<td>$cctitle</td>\n";
					print "<td align=\"right\">".number_format($amount,2)."</td>\n";
					print "<td align=\"right\"><a href=\"bank-payroll-bankcsv.php?id=$id&amp;y=$y\">Bank CSV</a></td>\n";
					print "<td align=\"right\"><a href=\"bank-payroll-delete.php?id=$id&amp;y=$y\">Delete</a></td>\n";
					print "</tr>\n";

				}

				$numrows = $result->num_rows;
				print "<tr>\n";
				print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
				print "	<td colspan=\"2\" align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($total,2)."</b></td>\n";
				print "	<td colspan=\"2\" align=\"right\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
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
