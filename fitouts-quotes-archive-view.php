<?php
$page = "Fitouts";
$subpage = "Archive";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		
        print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
        include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Archived Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$dcamount  = $row['dcamount'];
					$dcpercent = $row['dcpercent'];
					$ufpercent = $row['ufpercent'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\"><b>Units</b></p>\n";
					$sql = "SELECT * FROM fitouts_quotes_units WHERE qid = '$qid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\" width=\"80%\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "	</tr>\n";
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";
						while ($row = $result->fetch_assoc()) {
							$unitname = $row['unitname'];
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\">$unitname</td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Units Added Yet</p>\n";
					}

					print "<p align=\"center\"><b>Items</b></p>\n"; 
					$sql = "SELECT qi.*, i.title AS ititle, i.power, i.upgrade, i.amount
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\"  width=\"80%\"cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" width=\"110\">&nbsp</td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amount</b></td>\n";
						print "	</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						while ($row = $result->fetch_assoc()) {
							$iid     = $row['ID'];
							$ititle  = $row['ititle'];
							$amount  = $row['amount'];
							$net    += $amount;
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td>$ititle</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td align=\"right\">".number_format($amount,2)."</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print " <td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\">Net Charges</td>\n";
						print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999\">".number_format($net,2)."</td>\n";
						print "</tr>\n";

						if ($dcamount > 0) { $discount = $dcamount; }
						elseif ($dcpercent > 0) { $discount = $net / 100 * $dcpercent; }

						print "<tr>\n";
						print " <td>&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\">Discount</td>\n";
						print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999\">".number_format($discount,2)."</td>\n";
						print "</tr>\n";
						$net -= $discount;
						print "<tr>\n";
						print " <td>&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\"><b>Net</b></td>\n";
						print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999\"><b>".number_format($net,2)."</b></td>\n";
						print "</tr>\n";
						
						$vat = $net / 100 * $vatRate;
						// $vatRate comes from settings.php

						print "<tr>\n";
						print " <td>&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\"><b>Vat</b></td>\n";
						print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999\"><b>".number_format($vat,2)."</b></td>\n";
						print "</tr>\n";

						$total = $net + $vat;

						print "<tr>\n";
						print " <td>&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>Total</b></td>\n";
						print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>".number_format($total,2)."</b></td>\n";
						print "</tr>\n";

						if ($ufpercent > 0) {
							$upfront = $total / 100 * $ufpercent;

							print "<tr>\n";
							print " <td colspan=\"3\">&nbsp;</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print " <td>&nbsp;</td>\n";
							print "	<td style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>Payment Up Front</b></td>\n";
							print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>".number_format($upfront,2)."</b></td>\n";
							print "</tr>\n";

						}
						
						print "</table>\n";

						print "<p align=\"center\"><a href=\"fitouts-quotes-viewpdf.php?qid=$qid\" target=\"_blank\">View Pdf Quote</a></p>\n";

					} else {
						print "<p align=\"center\">No Items Added Yet</p>\n";
					}

				}
					
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-archive-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
