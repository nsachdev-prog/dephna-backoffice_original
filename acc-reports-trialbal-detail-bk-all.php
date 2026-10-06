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
		<title>Dephna - Trial Balance Detail</title>
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
                    
                    print "<p align=\"center\"><span class=\"larger\">TB Detail Statement Only Items</span><br>$txt_st to $txt_en</p>\n";

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

                    // get bank dr
                    $sql = "SELECT b.txtype, b.amount, b.dephnaref, b.bankref, b.details, b.year, b.month, b.day,
                                c.title AS cctitle
                            FROM bank AS b
                            LEFT JOIN costcentres AS c ON c.ID = b.costcentre
                            WHERE CONCAT(b.year,\"-\",b.month,\"-\",b.day) >= '$period_start'
                            AND   CONCAT(b.year,\"-\",b.month,\"-\",b.day) <= '$period_end'
                            AND c.ccgroup = '3'
                            ORDER BY b.year, b.month, b.day
                    ";

                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                        $txtypes = array(
                            "DR"=>"Payment",
                            "dr"=>"Payment (p)",
                            "CR"=>"Receipt",
                            "dr"=>"Receipt (p)"
                        );

                        print "<table width=\"90%\" class=\"table1\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Cost Centre</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Bank Ref</b></td>\n";
                        // print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>DR</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>CR</b></td>\n";
                        print "</tr>\n";
                
                        while ($row = $result->fetch_assoc()) {

                            $invnum   = $row['dephnaref'];
                            $txtype   = $row['txtype'];
                            $cctitle  = $row['cctitle'];
                            $year     = $row['year'];
                            $month    = $row['month'];
                            $day      = $row['day'];
                            $amount   = $row['amount'];
                            $bankref  = $row['bankref'];
                            $details  = $row['details'];
                            
                            if ($txtype == "DR") {
                                $totdr += $amount;
                            } elseif ($txtype == "CR") {
                                $totcr += $amount;
                            }

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$day-$month-$year</td>\n";
                            print "	<td>{$txtypes[$txtype]}</td>\n";
                            print "	<td>$invnum</td>\n";
                            print "	<td>$cctitle</td>\n";
                            print "	<td>$bankref</td>\n";
                            // print "	<td>$details</td>\n";
                            if ($txtype == "DR") {
                                print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
                                print "	<td>&nbsp;</td>\n";
                            } elseif ($txtype == "CR") {
                                print "	<td>&nbsp;</td>\n";
                                print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
                            }
                            print "</tr>\n";

                        }

                        print "<tr>\n";
                        print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                        print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totdr,2)."</b></td>\n";
                        print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totcr,2)."</b></td>\n";
				        print "</tr>\n";
				        print "</table>\n";
                        
                    } else {
                        $resultMessage = "<b>Could not get details</b><br>" . $dbcnx->error;
                    }

                    if (!$print) {
                        print "<p align=\"center\"><a href=\"acc-reports-trialbal-detail-bk-all.php?id=$id&amp;fr=$fr&amp;to=$to&amp;print=1\" target=\"_blank\">Print</a></p>"; 
                    }

                } else {
                    $resultMessage = "<b>Could Not Get Period</b><br>" . $dbcnx->error;
                }
                
                $dbcnx->close();

            } else {
                $resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "<b>Page Called Without ID or CCID</b>";
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
    print "<p align=\"center\"><a href=\"acc-reports-trialbal.php?id=$id&amp;fr=$fr&amp;to=$to\">Return</a></p>\n";
    print "</td>\n";
    include('footer.php');
}
?>
