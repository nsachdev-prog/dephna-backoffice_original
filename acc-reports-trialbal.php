<?php
$page = "Accounts";
$subpage = "Trial Balance";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Trial Balance</title>
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
					$tangible_assets = $row['tangible_assets'];
					$share_capital   = $row['share_capital'];
					$bank_balance    = $row['bank_balance'];
					$notes           = $row['notes'];

					$notes = str_replace('\n',"<br />",$notes);

					// add start day and end day
					if ($fr) { $period_start = $fr; } else { $period_start = $st; }
					if ($to) { $period_end   = $to; } else { $period_end   = $en; }
					list($sy,$sm,$sd) = explode("-",$period_start);
					list($ey,$em,$ed) = explode("-",$period_end);
					$txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
					$txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
					$bank_period_end = "$ey-$em";

					print "<p align=\"center\"><span class=\"larger\">Trial Balance</span><br>$txt_st to $txt_en</p>\n";

					if (!$print) {
						print "<form name=\"addform\" method=\"post\" action=\"acc-reports-trialbal.php?id=$id\">\n";
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

					print "<table align=\"center\" width=\"60%\" class=\"table1\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "<td>&nbsp;</td>\n";
					print "<td align=\"right\"><b>Debit</b></td>\n";
					print "<td align=\"right\"><b>Credit</b></td>\n";
					print "</tr>\n";

					// get debtors
					$sql = "SELECT SUM(l.linenet) AS net, SUM(l.vattotal) AS vat, SUM(l.linetotal) AS total, l.location AS location
							FROM inv_lines AS l
							LEFT JOIN inv_headers AS h ON l.hid = h.ID
							WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
							AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
							AND (h.txtype = 'I' OR h.txtype = 'C')
							AND l.unittype != 'Deposit'
							GROUP BY l.location
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$debtors = array();
						while ($row = $result->fetch_assoc()) {
							$location = $row['location'];
							if (!$location) { $location = "Missing"; }
							$debtors[$location] = $row['net'];
							$sl_vat     += $row['vat'];
							$sl_control += $row['total'];
						}
						$totdr += $sl_control;
						$totcr += $sl_vat;
					}

					// get creditors - Cost of Sales
					$sql = "SELECT SUM(l.net) AS net, SUM(l.vat) AS vat, SUM(l.total) AS total,
								   c.title AS cctitle, c.ID AS ccid
							FROM purchases_lines AS l
							LEFT JOIN costcentres AS c ON c.ID = l.ccid
							LEFT JOIN purchases AS p ON p.ID = l.hid
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C')
							AND c.ccgroup = '1'
							AND p.oneoff != 'on'
							GROUP BY c.title
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$costofsales = array();
						while ($row = $result->fetch_assoc()) {
							$cctitle = $row['cctitle'];
							if (!$cctitle) { $cctitle = "Missing"; }
							$costofsales[$cctitle] = $row['ccid']."--".$row['net'];
							$pl_vat += $row['vat'];
							$pl_control += $row['total'];
							$pl_net1 += $row['net'];
						}
					}

					// get creditors - Admin Expenses
					$sql = "SELECT SUM(l.net) AS net, SUM(l.vat) AS vat, SUM(l.total) AS total,
								   c.title AS cctitle, c.ID AS ccid
							FROM purchases_lines AS l
							LEFT JOIN costcentres AS c ON c.ID = l.ccid
							LEFT JOIN purchases AS p ON p.ID = l.hid
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C')
							AND c.ccgroup = '2'
							AND p.oneoff != 'on'
							GROUP BY c.title
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$adminexpenses = array();
						while ($row = $result->fetch_assoc()) {
							$cctitle = $row['cctitle'];
							if (!$cctitle) { $cctitle = "Missing"; }
							$adminexpenses[$cctitle] = $row['ccid']."--".$row['net'];
							$pl_vat += $row['vat'];
							$pl_control += $row['total'];
							$pl_net2 += $row['net'];
						}
					}

					// get bank expences
					$sql = "SELECT
								SUM(CASE WHEN b.txtype = 'DR' THEN b.amount ELSE 0 END) AS totaldr,
								SUM(CASE WHEN b.txtype = 'CR' THEN b.amount ELSE 0 END) AS totalcr,
								b.txtype, c.title AS cctitle, c.ID AS ccid
							FROM bank AS b
							LEFT JOIN costcentres AS c ON c.ID = b.costcentre
							WHERE CONCAT(b.year,\"-\",b.month,\"-\",b.day) >= '$period_start'
							AND   CONCAT(b.year,\"-\",b.month,\"-\",b.day) <= '$period_end'
							AND c.ccgroup = '3'
							GROUP BY c.title
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$bankexpenses = array();
						while ($row = $result->fetch_assoc()) {
							$cctitle    = $row['cctitle'];
							if (!$cctitle) { $cctitle = "Missing"; }
							$totaldr    = $row['totaldr'];
							$totalcr    = $row['totalcr'];
							$tottot = $totaldr - $totalcr;
							$bankexpenses[$cctitle] = $row['ccid']."--".$tottot;
							$bk_control += $tottot;
							$pl_net2    += $tottot;
							$totdr      += $totaldr;
							$totcr      += $totalcr;
						}
					}
					$totcr += $bk_control;

					// get creditors - One Off Expenses
					$sql = "SELECT SUM(l.net) AS net, SUM(l.vat) AS vat, SUM(l.total) AS total,
								   c.title AS cctitle, c.ID AS ccid
							FROM purchases_lines AS l
							LEFT JOIN costcentres AS c ON c.ID = l.ccid
							LEFT JOIN purchases AS p ON p.ID = l.hid
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C' OR p.txtype = 'BP')
							AND p.oneoff = 'on'
							GROUP BY c.title
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$oneoffexpenses = array();
						while ($row = $result->fetch_assoc()) {
							$cctitle = $row['cctitle'];
							if (!$cctitle) { $cctitle = "Missing"; }
							$oneoffexpenses[$cctitle] = $row['ccid']."--".$row['net'];
							$pl_vat += $row['vat'];
							$pl_control += $row['total'];
							$pl_net3 += $row['net'];
						}
					} else {
						print $dbcnx->error;
					}

					$totdr += $pl_vat;
					if ($pl_control < 0) {
						$temp_control = ABS($pl_control);
						$totdr += $temp_control;
					} else {
						$totcr += $pl_control;
					}

					print "<tr>\n";
					print "<td><b><a href=\"acc-reports-trialbal-detail-all.php?id=$id&amp;fr=$fr&amp;to=$to\">Sales</a></b></td>\n";
					print "<td align=\"right\"><b>".number_format($sl_control,0)."</b></td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>VAT</b></td>\n";
					print "<td align=\"right\"><b>".number_format($pl_vat,0)."</b></td>\n";
					print "<td align=\"right\"><b>".number_format($sl_vat,0)."</b></td>\n";
					print "</tr>\n";

					foreach ($debtors AS $key => $value) {
						print "<tr>\n";
						if ($print) {
							print "<td>$key</td>\n";
						} else {
							print "<td><a href=\"acc-reports-trialbal-detail-sl.php?id=$id&amp;fr=$fr&amp;to=$to&loc=$key\">$key</a></td>\n";
						}
						print "<td>&nbsp;</td>\n";
						print "<td align=\"right\">".number_format($value,0)."</td>\n";
						print "</tr>\n";
						$totcr += $value;
					}

					print "<tr>\n";
					print "<td><b><a href=\"acc-reports-trialbal-detail-pl-all.php?id=$id&amp;fr=$fr&amp;to=$to\">Purchases</a></b></td>\n";
					print "<td>&nbsp;</td>\n";
					print "<td align=\"right\"><b>".number_format($pl_control,0)."<b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b><a href=\"acc-reports-trialbal-detail-bk-all.php?id=$id\">Bank (Statement Only Items)</a></b></td>\n";
					if ($bk_control < 0) {
						$bk_control = ABS($bk_control);
						print "<td align=\"right\"><b>".number_format($bk_control,0)."<b></td>\n";
						print "<td>&nbsp;</td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
						print "<td align=\"right\"><b>".number_format($bk_control,0)."<b></td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td><b>Direct Expenses</b></td>\n";
					print "<td align=\"right\"><b>".number_format($pl_net1,0)."</b></td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";
					foreach ($costofsales AS $key => $value) {
						list($ccid,$value) = explode("--",$value);
						print "<tr>\n";
						if ($print) {
							print "<td>$key</td>\n";
						} else {
							print "<td><a href=\"acc-reports-trialbal-detail-pl.php?id=$id&amp;fr=$fr&amp;to=$to&ccid=$ccid&cctitle=$key\">$key</a></td>\n";
						}
						print "<td align=\"right\">".number_format($value,0)."</td>\n";
						print "<td>&nbsp;</td>\n";
						print "</tr>\n";
						if ($value < 0) {
							$totcr += $value;
						} else {
							$totdr += $value;
						}
					}

					print "<tr>\n";
					print "<td><b>Indirect Expenses</b></td>\n";
					if ($pl_net2 < 0) {
						$pl_net2 = ABS($pl_net2);
						print "<td>&nbsp;</td>\n";
						print "<td align=\"right\"><b>".number_format($pl_net2,0)."</b></td>\n";
					} else {
						print "<td align=\"right\"><b>".number_format($pl_net2,0)."</b></td>\n";
						print "<td>&nbsp;</td>\n";
					}
					print "</tr>\n";
					foreach ($adminexpenses AS $key => $value) {
						list($ccid,$value) = explode("--",$value);
						print "<tr>\n";
						if ($print) {
							print "<td>$key</td>\n";
						} else {
							print "<td><a href=\"acc-reports-trialbal-detail-pl.php?id=$id&amp;fr=$fr&amp;to=$to&ccid=$ccid&cctitle=$key\">$key</a></td>\n";
						}
						if ($value < 0) {
							$value = ABS($value);
							$totcr += $value;
							print "<td>&nbsp;</td>\n";
							print "<td align=\"right\">".number_format($value,0)."</td>\n";
						} else  {
							$totdr += $value;
							print "<td align=\"right\">".number_format($value,0)."</td>\n";
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
					}
					
					foreach ($bankexpenses AS $key => $value) {
						list($ccid,$value) = explode("--",$value);
						print "<tr>\n";
						if ($print) {
							print "<td>$key</td>\n";
						} else {
							print "<td><a href=\"acc-reports-trialbal-detail-bk.php?id=$id&amp;fr=$fr&amp;to=$to&ccid=$ccid&cctitle=$key\">$key</a></td>\n";
						}
						if ($value < 0) {
							$value = ABS($value);
							print "<td>&nbsp;</td>\n";
							print "<td align=\"right\">".number_format($value,0)."</td>\n";
						} else  {
							print "<td align=\"right\">".number_format($value,0)."</td>\n";
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
					}

					print "<tr>\n";
					print "<td><b>One Off Expenses</b></td>\n";
					if ($pl_net3 < 0) {
						$pl_net3 = ABS($pl_net3);
						print "<td>&nbsp;</td>\n";
						print "<td align=\"right\"><b>".number_format($pl_net3,0)."</b></td>\n";
					} else {
						print "<td align=\"right\"><b>".number_format($pl_net3,0)."</b></td>\n";
						print "<td>&nbsp;</td>\n";
					}
					print "</tr>\n";
					foreach ($oneoffexpenses AS $key => $value) {
						list($ccid,$value) = explode("--",$value);
						print "<tr>\n";
						if ($print) {
							print "<td>$key</td>\n";
						} else {
							print "<td><a href=\"acc-reports-trialbal-detail-oo.php?id=$id&amp;fr=$fr&amp;to=$to&ccid=$ccid&cctitle=$key\">$key</a></td>\n";
						}
						if ($value < 0) {
							$value = ABS($value);
							$totcr += $value;
							print "<td>&nbsp;</td>\n";
							print "<td align=\"right\">".number_format($value,0)."</td>\n";
						} else  {
							$totdr += $value;
							print "<td align=\"right\">".number_format($value,0)."</td>\n";
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
					}

					print "<tr>\n";
					print "<td class=\"blank\"></td>\n";
					print "<td class=\"doubleline\"></td>\n";
					print "<td class=\"doubleline\"></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>&nbsp;</td>\n";
					print "<td align=\"right\"><b>".number_format($totdr,0)."</b></td>\n";
					print "<td align=\"right\"><b>".number_format($totcr,0)."</b></td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p>&nbsp;</p>\n";

					if (!$print) {
						print "<p align=\"center\"><a href=\"acc-reports-trialbal.php?id=$id&amp;fr=$fr&amp;to=$to&amp;print=1\" target=\"_blank\">Print</a></p>"; 

						/* print "<p align=\"center\"><b>Check PL Control</b></p>\n";
						$sql = "SELECT SUM(l.net) AS net, SUM(l.vat) AS vat, SUM(l.total) AS total
								FROM purchases_lines AS l
								LEFT JOIN purchases AS p ON p.ID = l.hid
								WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
								AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
								AND (p.txtype = 'I' OR p.txtype = 'C')
	 					";
	 					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$net    = $row['net'];
							$vat    = $row['vat'];
							$tot    = $row['total'];
							print "<p align=\"center\">Current Ledger Net:".number_format($net,0)." VAT:".number_format($vat,0)." Total:".number_format($tot,0)."</p>\n";

						}

						$ledger = "{$sy}_purchases";
						$sql = "SELECT SUM(total) AS total
								FROM $ledger AS p
								WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
								AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
								AND (p.txtype = 'I' OR p.txtype = 'C')
	 					";
	 					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$tot    = $row['total'];
							print "<p align=\"center\">Saved Ledger Total:".number_format($tot,0)."</p>\n";

						}


						print "<p align=\"center\"><b>Check SL Control</b></p>\n";
						$sql = "SELECT SUM(l.linenet) AS net, SUM(l.vattotal) AS vat, SUM(l.linetotal) AS total
								FROM inv_lines AS l
								LEFT JOIN inv_headers AS p ON p.ID = l.hid
								WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
								AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
								AND (p.txtype = 'I' OR p.txtype = 'C')
								AND l.unittype != 'Deposit'
	 					";
	 					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$net    = $row['net'];
							$vat    = $row['vat'];
							$tot    = $row['total'];
							print "<p align=\"center\">Current Ledger Net:".number_format($net,0)." VAT:".number_format($vat,0)." Total:".number_format($tot,0)."</p>\n";

						}

						$ledger = "{$sy}_inv_headers";
						$sql = "SELECT SUM(invtotal) AS total
								FROM $ledger AS p
								WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
								AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
								AND (p.txtype = 'I' OR p.txtype = 'C')
	 					";
	 					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$tot    = $row['total'];
							print "<p align=\"center\">Saved Ledger Total:".number_format($tot,0)." Including Deposits</p>\n";
						} */

						/* print "<p align=\"center\"><b>Bank Transactions In Wrong CC Group</b>\n";
						$sql = "SELECT
									SUM(CASE WHEN b.txtype = 'DR' THEN b.amount ELSE 0 END) AS totaldr,
									SUM(CASE WHEN b.txtype = 'CR' THEN b.amount ELSE 0 END) AS totalcr,
									c.title AS cctitle, c.ID AS ccid
								FROM bank AS b
								LEFT JOIN costcentres AS c ON c.ID = b.costcentre
								WHERE CONCAT(b.year,\"-\",b.month,\"-\",b.day) >= '$period_start'
								AND   CONCAT(b.year,\"-\",b.month,\"-\",b.day) <= '$period_end'
								AND c.ccgroup != '3'
								GROUP BY c.title
						";

						// Bank with wrong cc group
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							print "<table align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
							print "<tr>\n";
								print "<td><b>Group</b></td>\n";
								print "<td align=\"right\"><b>Dr</b></td>\n";
								print "<td align=\"right\"><b>Cr</b></td>\n";
								print "<tr>\n";
							while ($row = $result->fetch_assoc()) {
								$cctitle    = $row['cctitle'];
								$totaldr    = $row['totaldr'];
								$totalcr    = $row['totalcr'];
								$totdr      += $totaldr;
								$totcr      += $totalcr;

								print "<tr>\n";
								print "<td valign=\"top\">$cctitle</td>\n";
								print "<td valign=\"top\" align=\"right\">".number_format($totaldr,2)."</td>\n";
								print "<td valign=\"top\" align=\"right\">".number_format($totalcr,2)."</td>\n";
								print "<tr>\n";
							}
							print "</table>\n";
						} else {
							print "<p align=\"center\">None Found<br>" . $dbcnx->error . "</p>\n";
						} */


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
?>
