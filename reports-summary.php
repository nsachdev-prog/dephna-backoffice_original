<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
$page = "Bank";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        $date = date("d-m-Y");
        list($d,$m,$y) = explode("-",$date);

        foreach($SYScompanies AS $dbname => $company) {

            $html .= "<h2>$company $date</h2>\n";

            $html .= "<h3>Bank</h3>\n";

            // open database
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

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
                            e.type AS slextype
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
                            'None' AS slextype
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
                            'None' AS slextype
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

                    $html .= "<tr>\n";
                    $html .= "<td colspan=\"3\">&nbsp;</td>\n";
                    $html .= "<td><i>Openning Balance</i></td>\n";
                    $html .= "<td align=\"right\">&nbsp;</td>\n";
                    $html .= "<td align=\"right\">&nbsp;</td>\n";
                    $html .= "<td align=\"right\"><i>".number_format($opening_balance,2)."</i></td>\n";
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
                        if ($compname == "Imported From Tally") {
                            $compname = $bankref;
                        }
                        if (($amount < 0.00) AND ($ledger == "SL")) {
                            $credit = abs($amount);
                            $totalCredit += $credit;
                            $running_balance += $credit;
                            $numcr++;
                            $debit = "&nbsp;";
                        } elseif (($amount > 0.00) AND ($ledger == "PL")) {
                            $credit = abs($amount);
                            $totalCredit += $credit;
                            $running_balance += $credit;
                            $numcr++;
                            $debit = "&nbsp;";
                        } elseif ($ledger == "BK") {
                            if ($txtype == "CR") {
                                $credit = abs($amount);
                                $totalCredit += $credit;
                                $running_balance += $credit;
                                $numcr++;
                                $debit = "&nbsp;";
                            } else {
                                $debit = abs($amount);
                                $totalDebit += $debit;
                                $running_balance -= $debit;
                                $numdr++;
                                $credit = "&nbsp;";
                            }
                        } else {
                            $debit = abs($amount);
                            $totalDebit += $debit;
                            $running_balance -= $debit;
                            $numdr++;
                            $credit = "&nbsp;";
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
                        $html .= "<td align=\"right\">".number_format($debit,2)."</td>\n";
                        $html .= "<td align=\"right\">".number_format($credit,2)."</td>\n";
                        $html .= "<td align=\"right\">".number_format($running_balance,2)."</td>\n";
                        $html .= "<td style=\"padding-left:10px\">$crby</td>\n";
                        $html .= "</tr>\n";
                    }

                    $html .= "<tr>\n";
                    $html .= "<td colspan=\"3\">&nbsp;</td>\n";
                    $html .= "<td><i>Closing Balance</i></td>\n";
                    $html .= "<td align=\"right\">&nbsp;</td>\n";
                    $html .= "<td align=\"right\">&nbsp;</td>\n";
                    $html .= "<td align=\"right\"><i>".number_format($running_balance,2)."</i></td>\n";
                    $html .= "<td style=\"padding-left:10px\">&nbsp;</td>\n";
                    $html .= "</tr>\n";

                    $numrows = $result->num_rows;
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

                    $html .= "<h3>Debtors</h3>\n";
                    $sql = "SELECT
                                SUM(CASE WHEN u.ad_managed != 'Yes' THEN l.outstanding ELSE 0 END) AS unmanaged,
                                SUM(CASE WHEN u.ad_managed  = 'Yes' THEN l.outstanding ELSE 0 END) AS managed
                            FROM inv_headers AS l
                            LEFT JOIN users AS u ON u.custref = l.custref
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $unmanaged = $row['unmanaged'];
                        $managed   = $row['managed'];
                        $debtors   = $managed + $unmanaged;
                    }
                    $html .= "<table>\n";
                    $html .= "<tr><td><b>Total Unmanaged</b></td><td align=\"right\"><b>".number_format($unmanaged,2)."</b></td></tr>\n";
                    $html .= "<tr><td><b>Total Managed</b></td><td align=\"right\"><b>".number_format($managed,2)."</b></td></tr>\n";
                    $html .= "<tr><td><b>Total Outstannding</b></td><td align=\"right\"><b>".number_format($debtors,2)."</b></td></tr>\n";
                    $html .= "</table>\n";

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

                        $html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
                        $html .= "<tr>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\" width=\"40\"><b>Days</b></td>\n";
                        $html .= "</tr>\n";
        
                        $now = time();
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

                        $html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
                        $html .= "<tr>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\" width=\"40\"><b>Days</b></td>\n";
                        $html .= "</tr>\n";

                        $now = time();
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

                    $html .= "<h3>BK Manager</h3>\n";

                    $sql = "SELECT * FROM bk_manager $search ORDER BY crdate";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                        $html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
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

                    $html .= "<h3>Purchase Ledger</h3>\n";

                    $sql = "SELECT p.txtype, p.dephnaref, p.updby, s.suppname, p.net, p.vat, p.total,
                                    s.suppname, c.title
                            FROM purchases p
                            LEFT JOIN suppliers s ON p.suppid = s.ID
                            LEFT JOIN costcentres c ON p.costcentre = c.ID 
                            WHERE $dsearch
                            ORDER BY p.lastupd
                    ";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                        $html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
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
                        $html .= "<table cellspacing=\"0\" cellpadding=\"4\">\n";
                        $html .= "<tr>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                        $html .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
                        $html .= "</tr>\n";

                        while ($row = $result->fetch_assoc()) {

                            $year     = $row['year'];
                            $month    = $row['month'];
                            $day      = $row['day'];
                            $amount   = $row['total'];
                            $compname = $row['suppname'];
                            $txnnum   = $row['dephnaref'];
                            $txtype   = $row['txtype'];
        
                            $totAmount += $amount;
        
                            if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
                            
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
                }
                
                $dbcnx->close();
            } else {
                $resultMessage = "<b>Could not connect to database $dbname!</b><br>" . $dbcnx->connect_error;
            }
        }
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		$html .= "<p>$resultMessage</p>\n";
    }
    print $html;
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
