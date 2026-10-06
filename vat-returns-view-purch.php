<?php
$page = "VAT";
$subpage = "View Purchases";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
    ?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - VAT - View Purchases</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
    <style>
        table { font-size:0.9em; }
    </style>
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
                    print "<style> { table font-size:0.8em; } </style>\n";
                } else {
                    print "<p align=\"center\" class=\"heading\">VAT</p>\n";
                }

                $sql = "SELECT * FROM vat_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];

                    $period_start = $period_start . "-01";
                    $period_end   = $period_end   . "-01";
                    $ed = date("t",strtotime($period_end));
                    list($sy,$sm,$sd)   = explode("-",$period_start);
                    list($ey,$em,$junk) = explode("-",$period_end);
                    $txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
                    $txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));

                    print "<p align=\"center\"><span class=\"larger\">Purchases</span><br>$txt_st to $txt_en</p>\n";

                    $from = "";

					// See if Final figures exist
					$sql = "SELECT h.*, s.suppname
							FROM VAT_PL_Final AS h
							LEFT JOIN suppliers AS s ON s.ID = h.suppid
							WHERE CONCAT(h.year,\"-\",h.month) >= '$sy-$sm'
							  AND CONCAT(h.year,\"-\",h.month) <= '$ey-$em'
							ORDER BY h.year, h.month, h.day, h.invnumber
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$from = "Final";
					} else {
						// get from current
                        $sql = "SELECT p.txtype, p.dephnaref, p.year, p.month, p.day, p.net, p.vat, p.total AS tot, s.suppname
                                FROM purchases AS p
                                LEFT JOIN suppliers AS s ON s.ID = p.suppid
                                WHERE CONCAT(p.year,\"-\",p.month) >= '$sy-$sm'
                                AND CONCAT(p.year,\"-\",p.month) <= '$ey-$em'
                                AND (txtype = 'I' OR txtype = 'C')
                                ORDER BY p.year, p.month, p.day, p.dephnaref
                        ";
                        if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                            $from = "Current";
						}
                    }

                    if ($from) {

                        print "<p align=\"center\"><span class=\"larger\">$from</p>\n";

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

                        // row background colours
                        print "<style>\n";
                        print ".r0 { background-color:#fff }\n";
                        print ".r1 { background-color:#ddd }\n";
                        print "</style>\n";

                        $types = array(
                            "I"=>"Invoice",
                            "C"=>"Credit Note"
                        );
                    
                        while ($row = $result->fetch_assoc()) {
                            $txtype    = $row['txtype'];
                            $invnumber = $row['dephnaref'];
                            $year      = $row['year'];
                            $month     = $row['month'];
                            $day       = $row['day'];
                            $net       = $row['net'];
                            $vat       = $row['vat'];
                            $tot       = $row['tot'];
                            $suppname  = $row['suppname'];

                            if ($tot == 0) {
                                $suppname = "Cancelled";
                            }

                            $totnet += $net;
                            $totvat += $vat;
                            $tottot += $tot;

                            if (strlen($compname) > 25) { $compname = substr($compname, 0, 25); }

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$day-$month-$year</td>\n";
                            print " <td>{$types[$txtype]}</td>\n";
                            print "	<td>$invnumber</td>\n";
                            print "	<td>$suppname</td>\n";
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

                    } else {
						$resultMessage .= "<b>No Transactions Found</b><br />" . $dbcnx->error;
					}

                    if (!$print) { 
                        print "<p align=\"center\"><a href=\"vat-returns-view-purch.php?id=$id&amp;print=1\" target=\"_blank\">Print</a></p>\n";
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
    print "<p align=\"center\"><a href=\"vat-returns-view.php?id=$id\">Return</a></p>\n";
    print "</td>\n";
    include('footer.php');
}
?>
