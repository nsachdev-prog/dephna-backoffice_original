<?php
$page = "Reporting";
$subpage = "Deposits Wronly Assigned";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');

include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	// open database
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; } else { $ye = date("Y"); }

		print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";

		print "<p align=\"center\" class=\"larger\">Deposits Wrongly Assigned</p>\n";
		include('reporting-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		// filter form
		print "<form name=\"addform\" method=\"post\" action=\"reports-deposits1.php\">\n";
		print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
		print "<tr>\n";
		$sql = "SELECT `year` FROM inv_headers GROUP BY `year` ORDER BY `year` DESC";
		if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
			print "		<td align=\"right\"><b>Year</b></td>\n";
			print "		<td><select name=\"ye\" class=\"element_smaller\">\n";
			while ($row = $result->fetch_assoc()) {
				$year = $row['year'];
				print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
			}
			print "		</select>";
			print "		</td>\n";
		}if ($n == 6) {
			$menustr[] = "<br>";
			$n = 0;
		}
		print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
		print " <td><a href=\"reports-expenses.php\">Reset</a></td>\n";
		print "</tr>";
		print "</table>\n";
		print "</form>\n";

		print "<p align=\"center\">The 'Type Of Sale' selected for Contract Deposit Invoices and Credits should be 'Deposit'.<br>";
		print "This looks for the word deposit in details1 &amp; details2 to find possible problems.<br>The results may not be wrong because deposits can also be paid on fitouts.";
		print "</p>\n";

		$sql = "SELECT l.details1, l.details2, l.unittype, h.invnumber, h.txtype, l.linetotal, c.compname,
					h.year, h.month, h.day, l.unittype, h.crby
				FROM `inv_lines` AS l
				LEFT JOIN `inv_headers` AS h ON h.ID = l.hid
				LEFT JOIN users AS c ON c.custref = h.custref
				WHERE (l.details2 LIKE '%Deposit%' OR l.details1 LIKE '%Deposit%' )
					AND l.unittype != 'Deposit'
					AND h.year = '$ye'
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
		

			print "<table align=\"center\" width=\"98%\" cellspacing=\"0px\" cellpadding=\"4px\">\n";

			print "<tr>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Date</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">T</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Ref</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Details1</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Details2</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">TypeOfSale</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\" align=\"right\">Total</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Entered By</td>\n";
			print "<tr>\n";

			$total = 0;

			while ($row = $result->fetch_assoc()) {

				$invnum   = $row['invnumber'];
				$txtype   = $row['txtype'];
				$compname = $row['compname'];
				$details1 = $row['details1'];
				$details2 = $row['details2'];
				$year     = $row['year'];
				$month    = $row['month'];
				$day      = $row['day'];
				$linetotal = $row['linetotal'];
				$unittype = $row['unittype'];
				$crby = $row['crby'];

				$total += $linetotal;

				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "<td valign=\"top\">$day-$month-$year</td>\n";
				print "<td valign=\"top\">$txtype</td>\n";
				print "<td valign=\"top\">$invnum</td>\n";
				print "<td valign=\"top\">$details1</td>\n";
				print "<td valign=\"top\">$details2</td>\n";
				print "<td valign=\"top\">$unittype</td>\n";
				print "<td valign=\"top\" align=\"right\">".number_format($linetotal,2)."</td>\n";
				print "<td valign=\"top\">$crby</td>\n";
				print "<tr>\n";

			}
			print "<tr>\n";
			print "<td style=\"border-top:1px solid #999999\"><b>" . $result->num_rows . "</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\" colspan=\"5\">&nbsp;</td>\n";
			print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($total,2)."</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";

			print "</table>\n";

		} else {
			$resultMessage .= "Could not get results!</b><br>" . $dbcnx->error;
		}
		
		$dbcnx->close();

	} else {
		$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
	}
		
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
if ($print) {
	?>
	</body>
</html>
<?php
} else {
	print "</td>\n";
	include('footer.php');
}
?>
