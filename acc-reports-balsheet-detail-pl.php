<?php
$page = "Accounts";
$subpage = "Balance Sheet";
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

                // get period dates and manual figures
                $sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start    = $row['period_start'];
                    $period_end      = $row['period_end'];

                    // add start day and end day
                    list($sy,$sm,$sd) = explode("-",$period_start);
                    list($ey,$em,$ed) = explode("-",$period_end);
                    $txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
                    $txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
                    $bank_period_end = "$ey-$em";

                    print "<p align=\"center\"><span class=\"larger\">Balance Sheet - Creditors Outstanding</span><br>$txt_en</p>\n";

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

                    $txtypes = array("I"=>"Invoice","C"=>"Credit Note","P"=>"Payment","R"=>"Receipt");

                    $current_year = date("Y");
                    if ($current_year > $sy) {
                        $table = "{$ey}_purchases";
                    } else {
                        $table = "purchases";
                    }

                    // get creditors
                    $sql = "SELECT s.suppname, h.txtype, h.year, h.month, h.day, h.dephnaref, h.total, h.outstanding
                            FROM $table AS h
                            LEFT JOIN suppliers AS s ON s.ID = h.suppid
                            WHERE h.outstanding <> '0.00'
                            ORDER BY h.year DESC, h.month DESC, h.day DESC
                    ";

                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                        print "<table width=\"90%\" class=\"table1\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
                        print "</tr>\n";
                
                        while ($row = $result->fetch_assoc()) {

                            $invnum   = $row['dephnaref'];
                            $txtype   = $row['txtype'];
                            $compname = $row['suppname'];
                            $year     = $row['year'];
                            $month    = $row['month'];
                            $day      = $row['day'];
                            $invtotal = $row['total'];
                            $outstanding = $row['outstanding'];

                            if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }

                            $tottot += $outstanding;

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$day-$month-$year</td>\n";
                            print "	<td>{$txtypes[$txtype]}</td>\n";
                            print "	<td>$invnum</td>\n";
                            print "	<td>$compname</td>\n";
                            print "	<td align=\"right\">".number_format($invtotal,2)."</td>\n";
                            print "	<td align=\"right\">".number_format($outstanding,2)."</td>\n";
                            print "</tr>\n";

                        }

                        print "<tr>\n";
                        print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                        print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($tottot,2)."</b></td>\n";
				        print "</tr>\n";
				        print "</table>\n";
                        
                    } else {
                        $resultMessage = "<b>Could not get details</b><br>" . $dbcnx->error;
                    }

                    if (!$print) {
                        print "<p align=\"center\"><a href=\"acc-reports-balsheet-detail-pl.php?id=$id&amp;print=1\" target=\"_blank\">Print</a></p>";
                        print "<p align=\"center\"><a href=\"acc-reports-balsheet-detail-pl-dnl.php?id=$id\" target=\"_blank\">Download CSV</a></p>"; 
                    }

                } else {
                    $resultMessage = "<b>Could Not Get Period</b><br>" . $dbcnx->error;
                }
                
                $dbcnx->close();

            } else {
                $resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "<b>Page Called Without ID or Location</b>";
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
    print "<p align=\"center\"><a href=\"acc-reports-balsheet.php?id=$id\">Return</a></p>\n";
    print "</td>\n";
    include('footer.php');
}
?>
