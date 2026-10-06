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

			$txtypes = array("I"=>"Invoice","C"=>"Credit Note","R"=>"Refund","BP"=>"Bank Payment","BR"=>"Bank Receipt");
			
			if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
			if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
			if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
			if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; }
			if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
			if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
			$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";
			
			$id = $_REQUEST['id'];
			$hid = $_REQUEST['hid'];
			if ($id) {
				$sql = "SELECT p.*, s.suppname, s.ID AS suppid, c.title
						FROM purchases p
						JOIN suppliers s ON p.suppid = s.ID
						JOIN costcentres c ON p.costcentre = c.ID
						WHERE p.ID = '$hid'
				";
				if ($result = $dbcnx->query($sql)) {
					if ($result->num_rows) {
						$row     = $result->fetch_assoc();
						$date    = $row['day']."/".$row['month']."/".$row['year'];
						$txtype  = $row['txtype'];
						$heading = $txtypes[$txtype];
						$paidon  = $row['paidon'];
						$oneoff  = $row['oneoff'];
						$pdf     = $row['pdf'];
						$suppid  = $row['suppid'];
						if ($paidon > 0) {
							$paidon = date("d/m/Y",$paidon);
						} else {
							$paidon = "Unpaid";
						}
						$topay = $row['topay'];
						if ($topay > 0) {
							$topay = "- Being paid next run.";
						} else {
							$topay = "";
						}
						$dispute = $row['dispute'];
						if ($dispute > 0) {
							$dispute = "Yes";
						} else {
							$dispute = "No";
						}
						if ($oneoff) {
							$oneoff = "Yes";
						} else {
							$oneoff = "No";
						}
						$lastupd      = $row['lastupd'];
						$updby        = $row['updby'];
						$lastupd      = date("D jS M Y \a\\t H:i",$lastupd);
						print "<p align=\"center\" class=\"larger\">View $heading</p>\n";

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
						print "	<td align=\"right\"><b>Supplier Ref:</b></td>\n";
						print "	<td>{$row['supplierref']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Tally Cost Centre:</b></td>\n";
						print "	<td>{$row['title']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>One Off:</b></td>\n";
						print "	<td>$oneoff</td>\n";
						print "</tr>\n";

						if ($pdf) {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Pdf:</b></td>\n";
							print "	<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">$pdf</a> - <a href=\"purchases-pdf-addedit.php?id=$id&amp;hid=$hid&amp;$qString\">Update</a></td>\n";
							print "</tr>\n";
						} else {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Pdf:</b></td>\n";
							print "	<td><a href=\"purchases-pdf-addedit.php?id=$id&amp;hid=$hid&amp;$qString\">Add</a></td>\n";
							print "</tr>\n";
						}

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

						$txntotal = $row['total'];
						print "<tr>\n";
						print "	<td align=\"right\"><b>Total:</b></td>\n";
						print "	<td>" . number_format($txntotal,2) . "</td>\n";
						print "</tr>\n";

						$outstanding = $row['outstanding'];
						print "<tr>\n";
						print "	<td align=\"right\"><b>Outstanding:</b></td>\n";
						print "	<td>" . number_format($outstanding,2) . "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Paid:</b></td>\n";
						print "	<td>$paidon $topay</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>In Dispute:</b></td>\n";
						print "	<td>$dispute</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Notes:</b></td>\n";
						print "	<td width=\"300px\">{$row['notes']}</td>\n";
						print "</tr>\n";
						print "</table>\n";

						print "<p align=\"center\"><b>CAM Cost Centres</b></p>\n";

						$sql = "SELECT l.ccid, l.details, l.net, l.vat, l.total, c.title
								FROM purchases_lines AS l
								LEFT JOIN costcentres AS c ON l.ccid = c.ID
								WHERE l.hid = '$hid'
								ORDER BY l.ID
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							print "<table align=\"center\" cellspacing=\"4px\" cellpadding=\"2px\">\n";
							print "<tr>\n";
							print "<td><b>Cost Centre</b></td>\n";
							print "<td><b>Details</b></td>\n";
							print "<td align=\"right\"><b>Net</b></td>\n";
							print "<td align=\"right\"><b>Vat</b></td>\n";
							print "<td align=\"right\"><b>Total</b></td>\n";
							print "</tr>\n";
							while ($row = $result->fetch_assoc()) {
								$linenet = $row['net'];
								$linevat = $row['vat'];
								$linetot = $row['total'];
								$cctitle = $row['title'];
								$details = $row['details'];
								$nettotal += $linenet;
								$vattotal += $linevat;
								$trntotal += $linetot;
								print "<tr>\n";
								print "<td>$cctitle</td>\n";
								print "<td>$details</td>\n";
								print "<td align=\"right\">".number_format($linenet,2)."</td>\n";
								print "<td align=\"right\">".number_format($linevat,2)."</td>\n";
								print "<td align=\"right\">".number_format($linetot,2)."</td>\n";
								print "</tr>\n";
							}
							print "<tr>\n";
							print "<td colspan=\"2\" align=\"right\"><b>Totals</b></td>\n";
							print "<td align=\"right\"><b>".number_format($nettotal,2)."</b></td>\n";
							print "<td align=\"right\"><b>".number_format($vattotal,2)."</b></td>\n";
							print "<td align=\"right\"><b>".number_format($trntotal,2)."</b></td>\n";
							print "<td>&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";
						} else {
							print "<p align=\"center\">No Breakdown Found</p>\n";
						}

						print "<p align=\"center\"><a href=\"purchases-addedit.php?id=$id&amp;hid=$hid&amp;$qString\">Edit</a>";
						if ($accessRights[$page] > 2) {
							print "&nbsp;|&nbsp;<a href=\"purchases-cancel.php?id=$id&amp;hid=$hid&amp;$qString\">Cancel</a>";
						}
						if ($outstanding == $txntotal) {
							print " | <a href=\"purchases-move.php?id=$id&hid=$hid&$qString\">Move</a>";
						}
						print "</p>\n";
						
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";

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
	print "<p align=\"center\"><a href=\"pl-ledger.php?id=$suppid&$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
