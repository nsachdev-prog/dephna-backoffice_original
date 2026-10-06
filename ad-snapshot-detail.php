<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Snapshot";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Debtors Snapshot</title>
		<link href="style.css" rel="stylesheet" type="text/css" />
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

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\">AGED DEBTORS</p>\n";

            if (!$print) {
                include('ad-submenu.php');
			    print "<p align=\"center\">$menustr</p>\n";
            } else {
                print "<p align=\"center\" class=\"larger\">" . date("d-m-Y") . "</p>\n";
            }

            print "<p align=\"center\" class=\"larger\"><b>Snapshot</b></p>\n";

            $fourteenDaysAgo = date('Y-m-d', strtotime('-14 days'));
            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

            $sql = "SELECT SUM(h.outstanding) AS outstanding, h.custref, c.compname AS customer, c.tim, c.ad_managed, c.terminationWarning, c.terminationNotice, c.ID AS id, c.ad_dd_set,
                            (SELECT COUNT(*) FROM pplans_headers WHERE custref = h.custref AND `status` = '1') AS pplan
                    FROM inv_headers AS h
                    JOIN users AS c ON h.custref = c.custref
                    GROUP BY h.custref
                    HAVING outstanding <> 0
                    ORDER BY c.tim DESC, pplan DESC, outstanding DESC
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                print "<table width=\"98%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Over 30 Days</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Over 14 Days</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>DD</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>PPlan</b></td>\n";
                print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Mngd</b></td>\n";
                print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Rstd</b></td>\n";
                print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Warning</b></td>\n";
                print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Notice</b></td>\n";
                print "</tr>\n";
                $pplans = 0;
                $totover30Days = 0;
                $totover30DaysQty = 0;
                $tot14Days = 0;
                $tot14DaysQty = 0;
                $tottim = 0;
                $totad_managed = 0;
                $totOutstanding = 0;
                $totOutstandingQty = 0;
                $totterminationWarning = 0;
                $totterminationNotice = 0;
                $ad_dd_setQty = 0;
                while ($row = $result->fetch_assoc()) {
                    $creditControl = 0;
                    $id = $row['id'];
                    $custref     = $row['custref'];
                    $customer    = $row['customer'];
                    $outstanding = $row['outstanding'];
                    $tim         = $row['tim'];
                    $ad_managed  = $row['ad_managed'];
                    $terminationWarning = $row['terminationWarning'];
                    $terminationNotice  = $row['terminationNotice'];
                    $ad_dd_set   = $row['ad_dd_set'];
                    $pplan       = $row['pplan'];
                    if (strlen($customer) > 20) { $customer = substr($customer, 0, 20); }
                    if ($tim != "Yes") { $tim = "&nbsp;"; } else { $tottim++; $creditControl++; }
                    if ($ad_managed == "No") { $ad_managed = "&nbsp;"; } else { $totad_managed++; $creditControl++; }
                    $totOutstanding += $outstanding;
                    $totOutstandingQty++;
                    if ($terminationWarning > "0") {
                        $totterminationWarning++;
                        $terminationWarning = date('d-m-Y', strtotime($terminationWarning));
                    }
                    if ($terminationNotice > "0") {
                        $totterminationNotice++;
                        $terminationNotice = date('d-m-Y', strtotime($terminationNotice));
                    }
                    if ($ad_dd_set == "Yes") {
                        $ad_dd_setQty++;
                        $creditControl++;
                    } else {
                        $ad_dd_set = "&nbsp;";
                    }
                    // do they have a payment plan?
                    if ($pplan) {
                        $paymentPlan = "Yes";
                        $creditControl++;
                        $pplans++;
                    } else {
                        $paymentPlan = "&nbsp;";
                    }
                    // over 30 days?
                    $sql = "SELECT SUM(outstanding) AS 30days
                            FROM inv_headers
                            WHERE custref = '$custref'
                             AND CONCAT(year, '-', month, '-', day) < '$thirtyDaysAgo'
                            HAVING SUM(outstanding) <> 0
                    ";
                    if ($qresult = $dbcnx->query($sql) AND $qresult->num_rows > 0) {
                        $qrow = $qresult->fetch_assoc();
                        $over30Days = $qrow['30days'];
                        $totover30Days += $over30Days;
                        $totover30DaysQty++;
                    } else {
                        $over30Days = 0;
                    }
                    // over 14 days?
                    $sql = "SELECT SUM(outstanding) AS 14days
                            FROM inv_headers
                            WHERE custref = '$custref'
                             AND CONCAT(year, '-', month, '-', day) < '$fourteenDaysAgo'
                             AND CONCAT(year, '-', month, '-', day) >= '$thirtyDaysAgo'
                            HAVING SUM(outstanding) <> 0
                    ";
                    if ($qresult = $dbcnx->query($sql) AND $qresult->num_rows > 0) {
                        $qrow = $qresult->fetch_assoc();
                        $over14Days = $qrow['14days'];
                        $tot14Days += $over14Days;
                        $tot14DaysQty++;
                    } else {
                        $over14Days = 0;
                    }

                    if (($creditControl == 0) AND ($outstanding > 0)) {
                        $customer = "<span style=\"color:red;\">$customer</a>";
                    }

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>DP$custref</td>\n";
                    if ($print) {
                        print "	<td>$customer</td>\n";
                    } else {
                        print "	<td><a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id=$id','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">$customer</a></td>\n";
                    }
                    print "	<td align=\"right\">" . number_format($outstanding, 2) . "</td>\n";
                    print "	<td align=\"right\">" . number_format($over30Days, 2) . "</td>\n";
                    print "	<td align=\"right\">" . number_format($over14Days, 2) . "</td>\n";
                    print "	<td align=\"right\">$ad_dd_set</td>\n";
                    print "	<td align=\"right\">$paymentPlan</td>\n";
                    print "	<td align=\"right\">$ad_managed</td>\n";
                    print "	<td align=\"right\">$tim</td>\n";
                    print "	<td align=\"right\">$terminationWarning</td>\n";
                    print "	<td align=\"right\">$terminationNotice</td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\"><b>Current</b></td>\n";
                print "	<td colspan=\"2\" style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($totOutstanding, 2) . " (" . $totOutstandingQty . ")</b></td>\n";
                print "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b>" . number_format($totover30Days, 2) . " (" . $totover30DaysQty . ")</b></td>\n";
                print "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b>" . number_format($tot14Days, 2) . " (" . $tot14DaysQty . ")</b></td>\n";
                print "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b>$ad_dd_setQty</b></td>\n";
                print " <td align=\"right\" style=\"border-top:1px solid #999999\"><b>$pplans</b></td>\n";
                print " <td align=\"right\" style=\"border-top:1px solid #999999\"><b>$totad_managed</b></td>\n";
                print " <td align=\"right\" style=\"border-top:1px solid #999999\"><b>$tottim</b></td>\n";
                print " <td align=\"right\" style=\"border-top:1px solid #999999\"><b>$totterminationWarning</b></td>\n";
                print " <td align=\"right\" style=\"border-top:1px solid #999999\"><b>$totterminationNotice</b></td>\n";
                print "</tr>\n";

                // get previous totals from ad_snapshots
                $sql = "SELECT *
                        FROM ad_snapshots
                        ORDER BY snapdate DESC
                ";
                if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $snapdate = $row['snapdate'];
                        $outstanding    = $row['outstanding'];
                        $outstandingQty = $row['outstandingQty'];
                        $over30Days     = $row['over30Days'];
                        $over30DaysQty  = $row['over30DaysQty'];
                        $over14Days     = $row['over14Days'];
                        $over14DaysQty  = $row['over14DaysQty'];
                        $ad_dd_set      = $row['ad_dd_set'];
                        $paymentPlans   = $row['paymentPlans'];
                        $managed        = $row['managed'];
                        $restricted     = $row['restricted'];
                        $termWarning    = $row['termWarning'];
                        $termNotice     = $row['termNotice'];
                        // reverse the date
                        $snapdate = date('d-m-Y', strtotime($snapdate));
                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	<td>$snapdate</td>\n";
                        print "	<td colspan=\"2\" align=\"right\">" . number_format($outstanding, 2) . " (" . $outstandingQty . ")</td>\n";
                        print "	<td align=\"right\">" . number_format($over30Days, 2) . " (" . $over30DaysQty . ")</b></td>\n";
                        print "	<td align=\"right\">" . number_format($over14Days, 2) . " (" . $over14DaysQty . ")</b></td>\n";
                        print "	<td align=\"right\">$ad_dd_set</td>\n";
                        print " <td align=\"right\">$paymentPlans</td>\n";
                        print " <td align=\"right\">$managed</td>\n";
                        print " <td align=\"right\">$restricted</td>\n";
                        print " <td align=\"right\">$termWarning</td>\n";
                        print " <td align=\"right\">$termNotice</td>\n";
                        print "</tr>\n";
                    }
                    print "<tr>\n";
                    print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\" align=\"right\">&nbsp;</td>\n";
                    print "</tr>\n";
                }
                print "</table>\n";
            } else {
                print "<p align=\"center\">No current debtors found</p>\n";
            }

            if (!$print) {
                print "<p align=\"center\"><a href=\"ad-snapshot-detail.php?print=1\" target=\"_blank\">Print</a></p>";
            }

            $dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
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
	print "</td>\n";
	include('footer.php');
}
?>
