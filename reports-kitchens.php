<?php
$page = "Reporting";
$subpage = "Bookdebt Report";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">Kitchens By Customer</span></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

            // get customers by unittype kitchen
            $sql = "SELECT b.custid, b.siteid, b.unitid, c.compname, u.title AS unitname, s.title AS sitename
                    FROM bookings AS b
                    LEFT JOIN users AS c ON b.custid = c.custref
                    LEFT JOIN units AS u ON u.ID = b.unitid
                    LEFT JOIN sites AS s ON s.ID = b.siteid
                    WHERE b.unittype = 'kitchen'
                    ORDER BY c.compname
            ";

            // print results as custname, sitename, unitname
            if ($result = $dbcnx->query($sql)) {
                print "<table cellpadding=\"2\" cellspacing=\"2\" align=\"center\">\n";
                while ($row = $result->fetch_assoc()) {
                    $custid = $row['custid'];
                    if ($custid != $lastcustid) {
                        print "<tr><td><b>Total</b></td><td><b>&nbsp;</b></td><td><b>$custtotal</b></td></tr>\n";
                        print "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
                        $custtotal = 0;
                        print "<tr><td colspan=\"3\"><b>" . $row['compname'] . "</b></td></tr>\n";
                    }
                    print "<tr><td>" . $row['sitename'] . "</td><td>" . $row['unitname'] . "</td><td>&nbsp;</td></tr>\n";
                    $custtotal++;
                    $grandtotal++;
                    $lastcustid = $custid;
                }
                print "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
                print "<tr><td><b>Grand Total</b></td><td><b>$grandtotal</b></td></tr>\n";
                print "</table>\n";
                $result->free();
            } else {
                print "<p align=\"center\"><b>Query Failed!</b></p>\n";
            }
            
            
			  
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
