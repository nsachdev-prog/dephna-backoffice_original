<?php
$page = "Purchases";
$subpage = "Unallocated Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
    print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('purchases-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Unallocated Payments</p>\n";

            $sql = "SELECT s.ID, s.suppname, SUM(p.outstanding) AS total_outstanding
					FROM purchases AS p
					LEFT JOIN suppliers AS s ON p.suppid = s.ID
					WHERE p.txtype = 'P' AND p.outstanding < 0
					GROUP BY s.ID
					ORDER BY s.suppname
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Unallocated</b></td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$suppname  = $row['suppname'];
					$outstanding = $row['total_outstanding'];
					$pageTotal += $outstanding;
					if (strlen($suppname) > 40) { $suppname = substr($suppname, 0, 40); }
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td><a href=\"pl-ledger.php?id=$id&amp;pa=$pageNum&amp;$qString&amp;from=ledgers\">$suppname</a></td>\n";
					print "	<td align=\"right\">".number_format($outstanding,2)."</td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\" colspan=\"2\"><b>".number_format($pageTotal,2)."</b></td>\n";
				print "</tr>\n";
				
                print "</table>\n";

			} else {
				$resultMessage = "No Negative Accounts<br />".$dbcnx->error;
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
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
