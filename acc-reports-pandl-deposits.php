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
		<title>Dephna - Profit And Loss Turnover</title>
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

                if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
                if ($_REQUEST['to']) { $to = $_REQUEST['to']; }

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

                    $notes = str_replace('\n',"<br>",$notes);

                    // add start day and end day
                    if ($fr) { $period_start = $fr; } else { $period_start = $st; }
                    if ($to) { $period_end   = $to; } else { $period_end   = $en; }
                    list($sy,$sm,$sd) = explode("-",$period_start);
                    list($ey,$em,$ed) = explode("-",$period_end);
                    $txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
                    $txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
                    
                    print "<p align=\"center\"><span class=\"larger\">P&amp;L Deposits</span><br>$txt_st to $txt_en</p>\n";

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
				    print ".r0 { background-color:#fff }\n";
				    print ".r1 { background-color:#ddd }\n";
                    print "</style>\n";

                    // get net turnover from sales ledger excluding deposits
                    $sql = "SELECT SUM(l.linenet) AS net, SUM(l.vattotal) AS vat ,SUM(l.linetotal) AS total,
                                h.txtype, h.invnumber, h.year, h.month, h.day, u.compname
                            FROM inv_headers AS h
                            LEFT JOIN inv_lines AS l ON l.hid = h.ID
                            LEFT JOIN users AS u ON u.custref = h.custref
                            WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
                            AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
                            AND (h.txtype = 'I' OR h.txtype = 'C')
                            AND l.unittype = 'Deposit'
                            GROUP BY l.hid
                            ORDER BY h.year, h.month, h.day
                    ";

                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                        $txtypes = array("I"=>"Invoice","C"=>"Credit Note");

                        print "<table width=\"90%\" class=\"table1\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Net</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Vat</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
                        if (!$print) {
                            print "	<td width=\"20\" style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                        }
                        print "</tr>\n";
                
                        while ($row = $result->fetch_assoc()) {

                            $invnum   = $row['invnumber'];
                            $txtype   = $row['txtype'];
                            $compname = $row['compname'];
                            $year     = $row['year'];
                            $month    = $row['month'];
                            $day      = $row['day'];
                            $net      = $row['net'];
                            $vat      = $row['vat'];
                            $total    = $row['total'];

                            if (strlen($compname) > 28) { $compname = substr($compname, 0, 28); }

                            $totnet += $net;
                            $totvat += $vat;
                            $tottot += $total;

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$day-$month-$year</td>\n";
                            print "	<td>{$txtypes[$txtype]}</td>\n";
                            print "	<td>$invnum</td>\n";
                            print "	<td>$compname</td>\n";
                            print "	<td align=\"right\">".number_format($net,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($vat,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($total,2)."</td>\n";
                            if (!$print) {
                                print "	<td align=\"center\"><a href=\"customer-pdf-invoice.php?inv=$invnum\" target=\"_blank\">Pdf</a></td>\n";
                            }
                            print "</tr>\n";

                        }

                        $numrows = $result->num_rows;
                        print "<tr>\n";
                        print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
                        print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totnet,2)."</b></td>\n";
                        print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totvat,2)."</b></td>\n";
                        print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
                        if (!$print) {
                            print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                        }
				        print "</tr>\n";
				        print "</table>\n";
                        
                    } else {
                        $resultMessage = "<b>No Turnover</b><br>" . $dbcnx->error;
                    }

                    if (!$print) {
                        print "<p align=\"center\"><a href=\"acc-reports-pandl-deposits.php?id=$id&amp;fr=$fr&amp;to=$to&amp;print=1\" target=\"_blank\">Print</a></p>"; 
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
    print "<p align=\"center\"><a href=\"acc-reports-pandl.php?id=$id&amp;fr=$fr&amp;to=$to\">Return</a></p>\n";
    print "</td>\n";
    include('footer.php');
}
?>
