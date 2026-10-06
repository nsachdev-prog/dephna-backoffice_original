<?php
$page = "Sales_Ledger";
$subpage = "Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
    print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	if ($accessRights[$page]) {
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
        if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
		if ($_REQUEST['mn']) { $mn = $_REQUEST['mn']; }
		if ($_REQUEST['yr']) { $yr = $_REQUEST['yr']; }
		if (!$yr) { $yr = date("Y"); }
        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		if ($id) {

			if (($mn) AND ($mn != "All")) { $search = "AND month = '$mn'"; $mntxt = "$mn-"; }

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT * FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$username     = $row['username'];
					$custref      = $row['custref'];
					$compname     = $row['compname'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$restricted   = $row['tim'];

					if ($restricted == "Yes") {
						$restricted = "<span class=\"redtext\">Restricted</span>";
					} else {
						$restricted = "<span style=\"color:green\">Not Restricted</span>";
					}

					print "<p class=\"larger\" align=\"center\">$compname</p>\n";
					include('sl-ledger-submenu.php');
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
						FROM inv_headers
						WHERE custref = '$custref'
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

					// check for payment plan
					$sql = "SELECT ID FROM pplans_headers WHERE custref = '$custref' AND `status` = 1";
					if (($pplresult = $dbcnx->query($sql)) && ($pplresult->num_rows)) {
						$pplrow = $pplresult->fetch_assoc();
						$ppid = $pplrow['ID'];
						$payplan = "<a href=\"pplans-view.php?ppid=$ppid&amp;id=$id\">Yes - View</a>";
					} else {
						$payplan = "No";
					}

					print "<table width=\"90%\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";

					print "<td align=\"left\" valign=\"top\" width=\"50%\">";

					print "<table cellspacing=\"0px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td colspan=\"2\"><b>Contact Details</b></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Cust Ref</td>\n";
					print "<td>DP$custref</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Contact Name</td>\n";
					print "	<td>$username</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Telephone 1</td>\n";
					print "	<td>$telephone</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Telephone 2</td>\n";
					print "	<td>$telephone2</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Payment Plan</td>\n";
					print "	<td>$payplan</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Status</td>\n";
					print "	<td><a href=\"ad-restriction.php?custref=$custref\">$restricted</a></td>";
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
					print "<form name=\"filterform\" method=\"post\" action=\"sl-ledger.php?id=$id&amp;$qString\">\n";
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
							FROM inv_headers
							WHERE custref = '$custref'
							GROUP BY year
					";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$years[] = $row['year'];
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
					$sql = "SELECT h.ID, h.txtype, h.invnumber, h.year, h.month, h.day, h.invtotal, h.outstanding, h.tally_guid,
								e.type AS slextype
							FROM inv_headers AS h
							LEFT JOIN sl_extra AS e ON h.ID = e.hid
							WHERE h.custref = '$custref' AND year = '$yr' $search
							ORDER BY h.year DESC, h.month DESC, h.day DESC
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>ORef</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
						print "</tr>\n";

						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"p"=>"Payment (p)",
							"R"=>"Receipt",
							"r"=>"Receipt (p)",
							"X"=>"Unknown"
						);

						$slexTypes = array(
							"RC" => "Returned Cheque",
							"BD" => "Bounced DD",
							"DR" => "Deposit Repayment",
							"BC" => "Bank Charges"
						);

						while ($row = $result->fetch_assoc()) {
							$hid	   = $row['ID'];
							$txtype	   = $row['txtype'];
							$invnumber = $row['invnumber'];
							$year      = $row['year'];
							$month     = $row['month'];
							$day       = $row['day'];
							$invtotal  = $row['invtotal'];
							$outstanding = $row['outstanding'];
							$slextype = $row['slextype'];
							$inv_compname  = "";

							if ($txtype == "C" AND $year > '2020') {
								$inv_credited = $row['tally_guid'];
								if ($inv_credited) {
									$sql = "SELECT i.custref, c.compname, outstanding AS invoutstanding
											FROM inv_headers AS i
											LEFT JOIN users AS c ON i.custref = c.custref
											WHERE i.invnumber = '$inv_credited'
									";
									if (($result2 = $dbcnx->query($sql)) && ($result2->num_rows)) {
										$row2 = $result2->fetch_assoc();
										$inv_clientref = $row2['custref'];
										$inv_compname  = $row2['compname'];
										$invoutstanding = $row2['invoutstanding'];
										if (strlen($inv_compname) > 15) { $inv_compname = substr($inv_compname,0,15); }
										if ($invoutstanding == 0) {
											$inv_compare = " <style=\"redtext\">Cleared</style>";
										} else {
											$inv_compare = " (".number_format($invoutstanding,2).")";
										}
									}
								}
							} else {
								$inv_compname  = "";
								$inv_credited  = "";
								$inv_compare = "&nbsp;";
								$bd_credited = $row['tally_guid'];
							}

							if ($txtype == "I" OR $txtype == "C") {
								$tottot += $invtotal;
								// get vat and net
								$sql = "SELECT SUM(linenet) AS linenet, SUM(vattotal) AS vattotal
										FROM inv_lines
										WHERE hid = '$hid'
								";
								if (($lresult = $dbcnx->query($sql)) && ($lresult->num_rows)) {
									$lrow = $lresult->fetch_assoc();
									$linenet  = $lrow['linenet'];
									$vattotal = $lrow['vattotal'];
									$nettot += $linenet;
									$vattot += $vattotal;
								}
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$year-$month-$day</td>\n";
							if ($slextype) {
								if ($txtype == "p") {
									$inv_compname = " (p)" . $inv_compname;
								}
								print "<td>{$slexTypes[$slextype]} $inv_compname</td>\n";
							} else {
								print "<td>{$types[$txtype]} $inv_compname</td>\n";
							}
							print "	<td>$invnumber</td>\n";
							print "	<td>$inv_credited $inv_compare</td>\n";
							print "	<td align=\"right\">".number_format($invtotal,2)."</td>\n";
							print "	<td align=\"right\">".number_format($outstanding,2)."</td>\n";
							if ($txtype == "I" OR $txtype == "C") {
								print "	<td width=\"40px\" align=\"center\"><a href=\"customer-pdf-invoice.php?inv=$invnumber\" target=\"_blank\">View</a></td>\n";
								if ($txtype == "I" AND $outstanding == $invtotal AND $outstanding > 0) {
									if ($bd_credited) {
										print "	<td width=\"40px\" align=\"center\">BDebt</td>\n";
									} else {
										print "	<td width=\"40px\" align=\"center\"><a href=\"sl-credit.php?inv=$invnumber\">Credit</a></td>\n";
									}
								} else {
									print "	<td width=\"40px\">&nbsp;</td>\n";
								}
							} elseif ($txtype == "R" OR $txtype == "r") { // we don't have allocations for older receipts
								print "	<td width=\"40px\" align=\"center\"><a href=\"sl-receipt-details.php?id=$id&hid=$hid&$qString\">View</a></td>\n";
								print "	<td width=\"40px\">&nbsp;</td>\n";
							} elseif ($txtype == "P" OR $txtype == "p") {
								print "	<td width=\"40px\" align=\"center\"><a href=\"sl-payment-details.php?id=$id&hid=$hid&$qString\">View</a></td>\n";
								print "	<td width=\"40px\">&nbsp;</td>\n";
							} else {
								print "	<td width=\"40px\">&nbsp;</td>\n";
								print "	<td width=\"40px\">&nbsp;</td>\n";
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
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>".number_format($nettot,2)."</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>".number_format($vattot,2)."</b></td>\n";
						print "<td align=\"right\" style=\"border-bottom:1px solid #999\"><b>".number_format($tottot,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\"><b>No Invoices Founnd.<br></b>".$dbcnx->error."</p>\n";
					}

					# list outstanding deposit payments
					$thismonth = date("Ym");
					$sql = "
						SELECT * FROM deposits
						WHERE custref = '$custref' AND CONCAT(year,month)>='$thismonth'
						ORDER BY year DESC, month DESC
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows))	{
						print "<p align=\"center\"><b>Outstanding Deposit Payments</b></p>\n";
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td class=\"colHead\">Due</td>\n";
						print "	<td class=\"colHeadEnd\" align=\"right\">Amount</td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$id	       = $row['ID'];
							$month     = $row['month'];
							$year      = $row['year'];
							$amount    = $row['amount'];

							print "<tr>\n";
							print "	<td class=\"col\">$year/$month</td>\n";
							print "	<td class=\"colEnd\" align=\"right\">$amount</td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
					}

					print "<p align=\"center\"><a href=\"sl-ledger-print.php?id=$id\" target=\"_blank\">Print</a></p>\n";

				} else {
					$resultMessage = "Could not find customer<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without user id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($from == "daily") {
			print "<p align=\"center\"><a href=\"sl-daybook-daily.php?$qString\">Return</a></p>\n";
		} elseif ($from == "monthly") {
			print "<p align=\"center\"><a href=\"sl-daybook-monthly.php?$qString\">Return</a></p>\n";
		} else {
			print "<p align=\"center\"><a href=\"sl-ledgers-list.php?$qString\">Return</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
