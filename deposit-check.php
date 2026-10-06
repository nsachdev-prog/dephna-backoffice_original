<?php
$page = "Reporting";
$subpage = "Deposits1";
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
		print "<p align=\"center\">The 'Type Of Sale' selected for Deposit Invoices and Credits should be 'Deposits'</p>\n";

		$sql = "SELECT l.details1, l.details2, l.unittype, h.invnumber, h.txtype, l.linetotal, c.compname, h.year, h.month, h.day, l.unittype
				FROM `inv_lines` AS l
				LEFT JOIN `inv_headers` AS h ON h.ID = l.hid
				LEFT JOIN users AS c ON c.custref = h.custref
				WHERE (l.details2 LIKE '%Deposit%' OR l.details1 LIKE '%Deposit%' )
					AND l.unittype != 'Deposit'
					AND h.year = '2021'
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
			print "<tr>\n";

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

				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "<td valign=\"top\">$day-$month-$year</td>\n";
				print "<td valign=\"top\">$txtype</td>\n";
				print "<td valign=\"top\">$invnum</td>\n";
				print "<td valign=\"top\">$details1</td>\n";
				print "<td valign=\"top\">$details2</td>\n";
				print "<td valign=\"top\">$unittype</td>\n";
				print "<td valign=\"top\" align=\"right\">$linetotal</td>\n";
				print "<tr>\n";

			}

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
	print "<p align=\"center\"><a href=\"acc-periods-list.php\">Periods</a></p>\n";
	print "</td>\n";
	include('footer.php');
}
?>
