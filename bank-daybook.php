<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bank";
$subpage = "Daybook";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
    ?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Bank - Daybook</title>
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

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering
            $date = $_REQUEST['date'];
            if (!$date) { $date = date("d-m-Y"); }
            if ($date == date("d-m-Y")) {
                $orderby = "";
            } else {
                $orderby = "ORDER BY ABS(amount)";
            }
            list($d,$m,$y) = explode("-",$date);
            $search[] = "year='$y'";
            $search[] = "month='$m'";
            $search[] = "day='$d'";
            $prevday = date("d-m-Y",strtotime("$y-$m-$d -1 Day"));
            $nextday = date("d-m-Y",strtotime("$y-$m-$d +1 Day"));
            $search[]  = "(BINARY txtype = 'R'
                        OR BINARY txtype = 'P'
                        OR BINARY txtype = 'DR'
                        OR BINARY txtype = 'CR'
                        OR BINARY txtype = 'BP'
                        OR BINARY txtype = 'BR'
            )";
            
            if ($search) { $search = implode(" AND ", $search);}
            $qString ="";
            
            $types = array(
                "I"=>"Invoice",
                "C"=>"Credit Note",
                "P"=>"Payment",
                "R"=>"Receipt",
                "DR"=>"Payment",
                "CR"=>"Receipt",
                "BP"=>"Payment",
                "BR"=>"Receipt"
            );

            $slexTypes = array(
                "RC" => "Returned Cheque",
                "BD" => "Bounced DD",
                "DR" => "Deposit Repayment",
                "BC" => "Bank Charges",
            );

            print "<p align=\"center\" class=\"heading\">BANK</p>\n";
            if (!$print) {
                include('bank-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
                // print filter form
                print "<form name=\"addform\" method=\"post\" action=\"bank-daybook.php\">\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"bank-daybook.php?date=$prevday&$qString\"><b>&lt;&lt;</b></a></td>\n";
                print "<td style=\"font-size:1.2em\"><b>Day</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
                print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                print "<td style=\"font-size:3.6em\"><a href=\"bank-daybook.php\"><b>&bull;</b></a></td>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"bank-daybook.php?date=$nextday&$qString\"><b>&gt;&gt;</b></a></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";
            }
            print "<p align=\"center\" class=\"heading\">Daybook</p>\n";

            // BANK BALANCE
            $previousmonth = date("Y-m",strtotime("$y-$m-01 -1 Month"));
            // get opening balance
            $sql = "SELECT * FROM bank_periods WHERE period_ending = '$previousmonth'";
            if ($oresult = $dbcnx->query($sql) AND $oresult->num_rows) {
                $orow = $oresult->fetch_assoc();
                $opening_balance = $orow['balance'];
            } else {
                $opening_balance = 0;
            }
            // Sales Ledger
            $sql = "SELECT SUM(invtotal) AS amount
                FROM inv_headers
                WHERE year = '$y' AND month = '$m' AND day <= '$d' AND (BINARY txtype = 'P' OR BINARY txtype = 'R')
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $row = $result->fetch_assoc();
                $slamount = $row['amount'];
            } else {
                $slamount = 0;
            }
            // Purchase Ledger
            $sql = "SELECT SUM(total) AS amount
                FROM purchases
                WHERE year = '$y' AND month = '$m' AND day <= '$d'
                    AND (BINARY txtype = 'P'
                        OR BINARY txtype = 'R'
                        OR BINARY txtype = 'BP'
                        OR BINARY txtype = 'BR')
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $row = $result->fetch_assoc();
                $plamount = $row['amount'];
            } else {
                $plamount = 0;
            }
            // Bank Ledger
            $sql = "SELECT
                    SUM(CASE WHEN BINARY txtype = 'DR' THEN amount END) as debits,
                    SUM(CASE WHEN BINARY txtype = 'CR' THEN amount END) as credits
                FROM bank
                WHERE year = '$y' AND month = '$m' AND day <= '$d'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $row = $result->fetch_assoc();
                $debits  = $row['debits'];
                $credits = $row['credits'];
            } else {
                $debits  = 0;
                $credits = 0;
            }
            $bank_balance = $opening_balance - $slamount; // (minus because receipts are a negative. payments are positive.)
            $bank_balance += $plamount;
            $bank_balance -= $debits;
            $bank_balance += $credits;

            // print "OB: ".number_format($opening_balance,2)." | SL: ".number_format($slamount,2)." | PL: ".number_format($plamount,2)." | DR: ".number_format($debits,2)." | CR: ".number_format($credits,2)."<br>";

            print "<p align=\"center\" class=\"larger\">$d-$m-$y Balance: ".number_format($bank_balance,2)."</p>";

            // TRANSACTIONS
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
                    WHERE $search
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
                    WHERE $search
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
					WHERE $search
					$orderby
            ";

			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					
                print "<table width=\"95%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Fr</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999; padding-left:10px\"><b>Entered By</b></td>\n";
                print "</tr>\n";

                $numdr = 0;
                $numcr = 0;
                $cctotal = 0;
                $amtotal = 0;
                $cottotal = 0;
                $ddtotal = 0;
                $drtotal = 0;
                $ccnum = 0;
                $amnum = 0;
                $cotnum = 0;
                $ddnum = 0;
                $drnum = 0;

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
                    $debit = 0;
                    $credit = 0;
                    if ($compname == "Imported From Tally") {
                        $compname = $bankref;
                    }
                    if ($supplierref == "cc") {
                        $cctotal += $amount;
                        $ccnum++;
                    } elseif ($supplierref == "am") {
                        $amtotal += $amount;
                        $amnum++;
                    } elseif ($supplierref == "cot") {
                        $cottotal += $amount;
                        $cotnum++;
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
                            $numcr++;
                            $debit = 0;
                        } elseif (($amount > 0.00) AND ($ledger == "PL")) {
                            $credit = abs($amount);
                            $totalCredit += $credit;
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
                            $numdr++;
                            $credit = 0;
                        }
                        if (strlen($compname) > 46) { $compname = substr($compname, 0, 46); }
                        
                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "<td>$thisdate</td>\n";
                        if (($slextype) AND ($slextype != "None")) {
                            print "<td>{$slexTypes[$slextype]}</td>\n";
                        } else {
                            print "<td>{$types[$txtype]}</td>\n";
                        }
                        print "<td>$ledger</td>\n";
                        print "<td>$txnnum$cc</td>\n";
                        if ($ledger == "SL") {
                            print "<td><a href=\"sl-ledger.php?id=$compid&from=bankdb\">$compname</a></td>\n";
                        } elseif ($ledger == "PL") {
                            print "<td><a href=\"pl-ledger.php?id=$compid&from=bankdb\">$compname</a></td>\n";
                        } elseif ($ledger == "BK") {
                            print "<td><a href=\"bank-view.php?id=$recid&date=$date\">$compname</a></td>\n";
                        } else {
                            print "<td>$compname</td>\n";
                        }
                        if ($debit) {
                            print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                            print "<td align=\"right\">&nbsp;</td>\n";
                        }
                        if ($credit) {
                            print "<td align=\"right\">&nbsp;</td>\n";
                            print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                        }
                        print "<td style=\"padding-left:10px\">$crby</td>\n";
                        print "</tr>\n";
                    }
                }

                if ($cctotal) {
                    if ($cctotal > 0.00) {
                        $credit = abs($cctotal);
                        $totalCredit += $credit;
                        $numcr++;
                        $debit = 0;
                    } else {
                        $debit = abs($cctotal);
                        $totalDebit += $debit;
                        $numdr++;
                        $credit = 0;
                    }
                    $ccnum--;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    print "<td><a href=\"bank-daybook-cc.php?date=$date\">Barclaycard</a></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    if ($debit) {
                        print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }
                    if ($credit) { 
                        print "<td align=\"right\">&nbsp;</td>\n";
                        print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                    }
                    print "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                    print "</tr>\n";
                }

                if ($amtotal) {
                    if ($amtotal > 0.00) {
                        $credit = abs($amtotal);
                        $totalCredit += $credit;
                        $numcr++;
                        $debit = 0;
                    } else {
                        $debit = abs($amtotal);
                        $totalDebit += $debit;
                        $numdr++;
                        $credit = 0;
                    }
                    $amnum--;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    print "<td><a href=\"bank-daybook-amex.php?date=$date\">American Express</a></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    if ($debit) {
                        print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }
                    if ($credit) {
                        print "<td align=\"right\">&nbsp;</td>\n";
                        print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                    }
                    print "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                    print "</tr>\n";
                }

                if ($cottotal) {
                    if ($cottotal > 0.00) {
                        $credit = abs($cottotal);
                        $totalCredit += $credit;
                        $numcr++;
                        $debit = 0;
                    } else {
                        $debit = abs($cottotal);
                        $totalDebit += $debit;
                        $numdr++;
                        $credit = 0;
                    }
                    $cotnum--;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    print "<td><a href=\"bank-daybook-cot.php?date=$date\">Capital on Tap</a></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    if ($debit) {
                        print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }
                    if ($credit) {
                        print "<td align=\"right\">&nbsp;</td>\n";
                        print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                    }
                    print "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                    print "</tr>\n";
                }

                if ($drtotal) {
                    if ($drtotal > 0.00) {
                        $debit = abs($drtotal);
                        $totalDebit += $debit;
                        $numdr++;
                        $credit = 0;
                    } else {
                        $credit = abs($drtotal);
                        $totalCredit += $credit;
                        $numcr++;
                        $debit = 0;
                    }
                    $drnum--;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    print "<td>Deposit Repayments</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    if ($debit) {
                        print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }
                    if ($credit) {
                        print "<td align=\"right\">&nbsp;</td>\n";
                        print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                    }
                    print "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                    print "</tr>\n";
                }

                if ($ddtotal) {
                    if ($ddtotal > 0.00) {
                        $debit = abs($ddtotal);
                        $totalDebit += $debit;
                        $numdr++;
                        $credit = 0;
                    } else {
                        $credit = abs($ddtotal);
                        $totalCredit += $credit;
                        $numcr++;
                        $debit = 0;
                    }
                    $ddnum--;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    print "<td>Direct Debits</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    if ($debit) {
                        print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }
                    if ($credit) {
                        print "<td align=\"right\">&nbsp;</td>\n";
                        print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                    }
                    print "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                    print "</tr>\n";
                }

                $numrows = $result->num_rows - $ccnum - $amnum - $ctnum - $ddnum - $drnum;
                print "<tr>\n";
                print "<td style=\"border-top:1px solid #999999\"><b>$numrows | $numdr | $numcr</b></td>\n";
                print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalDebit,2)."</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalCredit,2)."</b></td>\n";
                print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                
                print "</table>\n";

                if (!$print) { 
                    print "<p align=\"center\"><a href=\"bank-daybook.php?date=$date&amp;print=1\" target=\"_blank\">Print</a></p>\n";
                }
			} else {
				$resultMessage = "<b>No Transactions Found!</b><br>" . $dbcnx->error;
            }

            if (!$print) { 
                print "<p align=\"center\"><a href=\"bnk-get-statement.php\">Live Bank Statement</a></p>\n";
                // print "<p align=\"center\"><a href=\"bnk-get-authorisation.php\">Start Bank Authorisation</a></p>\n";
            }
            
            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
            print "	});\n";
            print "</script>\n";

			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
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
    print "</td>\n";
    include('footer.php');
}
?>
