<?php
$page = "Reporting";
$subpage = "DB Integrity Check";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');

include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	// open database
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; } else { $ye = date("Y") -1; }
        $currentYear = date("Y");

		print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";

		print "<p align=\"center\" class=\"larger\">Database Integrity Check</p>\n";
		include('reporting-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		// filter form
		print "<form name=\"addform\" method=\"post\" action=\"reports-integrity.php\">\n";
		print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
		print "<tr>\n";
		$sql = "SELECT `year` FROM inv_headers GROUP BY `year` ORDER BY `year` DESC";
		if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
			print "		<td align=\"right\"><b>Year</b></td>\n";
			print "		<td><select name=\"ye\" class=\"element_smaller\">\n";
			while ($row = $result->fetch_assoc()) {
				$year = $row['year'];
                if ($year < $currentYear AND $year > 2018) {
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

		print "<p align=\"center\"><b>Check previous years gross transaction values against current ledger</b></p>\n";

        print "<p align=\"center\"><b>Sales Ledger</b></p>\n";

        $table = "{$ye}_inv_headers";

        $sql = "SELECT ID, txtype, invnumber, `year`, `month`, `day`, custref, invtotal, crby
                FROM (
                SELECT ID, invtotal FROM $table
                UNION ALL
                SELECT ID, invtotal FROM inv_headers
                ) tbl
                WHERE `year` = '$ye'
                GROUP BY ID
                HAVING count(*) = 1
                ORDER BY `year`, `month`, `day`
        ";

        /* $sql = "SELECT t1.* FROM $table AS t1
                LEFT JOIN inv_headers AS t2 ON t2.ID = t1.ID
                WHERE t1.year = '$ye'
                AND t2.ID IS NULL
        "; */
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			print "<table align=\"center\" width=\"98%\" cellspacing=\"0px\" cellpadding=\"4px\">\n";

			print "<tr>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Date</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">T</td>\n";
            print "<td style=\"border-bottom:1px solid #999999\">Custref</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Ref</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\" align=\"right\">Total</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Entered By</td>\n";
			print "<tr>\n";

			$total = 0;

			while ($row = $result->fetch_assoc()) {

				$invnum   = $row['invnumber'];
				$txtype   = $row['txtype'];
				$year     = $row['year'];
				$month    = $row['month'];
				$day      = $row['day'];
				$invtotal = $row['invtotal'];
				$custref  = $row['custref'];
				$crby = $row['crby'];

				$total += $linetotal;

				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "<td valign=\"top\">$day-$month-$year</td>\n";
				print "<td valign=\"top\">$txtype</td>\n";
                print "<td valign=\"top\">DP$custref</td>\n";
				print "<td valign=\"top\">$invnum</td>\n";
				print "<td valign=\"top\" align=\"right\">".number_format($linetotal,2)."</td>\n";
				print "<td valign=\"top\">$crby</td>\n";
				print "<tr>\n";

			}
			print "<tr>\n";
			print "<td style=\"border-top:1px solid #999999\"><b>" . $result->num_rows . "</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\" colspan=\"3\">&nbsp;</td>\n";
			print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($total,2)."</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";

			print "</table>\n";

		} else {
			print "<p align=\"center\">Okay. All Values Match</p>";
		}


        print "<p align=\"center\"><b>Purchase Ledger</b></p>\n";

        $table = "{$ye}_purchases";

        $sql = "SELECT ID, txtype, dephnaref, `year`, `month`, `day`, suppid, total, updby
                FROM (
                SELECT ID, total FROM $table
                UNION ALL
                SELECT ID, total FROM purchases
                ) tbl
                WHERE `year` = '$ye'
                GROUP BY ID
                HAVING count(*) = 1
                ORDER BY `year`, `month`, `day`
        ";

		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			print "<table align=\"center\" width=\"98%\" cellspacing=\"0px\" cellpadding=\"4px\">\n";

			print "<tr>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Date</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">T</td>\n";
            print "<td style=\"border-bottom:1px solid #999999\">Custref</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Ref</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\" align=\"right\">Total</td>\n";
			print "<td style=\"border-bottom:1px solid #999999\">Entered By</td>\n";
			print "<tr>\n";

			$tot = 0;

			while ($row = $result->fetch_assoc()) {

				$dephnaref = $row['dephnaref'];
				$txtype   = $row['txtype'];
				$year     = $row['year'];
				$month    = $row['month'];
				$day      = $row['day'];
				$total    = $row['total'];
				$suppid   = $row['suppid'];
				$updby    = $row['updby'];

				$tot += $total;

				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "<td valign=\"top\">$day-$month-$year</td>\n";
				print "<td valign=\"top\">$txtype</td>\n";
                print "<td valign=\"top\">SID$suppid</td>\n";
				print "<td valign=\"top\">$dephnaref</td>\n";
				print "<td valign=\"top\" align=\"right\">".number_format($total,2)."</td>\n";
				print "<td valign=\"top\">$updby</td>\n";
				print "<tr>\n";

			}
			print "<tr>\n";
			print "<td style=\"border-top:1px solid #999999\"><b>" . $result->num_rows . "</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\" colspan=\"3\">&nbsp;</td>\n";
			print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($tot,2)."</b></td>\n";
			print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";

			print "</table>\n";

		} else {
			print "<p align=\"center\">Okay. All Values Match</p>";
		}

		$table = "{$ye}_inv_headers";

		print "<p align=\"center\"><b>Check SL Control against SL Transactions</b></p>\n";
		$sql = "SELECT SUM(l.linetotal) AS ltotal
				FROM inv_lines AS l
				LEFT JOIN $table AS h ON h.ID = l.hid
				WHERE h.year = '$ye'
				  AND (h.txtype = 'I' OR h.txtype = 'C')
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$ltotal = $row['ltotal'];
			$ltotal = round($ltotal, 2);

			$sql = "SELECT SUM(invtotal) AS htotal
					FROM $table
					WHERE `year` = '$ye'
					AND (txtype = 'I' OR txtype = 'C')
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$htotal = $row['htotal'];
				$diff = $htotal - $ltotal;
				if ($diff) {
					print "<p align=\"center\">Lines: ".number_format($ltotal,2)."<br>Control: ".number_format($htotal,2)."<br>Difference Of: ".number_format($diff,2)."</p>\n";
				} else {
					print "<p align=\"center\">Lines: ".number_format($ltotal,2)."<br>Control: ".number_format($htotal,2)."<br>Okay. No Difference</p>\n";
				}

			} else {
				print "<p align=\"center\">Headers Error" . $dbcnx->error . "</p>\n";
			}

		} else {
			print "<p align=\"center\">Lines Error" . $dbcnx->error . "</p>\n";
		}

		$table = "{$ye}_purchases";

		print "<p align=\"center\"><b>Check PL Control against PL Transactions</b></p>\n";
		$sql = "SELECT SUM(l.total) AS ltotal
				FROM purchases_lines AS l
				LEFT JOIN $table AS h ON h.ID = l.hid
				WHERE h.year = '$ye'
				  AND (h.txtype = 'I' OR h.txtype = 'C')
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$ltotal = $row['ltotal'];
			$ltotal = round($ltotal, 2);

			$sql = "SELECT SUM(total) AS htotal
					FROM $table
					WHERE `year` = '$ye'
					AND (txtype = 'I' OR txtype = 'C')
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$htotal = $row['htotal'];
				$diff = $htotal - $ltotal;
				if ($diff) {
					print "<p align=\"center\">Lines: ".number_format($ltotal,2)."<br>Control: ".number_format($htotal,2)."<br>Difference Of: ".number_format($diff,2)."</p>\n";
				} else {
					print "<p align=\"center\">Lines: ".number_format($ltotal,2)."<br>Control: ".number_format($htotal,2)."<br>Okay. No Difference</p>\n";
				}

			} else {
				print "<p align=\"center\">Headers Error" . $dbcnx->error . "</p>\n";
			}

		} else {
			print "<p align=\"center\">Lines Error" . $dbcnx->error . "</p>\n";
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
