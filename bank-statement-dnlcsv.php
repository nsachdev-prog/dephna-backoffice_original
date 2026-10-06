<?php
$page = "Bank";
$subpage = "Statement";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        $year = $_REQUEST['year'];

        if ($year) {

            // open database
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                // get filtering
                $search[] = "year='$year'";
                $search[]  = "(BINARY txtype = 'R'
                            OR BINARY txtype = 'P'
                            OR BINARY txtype = 'DR'
                            OR BINARY txtype = 'CR'
                            OR BINARY txtype = 'BP'
                            OR BINARY txtype = 'BR'
                )";

                if ($search) { $search = implode(" AND ", $search);}
                
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

                header('Content-type: text/csv');
                header("Content-Disposition: attachment; filename=Dephna-Statement-$year.csv");
                header('Content-Transfer-Encoding: ISO-8859-1');
                header('Pragma: no-cache, must-revalidate');
                header('Expires: 0');

                print "$compName\n";
                print "Statement\n";
                print "$year\n";
                print "\n";

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

                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					
                        print "Date,";
                        print "Type,";
                        print "Fr,";
                        print "Ref,";
                        print "Details,";
                        print "Debit,";
                        print "Credit\n";
        
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
                                $debit = "";
                            } elseif (($amount > 0.00) AND ($ledger == "PL")) {
                                $credit = abs($amount);
                                $debit = "";
                            } elseif ($ledger == "BK") {
                                if ($txtype == "CR") {
                                    $credit = abs($amount);
                                    $debit = "";
                                } else {
                                    $debit = abs($amount);
                                    $credit = "";
                                }
                            } else {
                                $debit = abs($amount);
                                $credit = "";
                            }
                            if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
                            
                            print "$thisdate,";
                            if (($slextype) AND ($slextype != "None")) {
                                print "{$slexTypes[$slextype]},";
                            } else {
                                print "{$types[$txtype]},";
                            }
                            print "$ledger,";
                            print "$txnnum,";
                            print "$compname,";
                            print "$debit,";
                            print "$credit\n";
                        }
                    }
                    print "\n";
                }
                $dbcnx->close();
            }
        }
	}
}
?>
