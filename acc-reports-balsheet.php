<?php
$page = "Accounts";
$subpage = "Balance Sheet";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Balance Sheet</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		$id = $_REQUEST['id'];

		if ($id) {

			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				if ($print) {
					print "<p align=\"center\"><span class=\"heading\">$compName</span><br>$compAddr</p>\n";
				} else {
					print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";
					include('acc-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
				}

				// get period dates and manual figures
				$sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$period_start    = $row['period_start'];
					$period_end      = $row['period_end'];
					$tangible_assets = $row['tangible_assets'];
					$share_capital   = $row['share_capital'];
					$bank_balance    = $row['bank_balance'];
					$deposits_balance = $row['deposits_balance'];
					$notes           = $row['notes'];

					$notes = str_replace('\n',"<br>",$notes);

					// add start day and end day
					list($sy,$sm,$sd) = explode("-",$period_start);
					list($ey,$em,$ed) = explode("-",$period_end);
					$txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
					$txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
					$bank_period_end = "$ey-$em";

					print "<p align=\"center\"><span class=\"larger\">Balance Sheet</span><br>$txt_st to $txt_en</p>\n";

					$current_year = date("Y");

					if ($current_year > $sy) {
						// get debtors from sl_periods
						$sql = "SELECT balance AS debtors
								FROM sl_periods
								WHERE period_ending = '$ey-$em'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$debtors = $row['debtors'];
						} else {
							$debtors = "0";
							print $dbcnx->error;
						}
					} else {

						// get debtors outstanding from sales ledger excluding 
						$sql = "SELECT SUM(outstanding) AS debtors
								FROM inv_headers
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$debtors = $row['debtors'];
						} else {
							$debtors = "0";
							print $dbcnx->error;
						}
					}

					// get deposits movement this period
					$sql = "SELECT SUM(l.linetotal) AS deposits
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON l.hid = h.ID
						LEFT JOIN users AS c ON h.custref = c.custref
						WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
						AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
						AND   l.unittype = 'Deposit'
						AND   (h.txtype = 'I' OR h.txtype = 'C')
					";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$deposits = $row['deposits'];
					}

					// get cash at bank
					if (!$bank_balance) {
						$bank_balance = 0;
					}

					// calculate current assets
					$current_assets = $debtors + $bank_balance;

					if ($current_year > $sy) {
						// get creditors from pl_periods
						$sql = "SELECT balance AS creditors
								FROM pl_periods
								WHERE period_ending = '$ey-$em'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$creditors = $row['creditors'];
						} else {
							$creditors = "0";
							print $dbcnx->error;
						}
						// get pending payment from saved year
						$sql = "SELECT SUM(total) AS pending
								FROM {$ey}_purchases
								WHERE BINARY txtype = 'p' AND `year` = '$ey'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$pending = ABS($row['pending']);
						} else {
							$pending = "0";
							print $dbcnx->error;
						}
					} else {
						// get creditors from current
						$sql = "SELECT SUM(outstanding) AS creditors
								FROM purchases
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$creditors = $row['creditors'];
						} else {
							$creditors = "0";
							print $dbcnx->error;
						}
						// get pending payment from saved year
						$sql = "SELECT SUM(total) AS pending
								FROM purchases
								WHERE BINARY txtype = 'p' AND `year` = '$ey'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$pending = ABS($row['pending']);
						} else {
							$pending = "0";
							print $dbcnx->error;
						}
					}

					// reduce current assets by pending payments
					$current_assets = $current_assets - $pending;

					// calculate net current assets/liabilities
					$net_assets_liabilities = $current_assets - $creditors;

					// calculate total assets less current liabilities
					$total_assets_liabilities = $tangible_assets + $net_assets_liabilities;

					// get provisions for liabilites
					$sql = "SELECT SUM(amount) AS provisions
							FROM acc_provisions
							WHERE pid = '$id'
					";
					 if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$provisions = $row['provisions'];
					} else {
						$provisions = "0";
						print $dbcnx->error;
					}                       

					// calculate net assets
					$net_assets = $total_assets_liabilities - $provisions - $deposits_balance - $deposits;

					// calculate profit and loss reserves
					$profit_loss_reserves = $net_assets - $share_capital;

					// calculate total equity
					$total_equity = $profit_loss_reserves + $share_capital;
					

					// print it

					// styling
					print "<style>\n";
					if ($print) {
						print ".table1 { font-size:1.4em }\n";
					} else {
						print ".table1 { font-size:1.1em }\n";
					}
					print ".blank { height:0px }\n";
					print ".line { border-bottom:1px solid #999999; height:0px }\n";
					print ".doubleline { border-top:1px solid #999999; border-bottom:1px solid #999999; height:0px }\n";
					print "</style>\n";

					print "<table align=\"center\" width=\"50%\" class=\"table1\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "<td colspan=\"3\"><b>Fixed Assets</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Tangible Assets</td>\n";
					print "<td>&nbsp;</td>\n";
					print "<td align=\"right\">".number_format($tangible_assets,0)."&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td colspan=\"3\"><b>Current Assets</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><a href=\"acc-reports-balsheet-detail-sl.php?id=$id\">Debtors (at $txt_en)</a></td>\n";
					print "<td align=\"right\">".number_format($debtors,0)."&nbsp;</td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Cash at bank and in hand</td>\n";
					print "<td align=\"right\">".number_format($bank_balance,0)."&nbsp;</td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><a href=\"acc-reports-balsheet-detail-pl-pending.php?id=$id\">Uncleared Payments</a></td>\n";
					print "<td align=\"right\">(".number_format($pending,0).")</td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "<td class=\"blank\"></td>\n";
					print "<tr>\n";

					print "<tr>\n";
					print "<td>&nbsp;</td>\n";
					print "<td align=\"right\">".number_format($current_assets,0)."&nbsp;</td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b><a href=\"acc-reports-balsheet-detail-pl.php?id=$id\">Creditors (at $txt_en)</a><br />Amounts falling due within one year</b></td>\n";
					print "<td valign=\"bottom\" align=\"right\">(".number_format($creditors,0).")</td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Net Current Assets</b></td>\n";
					print "<td>&nbsp;</td>\n";
					if ($net_assets_liabilities < 0) {
						print "<td valign=\"top\" align=\"right\">(".number_format(abs($net_assets_liabilities),0).")</td>\n";
					} else {
						print "<td valign=\"top\" align=\"right\">".number_format($net_assets_liabilities,0)."&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "<tr>\n";

					print "<tr>\n";
					print "<td><b>Total assets less current liabilities</b></td>\n";
					print "<td>&nbsp;</td>\n";
					if ($total_assets_liabilities < 0) {
						print "<td valign=\"top\" align=\"right\">(".number_format(abs($total_assets_liabilities),0).")</td>\n";
					} else {
						print "<td valign=\"top\" align=\"right\">".number_format($total_assets_liabilities,0)."&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td colspan=\"3\"><b>Provisions for liabilities</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Provisions</td>\n";
					print "<td>&nbsp;</td>\n";
					print "<td valign=\"top\" align=\"right\">(".number_format($provisions,0).")</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Deposits Balance Brought Forward</td>\n";
					print "<td>&nbsp;</td>\n";
					print "<td valign=\"top\" align=\"right\">(".number_format($deposits_balance,0).")</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><a href=\"acc-reports-balsheet-detail-deps.php?id=$id\">Deposits Movement This Period</a></td>\n";
					print "<td>&nbsp;</td>\n";
					print "<td valign=\"top\" align=\"right\">(".number_format($deposits,0).")</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Net Assets</b></td>\n";
					print "<td>&nbsp;</td>\n";
					if ($net_assets < 0) {
						print "<td valign=\"top\" align=\"right\">(".number_format(abs($net_assets),0).")</td>\n";
					} else {
						print "<td valign=\"top\" align=\"right\">".number_format($net_assets,0)."&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"doubleline\"></td>\n";
					print "<tr>\n";

					print "<tr>\n";
					print "<td colspan=\"3\"><b>Capital and reserves</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Called up share capital</td>\n";
					print "<td>&nbsp;</td>\n";
					print "<td valign=\"top\" align=\"right\">".number_format($share_capital,0)."&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Profit and loss reserves</td>\n";
					print "<td>&nbsp;</td>\n";
					if ($profit_loss_reserves < 0) {
						print "<td valign=\"top\" align=\"right\">(".number_format(abs($profit_loss_reserves),0).")</td>\n";
					} else {
						print "<td valign=\"top\" align=\"right\">".number_format($profit_loss_reserves,0)."&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "<tr>\n";

					print "<tr>\n";
					print "<td><b>Total Equity</b></td>\n";
					print "<td>&nbsp;</td>\n";
					if ($total_equity < 0) {
						print "<td valign=\"top\" align=\"right\">(".number_format(abs($total_equity),0).")</td>\n";
					} else {
						print "<td valign=\"top\" align=\"right\">".number_format($total_equity,0)."&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"doubleline\"></td>\n";
					print "<tr>\n";

					print "</table>\n";

					print "<p>&nbsp;</p>\n";

					print "<table align=\"center\" width=\"50%\" class=\"table1\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "<td>$notes</b></td>\n";
					print "</tr>\n";

					print "</table>\n";

					// try adjusments
					/* if ($current_year > $sy) {
						$sledger = "{$sy}_inv_headers";
						$pledger = "{$sy}_purchases";
						print "<p align=\"center\" class=\"larger\">Possible Adjustments</p>\n";
						$sql = "SELECT l.linetotal AS unpaiddeps, h.invnumber, h.year, h.month, h.day, h.custref
								FROM inv_lines AS l
								LEFT JOIN inv_headers AS h ON h.ID = l.hid
								LEFT JOIN $sledger AS h2 ON h2.invnumber = h.invnumber
								WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
								  AND CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
								  AND l.unittype = 'Deposit'
								  AND h.txtype = 'I'
								  AND h2.outstanding != '0.00'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							print "<table align=\"center\" width=\"50%\" class=\"table1\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
							print "<tr>\n";
							print "<td colspan=\"4\"><b>Unpaid Deposits</b><br><i>Some of these may simply be unallocated.</i></td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "<td><b>Date</b></td>\n";
							print "<td><b>Custref</b></td>\n";
							print "<td><b>Our Ref</b></td>\n";
							print "<td align=\"right\"><b>Amount</b></td>\n";
							print "</tr>\n";
							$tot = 0;
							while ($row = $result->fetch_assoc()) {
								$unpaiddeps = $row['unpaiddeps'];
								$invnumber  = $row['invnumber'];
								$custref    = $row['custref'];
								$year  = $row['year'];
								$month = $row['month'];
								$day   = $row['day'];

								$tot += $unpaiddeps;

								print "<tr>\n";
								print "<td>$day/$month/$year</td>\n";
								print "<td>DP$custref</td>\n";
								print "<td>$invnumber</td>\n";
								print "<td align=\"right\">".number_format($unpaiddeps,2)."</td>\n";
								print "</tr>\n";

							}
							print "<tr>\n";
							print "<td colspan=\"3\">&nbsp;</td>\n";
							print "<td align=\"right\"><b>".number_format($tot,2)."</b></td>\n";
							print "</tr>\n";
							print "</table>\n";
						} else {
							$resultMessage = "Could not get unpaid deposits!<br>" . $dbcnx->error;
						}

						$sql = "SELECT h.total, h.year, h.month, h.day, s.suppname, h.dephnaref
								FROM $pledger AS h
								LEFT JOIN suppliers AS s ON s.ID = h.suppid
								WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
								  AND CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
								  AND BINARY h.txtype = 'p'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							print "<table align=\"center\" width=\"50%\" class=\"table1\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
							print "<tr>\n";
							print "<td colspan=\"4\"><b>Pending Payments</b><br><i>Payments made to suppliers not cleared the bank.</i></td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "<td><b>Date</b></td>\n";
							print "<td><b>Supplier</b></td>\n";
							print "<td><b>Our Ref</b></td>\n";
							print "<td align=\"right\"><b>Amount</b></td>\n";
							print "</tr>\n";
							$tot = 0;
							while ($row = $result->fetch_assoc()) {
								$total     = $row['total'];
								$dephnaref = $row['dephnaref'];
								$suppname    = $row['suppname'];
								$year  = $row['year'];
								$month = $row['month'];
								$day   = $row['day'];

								$tot += $total;

								print "<tr>\n";
								print "<td>$day/$month/$year</td>\n";
								print "<td>$suppname</td>\n";
								print "<td>$dephnaref</td>\n";
								print "<td align=\"right\">".number_format($total,2)."</td>\n";
								print "</tr>\n";

							}
							print "<tr>\n";
							print "<td colspan=\"3\">&nbsp;</td>\n";
							print "<td align=\"right\"><b>".number_format($tot,2)."</b></td>\n";
							print "</tr>\n";
							print "</table>\n";
						} else {
							$resultMessage = "Could not get unpaid deposits!<br>" . $dbcnx->error;
						}
					} */

				} else {
					$resultMessage = "<b>Could Not Get Period</b><br>" . $dbcnx->error;
				}
				
				$dbcnx->close();

				if (!$print) {
					print "<p align=\"center\"><a href=\"acc-reports-balsheet.php?id=$id&amp;print=1\" target=\"_blank\">Print</a></p>"; 
				}

			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without ID</b>";
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
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
