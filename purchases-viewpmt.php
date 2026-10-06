<?php
$page = "Purchases";
$subpage = "Purchase Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$txtypes = array("I"=>"Invoice","C"=>"Credit Note");
			
			if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
			if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
			if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
			if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; }
			if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
			if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
			$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";
			
			$id = $_REQUEST['id'];
			if ($id) {
				$sql = "SELECT p.ID, p.dephnaref, p.year, p.month, p.day, p.notes,
							   p.suppid, p.net, p.vat, p.total, p.paidon,
							   s.suppname
					FROM purchases p
					JOIN suppliers s ON p.suppid=s.ID
					WHERE p.ID='$id'
				";
				if ($result = $dbcnx->query($sql)) {
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$date = $row['day']."/".$row['month']."/".$row['year'];
						$paidon = $row['paidon'];
						$paidon = date("d/m/Y",$paidon);
						print "<p align=\"center\" class=\"larger\">View Payment</p>\n";

						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Supplier:</b></td>\n";
						print "	<td>{$row['suppname']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Date:</b></td>\n";
						print "	<td>$date</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Dephna Ref:</b></td>\n";
						print "	<td>{$row['dephnaref']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Net:</b></td>\n";
						print "	<td>" . number_format($row['net'],2) . "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>VAT:</b></td>\n";
						print "	<td>" . number_format($row['vat'],2) . "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Total:</b></td>\n";
						print "	<td>" . number_format($row['total'],2) . "</td>\n";
						print "</tr>\n";

						print "</table>\n";

						print "<p align=\"center\" class=\"heading\">Allocation</p>\n";

						$sql = "SELECT ID, txtype, dephnaref, supplierref, year, month, day, net, vat, total
								FROM purchases
								WHERE allocatedto='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							if ($result->num_rows) {
								print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
								print "<tr>\n";
								print "<td><b>Date</b></td>\n";
								print "<td><b>Type</b></td>\n";
								print "<td><b>Dephna Ref</b></td>\n";
								print "<td><b>Supplier Ref</b></td>\n";
								print "<td align=\"right\"><b>Net</b></td>\n";
								print "<td align=\"right\"><b>VAT</b></td>\n";
								print "<td align=\"right\"><b>Total</b></td>\n";
								print "</tr>\n";
								while ($row = $result->fetch_assoc()) {
									$date = $row['day']."/".$row['month']."/".$row['year'];
									$txtype = $row['txtype'];
									$txtype = $txtypes[$txtype];
									$dephnaref = $row['dephnaref'];
									$supplierref = $row['supplierref'];
									$net = $row['net'];
									$vat = $row['vat'];
									$total = $row['total'];
									print "<tr>\n";
									print "<td>$date</td>\n";
									print "<td>$txtype</td>\n";
									print "<td>$dephnaref</td>\n";
									print "<td>$supplierref</td>\n";
									print "<td align=\"right\">$net</td>\n";
									print "<td align=\"right\">$vat</td>\n";
									print "<td align=\"right\">$total</td>\n";
									print "</tr>\n";
								}
								print "</table>\n";
							}
						}

					} else {
						$resultMessage = "<b>Transactions not found!</b>";
					}
				} else {
					$resultMessage = "<b>Could not get transaction from database</b><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<b>Page called without id</b>";
			}	
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"purchases-ledger.php?$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
