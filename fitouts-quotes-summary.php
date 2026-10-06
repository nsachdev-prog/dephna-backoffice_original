<?php
/* error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); */
$page = "Fitouts";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname, s.title AS sitename, s.siteaddress
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					LEFT JOIN sites AS s ON q.siteid = s.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$sitename  = $row['sitename'];
					$siteaddress = $row['siteaddress'];
					$dcamount  = $row['dcamount'];
					$dcpercent = $row['dcpercent'];
					$ufpercent = $row['ufpercent'];
					$prov_sum  = $row['prov_sum'];
					// $siteaddress = str_replace('\n', " ,", $siteaddress);

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\"><b>$sitename:</b> $siteaddress</p>\n";

					print "<p align=\"center\"><b>Units</b></p>\n";
					$unitcount = 0;
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
							$unitcount++;
						}
						print "<tr>\n";
						print "<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Units Added Yet</p>\n";
					}

					if ($unitcount) {
						$unitspower = $unitcount * $unit_amps;
						$unitskws   = $unitcount * $unit_kws;
						// $unit_amps comes from settings.php
					} else {
						$unitspower = 0;
						$unitskws = 0;
					}

					print "<p align=\"center\"><b>Items</b></p>\n"; 
					$sql = "SELECT qi.*, i.upgrade, i.amount, i.type
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
							ORDER BY i.type, qi.title
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\"  width=\"96%\"cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Cust's Name/Details/Notes</b></td>\n";
						print "	    <td colspan=\"2\" style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amps</b></td>\n";
						print "	    <td colspan=\"2\" style=\"border-bottom:1px solid #999\" align=\"right\"><b>Kws</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\" width=\"30\"><b>Qty</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\" width=\"80\"><b>Each</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amount</b></td>\n";
						print "	</tr>\n";

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "<td>&nbsp;</td>\n";
						print "<td colspan=\"2\"><i>Power Available from $unitcount Units</i></td>\n";
						print "<td align=\"right\">".number_format($unitspower,1)."</td>\n";
						print "<td align=\"center\">+</td>\n";
						print "<td align=\"right\">".number_format($unitskws,1)."</td>\n";
						print "<td align=\"center\">+</td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print "</tr>\n";

						$rownum = 1;
						$net = 0;

						$lasttype = "";

						while ($row = $result->fetch_assoc()) {
							$iid     = $row['ID'];
							$title  = $row['title'];
							$ctitle  = $row['custtitle'];
							$power  = $row['power'];
							$powerg = $row['powerg'];
							$upgrade = $row['upgrade'];
							$each    = $row['amount'];
							$qty     = $row['qty'];
							$amount  = $each * $qty;
							$net    += $amount;
							$details = $row['details'];
							$type	 = $row['type'];
							$notes   = $row['notes'];

							$power = $power * $qty;
							$powerg = $powerg * $qty;
							if ($upgrade) {
								if ($power) {
									$totalamps -= $power;
									$ampssign = "+";
								} else {
									$ampssign = "";
								}
								if ($powerg) {
									$totalkws -= $powerg;
									$kwssign = "+";
								} else {
									$kwssign = "";
								}
							} else {
								if ($power) {
									$totalamps += $power;
									$ampssign = "-";
								} else {
									$ampssign = "";
								}
								if ($powerg) {
									$totalkws += $powerg;
									$kwssignsign = "-";
								} else {
									$kwssign = "";
								}
							}

							if ($type != $lasttype) {
								print "<tr>\n";
								print "	<td colspan=\"10\" style=\"background-color:#0c4173;font-size:14px;color:#eee\"><b>&nbsp;$type</b></td>\n";
								print "</tr>\n";
								$lasttype = $type;
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\" align=\"right\">$rownum</td>\n";
							print "<td valign=\"top\">$title</td>\n";
							print "<td valign=\"top\">$ctitle";
							if ($details) { print "<br><span style=\"font-size:10px;\">$details</span>"; }
							if ($notes) { print "<br><span style=\"font-size:10px;\">$notes</span>"; }
							print "</td>\n";
							print "<td valign=\"top\" align=\"right\">".number_format($power,1)."</td>\n";
							print "<td valign=\"top\" align=\"center\">$ampssign</td>\n";
							print "<td valign=\"top\" align=\"right\">".number_format($powerg,1)."</td>\n";
							print "<td valign=\"top\" align=\"center\">$kwssign</td>\n";
							print "<td valign=\"top\" align=\"right\">$qty</td>\n";
							print "<td valign=\"top\" align=\"right\">".number_format($each,2)."</td>\n";
							print "<td valign=\"top\" align=\"right\">".number_format($amount,2)."</td>\n";
							print "</tr>\n";
							$rownum++;

							$ampsused = $unitspower - $totalamps;
							$kwsused = $unitskws - $totalkws;

						}

						if ($ampsused < 0) {
							$ampsstyle = "color:Red;";
							$amps_exceeded = 1;
						} else {
							$ampsstyle = "color:Green;";
						}
						if ($kwsused < 0) {
							$kwsstyle = "color:Red;";
							$kws_exceeded = 1;
						} else {
							$kwsstyle = "color:Green;";
						}

                        print "<tr>\n";
                        print "	<td colspan=\"3\" style=\"border-top:1px solid #999\">Power Still Available</td>\n";
                        print "	<td align=\"right\" style=\"border-top:1px solid #999; $ampsstyle\">".number_format($ampsused,1)."</td>\n";
                        print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "	<td align=\"right\" style=\"border-top:1px solid #999; $kwsstyle\">".number_format($kwsused,1)."</td>\n";
                        print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "</tr>\n";

						print "<tr>\n";
						print " <td colspan=\"8\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\">Charges</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999\">".number_format($net,2)."</td>\n";
						print "</tr>\n";

						if ($dcamount > 0) { $discount = $dcamount; }
						elseif ($dcpercent > 0) { $discount = $net / 100 * $dcpercent; }

						print "<tr>\n";
						print " <td colspan=\"8\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\">Discount</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999\">".number_format($discount,2)."</td>\n";
						print "</tr>\n";
						$net -= $discount;
						print "<tr>\n";
						print " <td colspan=\"8\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\"><b>Net</b></td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999\"><b>".number_format($net,2)."</b></td>\n";
						print "</tr>\n";
						
						$vat = $net / 100 * $vatRate;
						// $vatRate comes from settings.php

						print "<tr>\n";
						print " <td colspan=\"8\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999\"><b>Vat</b></td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999\"><b>".number_format($vat,2)."</b></td>\n";
						print "</tr>\n";

						$total = $net + $vat;

						print "<tr>\n";
						print " <td colspan=\"8\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>Total</b></td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>".number_format($total,2)."</b></td>\n";
						print "</tr>\n";

						if ($ufpercent > 0) {
							$upfront = $total / 100 * $ufpercent;

							print "<tr>\n";
							print " <td colspan=\"10\">&nbsp;</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print " <td colspan=\"8\">&nbsp;</td>\n";
							print "	<td style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>Up Front</b></td>\n";
							print "	<td align=\"right\" colspan=\"1\" style=\"border-top:1px solid #999;border-bottom:1px solid #666\"><b>".number_format($upfront,2)."</b></td>\n";
							print "</tr>\n";

						}

						if ($prov_sum > 0) {
							print "<tr>\n";
							print " <td colspan=\"10\">&nbsp;</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print " <td colspan=\"8\">&nbsp;</td>\n";
							print "	<td>Provisional Sum</td>\n";
							print "	<td align=\"right\">".number_format($prov_sum,2)."</td>\n";
							print "</tr>\n";
						}
						
						print "</table>\n";

						print "<p align=\"center\"><a href=\"fitouts-quotes-viewpdf.php?qid=$qid\" target=\"_blank\">View Pdf Quote</a></p>\n";

					} else {
						print "<p align=\"center\">No Items Added Yet</p>\n";
					}

					if ($accessRights[$page] > 2) {
						print "<p align=\"center\">";
						print "<a href=\"fitouts-quotes-contract.php?qid=$qid\">Back</a> | ";
						print "<a href=\"fitouts-quotes-complete.php?qid=$qid\">Next</a>";
						print "</p>\n";
					} else {
						print "<p align=\"center\">";
						print "<a href=\"fitouts-quotes-contract.php?qid=$qid\">Back</a>";
						print "</p>\n";
					}

					print "<p align=\"center\">";
					print "<a href=\"fitouts-quotes-print.php?qid=$qid\" target=\"_blank\">Print</a>\n";
					print "</p>\n";

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
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
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
