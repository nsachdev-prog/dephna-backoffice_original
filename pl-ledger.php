<?php
$page = "Purchases";
$subpage = "Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
    print "<p align =\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	if ($accessRights[$page]) {
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		if ($_REQUEST['mn']) { $mn = $_REQUEST['mn']; }
		if ($_REQUEST['yr']) { $yr = $_REQUEST['yr']; }
		if (!$yr) { $yr = date("Y"); }
		$qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa";

		if ($id) {

			if (($mn) AND ($mn != "All")) { $search = "AND month = '$mn'"; $mntxt = "$mn-"; }

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				if ($_REQUEST['toggleTo']) {
					$toggleTo = $_REQUEST['toggleTo'];
					$hid = $_REQUEST['hid'];
					$sql = "UPDATE purchases SET supplierref = '$toggleTo' WHERE ID = '$hid'";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "Supplier Ref Changed";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','PLID:$id To $toggleTo','$page','$now')");
					} else {
						$resultMessage .= "Could not change supplier ref<br />" . $dbcnx->error;
					}
				}

	  			$sql = "SELECT * FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppname     = $row['suppname'];
					$suppref      = $row['suppref'];
					$contname     = $row['contname'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$suppcustref  = $row['suppcustref'];

					print "<p class=\"larger\" align=\"center\">$suppname</p>\n";
					include('pl-ledger-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";

					$currentmonth   = date("Ym");
					$onemonthago    = date("Ym",strtotime("-1 Month"));
					$twomonthsago   = date("Ym",strtotime("-2 Months"));
					$threemonthsago = $twomonthsago . "01";

					// get account aging
					$sql = "SELECT
						SUM(outstanding) AS outstanding,
						SUM(CASE WHEN CONCAT(year,month) = '$currentmonth' THEN outstanding END) as ocurrentmonth,
						SUM(CASE WHEN CONCAT(year,month) = '$onemonthago' THEN outstanding END) as oonemonthago,
						SUM(CASE WHEN CONCAT(year,month) = '$twomonthsago' THEN outstanding END) as otwomonthsago,
						SUM(CASE WHEN CONCAT(year,month,day) < '$threemonthsago' THEN outstanding END) as othreemonthsago
						FROM purchases
						WHERE suppid = '$id'
					";
					if (($result = $dbcnx->query($sql) AND ($result->num_rows))) {
						$row = $result->fetch_assoc();
						$outstanding     = $row['outstanding'];
						$ocurrentmonth   = $row['ocurrentmonth'];
						$oonemonthago    = $row['oonemonthago'];
						$otwomonthsago   = $row['otwomonthsago'];
						$othreemonthsago = $row['othreemonthsago'];
					}
					if (!$outstanding)     { $outstanding = "0.00"; }
					if (!$ocurrentmonth)   { $ocurrentmonth = "0.00"; }
					if (!$oonemonthago)    { $oonemonthago = "0.00"; }
					if (!$otwomonthsago)   { $otwomonthsago = "0.00"; }
					if (!$othreemonthsago) { $othreemonthsago = "0.00"; }

					print "<table width=\"90%\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";

					print "<td align=\"left\" valign=\"top\" width=\"50%\">";

					print "<table cellspacing=\"0px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td colspan=\"2\"><b>Contact Details</b></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Our Ref</td>\n";
					print "<td>SU$suppref</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Supplier Ref</td>\n";
					print "<td>$suppcustref</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Contact Name</td>\n";
					print "	<td>$contname</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Telephone 1</td>\n";
					print "	<td>$telephone</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Telephone 2</td>\n";
					print "	<td>$telephone2</td>";
					print "</tr>\n";
					print "</table>";

					print "</td>\n";
					print "<td align=\"right\" width=\"50%\">";

					print "<table align=\"right\" cellspacing=\"0px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td colspan=\"2\" align=\"right\"><b>Outstanding</b></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Total</td>\n";
					print "<td align=\"right\">".number_format($outstanding,2)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>This Month</td>\n";
					print "<td align=\"right\">".number_format($ocurrentmonth,2)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Last Month</td>\n";
					print "<td align=\"right\">".number_format($oonemonthago,2)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Two Months</td>\n";
					print "<td align=\"right\">".number_format($otwomonthsago,2)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Older</td>\n";
					print "<td align=\"right\">".number_format($othreemonthsago,2)."</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";


					// filters
					print "<form name=\"filterform\" method=\"post\" action=\"pl-ledger.php?id=$id&amp;$qString\">\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td>\n";
					print "	<select name=\"mn\" class=\"element_date\">\n";
					$options = array(
						"01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun",
						"07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec"
					);
					print "		<option>All</option>\n";
					foreach ($options AS $k => $o) {
						print "		<option "; if ($mn == $k) { print "SELECTED "; } print "value=\"$k\">$o</option>\n";
					}
					print "	</select>\n";
					// get available years
					$sql = "SELECT year
							FROM purchases
							WHERE suppid = '$id'
							GROUP BY year
					";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$years[] = $row['year'];
						}
						// add current year if not in list
						if (!in_array(date("Y"),$years)) {
							$years[] = date("Y");
						}
					} else {
						$years[] = date("Y");
					}
					print "	<select name=\"yr\" class=\"element_date\">\n";
					foreach ($years AS $key => $thisyear) {
						print "		<option "; if ($yr == $thisyear) { print "SELECTED "; } print "value=\"$thisyear\">$thisyear</option>\n";
					}
					print "	</select>\n";
					print "	</td>\n";
					print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					// get results
					$sql = "SELECT ID, txtype, dephnaref, supplierref, year, month, day, pdf, net, vat, total, paidon, outstanding
						FROM purchases
						WHERE suppid = '$id' AND year = '$yr' $search
						ORDER BY year DESC, month DESC, day DESC
					";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\"><b>Type</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999\"><b>Our Ref</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999\"><b>Supp Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amount</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Outstanding</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\" width=\"30px\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
						print "</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						$txtypes = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"S"=>"Statement",
							"r"=>"Refund (p)",
							"R"=>"Refund",
							"p"=>"Payment (p)",
							"BR"=>"Bank Receipt",
                            "BP"=>"Bank Payment",
                            "bp"=>"Bank Payment (p)"
						);

						while ($row = $result->fetch_assoc()) {
							$hid	   = $row['ID'];
							$txtype	   = $row['txtype'];
                            $dephnaref = $row['dephnaref'];
                            $supplierref = $row['supplierref'];
							$year      = $row['year'];
							$month     = $row['month'];
							$day       = $row['day'];
							$pdf       = $row['pdf'];
							$total     = $row['total'];
							$net       = $row['net'];
							$vat       = $row['vat'];
							$paidon    = $row['paidon'];
							$outstanding = $row['outstanding'];

							if ($txtype == "I" OR $txtype == "C") {
								$totnet += $net;
								$totvat += $vat;
								$tottot += $total;
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$year-$month-$day</td>\n";
							print "<td>{$txtypes[$txtype]}</td>\n";
                            print "	<td>$dephnaref</td>\n";
                            print "	<td>$supplierref";
							if (($supplierref == "am" OR $supplierref == "cc" OR $supplierref == "cot") AND $txtype == "p") {
								if ($supplierref == "am") {
									print "&nbsp;&nbsp;Change to<a href=\"pl-ledger.php?id=$id&amp;toggleTo=cc&amp;hid=$hid&amp;$qString\">&nbsp;(cc)&nbsp;</a> or <a href=\"pl-ledger.php?id=$id&amp;toggleTo=cot&amp;hid=$hid&amp;$qString\">&nbsp;(cot)&nbsp;</a>";
								} elseif ($supplierref == "cc") {
									print "&nbsp;&nbsp;Change to<a href=\"pl-ledger.php?id=$id&amp;toggleTo=am&amp;hid=$hid&amp;$qString\">&nbsp;(am)&nbsp;</a> or <a href=\"pl-ledger.php?id=$id&amp;toggleTo=cot&amp;hid=$hid&amp;$qString\">&nbsp;(cot)&nbsp;</a>";
								} elseif ($supplierref == "cot") {
									print "&nbsp;&nbsp;Change to<a href=\"pl-ledger.php?id=$id&amp;toggleTo=am&amp;hid=$hid&amp;$qString\">&nbsp;(am)&nbsp;</a> or <a href=\"pl-ledger.php?id=$id&amp;toggleTo=cc&amp;hid=$hid&amp;$qString\">&nbsp;(cc)&nbsp;</a>";
								}
							}
							print "</td>\n";
							print "	<td align=\"right\">".number_format($total,2)."</td>\n";
							print "	<td align=\"right\">".number_format($outstanding,2)."</td>\n";
                            if ($txtype == "I" OR $txtype == "C") {
								print "	<td width=\"40px\" align=\"center\"><a href=\"purchases-view.php?id=$id&hid=$hid\">View</a></td>\n";
							} elseif ($txtype == "P" OR $txtype == "p") {
								print "	<td width=\"30px\" align=\"center\"><a href=\"pl-payment-details.php?id=$id&hid=$hid&$qString\">View</a></td>\n";
							} elseif ($txtype == "R" OR $txtype == "r") {
								print "	<td width=\"30px\" align=\"center\"><a href=\"pl-refund-details.php?id=$id&hid=$hid&$qString\">View</a></td>\n";
							} elseif ($txtype == "S") {	
								print "<td align=\"center\"><a href=\"pl-statement-details.php?id=$id&amp;hid=$hid&amp;$qString\">View</a></td>\n";
							} else {
								print "	<td width=\"30px\">&nbsp;</td>\n";
							}
							if ($pdf) {
								print "	<td width=\"20px\" align=\"center\"><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">Pdf</a></td>\n";
							} else {
								print "	<td width=\"20px\">&nbsp;</td>\n";
							}
							print "</tr>\n";
						}

						$numrows = $result->num_rows;
						print "<tr>\n";
						print "<td colspan=\"8\" style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
						print "</tr>\n";

						print "</table>\n";

						print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "<td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>Net</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>Vat</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>Total</b></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td style=\"border-bottom:1px solid #999\"><b>Turnover $mntxt$yr</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>".number_format($totnet,2)."</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>".number_format($totvat,2)."</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>".number_format($tottot,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";


					} else {
						print "<p align=\"center\"><b>No Transactions Founnd.<br></b>".$dbcnx->error."</p>\n";
					}

					// file integrity check
					/* $sql = "SELECT (SUM(total) - SUM(outstanding)) AS bal
							FROM purchases
							WHERE suppid = '$id'
							AND `year` >= '2019'
							AND `month` >= '08'
					";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$bal = $row['bal'];
						if ($bal) {
							print "<p style=\"text-align:center;color:grey\">Integrity: Good $bal</p>\n";
						} else {
							print "<p style=\"text-align:center;color:red\">Integrity Error: $bal</p>\n";
						}
					} else {
						print "<p style=\"text-align:center;color:grey\">Could Not Check Integrity<br />".$dbcnx->error."</p>\n";
					} */

					// print "<p align=\"center\"><a href=\"pl-ledger-print.php?id=$id\" target=\"_blank\">Print</a></p>\n";

				} else {
					$resultMessage = "Could not find supplier<br />" . $dbcnx->error;
				}
				$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		} else {
			$resultMessage = "Page called without supplier id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}

		print "<p align=\"center\">";
		print "<a href=\"purchases-ledger.php?$qString\">Purchases</a> | ";
		print "<a href=\"pl-ledgers-list.php?$qString\">Ledgers</a>";
		print "</p>\n";

	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
