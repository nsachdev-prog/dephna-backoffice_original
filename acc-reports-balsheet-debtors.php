<?php
$page = "Accounts";
$subpage = "Balance Sheet Debtors";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
    ?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Balance Sheet Debtors</title>
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
                    $tangible_assets = $row['tangible_assets'];
                    $share_capital   = $row['share_capital'];
                    $bank_balance    = $row['bank_balance'];
                    $notes           = $row['notes'];

                    $notes = str_replace('\n',"<br>",$notes);

                    // add start day and end day
                    list($sy,$sm,$sd) = explode("-",$period_start);
                    list($ey,$em,$ed) = explode("-",$period_end);
                    $txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
                    $txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
                    $bank_period_end = "$ey-$em";

                    print "<p align=\"center\"><span class=\"larger\">Balance Sheet Debtors</span><br>$txt_st to $txt_en</p>\n";

                    // get outstanding debtors from sales ledger excluding deposits
                    $sql = "SELECT h.invtotal, h.outstanding, h.txtype, h.invnumber, h.year, h.month, h.day, h.custref
                            FROM inv_headers AS h
                            RIGHT JOIN inv_lines AS l ON l.hid = h.ID
                            WHERE (h.txtype = 'I' OR h.txtype = 'C')
                            AND h.outstanding <> '0'
                            AND l.unittype != 'Deposit'
                            GROUP BY h.invnumber
                            ORDER BY h.year, h.month, h.day
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                        $txtypes = array("I" => "Invoice", "C" => "Credit Note");
                        
                        // styling
                        print "<style>\n";
                        if ($print) {
                            print ".table1 { width:90%; font-size:1.4em }\n";
                        } else {
                            print ".table1 { width:90%; font-size:1.1em }\n";
                        }
                        print ".blank { height:0px }\n";
                        print ".line { border-bottom:1px solid #999999; height:0px }\n";
                        print ".doubleline { border-top:1px solid #999999; border-bottom:1px solid #999999; height:0px }\n";
                        print "</style>\n";

                        // row background colours
                        print "<style>\n";
                        print ".r0 { background-color:#fff }\n";
                        print ".r1 { background-color:#ddd }\n";
                        print "</style>\n";

                        print "<table align=\"center\" class=\"table1\" cellspacing=\"0\" cellpadding=\"2px\">\n";

                        print "<tr>\n";
                        print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</td>\n";
                        print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
                        print "</tr>\n";

                        while ($row = $result->fetch_assoc()) {
                            
                            $txtype    = $row['txtype'];
                            $invnumber = $row['invnumber'];
                            $year      = $row['year'];
                            $month     = $row['month'];
                            $day       = $row['day'];
                            $custref   = $row['custref'];
                            $outstanding = $row['outstanding'];
                            $invtotal  = $row['invtotal'];

                            $debtors += $outstanding;

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "<td>$day-$month-$year</td>\n";
                            print "<td>{$txtypes[$txtype]}</td>\n";
                            print "<td>$invnumber</td>\n";
                            print "<td>DP$custref</td>\n";
                            print "<td align=\"right\">".number_format($invtotal,2)."</td>\n";
                            print "<td align=\"right\">".number_format($outstanding,2)."</td>\n";
                            print "</tr>\n";

                        }

                        print "<tr>\n";
                        print "<td style=\"border-top:1px solid #999999\" colspan=\"6\" align=\"right\">".number_format($debtors,2)."</td>\n";
                        print "</tr>\n";


                        print "</table>\n";

                        if (!$print) {
                            print "<p align=\"center\"><a href=\"acc-reports-balsheet.php?id=$id&amp;print=1\" target=\"_blank\">Print</a></p>";
                            
                        }

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
    print "<p align=\"center\"><a href=\"acc-periods-list.php\">Periods</a></p>\n";
    print "</td>\n";
    include('footer.php');
}
?>
