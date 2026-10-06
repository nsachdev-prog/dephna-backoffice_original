<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bank";
$subpage = "Statement";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
    ?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Bank - Statement</title>
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
            $search = array();
            $date = $_REQUEST['date'];
            if (!$date) { $date = date("m-Y"); } 
            list($m,$y) = explode("-",$date);
            $search[] = "year='$y'";
            $search[] = "month='$m'";
            $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));
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
                "DR" => "Deposit Repayment"
            );

            print "<p align=\"center\" class=\"heading\">BANK</p>\n";
            if (!$print) {
                include('bank-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
                // print filter form
                print "<form name=\"addform\" method=\"post\" action=\"bank-statement.php\">\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"bank-statement.php?date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
                print "<td style=\"font-size:1.2em\"><b>Month</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
                print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                print "<td style=\"font-size:3.6em\"><a href=\"bank-statement.php\"><b>&bull;</b></a></td>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"bank-statement.php?date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";
            }
            print "<p align=\"center\" class=\"heading\">Statement - $thismonth</p>\n";

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
                        e.type AS slextype
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
                        'None' AS slextype
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
                        'None' AS slextype
					FROM bank AS b
                    LEFT JOIN costcentres AS c ON b.costcentre = c.ID
					WHERE $search
					ORDER BY year, month, day
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
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Balance</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999; padding-left:10px\"><b>Entered By</b></td>\n";
                print "</tr>\n";

                $numdr = 0;
                $numcr = 0;

                // get opening balance
                $lastmonth = date("Y-m",strtotime("$y-$m-01 -1 Month"));
                $sql = "SELECT * FROM bank_periods WHERE period_ending = '$lastmonth'";
                if ($oresult = $dbcnx->query($sql) AND $oresult->num_rows) {
                    $orow = $oresult->fetch_assoc();
                    $opening_balance = $orow['balance'];
                }

                if ($opening_balance) {
                    $details = "Opening Balance";
                    $running_balance = $opening_balance;
                } else {
                    $details = "Opening Balance Unknown";
                    $running_balance = 0;
                }
                print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                print "<td colspan=\"4\">&nbsp;</td>\n";
                print "<td><i>$details</i></td>\n";
                print "<td align=\"right\">&nbsp;</td>\n";
                print "<td align=\"right\">&nbsp;</td>\n";
                print "<td align=\"right\"><i>".number_format($running_balance,2)."</i></td>\n";
                print "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                print "</tr>\n";

                $totalDebit = 0;
                $totalCredit = 0;

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
                    if ($compname == "Imported From Tally") {
                        $compname = $bankref;
                    }
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
                    if (strlen($compname) > 40) { $compname = substr($compname, 0, 40); }
                    if (strlen($crby) > 8) { $crby = substr($crby, 0, 8); }
                    if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    if (($slextype) AND ($slextype != "None")) {
                        print "<td>{$slexTypes[$slextype]}</td>\n";
                    } else {
                        print "<td>{$types[$txtype]}</td>\n";
                    }
                    print "<td>$ledger</td>\n";
                    print "<td>$txnnum</td>\n";
                    if (($ledger == "SL") AND (!$print)) {
                        print "<td><a href=\"sl-ledger.php?id=$compid&from=daily\">$compname</a></td>\n";
                    } elseif (($ledger == "PL") AND (!$print)) {
                        print "<td><a href=\"pl-ledger.php?id=$compid&from=bankdb\">$compname</a></td>\n";
                    } elseif (($ledger == "BK") AND (!$print)) {
                        print "<td><a href=\"bank-view.php?id=$recid&date=$date&fr=st\">$compname</a></td>\n";
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
                    print "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
                    print "<td style=\"padding-left:10px\">$crby</td>\n";
                    print "</tr>\n";
                }

                $numrows = $result->num_rows;
                print "<tr>\n";
                print "<td style=\"border-top:1px solid #999999\"><b>$numrows | $numdr | $numcr</b></td>\n";
                print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalDebit,2)."</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalCredit,2)."</b></td>\n";
                print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                
                print "</table>\n";

                if (!$print) { 
                    print "<p align=\"center\"><a href=\"bank-statement.php?date=$date&amp;print=1\" target=\"_blank\">Print</a></p>\n";
                    print "<p align=\"center\"><a href=\"bank-statement-dnlcsv.php?year=$y\" target=\"_blank\">Download $y</a></p>\n";
                }

			} else {
				$resultMessage = "<b>No Transactions Found!</b><br>" . $dbcnx->error;
            }
            
            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
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
