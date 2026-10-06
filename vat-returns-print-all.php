<?php
$page = "VAT";
$subpage = "View";
include('settings.php');
include('session.php');

?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - VAT - View</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
    <body style="background:#fff">
<?php

if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        $id = $_REQUEST['id'];

        if ($id) {

            // open database
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                print "<p align=\"center\"><span class=\"heading\">$compName</span><br>$compAddr</p>\n";

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
                            print $dbcnx->error;
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
                            print $dbcnx->error;
                        }

                    }

                    $due = $sl_vat - $pl_vat;

                    // styling
                    print "<style>\n";
                    print ".vat-td { border:1px solid #999999; }\n";
                    print ".vat-spacer { height:1px; }\n";
                    print "</style>\n";

                    print "<p align=\"center\"><span class=\"larger\">Value Added Tax Return</span><br>$txt_st to $txt_en</p>\n";

                    print "<table width=\"92%\" align=\"center\" cellspacing=\"2px\" cellpadding=\"4px\">\n";
                    
                    print "<tr>\n";
                    print "<td class=\"vat-td\">VAT due this period on SALES and other outputs:<br>&nbsp;</td>\n";
                    print "<td class=\"vat-td\">1</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">".number_format($sl_vat,2)."</td>\n";
                    print "</tr>\n";
                    
                    print "<tr>\n";
                    print "<td class=\"vat-td\">VAT due this period on AQUISITIONS form other EC<br>MEMBER STATES:</td>\n";
                    print "<td class=\"vat-td\">2</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">&nbsp;</td>\n";
                    print "</tr>\n";
                    
                    print "<tr>\n";
                    print "<td class=\"vat-td\">Total VAT due (THE SUM OF BOXES 1 AND 2):<br>&nbsp;</td>\n";
                    print "<td class=\"vat-td\">3</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">".number_format($sl_vat,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-td\">VAT recailmed in this period on PURCHASES and other<br>inputs (including aquisistions from the EC):</td>\n";
                    print "<td class=\"vat-td\">4</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">".number_format($pl_vat,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-td\"><b>Net VAT to be paid to Customs or reclaimed by<br>you (DIFFERENCE BETWEEN BOXES 3 AND 4):</b></td>\n";
                    print "<td class=\"vat-td\"><b>5</b></td>\n";
                    print "<td class=\"vat-td\" align=\"right\"><b>".number_format($due,2)."</b></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-spacer\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-td\">Total value of SALES and all other outputs excluding any<br>VAT. INCLUDE YOUR BOX 8 FIGURE:</td>\n";
                    print "<td class=\"vat-td\">6</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">".number_format($sl_net,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-td\">Total value of PURCHASES and all other inputs excluding<br>any VAT. INCLUDE YOUR BOX 9 FIGURE:</td>\n";
                    print "<td class=\"vat-td\">7</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">".number_format($pl_net,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-td\">Total value of all SUPPLIES of goods and related services,<br>excluding any VAT to other EC MEMBER STATES:</td>\n";
                    print "<td class=\"vat-td\">8</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td class=\"vat-td\">Total value of all AQUISITIONS of goods and related<br>services, excluding any VAT for other EC MEMBER STATES:</td>\n";
                    print "<td class=\"vat-td\">9</td>\n";
                    print "<td class=\"vat-td\" align=\"right\">&nbsp;</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    print "<p>&nbsp;</p>";
                    print "<p align=\"center\"><span class=\"larger\">Purchases</span><br>$txt_st to $txt_en</p>\n";

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

                        print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Net</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>VAT</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
                        print "</tr>\n";                        

                        # row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

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

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$day-$month-$year</td>\n";
                            print " <td>{$types[$txtype]}</td>\n";
                            print "	<td>$invnumber</td>\n";
                            print "	<td>$compname</td>\n";
							print "	<td align=\"right\">".number_format($net,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($tot,2)."</td>\n";
                            print "</tr>\n";
                        }

                        print "<tr>\n";
                        print "	<td style=\"border-top:1px solid #999999\" colspan=\"5\" align=\"right\"><b>".number_format($totnet,2)."</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totvat,2)."</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";

                    }

                    print "<p>&nbsp;</p>";
                    print "<p align=\"center\"><span class=\"larger\">Sales</span><br>$txt_st to $txt_en</p>\n";

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

                        print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Net</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>VAT</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
                        print "</tr>\n";                        

                        # row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

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

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$day-$month-$year</td>\n";
                            print " <td>{$types[$txtype]}</td>\n";
                            print "	<td>$invnumber</td>\n";
                            print "	<td>$compname</td>\n";
							print "	<td align=\"right\">".number_format($net,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($tot,2)."</td>\n";
                            print "</tr>\n";
                        }

                        print "<tr>\n";
                        print "	<td style=\"border-top:1px solid #999999\" colspan=\"5\" align=\"right\"><b>".number_format($totnet,2)."</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totvat,2)."</b></td>\n";
                        print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";

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

?>
</body>
</html>
