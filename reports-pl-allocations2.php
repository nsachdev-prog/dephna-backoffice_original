<?php
$page = "Reporting";
$subpage = "Check PL Outstanding";
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

		print "<p align=\"center\" class=\"larger\">Check Purchase Ledger Outstanding</p>\n";
		include('reporting-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		// filter form
		print "<form name=\"addform\" method=\"post\" action=\"reports-pl-allocations2.php\">\n";
		print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
		print "<tr>\n";
		$sql = "SELECT `year` FROM purchases GROUP BY `year` ORDER BY `year` DESC";
		if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
			print "		<td align=\"right\"><b>Year</b></td>\n";
			print "		<td><select name=\"ye\" class=\"element_smaller\">\n";
			while ($row = $result->fetch_assoc()) {
				$year = $row['year'];
				if ($year > 2018) {
					print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";
		}
		print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
		print " <td><a href=\"reports-expenses.php\">Reset</a></td>\n";
		print "</tr>";
		print "</table>\n";
		print "</form>\n";

		if ($ye == date("Y")) {
			$table = "purchases";
			$allto = date("Y-m-d");
		} else {
			$table = "{$ye}_purchases";
			$allto = "$ye-12-31";
		}

		print "<p align=\"center\">Invoices/Credits marked as paid but still outstanding.<br>Uses current ledger in current year, archived ledgers in previous years.</p>\n";

        $sql = "SELECT h.ID, h.total, h.outstanding, h.updby,
                        c.suppname, h.dephnaref, h.txtype, h.year, h.month, h.day, h.suppid
                FROM $table AS h
                LEFT JOIN suppliers AS c ON c.ID = h.suppid
                WHERE h.year = '$ye'
                  AND h.paidon > '0'
                  AND (h.txtype = 'I' OR h.txtype = 'C')
                  AND h.outstanding = h.total
                ORDER BY h.year, h.month, h.day, h.dephnaref
        ";

		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
		
			print "<table align=\"center\" width=\"98%\" cellspacing=\"0px\" cellpadding=\"4px\">\n";

			print "<tr>\n";
			print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999\"><b>T</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
            print "<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
            print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Inv Total</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999\"><b>Alloc By</b></td>\n";
			print "<tr>\n";

            $outtot = 0;

			while ($row = $result->fetch_assoc()) {

				$invnum   = $row['dephnaref'];
				$txtype   = $row['txtype'];
                $suppid   = $row['suppid'];
				$suppname = $row['suppname'];
				$year     = $row['year'];
				$month    = $row['month'];
				$day      = $row['day'];
                $adate    = $row['date'];
				$invtotal = $row['total'];
				$outstanding = $row['outstanding'];
				$updby = $row['updby'];

                $outtot += $outstanding;

                print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                print "<td valign=\"top\">$day-$month-$year</td>\n";
                print "<td valign=\"top\">$txtype</td>\n";
                print "<td valign=\"top\">$invnum</td>\n";
                print "<td valign=\"top\">$suppname</td>\n";
                print "<td valign=\"top\" align=\"right\">".number_format($invtotal,2)."</td>\n";
                print "<td valign=\"top\" align=\"right\" class=\"$class\">".number_format($outstanding,2)."</td>\n";
                print "<td valign=\"top\">$updby</td>\n";
                print "<tr>\n";

			}
            $diff = $invtot - $amttot;
			print "<tr>\n";
			print "<td style=\"border-top:1px solid #999999\" colspan=\"5\">&nbsp;</td>\n";
            print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($outtot,2)."</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";

			print "</table>\n";


		} else {
			$resultMessage .= "Nothing Found</b><br>" . $dbcnx->error;
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
