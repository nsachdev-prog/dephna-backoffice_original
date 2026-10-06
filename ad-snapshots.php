<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Snapshots";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\">AGED DEBTORS</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\"><b>Snapshots</b></p>\n";

            include('ad-snapshot-inc.php');

            print "<table width=\"98%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\">&nbsp;</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Over 30 Days</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Over 14 Days</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Restricted</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Managed</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>PPlans</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Esc</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Warning</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Notice</b></td>\n";
            print "</tr>\n";
            
            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td><b>Current</b></td>\n";
            print "	<td align=\"right\">" . number_format($outstanding, 2) . "</td>\n";
            print "	<td align=\"right\">$outstandingQty</td>\n";
            print "	<td align=\"right\">" . number_format($over30Days, 2) . "</td>\n";
            print "	<td align=\"right\">$over30DaysQty</td>\n";
            print "	<td align=\"right\">" . number_format($over14Days, 2) . "</td>\n";
            print "	<td align=\"right\">$over14DaysQty</td>\n";
            print "	<td align=\"right\">" . number_format($restricted, 2) . "</td>\n";
            print "	<td align=\"right\">$restrictedQty</td>\n";
            print "	<td align=\"right\">" . number_format($managed, 2) . "</td>\n";
            print "	<td align=\"right\">$managedQty</td>\n";
            print "	<td align=\"right\">$paymentPlans</td>\n";
            print "	<td align=\"right\">$escalation</td>\n";
            print "	<td align=\"right\">$termWarning</td>\n";
            print "	<td align=\"right\">$termNotice</td>\n";
            print "</tr>\n";

            /* $sql = "SELECT * FROM ad_snapshots ORDER BY snapdate DESC";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td><b>" . $row['snapdate'] . "</b></td>\n";
                    print "	<td align=\"right\">" . number_format($row['outstanding'], 2) . "</td>\n";
                    print "	<td align=\"right\">" . $row['outstandingQty'] . "</td>\n";
                    print "	<td align=\"right\">" . number_format($row['over30Days'], 2) . "</td>\n";
                    print "	<td align=\"right\">" . $row['over30DaysQty'] . "</td>\n";
                    print "	<td align=\"right\">" . number_format($row['restricted'], 2) . "</td>\n";
                    print "	<td align=\"right\">" . $row['restrictedQty'] . "</td>\n";
                    print "	<td align=\"right\">" . number_format($row['managed'], 2) . "</td>\n";
                    print "	<td align=\"right\">" . $row['managedQty'] . "</td>\n";
                    print "	<td align=\"right\">" . $row['paymentPlans'] . "</td>\n";
                    print "	<td align=\"right\">" . $row['escalation'] . "</td>\n";
                    print "	<td align=\"right\">" . $row['termWarning'] . "</td>\n";
                    print "	<td align=\"right\">" . $row['termNotice'] . "</td>\n";
                    print "</tr>\n";
                }
            } else {
                print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                print "	<td colspan=\"13\">No snapshots available</td>\n";
                print "</tr>\n";
            } */

            print "<tr>\n";
            print "	<td colspan=\"15\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
            print "</tr>\n";

            print "</table>\n";
            
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
print "</td>\n";
include('footer.php');
?>
