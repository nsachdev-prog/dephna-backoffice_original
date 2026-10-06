<?php
$page = "Accounts";
$subpage = "Profit And Loss";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Profit And Loss</title>
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

				if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
				if ($_REQUEST['to']) { $to = $_REQUEST['to']; }
				if ($fr AND $to) { if ($to < $fr) { $to = ""; } }

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
					$st  = $row['period_start'];
					$en  = $row['period_end'];
					$tax_on_profit = $row['tax_on_profit'];

					if ($fr) { $period_start = $fr; } else { $period_start = $st; }
					if ($to) { $period_end   = $to; } else { $period_end   = $en; }
					$txt_st = date("jS F Y",strtotime("$period_start"));
					$txt_en = date("jS F Y",strtotime("$period_end"));

					print "<p align=\"center\"><span class=\"larger\">Profit And Loss</span><br>$txt_st to $txt_en</p>\n";

					if (!$print) {
						print "<form name=\"addform\" method=\"post\" action=\"acc-reports-pandl.php?id=$id\">\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>From:</b></td>\n";
						print "	<td><input type=\"text\" id=\"datepickerFr\" name=\"fr\" value=\"$fr\" class=\"element_smaller\"></td>\n";
						print "	<td align=\"right\"><b>To:</b></td>\n";
						print "	<td><input type=\"text\" id=\"datepickerTo\" name=\"to\" value=\"$to\" class=\"element_smaller\"></td>\n";
						print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
						print " <td><a href=\"acc-reports-pandl.php?id=$id\">Reset</a></td>\n";
						print "</tr>";
						print "</form>\n";
					}

					// get net turnover from sales ledger excluding deposits
					$sql = "SELECT SUM(l.linenet) AS turnover
							FROM inv_lines AS l
							LEFT JOIN inv_headers AS h ON l.hid = h.ID
							WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
							AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
							AND (h.txtype = 'I' OR h.txtype = 'C')
							AND l.unittype != 'Deposit'
							AND h.custref != '20199'
							AND h.custref != '20720'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$turnover = $row['turnover'];
					} else {
						$turnover = "0";
						print $dbcnx->error;
					}

					// get net deposits
					/* $sql = "SELECT SUM(l.linenet) AS deposits
							FROM inv_lines AS l
							LEFT JOIN inv_headers AS h ON l.hid = h.ID
							WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
							AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
							AND (h.txtype = 'I' OR h.txtype = 'C')
							AND l.unittype = 'Deposit'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$deposits = $row['deposits'];
					} else {
						$deposits = "0";
						print $dbcnx->error;
					} */

					// get cost of sales
					$sql = "SELECT SUM(p.net) AS cost_of_sales
							FROM purchases AS p
							LEFT JOIN costcentres AS c ON c.ID = p.costcentre
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C')
							AND c.ccgroup = '1'
							AND p.oneoff != 'on'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$cost_of_sales = $row['cost_of_sales'];
						$cost_of_sales *=-1;
					} else {
						$cost_of_sales = "0";
						print $dbcnx->error;
					}

					// get one off expenses
					$sql = "SELECT SUM(p.net) AS one_off_expenses
							FROM purchases AS p
							LEFT JOIN costcentres AS c ON c.ID = p.costcentre
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C')
							AND p.oneoff = 'on'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$one_off_expenses = $row['one_off_expenses'];
						$one_off_expenses *=-1;
					} else {
						$one_off_expenses = "0";
						print $dbcnx->error;
					}

					// calculate gross profit
					$gross_profit = $turnover + $cost_of_sales + $one_off_expenses;

					// get administrative expenses
					$sql = "SELECT SUM(p.net) AS admin_expenses
							FROM purchases AS p
							LEFT JOIN costcentres AS c ON c.ID = p.costcentre
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C')
							AND c.ccgroup = '2'
							AND p.oneoff != 'on'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$admin_expenses = $row['admin_expenses'];
						$admin_expenses *=-1;
					} else {
						$admin_expenses = "0";
						print $dbcnx->error;
					}

					// wages
					$sql = "SELECT SUM(amount) AS wages
							FROM bank
							WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
							AND   CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
							AND costcentre = '66'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$wages = $row['wages'];
						$wages *=-1;
					} else {
						$wages = "0";
					}

					// divs
					$sql = "SELECT SUM(amount) AS dividends
							FROM bank
							WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
							AND   CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
							AND costcentre = '67'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$dividends = $row['dividends'];
						$dividends *=-1;
					} else {
						$dividends = "0";
					}

					// NI and Paye
					$sql = "SELECT SUM(amount) AS paye
							FROM bank
							WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
							AND   CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
							AND costcentre = '55'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$paye = $row['paye'];
						$paye *=-1;
					} else {
						$paye = "0";
					}

					// Pension
					$sql = "SELECT SUM(amount) AS pension
							FROM bank
							WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
							AND   CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
							AND costcentre = '65'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$pension = $row['pension'];
						$pension *=-1;
					} else {
						$pension = "0";
					}

					// calculate operating profit
					$operating_profit = $gross_profit + $admin_expenses + $wages + $dividends + $paye + $pension;

					// get interest receivable and similar income
					// bank interest and charges that are credits
					$sql = "SELECT SUM(b.amount) AS receivables
							FROM bank AS b
							LEFT JOIN costcentres AS c ON c.ID = b.costcentre
							WHERE CONCAT(b.year,\"-\",b.month,\"-\",b.day) >= '$period_start'
							AND   CONCAT(b.year,\"-\",b.month,\"-\",b.day) <= '$period_end'
							AND BINARY b.txtype = 'CR'
							AND (b.costcentre = '44' OR b.costcentre = '45')
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$receivables = $row['receivables'];
					} else {
						$receivables = "0";
					}

					// get interest payable and similar income
					// bank interest and charges that are debits
					$sql = "SELECT SUM(b.amount) AS payables
							FROM bank AS b
							LEFT JOIN costcentres AS c ON c.ID = b.costcentre
							WHERE CONCAT(b.year,\"-\",b.month,\"-\",b.day) >= '$period_start'
							AND   CONCAT(b.year,\"-\",b.month,\"-\",b.day) <= '$period_end'
							AND BINARY b.txtype = 'DR'
							AND (b.costcentre = '44' OR b.costcentre = '45')
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$payables = $row['payables'];
						$payables *=-1;
					} else {
						$payables = "0";
					}

					// profit before taxation
					$profit_before_tax = $operating_profit + $receivables + $payables;

					if ($profit_before_tax > 0) {
						// already got tax_on_profit from manual entry in acc_periods
						if ($tax_on_profit) {
							$taxation = $profit_before_tax / 100 * $tax_on_profit;
							$taxation *=-1;
						}
					} else {
						$taxation = 0;
					}
					
					// calculate profit for the financial year
					$profit = $profit_before_tax + $taxation;

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
					if ($print) {
						print "<td>Turnover (excluding deposits)</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-turnover.php?id=$id&amp;fr=$fr&amp;to=$to\">Turnover</a> (excluding deposits)</td>\n";
					}
					print "<td align=\"right\">".acc_num_format($turnover)."</td>\n";
					print "</tr>\n";

					/* print "<tr>\n";
					if ($print) {
						print "<td>Deposits</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-deposits.php?id=$id&amp;fr=$fr&amp;to=$to\">Deposits</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($deposits)."</td>\n";
					print "</tr>\n"; */

					print "<tr>\n";
					if ($print) {
						print "<td>Cost Of Sales</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-cos.php?id=$id&amp;fr=$fr&amp;to=$to\">Cost Of Sales</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($cost_of_sales)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					if ($print) {
						print "<td>One Off Expenses</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-oneoff.php?id=$id&amp;fr=$fr&amp;to=$to\">One Off Expenses</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($one_off_expenses)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Gross Profit</b></td>\n";
					print "<td align=\"right\">".acc_num_format($gross_profit)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					if ($print) {
						print "<td>Administrative Expenses</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-admin.php?id=$id&amp;fr=$fr&amp;to=$to\">Administrative Expenses</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($admin_expenses)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					if ($print) {
						print "<td>Wages</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-wages.php?id=$id&amp;fr=$fr&amp;to=$to\">Wages</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($wages)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					if ($print) {
						print "<td>Dividends</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-divs.php?id=$id&amp;fr=$fr&amp;to=$to\">Dividends</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($dividends)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					if ($print) {
						print "<td>NI &amp; PAYE</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-paye.php?id=$id&amp;fr=$fr&amp;to=$to\">NI &amp; PAYE</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($paye)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					if ($print) {
						print "<td>Pension</td>\n";
					} else {
						print "<td><a href=\"acc-reports-pandl-pension.php?id=$id&amp;fr=$fr&amp;to=$to\">Pension</a></td>\n";
					}
					print "<td align=\"right\">".acc_num_format($pension)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Operating Profit</b></td>\n";
					print "<td align=\"right\">".acc_num_format($operating_profit)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><a href=\"acc-reports-pandl-intrec.php?id=$id&amp;fr=$fr&amp;to=$to\">Interest Receivable And Similar Income</a></td>\n";
					print "<td align=\"right\">".acc_num_format($receivables)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><a href=\"acc-reports-pandl-intpay.php?id=$id&amp;fr=$fr&amp;to=$to\">Interest Payable And Similar Expenses</a></td>\n";
					print "<td align=\"right\">".acc_num_format($payables)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Profit Before Taxation</b></td>\n";
					print "<td align=\"right\">".acc_num_format($profit_before_tax)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Tax On Profit</td>\n";
					print "<td align=\"right\">".acc_num_format($taxation)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"line\"></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Profit For The Financial Year</b></td>\n";
					print "<td align=\"right\">".acc_num_format($profit)."</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"doubleline\"></td>\n";
					print "</tr>\n";

					print "</table>\n";

					if (!$print) {
						print "<p align=\"center\"><a href=\"acc-reports-pandl.php?id=$id&amp;fr=$fr&amp;to=$to&amp;print=1\" target=\"_blank\">Print</a></p>";
						print "<p align=\"center\"><a href=\"acc-reports-pandl-costcentres.php?id=$id&amp;fr=$fr&amp;to=$to\">Costcentres</a></p>";
						
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepickerFr\" ).datepicker({dateFormat: 'yy-mm-dd', minDate: new Date('$st')});\n";
						print "		$( \"#datepickerTo\" ).datepicker({dateFormat: 'yy-mm-dd', maxDate: new Date('$en')});\n";
						print "	});\n";
						print "</script>\n";
					}  

				} else {
					$resultMessage = "<b>Could Not Get Period</b><br>" . $dbcnx->error;
				}
				
				$dbcnx->close();

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
function acc_num_format($nr)
{
	$nr = number_format($nr, 0);
	return $nr[0] == '-' ?  "(" . substr($nr, 1) . ")" : $nr."&nbsp;";
}
?>
