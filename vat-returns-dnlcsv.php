<?php
$page = "VAT";
$subpage = "View";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        $id = $_REQUEST['id'];

        if ($id) {

            // open database
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                header('Content-type: text/csv');
                header('Content-Disposition: attachment; filename=Dephna-VAT.csv');
                header('Content-Transfer-Encoding: ISO-8859-1');
                header('Pragma: no-cache, must-revalidate');
                header('Expires: 0');

                print "$compName\n";
                print "\n";

                $sql = "SELECT * FROM vat_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];
                    $paid_on      = $row['paid_on'];

                    $period_start = $period_start . "-01";
                    $period_end   = $period_end   . "-01";
                    $ed = date("t",strtotime($period_end));
                    list($sy,$sm,$sd)   = explode("-",$period_start);
                    list($ey,$em,$junk) = explode("-",$period_end);
                    $txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
                    $txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));

                    if ($paid_on) {
                        // get finalised figures
                        $sl_vat = $row['sl_vat'];
                        $sl_net = $row['sl_net'];
                        $pl_vat = $row['pl_vat'];
                        $pl_net = $row['pl_net'];

                    } else {
                        // get dynamic figures
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
                        if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                            $row = $result->fetch_assoc();
                            $sl_vat = $row['sl_vat'];
                            $sl_net = $row['sl_net'];
                        } else {
                            $sl_vat = "Error";
                        }

                        // get purchases vat
                        $sql = "SELECT SUM(vat) AS pl_vat, SUM(net) AS pl_net
                                FROM purchases
                                WHERE CONCAT(year,\"-\",month) >= '$sy-$sm'
                                AND CONCAT(year,\"-\",month) <= '$ey-$em'
                                AND (txtype = 'I' OR txtype = 'C')
                        ";
                        if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                            $row = $result->fetch_assoc();
                            $pl_vat = $row['pl_vat'];
                            $pl_net = $row['pl_net'];
                        } else {
                            $pl_vat = "Error";
                        }

                    }

                    $due = $sl_vat - $pl_vat;

                    print "Value Added Tax Return $txt_st to $txt_en\n";
                    print "\n";
                    
                    print "VAT due this period on SALES and other outputs:,1,".number_format($sl_vat,2,'.','')."\n";
                    print "VAT due this period on AQUISITIONS form other EC MEMBER STATES:,2,0\n";
                    print "Total VAT due (THE SUM OF BOXES 1 AND 2):,3,".number_format($sl_vat,2,'.','')."\n";
                    print "VAT recailmed in this period on PURCHASES and other inputs (including aquisistions from the EC):,4,".number_format($pl_vat,2,'.','')."\n";
                    print "Net VAT to be paid to Customs or reclaimed by you (DIFFERENCE BETWEEN BOXES 3 AND 4):,5,".number_format($due,2,'.','')."\n";
                    print "\n";
                    print "Total value of SALES and all other outputs excluding any VAT. INCLUDE YOUR BOX 8 FIGURE:,6,".number_format($sl_net,2,'.','')."\n";
                    print "Total value of PURCHASES and all other inputs excluding any VAT. INCLUDE YOUR BOX 9 FIGURE:,7,".number_format($pl_net,2,'.','')."\n";
                    print "Total value of all SUPPLIES of goods and related services excluding any VAT to other EC MEMBER STATES:,8,0\n";
                    print "Total value of all AQUISITIONS of goods and related services excluding any VAT for other EC MEMBER STATES:,9,0\n";
                    
                    print "\n";
                    
                    print "Purchases,$txt_st,$txt_en\n";
                    print "\n";

                    // get purchases
                    $sql = "SELECT p.txtype, p.dephnaref, p.year, p.month, p.day, p.net, p.vat, p.total, s.suppname
                            FROM purchases AS p
                            LEFT JOIN suppliers AS s ON s.ID = p.suppid
                            WHERE CONCAT(p.year,\"-\",p.month) >= '$sy-$sm'
                              AND CONCAT(p.year,\"-\",p.month) <= '$ey-$em'
                              AND (txtype = 'I' OR txtype = 'C')
                            ORDER BY p.year, p.month, p.day, p.dephnaref
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                        print "Date,Type,Ref,Supplier,Net,VAT,Total\n";

						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"p"=>"Payment (p)",
							"R"=>"Receipt",
							"r"=>"Receipt (p)",
							"X"=>"Unknown"
                        );
                        
                        while ($row = $result->fetch_assoc()) {
                            $txtype    = $row['txtype'];
                            $invnumber = $row['dephnaref'];
                            $year      = $row['year'];
                            $month     = $row['month'];
                            $day       = $row['day'];
                            $net       = $row['net'];
                            $vat       = $row['vat'];
                            $tot       = $row['total'];
                            $compname  = $row['suppname'];

                            if ($tot == 0) {
                                $compname = "Cancelled";
                            }

                            $totnet += $net;
                            $totvat += $vat;
                            $tottot += $tot;

                            if (strlen($compname) > 25) { $compname = substr($compname, 0, 25); }

                            print "$day-$month-$year,{$types[$txtype]},$invnumber,$compname,";
							print number_format($net,2,'.','').",";
                            print number_format($vat,2,'.','').",";
                            print number_format($tot,2,'.','')."\n";
                            
                        }
                        
                        print ",,,,";
                        print number_format($totnet,2,'.','').",";
                        print number_format($totvat,2,'.','').",";
                        print number_format($tottot,2,'.','')."\n";

                    }

                    print "\n";

                    print "Sales,$txt_st,$txt_en\n";
                    print "\n";

                    // get sales vat and net
                    $sql = "SELECT h.txtype, h.invnumber, h.year, h.month, h.day, h.custref, c.compname,
                                SUM(l.linenet) AS net,
                                SUM(l.vattotal) AS vat,
                                SUM(l.linetotal) AS tot
                            FROM inv_lines AS l
                            LEFT JOIN inv_headers AS h ON l.hid = h.ID
                            LEFT JOIN users AS c ON c.custref = h.custref
                            WHERE CONCAT(h.year,\"-\",h.month) >= '$sy-$sm'
                              AND CONCAT(h.year,\"-\",h.month) <= '$ey-$em'
                              AND (h.txtype = 'I' OR h.txtype = 'C')
                              AND l.unittype != 'Deposit'
                              AND h.custref != '20199'
                              AND h.custref != '20720'
                            GROUP BY h.invnumber
                            ORDER BY h.year, h.month, h.day, h.invnumber
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "Date,";
						print "Type,";
                        print "Ref,";
                        print "Customer,";
						print "Net,";
                        print "VAT,";
                        print "Total\n";

						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"p"=>"Payment (p)",
							"R"=>"Receipt",
							"r"=>"Receipt (p)",
							"X"=>"Unknown"
                        );
                        
                        while ($row = $result->fetch_assoc()) {
                            $txtype    = $row['txtype'];
                            $invnumber = $row['invnumber'];
                            $year      = $row['year'];
                            $month     = $row['month'];
                            $day       = $row['day'];
                            $net       = $row['net'];
                            $vat       = $row['vat'];
                            $tot       = $row['tot'];
                            $compname  = $row['compname'];

                            if ($tot == 0) {
                                $compname = "Cancelled";
                            }

                            $totnet += $net;
                            $totvat += $vat;
                            $tottot += $tot;

                            if (strlen($compname) > 25) { $compname = substr($compname, 0, 25); }

                            print "$day-$month-$year,";
                            print "{$types[$txtype]},";
                            print "$invnumber,";
                            print "$compname,";
							print number_format($net,2,'.','').",";
                            print number_format($vat,2,'.','').",";
                            print number_format($tot,2,'.','')."\n";
                            
                        }

                        print ",,,,";
                        print number_format($totnet,2,'.','').",";
                        print number_format($totvat,2,'.','').",";
                        print number_format($tottot,2,'.','')."\n";

                    }
                }
                $dbcnx->close();
            }
        }
	}
}
?>
