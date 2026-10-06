<?php
$page = "Accounts";
$subpage = "Fixed Running Costs";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
    ?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Fixed Running Costs</title>
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

                if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
                if ($_REQUEST['to']) { $to = $_REQUEST['to']; }
                if ($fr AND $to) { if ($to < $fr) { $to = ""; } }

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
                    $st  = $row['period_start'];
                    $en  = $row['period_end'];
                    $cos_published   = $row['cos_published'];

                    $notes = str_replace('\n',"<br>",$notes);

                    // add start day and end day
                    if ($fr) { $period_start = $fr; } else { $period_start = $st; }
                    if ($to) { $period_end   = $to; } else { $period_end   = $en; }
                    list($sy,$sm,$sd) = explode("-",$period_start);
                    list($ey,$em,$ed) = explode("-",$period_end);
                    $txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
                    $txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
                    $bank_period_end = "$ey-$em";

                    print "<p align=\"center\"><span class=\"larger\">Fixed Running Costs</span><br>$txt_st to $txt_en</p>\n";

                    if (!$print) {
                        print "<form name=\"addform\" method=\"post\" action=\"acc-reports-fixed.php?id=$id\">\n";
                        print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>From:</b></td>\n";
                        print "	<td><input type=\"text\" id=\"datepickerFr\" name=\"fr\" value=\"$fr\" class=\"element_smaller\"></td>\n";
                        print "	<td align=\"right\"><b>To:</b></td>\n";
                        print "	<td><input type=\"text\" id=\"datepickerTo\" name=\"to\" value=\"$to\" class=\"element_smaller\"></td>\n";
                        print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
                        print " <td><a href=\"acc-reports-pandl.php?id=$id\">Reset</a></td>\n";
                        print "</tr>";
                        print "</form>\n";
                    }

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
                    print "</style>\n";

                    print "<table align=\"center\" width=\"60%\" class=\"table1\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

                    print "<tr>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\"><b>Debit</b></td>\n";
                    print "<td align=\"right\"><b>Credit</b></td>\n";
                    print "<td align=\"right\"><b>Credit</b></td>\n";
                    print "</tr>\n";

                    // get turnover
                    $sql = "SELECT SUM(l.linenet) AS total
                            FROM inv_lines AS l
                            LEFT JOIN inv_headers AS h ON l.hid = h.ID
                            WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
                            AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
                            AND (h.txtype = 'I' OR h.txtype = 'C')
                            AND l.unittype != 'Deposit'
                    ";

                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $turnover = $row['total'];
                    }

                    // get sales utilities
                    $sql = "SELECT SUM(l.linenet) AS utilities
                            FROM inv_lines AS l
                            LEFT JOIN inv_headers AS h ON l.hid = h.ID
                            WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
                            AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
                            AND (h.txtype = 'I' OR h.txtype = 'C')
                            AND l.unittype = 'Utilities'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $utilites_sales = $row['utilities'];
                    }

                    // get sales fitouts
                    $sql = "SELECT SUM(l.linenet) AS fitouts
                            FROM inv_lines AS l
                            LEFT JOIN inv_headers AS h ON l.hid = h.ID
                            WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
                            AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
                            AND (h.txtype = 'I' OR h.txtype = 'C')
                            AND l.unittype = 'Fitout/Sundry'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $fitouts_sales = $row['fitouts'];
                    }

                    $fitouts_purch = $fitouts_sales / 4;
                    $cos_actual += $fitouts_purch;

                    $licence_fees = $turnover - $utilites_sales - $fitouts_sales;

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td><b>Turnover</b></td>\n";
                    print "<td align=\"right\"><b>".number_format($turnover,2)."</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Licence Fees</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-sl.php?id=$id&amp;ut=Licence&amp;fr=$fr&amp;to=$to\">Licence Fees</a></td>\n";
                    }
                    print "<td align=\"right\">".number_format($licence_fees,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Utilities</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-sl.php?id=$id&amp;ut=Utilities&amp;fr=$fr&amp;to=$to\">Utilities</a></td>\n";
                    }
                    print "<td align=\"right\">".number_format($utilites_sales,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Fitouts</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-sl.php?id=$id&amp;ut=Fitout/Sundry&amp;fr=$fr&amp;to=$to\">Fitouts</a></td>\n";
                    }
                    print "<td align=\"right\">".number_format($fitouts_sales,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td><b>Cost of Sales (Published)</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\"><b>".number_format($cos_published,2)."</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    // get rent paid
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND (l.ccid = '19'
                                OR l.ccid = '34'
                                OR l.ccid = '49'
                                OR l.ccid = '50'
                                OR l.ccid = '51'
                                OR l.ccid = '52'
                                OR l.ccid = '53')
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $rent_paid = $row['total'];
                        $cos_actual += $rent_paid;
                    } else {
                        print $dbcnx->error;
                    }

                    // get business rates
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '41'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $business_rates = $row['total'];
                        $cos_actual += $business_rates;
                        $direct_costs += $business_rates;
                    } else {
                        print $dbcnx->error;
                    }

                    // get refuge and waste
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '6'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $refuge_waste = $row['total'];
                        $cos_actual += $refuge_waste;
                        $direct_costs += $refuge_waste;
                    } else {
                        print $dbcnx->error;
                    }

                    // get pest control
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '5'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $pest_control = $row['total'];
                        $cos_actual += $pest_control;
                        $direct_costs += $pest_control;
                    } else {
                        print $dbcnx->error;
                    }

                    // get cleaning
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '4'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $cleaning = $row['total'];
                        $cos_actual += $cleaning;
                        $direct_costs += $cleaning;
                    } else {
                        print $dbcnx->error;
                    }

                    // get IT & Telecoms
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '20'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $telecoms = $row['total'];
                        $cos_actual += $telecoms;
                        $direct_costs += $telecoms;
                    } else {
                        print $dbcnx->error;
                    }

                    // get building upkeep
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '63'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $building_upkeep = $row['total'];
                        $cos_actual += $building_upkeep;
                        $direct_costs += $building_upkeep;
                    } else {
                        print $dbcnx->error;
                    }

                    // get utilities purchases
                    $sql = "SELECT SUM(l.net) AS total
                            FROM purchases_lines AS l
                            LEFT JOIN purchases AS p ON p.ID = l.hid
                            WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
                            AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
                            AND (p.txtype = 'I' OR p.txtype = 'C')
                            AND l.ccid = '24'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $utilites_purch = $row['total'];
                        $cos_actual += $utilites_purch;
                    } else {
                        print $dbcnx->error;
                    }
                    
                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td><b>Cost of Sales (Actual)</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\"><b>".number_format($cos_actual,2)."</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Rent Paid</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Rent&amp;fr=$fr&amp;to=$to\">Rent Paid</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($rent_paid,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td>Direct Costs</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($direct_costs,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Business Rates</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Rates&amp;fr=$fr&amp;to=$to\">Business Rates</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($business_rates,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Refuge &amp; Waste</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Refuge&amp;fr=$fr&amp;to=$to\">Refuge &amp; Waste</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($refuge_waste,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Pest Control</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Pest&amp;fr=$fr&amp;to=$to\">Pest Control</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($pest_control,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Cleaning</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Cleaning&amp;fr=$fr&amp;to=$to\">Cleaning</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($cleaning,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>IT &amp; Telecoms</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Telecoms&amp;fr=$fr&amp;to=$to\">IT &amp; Telecoms</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($telecoms,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Building Upkeep</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Building&amp;fr=$fr&amp;to=$to\">Building Upkeep</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($building_upkeep,2)."</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    if ($print) {
                        print "<td>Utilities</td>\n";
                    } else {
                        print "<td><a href=\"acc-reports-fixed-pl.php?id=$id&amp;ut=Utilities&amp;fr=$fr&amp;to=$to\">Utilities</a></td>\n";
                    }
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($utilites_purch,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td>Fitouts</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\">".number_format($fitouts_purch,2)."</td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    $gross_profit = $turnover - $cos_actual;

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td><b>Gross Profit</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\"><b>".number_format($gross_profit,2)."</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";

                    $one_off_costs = $cos_published - $cos_actual;

                    print "<tr>\n";
                    print "<td colspan=\"3\" class=\"blank\"></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td><b>One Off Costs</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "<td align=\"right\"><b>".number_format($one_off_costs,2)."</b></td>\n";
                    print "<td>&nbsp;</td>\n";
                    print "</tr>\n";



                    print "</table>\n";

                    print "<p>&nbsp;</p>\n";

                    if (!$print) {
                        print "<p align=\"center\"><a href=\"acc-reports-fixed.php?id=$id&amp;fr=$fr&amp;to=$to&amp;print=1\" target=\"_blank\">Print</a></p>";
                        print "<script type=\"text/javascript\">\n";
                        print "	$( document ).ready(function() {\n";
                        print "		$( \"#datepickerFr\" ).datepicker({dateFormat: 'yy-mm-dd', minDate: new Date('$st')});\n";
                        print "		$( \"#datepickerTo\" ).datepicker({dateFormat: 'yy-mm-dd', maxDate: new Date('$en')});\n";
                        print "	});\n";
                        print "</script>\n";
                    }

                    if (!$print) {
                        print "<p align=\"center\"><a href=\"pdf/Dephna-Fixed-Running-Costs-Previous.pdf\" target=\"_blank\">Previous Years</a></p>"; 
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
