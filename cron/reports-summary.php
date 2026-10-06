#!/usr/bin/php

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');

$date = date("d-m-Y");
list($d,$m,$y) = explode("-",$date);
$now = time();

foreach($SYScompanies AS $dbname => $company) {

	$html .= "<h2 style=\"padding:10px 0px 10px 0px\">$company $date</h2>\n";

	// open database
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$html .= "<h3 style=\"padding:10px 0px 10px 0px\">Bank</h3>\n";

		$dsearch = array();
		$dsearch[] = "year='$y'";
		$dsearch[] = "month='$m'";
		$dsearch[] = "day<'$d'";
		$dsearch = implode(" AND ", $dsearch);
		$tsearch = "(BINARY txtype = 'R'
					OR BINARY txtype = 'P'
					OR BINARY txtype = 'DR'
					OR BINARY txtype = 'CR'
					OR BINARY txtype = 'BP'
					OR BINARY txtype = 'BR'
		)";
		
		$types = array(
			"I"=>"Inv",
			"C"=>"Cred",
			"P"=>"Pay",
			"R"=>"Rec",
			"p"=>"Pay(p)",
			"r"=>"Rec(p)",
			"DR"=>"Pay",
			"CR"=>"Rec",
			"BP"=>"Pay",
			"BR"=>"Rec"
		);

		$slexTypes = array(
			"RC" => "Returned Cheque",
			"BD" => "Bounced DD",
			"DR" => "Deposit Repayment"
		);

		// get month opening balance
		$lastmonth = date("Y-m",strtotime("$y-$m-01 -1 Month"));
		$sql = "SELECT * FROM bank_periods WHERE period_ending = '$lastmonth'";
		if ($oresult = $dbcnx->query($sql) AND $oresult->num_rows) {
			$orow = $oresult->fetch_assoc();
			$opening_balance = $orow['balance'];
		}

		// get SL receipts
		$sql = "SELECT SUM(invtotal) AS amount FROM inv_headers WHERE $dsearch AND $tsearch";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$thismonth = $row['amount'] *-1;
			$opening_balance += $thismonth;
		}
		
		// get PL paymants
		$sql = "SELECT SUM(total) AS amount FROM purchases WHERE $dsearch AND $tsearch";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$thismonth = $row['amount'] *-1;
			$opening_balance -= $thismonth;
		}
		
		// gat bank receipts and payments
		$sql = "SELECT
				SUM(CASE WHEN txtype = 'DR' THEN amount ELSE 0 END) AS dr,
				SUM(CASE WHEN txtype = 'CR' THEN amount ELSE 0 END) AS cr
				FROM bank
				WHERE $dsearch AND $tsearch
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$dr = $row['dr'];
			$cr = $row['cr'];
			$opening_balance -= $dr;
			$opening_balance += $cr;
		}

		$dsearch = array();
		$dsearch[] = "year='$y'";
		$dsearch[] = "month='$m'";
		$dsearch[] = "day='$d'";
		$dsearch = implode(" AND ", $dsearch);

		$sql = "SELECT
					'SL' AS ledger,
					h.ID AS recid,
					h.invnumber AS txnnum,
					h.year,
					h.month,
					h.day,
					'None' AS bankref,
					h.custref AS custref,
					h.invtotal AS amount,
					h.txtype,
					h.crby AS crby,
					u.compname AS compname,
					u.ID AS compid,
					e.type AS slextype,
					'None' AS supplierref,
					h.ddmonth
				FROM inv_headers AS h
				LEFT JOIN users AS u ON h.custref = u.custref
				LEFT JOIN sl_extra AS e ON h.ID = e.hid
				WHERE $dsearch AND $tsearch
				UNION ALL
				SELECT
					'PL' AS ledger,
					p.ID AS recid,
					p.dephnaref AS txnnum,
					p.year,
					p.month,
					p.day,
					'None' AS bankref,
					'None' AS custref,
					p.total AS amount,
					p.txtype,
					p.updby AS crby,
					s.suppname AS compname,
					s.ID AS compid,
					'None' AS slextype,
					p.supplierref,
					'None' AS ddmonth
				FROM purchases AS p
				LEFT JOIN suppliers AS s ON p.suppid = s.ID  
				WHERE $dsearch AND $tsearch
				UNION ALL
				SELECT
					'BK' AS ledger,
					b.ID AS recid,
					b.dephnaref AS txnnum,
					b.year,
					b.month,
					b.day,
					b.bankref AS bankref,
					b.costcentre AS custref,
					b.amount AS amount,
					b.txtype,
					b.updby AS crby,
					c.title AS compname,
					'None' AS compid,
					'None' AS slextype,
					'None' AS supplierref,
					'None' AS ddmonth
				FROM bank AS b
				LEFT JOIN costcentres AS c ON b.costcentre = c.ID
				WHERE $dsearch AND $tsearch
				ORDER BY year, month, day
		";

		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				
			$html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
			$html .= "<tr>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Fr</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Balance</b></td>\n";
			$html .= "<td style=\"border-bottom:1px solid #999999; padding-left:10px\"><b>Entered By</b></td>\n";
			$html .= "</tr>\n";

			$numdr = 0;
			$numcr = 0;
			$totalDebit = 0;
			$totalCredit = 0;
			$drtotal = 0;
			$cctotal = 0;
			$amtotal = 0;
			$ddtotal = 0;

			$html .= "<tr>\n";
			$html .= "<td colspan=\"3\">&nbsp;</td>\n";
			$html .= "<td><b>Openning Balance</b></td>\n";
			$html .= "<td align=\"right\">&nbsp;</td>\n";
			$html .= "<td align=\"right\">&nbsp;</td>\n";
			$html .= "<td align=\"right\"><b>".number_format($opening_balance,2)."</b></td>\n";
			$html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
			$html .= "</tr>\n";

			$running_balance = $opening_balance;

			while ($row = $result->fetch_assoc()) {
				$thisdate = $row['day']."-".$row['month']."-".$row['year'];
				$compname = $row['compname'];
				$txnnum   = $row['txnnum'];
				$amount   = $row['amount'];
				$txtype   = $row['txtype'];
				$crby     = $row['crby'];
				$compid   = $row['compid'];
				$slextype = $row['slextype'];
				$ledger   = $row['ledger'];
				$recid    = $row['recid'];
				$bankref  = $row['bankref'];
				$supplierref = $row['supplierref'];
				$ddmonth  = $row['ddmonth'];
				if ($compname == "Imported From Tally") {
					$compname = $bankref;
				}
				if ($supplierref == "cc") {
					$cctotal += $amount;
					$ccnum++;
				} elseif ($supplierref == "am") {
					$amtotal += $amount;
					$amnum++;
				} elseif ($ddmonth == "dd") {
					$ddtotal += $amount;
					$ddnum++;
				} elseif ($slextype == "DR") {
					$drtotal += $amount;
					$drnum++;
				} else {
					if (($amount < 0.00) AND ($ledger == "SL")) {
						$credit = abs($amount);
						$totalCredit += $credit;
						$running_balance += $credit;
						$numcr++;
						$debit = 0;
					} elseif (($amount > 0.00) AND ($ledger == "PL")) {
						$credit = abs($amount);
						$totalCredit += $credit;
						$running_balance += $credit;
						$numcr++;
						$debit = 0;
					} elseif ($ledger == "BK") {
						if ($txtype == "CR") {
							$credit = abs($amount);
							$totalCredit += $credit;
							$running_balance += $credit;
							$numcr++;
							$debit = 0;
						} else {
							$debit = abs($amount);
							$totalDebit += $debit;
							$running_balance -= $debit;
							$numdr++;
							$credit = 0;
						}
					} else {
						$debit = abs($amount);
						$totalDebit += $debit;
						$running_balance -= $debit;
						$numdr++;
						$credit = 0;
					}
					if (strlen($compname) > 18) { $compname = substr($compname, 0, 18); }
					if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
					
					$html .= "<tr>\n";
					if (($slextype) AND ($slextype != "None")) {
						$html .= "<td>{$slexTypes[$slextype]}</td>\n";
					} else {
						$html .= "<td>{$types[$txtype]}</td>\n";
					}
					$html .= "<td>$ledger</td>\n";
					$html .= "<td>$txnnum</td>\n";
					$html .= "<td>$compname</td>\n";
					if ($debit) {
						$html .= "<td align=\"right\">".number_format($debit,2)."</td>\n";
					} else {
						$html .= "<td align=\"right\">&nbsp;</td>\n";
					}
					if ($credit) {
						$html .= "<td align=\"right\">".number_format($credit,2)."</td>\n";
					} else {
						$html .= "<td align=\"right\">&nbsp;</td>\n";
					}
					$html .= "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
					$html .= "<td style=\"padding-left:10px\">$crby</td>\n";
					$html .= "</tr>\n";
				}
			}

			if ($cctotal) {
				if ($cctotal > 0.00) {
					$credit = abs($cctotal);
					$totalCredit += $credit;
					$running_balance += $credit;
					$numcr++;
					$debit = 0;
				} else {
					$debit = abs($cctotal);
					$totalDebit += $debit;
					$running_balance -= $debit;
					$numdr++;
					$credit = 0;
				}
				$ccnum--;
				$html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
				$html .= "<td>Pay</td>\n";
				$html .= "<td>PL</td>\n";
				$html .= "<td>&nbsp;</td>\n";
				$html .= "<td>Barclaycard</td>\n";
				if ($debit) {
					$html .= "<td align=\"right\">".number_format($debit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				if ($credit) {
					$html .= "<td align=\"right\">".number_format($credit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				$html .= "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
				$html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
				$html .= "</tr>\n";
			}

			if ($amtotal) {
				if ($amtotal > 0.00) {
					$credit = abs($amtotal);
					$totalCredit += $credit;
					$running_balance += $credit;
					$numcr++;
					$debit = 0;
				} else {
					$debit = abs($amtotal);
					$totalDebit += $debit;
					$running_balance -= $debit;
					$numdr++;
					$credit = 0;
				}
				$amnum--;
				$html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
				$html .= "<td>Pay</td>\n";
				$html .= "<td>PL</td>\n";
				$html .= "<td>&nbsp;</td>\n";
				$html .= "<td>American Express</td>\n";
				if ($debit) {
					$html .= "<td align=\"right\">".number_format($debit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				if ($credit) {
					$html .= "<td align=\"right\">".number_format($credit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				$html .= "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
				$html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
				$html .= "</tr>\n";
			}

			if ($drtotal) {
				if ($drtotal > 0.00) {
					$debit = abs($drtotal);
					$totalDebit += $debit;
					$running_balance -= $debit;
					$numdr++;
					$credit = 0;
				} else {
					$credit = abs($drtotal);
					$totalCredit += $credit;
					$running_balance += $credit;
					$numcr++;
					$debit = 0;
				}
				$drnum--;
				$html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
				$html .= "<td>Pay</td>\n";
				$html .= "<td>SL</td>\n";
				$html .= "<td>&nbsp;</td>\n";
				$html .= "<td>Deposit Repayments</td>\n";
				if ($debit) {
					$html .= "<td align=\"right\">".number_format($debit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				if ($credit) {
					$html .= "<td align=\"right\">".number_format($credit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				$html .= "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
				$html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
				$html .= "</tr>\n";
			}

			if ($ddtotal) {
				if ($ddtotal > 0.00) {
					$debit = abs($ddtotal);
					$totalDebit += $debit;
					$running_balance -= $debit;
					$numdr++;
					$credit = 0;
				} else {
					$credit = abs($ddtotal);
					$totalCredit += $credit;
					$running_balance += $credit;
					$numcr++;
					$debit = 0;
				}
				$ddnum--;
				$html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
				$html .= "<td>Rec</td>\n";
				$html .= "<td>SL</td>\n";
				$html .= "<td>&nbsp;</td>\n";
				$html .= "<td>Direct Debits</td>\n";
				if ($debit) {
					$html .= "<td align=\"right\">".number_format($debit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				if ($credit) {
					$html .= "<td align=\"right\">".number_format($credit,2)."</td>\n";
				} else {
					$html .= "<td align=\"right\">&nbsp;</td>\n";
				}
				$html .= "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
				$html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
				$html .= "</tr>\n";
			}

			$html .= "<tr>\n";
			$html .= "<td colspan=\"3\">&nbsp;</td>\n";
			$html .= "<td><b>Closing Balance</b></td>\n";
			$html .= "<td align=\"right\">&nbsp;</td>\n";
			$html .= "<td align=\"right\">&nbsp;</td>\n";
			$html .= "<td align=\"right\"><b>".number_format($running_balance,2)."</b></td>\n";
			$html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
			$html .= "</tr>\n";

			$numrows = $result->num_rows - $ccnum - $amnum - $ddnum - $drnum;
			$html .= "<tr>\n";
			$html .= "<td style=\"border-top:1px solid #999999\"><b>$numrows | $numdr | $numcr</b></td>\n";
			$html .= "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			$html .= "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalDebit,2)."</b></td>\n";
			$html .= "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalCredit,2)."</b></td>\n";
			$html .= "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			
			$html .= "</table>\n";

		} else {
			$html .= "<p>No Transactions Today<br><b>Balance: ".number_format($opening_balance,2)."</b></p>";
		}

		if ($dbname == "dephna") {

			$html .= "<h3 style=\"padding:10px 0px 6px 0px\">Debtors Reporting</h3>\n";

			// build arrays of sites (user holds ip address instead of sitename).
			$sql = "SELECT * FROM sites";
			$sitesresult = $dbcnx->query($sql);
			while ($srow = $sitesresult->fetch_assoc()) {
				$title     = $srow['title'];
				$ipaddress = $srow['ipaddress'];
				$locations[$title] = $ipaddress;
				$sites[$ipaddress] = $title;
			}

			// get a page of results
			$sql = "SELECT
					SUM(l.outstanding) AS outstanding,
					u.ad_managed AS toggle,
					u.location
				FROM inv_headers AS l
				LEFT JOIN users AS u ON u.custref=l.custref
				WHERE l.outstanding <> 0
				GROUP BY u.custref
			";
			
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {
				$html .= "<p><b>Outstanding</b></p>\n";
				$html .= "<table cellspacing=\"0\" cellpadding=\"6\">\n";
				
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Managed</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Owed Not Inc Cr</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Owed Inc Cr</b></td>\n";
				$html .= "</tr>\n";
				
				$results = array();

				while ($row = $result->fetch_assoc()) {
					$location = $row['location'];
					$outstanding  = $row['outstanding'];
					$toggle   = $row['toggle'];
					
					if ($outstanding < 0) {
						$results[$location]['credit'] += $outstanding;
					} else {
						if ($toggle == "Yes") {
							$results[$location]['managed'] += $outstanding;
						} else {
							$results[$location]['debit'] += $outstanding;
							
						}
						$results[$location]['owed'] += $outstanding;
					}
				}
				
				$totalcredit = 0;
				$totaldebit  = 0;
				$totalmangd  = 0;
				$totalowed   = 0;
				
				foreach ($results AS $site => $result) {

					$owed2 = $result['owed'] + $result['credit'];
					
					$html .= "<tr>\n";
					$html .= "	<td>{$sites[$site]}</td>\n";
					$html .= "	<td align=\"right\">".number_format($result['credit'],2)."</td>\n";
					$html .= "	<td align=\"right\">".number_format($result['debit'],2)."</td>\n";
					$html .= "	<td align=\"right\">".number_format($result['managed'],2)."</td>\n";
					$html .= "	<td align=\"right\">".number_format($result['owed'],2)."</td>\n";
					$html .= "	<td align=\"right\">".number_format($owed2,2)."</td>\n";
					$html .= "</tr>\n";
					$totalcredit += $result['credit'];
					$totaldebit  += $result['debit'];
					$totalmangd  += $result['managed'];
					$totalowed   += $result['owed'];
					$totalowed2  += $owed2;
				}
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\"><b>Total Outstanding</b></td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalcredit,2)."</b></td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totaldebit,2)."</b></td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalmangd,2)."</b></td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalowed,2)."</b></td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalowed2,2)."</b></td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";
				$html .= "<p>&nbsp;</p>\n";
			} else {
				$html .= "<p><b>No Debtors Found!</b></p>" . $dbcnx->error;
			}

			$sql = "SELECT l.custref, SUM(l.outstanding) AS outstanding, u.compname, u.location, u.ad_managed, s.title AS sitename
				FROM inv_headers AS l
				LEFT JOIN users AS u ON u.custref = l.custref
				LEFT JOIN sites AS s ON u.location = s.ipaddress
				WHERE l.outstanding <> 0.00
				GROUP BY l.custref
				ORDER BY outstanding DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				$html .= "<p><b>Highest Debits</b></p>\n";

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\" width=\"40\"><b>Days</b></td>\n";
				$html .= "</tr>\n";

				$n = 1;

				while ($row = $result->fetch_assoc()) {
					$outstanding = $row['outstanding'];
					$managed = $row['ad_managed'];
					if ($outstanding > 0.00 AND $managed != "Yes") {
						$sitename = $row['sitename'];
						$debit = $outstanding;
						$pageTotal += $outstanding;
						$totalDebit += $debit;

						// get days since oldest outstanding
						$sql = "SELECT year, month, day FROM inv_headers WHERE custref={$row['custref']} AND outstanding <> 0 ORDER BY year, month, day LIMIT 0,1";
						if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
							$drow = $dresult->fetch_assoc();
							$year  = $drow['year'];
							$month = $drow['month'];
							$day   = $drow['day'];
							$unixdate = strtotime("$year-$month-$day");
							$seconds = $now - $unixdate;
							$days = round($seconds / (60 * 60 * 24));
						}

						$html .= "<tr>\n";
						$html .= " <td align=\"right\">$n</td>\n";
						$html .= "	<td>{$row['compname']}</td>\n";
						$html .= "	<td>$sitename</td>\n";
						$html .= "	<td align=\"right\">".number_format($debit,2)."</td>\n";
						$html .= "	<td align=\"right\">$days</td>\n";
						$html .= "</tr>\n";
						$n++;
						if ($n == 6) {
							break;
						}
					}
				}	
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" colspan=\"4\" align=\"right\">&nbsp;</td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

				// Reset results array
				mysqli_data_seek($result,0);

				$html .= "<p><b>Highest Managed</b></p>\n";

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\" width=\"40\"><b>Days</b></td>\n";
				$html .= "</tr>\n";

				$n = 1;

				while ($row = $result->fetch_assoc()) {
					$managed = $row['ad_managed'];
					if ($managed == "Yes") {
						$outstanding = $row['outstanding'];
						$sitename = $row['sitename'];
						$managed = $outstanding;
						$pageTotal += $outstanding;
						$totalManaged += $managed;
					
						// get days since oldest outstanding
						$sql = "SELECT year, month, day FROM inv_headers WHERE custref={$row['custref']} AND outstanding <> 0 ORDER BY year, month, day LIMIT 0,1";
						if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
							$drow = $dresult->fetch_assoc();
							$year  = $drow['year'];
							$month = $drow['month'];
							$day   = $drow['day'];
							$unixdate = strtotime("$year-$month-$day");
							$seconds = $now - $unixdate;
							$days = round($seconds / (60 * 60 * 24));
						}

						$html .= "<tr>\n";
						$html .= "  <td align=\"right\">$n</td>\n";
						$html .= "	<td>{$row['compname']}</td>\n";
						$html .= "	<td>$sitename</td>\n";
						$html .= "	<td align=\"right\">".number_format($managed,2)."</td>\n";
						$html .= "	<td align=\"right\">$days</td>\n";
						$html .= "</tr>\n";
						$n++;
						if ($n == 6) {
							break;
						}
					}
				}
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" colspan=\"4\" align=\"right\">&nbsp;</td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

			} else {
				$html .= "<p><b>No Results Found!</b></p>\n";
			}

			$html .= "<h3 style=\"padding:10px 0px 6px 0px\">Debtors Notes</h3>\n";
			$notedate = date("Y-m-d");
			$sql = "SELECT d.*, u.compname
					FROM ad_notes AS d
					LEFT JOIN users AS u ON u.custref = d.custref
					WHERE d.date = '$notedate'
					ORDER BY d.crdate
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr><td>\n";
				while ($row = $result->fetch_assoc()) {
					$reminder = $row['remdate'];
					if ($reminder) {
						$reminder = "- Reminder " . $reminder;
					} else {
						$reminder = "";
					}
					$notetime = date("H:i",$row['crdate']);
					$html .= "<b>$notetime - {$row['compname']} - {$row['crby']} $reminder</b><br>";
					$html .= "{$row['details']}<br>\r\n";
				}
				$html .= "</td></tr>\n";
				$numrows = $result->num_rows;
				$html .= "<tr>\n";
				$html .= "	<td><b>Total Notes: $numrows</b></td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";
			} else {
				$html .= "<p><b>No Notes Added Today</b></p>\n";
			}

			$html .= "<h3 style=\"padding:10px 0px 6px 0px\">Payment Plans</h3>\r\n";

			$sql = "SELECT
						SUM(CASE WHEN BINARY l.txtype = 'DR' THEN l.amount END) as debits,
						SUM(CASE WHEN BINARY l.txtype = 'CR' THEN l.amount END) as credits,
						h.txndate, h.status, u.compname, u.custref
					FROM pplans_lines AS l
                    LEFT JOIN pplans_headers AS h ON h.ID = l.ppid
					LEFT JOIN users AS u ON u.custref = h.custref
					WHERE h.status < '2'
					GROUP BY l.ppid
					ORDER BY h.status, h.txndate DESC
					
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Status</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999; width:120px\" align=\"right\"><b>DR</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999; width:120px\" align=\"right\"><b>CR</b></td>\n";
				$html .= "</tr>\r\n";

                $states = array(
                    0 => "Pending",
                    1 => "Active",
                    2 => "Complete"
                );
				
				while ($row = $result->fetch_assoc()) {
                    $compname  = $row['compname'];
                    $custref   = $row['custref'];
                    $txndate   = $row['txndate'];
					$status    = $row['status'];
					$debits    = $row['debits'];
					$credits   = $row['credits'];

					if (!$debits)  { $debits = 0; }
					if (!$credits) { $credits = 0; }

					$totdr += $debits;
					$totcr += $credits;
					
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
                    
					$html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
					$html .= "	<td valign=\"top\">$compname</td>\n";
					$html .= "	<td valign=\"top\">DP$custref</td>\n";
                    $html .= "	<td valign=\"top\">$txndate</td>\n";
                    $html .= "	<td valign=\"top\">{$states[$status]}</td>\n";
					$html .= "	<td align=\"right\">".number_format($debits,2)."</td>\n";
					$html .= "	<td align=\"right\">".number_format($credits,2)."</td>\n";
                    $html .= "</tr>\r\n";
                    
				}
				$html .= "<tr>\n";
				$html .= "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\">".number_format($totdr,2)."</td>\n";
				$html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\">".number_format($totcr,2)."</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\r\n";

			} else {
				$html .= "<p align=\"center\">Nothing Found!</p>\r\n";
			}


			$html .= "<h3 style=\"padding:10px 0px 6px 0px\">BK Manager</h3>\n";

			$sql = "SELECT * FROM bk_manager $search ORDER BY crdate";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\"><b>Started</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\"><b>By</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\"><b>Customer</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\"><b>Cust Ref</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\"><b>Site</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\"><b>Units</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>To Pay</b></td>\n";
				$html .= "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$bkid        = $row['ID'];
					$user        = $row['user'];
					$username    = $row['username'];
					$crdate      = $row['crdate'];
					$custref     = $row['custref'];
					$compname    = $row['compname'];
					$siteid      = $row['siteid'];
					$sitename    = $row['sitename'];
					$bktype      = $row['bktype'];
					$crdate = date("d-m-Y",$crdate);
					if (strlen($compname) > 12) { $compname = substr($compname, 0, 12); }

					if ($bkid) {
						# get units/virtuals booked
						$topay = 0;
						$allbookings = array();
						if ($bktype == "Units") {
							$sql = "SELECT unitname, unittype, rate, deptotal FROM bkm_units WHERE bkid='$bkid' ORDER BY unitname";
							$unitsresult = $dbcnx->query($sql);
							if (($unitsresult) && ($unitsresult->num_rows)) {
								$deposit = 0;
								$totrates = 0;
								while ($unitsrow = $unitsresult->fetch_assoc()) {
									$unitname = $unitsrow['unitname'];
									list($pre,$num) = explode(" ",$unitname);
									$allbookings[] = $num;
									$unittype = $unitsrow['unittype'];
									$rate     = $unitsrow['rate'];
									$deposit += $unitsrow['deptotal'];
									$sql = "SELECT vat FROM types WHERE title='$unittype'";
									if (($vtresult = $dbcnx->query($sql)) && ($vtresult->num_rows)) {
										$vtrow = $vtresult->fetch_assoc();
										$tvat = $vtrow['vat'];
									}
									if ($tvat == "Yes") {
										$thisvat = $rate/100*$vatRate;
										$rate += $thisvat;
									}
									$totrates += $rate;
								}
								$topay = $totrates + $deposit;
								$allbookings = implode(", ",$allbookings);
							} else {
								$allbookings = "&nbsp;";
							}
						} elseif($bktype == "Virtuals") {
							$sql = "SELECT pkname FROM bkm_virtuals WHERE bkid='$bkid' ORDER BY pkname";
							$vresult = $dbcnx->query($sql);
							if (($vresult) && ($vresult->num_rows)) {
								while ($vrow = $vresult->fetch_assoc()) {
									$thispkname = $vrow['pkname'];
									$allbookings[] = $thispkname;
								}
								$allbookings = implode(", ",$allbookings);
							} else {
								$allbookings = "&nbsp;";
							}
						}
					} else {
						$allbookings = "&nbsp;";
					}

					$html .= "<tr>\n";
					$html .= "	<td valign=\"top\">$crdate</td>\n";
					$html .= "	<td valign=\"top\">$username</td>\n";
					$html .= "	<td valign=\"top\">$compname</td>\n";
					$html .= "	<td valign=\"top\">DP$custref</td>\n";
					$html .= "	<td valign=\"top\">$sitename</td>\n";
					$html .= "	<td valign=\"top\">$allbookings</td>\n";
					$html .= "	<td valign=\"top\" align=\"right\">".number_format($topay,2)."</td>\n";
					$html .= "</tr>\n";
				}
				$numrows = $result->num_rows;
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
				$html .= "	<td colspan=\"6\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
				$html .= "</tr>\n";

				$html .= "</table>\n";
			} else {
				$html .= "<p><b>No Provisional Bookings Found</b></p>\n";
			}


			$html .= "<h3 style=\"padding:10px 0px 6px 0px\">Unconfirmed SRFs</h3>\n";
			$sql = "SELECT s.*, u.compname
					FROM srf2 AS s
					LEFT JOIN users AS u ON u.custref = s.custref
					WHERE s.confirmed IS NULL
					ORDER BY txndate DESC
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\">Date</td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Amount</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Entered By</b></td>\n";
				$html .= "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$txndate     = $row['txndate'];
					$compname    = $row['compname'];
					$unitname    = $row['unitname'];
					$charge      = $row['charge'];
					$updby       = $row['updby'];
					if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }
					$html .= "<tr>\n";
					$html .= "<td>$txndate</td>\n";
					$html .= "<td>$compname</td>\n";
					$html .= "<td>$unitname</td>\n";
					$html .= "<td align=\"right\">".number_format($charge,2)."</td>\n";
					$html .= "<td>$updby</td>\n";
					$html .= "</tr>\n";
				}
				$numrows = $result->num_rows;
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
				$html .= "	<td colspan=\"4\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

			} else {
				$html .= "<p><b>No Unconfirmed SRFs Found</b></p>\n";
			}


			$html .= "<h3 style=\"padding:10px 0px 6px 0px\">Purchase Ledger</h3>\n";

			$sql = "SELECT p.txtype, p.dephnaref, p.updby, s.suppname, p.net, p.vat, p.total,
							s.suppname, c.title
					FROM purchases p
					LEFT JOIN suppliers s ON p.suppid = s.ID
					LEFT JOIN costcentres c ON p.costcentre = c.ID 
					WHERE $dsearch
					ORDER BY p.lastupd
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\">Type</td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>CCentre</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Net</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Vat</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #999999\"><b>Entered By</b></td>\n";
				$html .= "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$txtype      = $row['txtype'];
					$suppname    = $row['suppname'];
					$dephnaref   = $row['dephnaref'];
					$costcentre  = $row['title'];
					$updby       = $row['updby'];
					$net = $row['net'];
					$vat = $row['vat'];
					$tot = $row['total'];
					if (strlen($suppname) > 10) { $suppname = substr($suppname, 0, 10); }
					if (strlen($supplierref) > 10) { $supplierref = substr($supplierref, 0, 10); }
					if (strlen($costcentre) > 10) { $costcentre = substr($costcentre, 0, 10); }

					$html .= "<tr>\n";
					$html .= "<td>{$types[$txtype]}</td>\n";
					$html .= "<td>$dephnaref</td>\n";
					$html .= "<td>$suppname</td>\n";
					$html .= "<td>$costcentre</td>\n";
					$html .= "<td align=\"right\">".number_format($net,2)."</td>\n";
					$html .= "<td align=\"right\">".number_format($vat,2)."</td>\n";
					$html .= "<td align=\"right\">".number_format($tot,2)."</td>\n";
					$html .= "<td>$updby</td>\n";
					$html .= "</tr>\n";
				}
				$numrows = $result->num_rows;
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
				$html .= "	<td colspan=\"8\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";
			} else {
				$html .= "<p><b>No Transactions Today</b></p>\n";
			}

			$html .= "<p><b>Pending</b></p>\n";

			$sql = "SELECT p.txtype, p.year, p.month, p.day, p.total, p.dephnaref, s.suppname
					FROM purchases AS p
					LEFT JOIN suppliers AS s ON p.suppid = s.ID
					WHERE BINARY txtype = 'p' OR BINARY txtype = 'r'
					ORDER BY p.year, p.month, p.day, s.suppname
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				$html .= "</tr>\n";

				$totAmount = 0;
				while ($row = $result->fetch_assoc()) {

					$year     = $row['year'];
					$month    = $row['month'];
					$day      = $row['day'];
					$amount   = $row['total'];
					$compname = $row['suppname'];
					$txnnum   = $row['dephnaref'];
					$txtype   = $row['txtype'];

					$totAmount += $amount;

					if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
					
					$html .= "<tr>\n";
					$html .= "	<td>$day-$month-$year</td>\n";
					$html .= "	<td>$compname</td>\n";
					$html .= "  <td>{$types[$txtype]}</td>\n";
					$html .= "	<td>$txnnum</td>\n";
					$html .= "	<td align=\"right\">".number_format($amount,2)."</td>\n";
					$html .= "</tr>\n";
					
				}

				$html .= "<tr>\n";
				$html .= "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				$html .= "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totAmount,2)."</b></td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

			} else {
				$html .= "<p><b>No Transactions Pending</b></p>\n";
			}

			$html .= "<p><b>VAT Periods</b></p>\r\n";

			$sql = "SELECT *
					FROM vat_periods
					ORDER BY period_start DESC
					LIMIT 0,3
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				$html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
				$html .= "<tr>\n";
				$html .= "<td style=\"border-bottom:1px solid #666\"><b>From</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #666\"><b>To</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #666\"><b align=\"right\">Amount</b></td>\n";
				$html .= "<td style=\"border-bottom:1px solid #666\"><span style=\"color:green;font-weight:bold\">Paid</span> / <span style=\"color:red;font-weight:bold\">Due</span></td>\n";
				$html .= "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$id           = $row['ID'];
					$period_start = $row['period_start'];
					$period_end   = $row['period_end'];
					$paid_on      = $row['paid_on'];

					$period_start = $period_start . "-01";
					$period_end   = $period_end   . "-01";
					$ed = date("t",strtotime($period_end));
					list($sy,$sm,$sd)   = explode("-",$period_start);
					list($ey,$em,$junk) = explode("-",$period_end);
					if ($paid_on) {
						$paid = $paid_on;
						list($py,$pm,$pd) = explode("-",$paid);
						$sl_vat = $row['sl_vat'];
						$pl_vat = $row['pl_vat'];
					} else {
						$paid = "Unpaid";
						// get sales vat and net
						$sql = "SELECT SUM(l.vattotal) AS sl_vat, SUM(l.linenet) AS sl_net
								FROM inv_lines AS l
								LEFT JOIN inv_headers AS h ON l.hid = h.ID
								WHERE CONCAT(h.year,\"-\",h.month) >= '$sy-$sm'
								AND CONCAT(h.year,\"-\",h.month) <= '$ey-$em'
								AND (h.txtype = 'I' OR h.txtype = 'C')
								AND l.unittype != 'Deposit'
								AND h.custref != '20199'
								AND h.custref != '20720'
						";
						if ($slresult = $dbcnx->query($sql) AND $slresult->num_rows) {
							$slrow = $slresult->fetch_assoc();
							$sl_vat = $slrow['sl_vat'];
							$sl_net = $slrow['sl_net'];
						} else {
							$sl_vat = 0;
						}

						// get purchases vat
						$sql = "SELECT SUM(vat) AS pl_vat, SUM(net) AS pl_net
								FROM purchases
								WHERE CONCAT(year,\"-\",month) >= '$sy-$sm'
								AND CONCAT(year,\"-\",month) <= '$ey-$em'
								AND (txtype = 'I' OR txtype = 'C')
						";
						if ($plresult = $dbcnx->query($sql) AND $plresult->num_rows) {
							$plrow = $plresult->fetch_assoc();
							$pl_vat = $plrow['pl_vat'];
							$pl_net = $plrow['pl_net'];
						} else {
							$pl_vat = 0;
						}
					}

					$due = $sl_vat - $pl_vat;
					
					$html .= "<tr>\n";
					$html .= "<td>$sd-$sm-$sy</td>\n";
					$html .= "<td>$ed-$em-$ey</td>\n";
					$html .= "<td align=\"right\">".number_format($due,2)."</td>\n";
					if (!$paid_on) {
						$nextday = date("Y-m-d",strtotime("$ey-$em-$ed +1 Day"));
						$dueon   = date("d-m-Y",strtotime("$nextday +1 Month 6 days"));
						$html .= "<td align=\"left\"><span style=\"color:red;font-weight:bold\">$dueon</span></td>\n";
					} else {
						$html .= " <td align=\"left\"><span style=\"color:green;font-weight:bold\">$pd-$pm-$py</span></td>\n";
					}
					// $html .= "	<td align=\"left\">$paid</td>\n";
					$html .= "</tr>\n";
				}
				$html .= "<tr>\n";
				$html .= " <td style=\"border-top:1px solid #666\" colspan=\"4\">&nbsp;</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";
			} else {
				$html .= "<p align=\"center\">No Periods Found!</p>\n";
			}

			// support snapshot
			$html .= "<p><b>Support Snapshot</b></p>\r\n";
			$sevenDaysAgo = date("Y-m-d", strtotime("-7 days"));

            $sql = "SELECT t.*, s.username AS assigned, c.compname
                    FROM support_tickets AS t
                    LEFT JOIN staff AS s ON s.ID = t.assignedto
					LEFT JOIN users AS c ON c.custref = t.custref
                    WHERE t.status = 'Open'
                      AND (t.flagged = '1'
                       OR t.crdate > UNIX_TIMESTAMP('$sevenDaysAgo'))
					ORDER BY t.crdate DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Tkt No</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Assigned</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Callbacks</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Emails</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Flagged</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Meetings</b></td>\n";
				$html .= "</tr>\n";

				$flagcount = 0;
                $meetcount = 0;
                $callbackcount = 0;
                $emailcount = 0;
                $assignments = array();
                $new = 0;

                while ($row = $result->fetch_assoc()) {
                    $tid        = $row['ID'];
                    $crdate     = $row['crdate'];
                    $ticketno   = $row['ticketno'];
                    $compname   = $row['compname'];
                    $subject    = $row['subject'];
                    $assigned   = $row['assigned'];
                    $flagged    = $row['flagged'];
                    $phone1     = $row['phone1'];

                    if ($crdate > strtotime("-7 days")) {
                        $new++;
                    }

                    $now  = time();
					$diff = $now - $crdate;
					$days = floor($diff/(60*60*24));

                    $crdate = date("d-m-Y",$crdate);

                    if (strlen($compname) > 20) { $subject = substr($compname, 0, 20); }

                    if ($flagged) {
                        $flagged = "Yes";
                        $flagcount++;
                    } else {
                        $flagged = "&nbsp;";
                    }

                    if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
                        $assignments[$assigned]++;
					}

                    // get meetings
                    $sql = "SELECT COUNT(*) AS meetings
                            FROM support_notes
                            WHERE tid = '$tid'
                              AND details LIKE 'Meeting...%'
                    ";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $meetings = $row2['meetings'];
                        $meetcount += $meetings;
                    }

                    // has there been a callback?
                    $sql = "SELECT COUNT(*) AS callbacks
                            FROM support_notes
                            WHERE tid = '$tid'
                              AND details LIKE 'Call Back...%'
                    ";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $callbacks = $row2['callbacks'];
                        if ($callbacks) {
                            $callbackcount += $callbacks;
                        } else {
                            $callbacks = 0;
                        }
                    } else {
                        $callbacks = 0;
                    }
                    if (!$callbacks) {
                        if ($phone1) {
                            $callbacks = "&nbsp;";
                        } else {
                            $callbacks = "nn";
                        }
                    }

                    // has there been an email answer?
                    $sql = "SELECT COUNT(*) AS emails
							FROM `support_emails`
							WHERE `tid` = '$tid'
							AND `origin` = 'Dephna'
					";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $emails = $row2['emails'];
                        if ($emails) {
                            $emailcount += $emails;
                        } else {
                            $email = "&nbsp;";
                        }
                    } else {
                        $email = "&nbsp;";
                    }

					$html .= "<tr>\n";
                    $html .= "	<td>$ticketno</td>\n";
                    $html .= "	<td>$crdate</td>\n";
                    $html .= "	<td>$compname</td>\n";
                    $html .= "	<td align=\"right\">$days</td>\n";
                    $html .= "	<td align=\"right\">$assigned</td>\n";
                    $html .= "	<td align=\"right\">$callbacks</td>\n";
                    $html .= "	<td align=\"right\">$emails</td>\n";
                    $html .= "	<td align=\"right\">$flagged</td>\n";
                    $html .= " <td align=\"right\">$meetings</td>\n";
                    $html .= "</tr>\n";

                }

				$html .= "<tr>\n";
                $html .= "	<td colspan=\"5\" style=\"border-top:1px solid #999999\"><b>Current - {$result->num_rows}&nbsp;&nbsp;&nbsp;New - $new</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$callbackcount</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$emailcount</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$flagcount</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$meetcount</b></td>\n";
                $html .= "</tr>\n";

                foreach ($assignments AS $assigned => $count) {
                    $html .= "<tr>\n";
                    $html .= "	<td colspan=\"4\">&nbsp;</td>\n";
                    $html .= "	<td align=\"right\">$assigned</td>\n";
                    $html .= "	<td align=\"right\">$count</td>\n";
                    $html .= "	<td colspan=\"3\">&nbsp;</td>\n";
                    $html .= "</tr>\n";
                }

				$html .= "</table>\n";
			} else {
				$mtml .= "<p align=\"center\">No Tickets Found!</p>\n";
			}

			// support tickets
			$html .= "<p><b>Support Tickets</b></p>\r\n";
			$query   = "SELECT COUNT(CASE WHEN `tdatetime` LIKE '%$notedate%' AND `type` = 'Support' THEN 1 END) AS totalticketstoday,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Support' THEN 1 END) AS opentickets,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Support' AND pending = '1' THEN 1 END) AS pendingtickets,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Support' AND fvdate  = '0' THEN 1 END) AS notviewed,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Support' AND replied = '0' THEN 1 END) AS notreplied
						FROM support_tickets
			";
			if ($result  = $dbcnx->query($query)) {
				$row     = $result->fetch_assoc();
				$totalticketstoday = $row['totalticketstoday'];
				$opentickets       = $row['opentickets'];
				$pendingtickets    = $row['pendingtickets'];
				$notviewed         = $row['notviewed'];
				$notreplied        = $row['notreplied'];

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Today</td>\n";
				$html .= "	<td>$totalticketstoday</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Open Tickets</td>\n";
				$html .= "	<td>$opentickets</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Pending</td>\n";
				$html .= "	<td>$pendingtickets</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Not Viewed</td>\n";
				$html .= "	<td>$notviewed</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Not Replied</td>\n";
				$html .= "	<td>$notreplied</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

			} else {
				$html .= "<p align=\"center\">Could Not Get Result!<br>" . $dbcnx->error . "</p>\n";
			}

			// accounts tickets
			$html .= "<p><b>Accounts Tickets</b></p>\r\n";
			$query   = "SELECT COUNT(CASE WHEN `tdatetime` LIKE '%$notedate%' AND `type` = 'Accounts' THEN 1 END) AS totalticketstoday,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Accounts' THEN 1 END) AS opentickets,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Accounts' AND pending = '1' THEN 1 END) AS pendingtickets,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Accounts' AND fvdate  = '0' THEN 1 END) AS notviewed,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Accounts' AND replied = '0' THEN 1 END) AS notreplied
						FROM support_tickets
			";
			if ($result  = $dbcnx->query($query)) {
				$row     = $result->fetch_assoc();
				$totalticketstoday = $row['totalticketstoday'];
				$opentickets       = $row['opentickets'];
				$pendingtickets    = $row['pendingtickets'];
				$notviewed         = $row['notviewed'];
				$notreplied        = $row['notreplied'];

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Today</td>\n";
				$html .= "	<td>$totalticketstoday</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Open Tickets</td>\n";
				$html .= "	<td>$opentickets</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Pending</td>\n";
				$html .= "	<td>$pendingtickets</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Not Viewed</td>\n";
				$html .= "	<td>$notviewed</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Not Replied</td>\n";
				$html .= "	<td>$notreplied</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

			} else {
				$html .= "<p align=\"center\">Could Not Get Result!<br>" . $dbcnx->error . "</p>\n";
			}

			// camilla tickets
			$html .= "<p><b>Camilla Tickets</b></p>\r\n";
			$query   = "SELECT COUNT(CASE WHEN `tdatetime` LIKE '%$notedate%' AND `type` = 'Camilla' THEN 1 END) AS totalticketstoday,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Camilla' THEN 1 END) AS opentickets,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Camilla' AND pending = '1' THEN 1 END) AS pendingtickets,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Camilla' AND fvdate  = '0' THEN 1 END) AS notviewed,
							   COUNT(CASE WHEN `status` = 'Open' AND `type` = 'Camilla' AND replied = '0' THEN 1 END) AS notreplied
						FROM support_tickets
			";
			if ($result  = $dbcnx->query($query)) {
				$row     = $result->fetch_assoc();
				$totalticketstoday = $row['totalticketstoday'];
				$opentickets       = $row['opentickets'];
				$pendingtickets    = $row['pendingtickets'];
				$notviewed         = $row['notviewed'];
				$notreplied        = $row['notreplied'];

				$html .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Today</td>\n";
				$html .= "	<td>$totalticketstoday</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Open Tickets</td>\n";
				$html .= "	<td>$opentickets</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Pending</td>\n";
				$html .= "	<td>$pendingtickets</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Not Viewed</td>\n";
				$html .= "	<td>$notviewed</td>\n";
				$html .= "</tr>\n";
				$html .= "<tr>\n";
				$html .= "	<td>Total Not Replied</td>\n";
				$html .= "	<td>$notreplied</td>\n";
				$html .= "</tr>\n";
				$html .= "</table>\n";

			} else {
				$html .= "<p align=\"center\">Could Not Get Result!<br>" . $dbcnx->error . "</p>\n";
			}

			// leads snapshot
			$html .= "<p><b>Leads Snapshot</b></p>\r\n";
			$sevenDaysAgo = date("Y-m-d", strtotime("-7 days"));

            $sql = "SELECT t.*, s.username AS assigned
                    FROM leads_tickets AS t
                    LEFT JOIN staff AS s ON s.ID = t.assignedto
                    WHERE t.status = 'Open'
                      AND (t.flagged = '1'
                       OR t.crdate > UNIX_TIMESTAMP('$sevenDaysAgo'))
					ORDER BY t.crdate DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                $html .= "<table cellspacing=\"0\" cellpadding=\"8\">\n";
                $html .= "<tr>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Tkt No</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
				$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Assigned</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Callbacks</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Emails</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Flagged</b></td>\n";
                $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Meetings</b></td>\n";
				$html .= "</tr>\n";

                $flagcount = 0;
                $meetcount = 0;
                $callbackcount = 0;
                $emailcount = 0;
                $assignments = array();
                $new = 0;
				$totLiveChat = 0;
                $totMoneypenny = 0;
                $totWeb = 0;

                while ($row = $result->fetch_assoc()) {
                    $tid        = $row['ID'];
                    $crdate     = $row['crdate'];
                    $ticketno   = $row['ticketno'];
                    $compname   = $row['compname'];
                    $subject    = $row['subject'];
                    $assigned   = $row['assigned'];
                    $flagged    = $row['flagged'];
                    $phone1     = $row['phone1'];
					$crby       = $row['crby'];

					if ($crby == "LiveChat") {
                        $totLiveChat++;
                    } elseif ($crby == "Moneypenny") {
                        $totMoneypenny++;
                    } elseif ($crby == "Web") {
                        $totWeb++;
                    }

                    if ($crdate > strtotime("-7 days")) {
                        $new++;
                    }

                    $crdate = date("d-m-Y H:i",$crdate);

                    if (strlen($compname) > 28) { $subject = substr($compname, 0, 28); }

                    if ($flagged) {
                        $flagged = "Yes";
                        $flagcount++;
                    } else {
                        $flagged = "&nbsp;";
                    }

                    if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
                        $assignments[$assigned]++;
					}

                    // get meetings
                    $sql = "SELECT COUNT(*) AS meetings
                            FROM leads_notes
                            WHERE tid = '$tid'
                              AND details LIKE 'Meeting...%'
                    ";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $meetings = $row2['meetings'];
                        $meetcount += $meetings;
                    }

                    // has there been a callback?
                    $sql = "SELECT COUNT(*) AS callbacks
                            FROM leads_notes
                            WHERE tid = '$tid'
                              AND details LIKE 'Call Back...%'
                    ";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $callbacks = $row2['callbacks'];
                        if ($callbacks) {
                            $callbackcount += $callbacks;
                        } else {
                            $callbacks = 0;
                        }
                    } else {
                        $callbacks = 0;
                    }
                    if (!$callbacks) {
                        if ($phone1) {
                            $callbacks = "&nbsp;";
                        } else {
                            $callbacks = "nn";
                        }
                    }

                    // has there been an email answer?
                    $sql = "SELECT COUNT(*) AS emails
							FROM `leads_emails`
							WHERE `tid` = '$tid'
							AND `origin` = 'Dephna'
					";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $emails = $row2['emails'];
                        if ($emails) {
                            $emailcount += $emails;
                        } else {
                            $email = "&nbsp;";
                        }
                    } else {
                        $email = "&nbsp;";
                    }

                    $html .= "<tr>\n";
                    $html .= "	<td>$ticketno</td>\n";
                    $html .= "	<td>$crdate</td>\n";
                    $html .= "	<td>$compname</td>\n";
                    $html .= "	<td align=\"right\">$assigned</td>\n";
                    $html .= "	<td align=\"right\">$callbacks</td>\n";
                    $html .= "	<td align=\"right\">$emails</td>\n";
                    $html .= "	<td align=\"right\">$flagged</td>\n";
                    $html .= " <td align=\"right\">$meetings</td>\n";
                    $html .= "</tr>\n";

                }

                $html .= "<tr>\n";
                $html .= "	<td colspan=\"4\" style=\"border-top:1px solid #999999\"><b>Current - {$result->num_rows}&nbsp;&nbsp;&nbsp;New - $new</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$callbackcount</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$emailcount</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$flagcount</b></td>\n";
                $html .= "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$meetcount</b></td>\n";
                $html .= "</tr>\n";

				$html .= "<tr>\n";
                $html .= "    <td colspan=\"3\" valign=\"top\">\n";
                $html .= "        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">\n";
                $html .= "            <tr>\n";
                $html .= "                <td style=\"border-bottom:1px solid #999999\"><b>Source</b></td>\n";
                $html .= "                <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Count</b></td>\n";
                $html .= "            <tr>\n";
                $html .= "                <td>LiveChat</td>\n";
                $html .= "                <td align=\"right\">$totLiveChat</td>\n";
                $html .= "            </tr>\n";
                $html .= "            <tr>\n";
                $html .= "                <td>Moneypenny</td>\n";
                $html .= "                <td align=\"right\">$totMoneypenny</td>\n";
                $html .= "            </tr>\n";
                $html .= "            <tr>\n";
                $html .= "                <td>Web</td>\n";
                $html .= "                <td align=\"right\">$totWeb</td>\n";
                $html .= "            </tr>\n";
                $html .= "        </table>\n";
                $html .= "    </td>\n";
                $html .= "    <td>&nbsp;</td>\n";
                $html .= "    <td colspan=\"5\" valign=\"top\">\n";
                $html .= "        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">\n";
                $html .= "            <tr>\n";
                $html .= "                <td style=\"border-bottom:1px solid #999999\"><b>Assigned</b></td>\n";
                $html .= "                <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Count</b></td>\n";
                $html .= "            <tr>\n";
                foreach ($assignments AS $assigned => $count) {
                    $html .= "<tr>\n";
                    $html .= "	<td>$assigned</td>\n";
                    $html .= "	<td align=\"right\">$count</td>\n";
                    $html .= "</tr>\n";
                }
                $html .= "        </table>\n";
                $html .= "    </td>\n";
                $html .= "</tr>\n";

				$html .= "</table>\n";
			} else {
				$html .= "<p align=\"center\">Nothing Found!</p>\n";
			}

			// leads
			$html .= "<p><b>Leads</b></p>\r\n";
			$from = strtotime("-24 Hours");
			$thismonth = date("Y-m");
			$lastmonth = date("Y-m",strtotime("-1 Month"));
			$twomonths = date("Y-m",strtotime("-2 Months"));

			$html .= "<table cellspacing=\"5\" cellpadding=\"2\">\n";
			$html .= "<tr>\n";
			$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
			$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>24 Hours</b></td>\n";
			$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>This Month</b></td>\n";
			$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Last Month</b></td>\n";
			$html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Two Months Ago</b></td>\n";
			$html .= "</tr>\n";
			// Web
			$query   = "SELECT
							COUNT(CASE WHEN crdate > '$from' AND crby = 'Web' THEN 1 ELSE NULL END) AS today,
							COUNT(CASE WHEN tdatetime LIKE '$thismonth%' AND crby = 'Web' THEN 1 ELSE NULL END) AS thismonth,
							COUNT(CASE WHEN tdatetime LIKE '$lastmonth%' AND crby = 'Web' THEN 1 ELSE NULL END) AS lastmonth,
							COUNT(CASE WHEN tdatetime LIKE '$twomonths%' AND crby = 'Web' THEN 1 ELSE NULL END) AS twomonths
						FROM leads_tickets
			";
			$result  = $dbcnx->query($query);
			$row     = $result->fetch_assoc();
			$html .= "<tr>\n";
			$html .= "	<td>Web</td>\n";
			$html .= "	<td align=\"right\">{$row['today']}</td>\n";
			$html .= "	<td align=\"right\">{$row['thismonth']}</td>\n";
			$html .= "	<td align=\"right\">{$row['lastmonth']}</td>\n";
			$html .= "	<td align=\"right\">{$row['twomonths']}</td>\n";
			$html .= "</tr>\n";
			// Moneypenny
			$query   = "SELECT
							COUNT(CASE WHEN crdate > '$from' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS today,
							COUNT(CASE WHEN tdatetime LIKE '$thismonth%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS thismonth,
							COUNT(CASE WHEN tdatetime LIKE '$lastmonth%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS lastmonth,
							COUNT(CASE WHEN tdatetime LIKE '$twomonths%' AND crby = 'Moneypenny' THEN 1 ELSE NULL END) AS twomonths
						FROM leads_tickets
			";
			$result = $dbcnx->query($query);
			$row = $result->fetch_assoc();
			$html .= "<tr>\n";
			$html .= "	<td>Moneypenny</td>\n";
			$html .= "	<td align=\"right\">{$row['today']}</td>\n";
			$html .= "	<td align=\"right\">{$row['thismonth']}</td>\n";
			$html .= "	<td align=\"right\">{$row['lastmonth']}</td>\n";
			$html .= "	<td align=\"right\">{$row['twomonths']}</td>\n";
			$html .= "</tr>\n";
			// LiveChat
			$query   = "SELECT
							COUNT(CASE WHEN crdate > '$from' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS today,
							COUNT(CASE WHEN tdatetime LIKE '$thismonth%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS thismonth,
							COUNT(CASE WHEN tdatetime LIKE '$lastmonth%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS lastmonth,
							COUNT(CASE WHEN tdatetime LIKE '$twomonths%' AND crby = 'LiveChat' THEN 1 ELSE NULL END) AS twomonths
						FROM leads_tickets
			";
			$result = $dbcnx->query($query);
			$row = $result->fetch_assoc();
			$html .= "<tr>\n";
			$html .= "	<td>LiveChat</td>\n";
			$html .= "	<td align=\"right\">{$row['today']}</td>\n";
			$html .= "	<td align=\"right\">{$row['thismonth']}</td>\n";
			$html .= "	<td align=\"right\">{$row['lastmonth']}</td>\n";
			$html .= "	<td align=\"right\">{$row['twomonths']}</td>\n";
			$query = "<tr>\n";
			$html .= "</table>\n";
		} 
		
		$dbcnx->close();
	} else {
		$resultMessage = "<b>Could not connect to database $dbname!</b><br>" . $dbcnx->connect_error;
	}
}

if ($resultMessage) {
	$html .= "<p>$resultMessage</p>\n";
}

require "../PHPMailer/src/SMTP.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/Exception.php";

$fromname = "Dephna Robot";
$fromemail = "robot@dephna.com";
$subject = "Daily Accounts Summary";

$mail = new PHPMailer(true);

try {

	include('SMTP.php');
	
	$mail->setFrom($fromemail, $fromname);
	$mail->addAddress("rajive@dephna.com", "Rajive Sachdev");
	$mail->addAddress("nimesh@dephna.com", "Nimesh Sachdev");
	$mail->isHTML(true);
	$mail->Subject = $subject;
	$mail->Body    = $html;

	$mail->send();

} catch (Exception $e) {
	echo "Email FAILED Mailer Error: {$mail->ErrorInfo}<br>";
}
?>
