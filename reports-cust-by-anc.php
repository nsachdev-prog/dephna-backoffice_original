<?php
$page = "Reporting";
$subpage = "Ancillaries";
$subsubpage = "By Ancillary";
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
			print "<p align=\"center\" class=\"heading\">$subpage</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			// get filtering if any and build search array
			if ($_REQUEST['cu'] != "All") { $cu = $_REQUEST['cu']; }
			if ($_REQUEST['an'] != "All") { $an = $_REQUEST['an']; }
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; }
			if ($cu) { $search[] = "a.custref='$cu'"; }
			if ($an) { $search[] = "a.ancid='$an'"; }
			if ($lo) { $search[] = "b.siteid='$lo'"; }
			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-cust-by-anc.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Customer</b></td>\n";
			print "		<td><select name=\"cu\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT custref, compname FROM users ORDER BY compname";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
                    $thiscustref  = $row['custref'];
                    $thiscompname = $row['compname'];
                    print "		<option value=\"$thiscustref\"";
                    if ($cu == $thiscustref) {
                        print " SELECTED";
                        $custselected = $thiscompname;
                    }
                    print ">$thiscompname</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT ID, title FROM sites ORDER BY title";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$thissiteid = $row['ID'];
					$thistitle  = $row['title'];
					print "		<option value=\"$thissiteid\""; if ($lo == $thissiteid) { print " SELECTED"; } print ">$thistitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td align=\"right\"><b>Ancillary</b></td>\n";
			print "		<td><select name=\"an\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT ancid, anctitle FROM bookings_an GROUP BY ancid ORDER BY anctitle";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
                    $thisancid    = $row['ancid'];
                    $thisanctitle = $row['anctitle'];
                    print "		<option value=\"$thisancid\"";
                    if ($an == $thisancid) {
                        print " SELECTED";
                    }
                    print ">$thisanctitle</option>\n";
				}
			}
			print "		</select>";
            print "		</td>\n";

            print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            
            print "		<td><a href=\"reports-cust-by-anc.php\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
            print "</form>\n";
            
			$sql = "SELECT COUNT(a.ancid) AS custs, a.anctitle, a.ancid
				FROM bookings_an AS a
				LEFT JOIN users AS u ON a.custref = u.custref
				LEFT JOIN bookings AS b ON b.ID = a.bkid
				$search
				GROUP BY a.ancid
                ORDER BY a.anctitle
            ";
            
            print "<p align=\"center\"><b>Currently In Use</b></p>\n";
            print "<p align=\"center\">";
            print "<a href=\"reports-anc-by-cust.php?cu=$cu&an=$an&lo=$lo\">By Customer</a> | <a href=\"reports-cust-by-anc.php?cu=$cu&an=$an&lo=$lo\"><span class=\"redtext\">By Ancillary</span></a>";
            print "</p>\n";

			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table align=\"center\" width=\"70%\" cellspacing=\"0\" cellpadding=\"2\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Anc ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Ancillary</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Usage</b></td>\n";
                print "</tr>\n";
                
                # row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
                    $anctitle   = $row['anctitle'];
                    $ancid      = $row['ancid'];
                    $custs      = $row['custs'];
                    $totcusts += $custs;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$ancid</td>\n";
					print "	<td>$anctitle</td>\n";
					print "	<td align=\"right\">$custs</td>\n";
					print "</tr>\n";
					
				}

                print "<tr>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\"><b>" . $result->num_rows . "</b></td>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\" align=\"right\"><b>$totcusts</b></td>\n";
                print "</tr>\n";
                
				print "</table>\n";

			} else {
				print "	<p align=\"center\">No Customers with Acillaries<br>" . $dbcnx->error . "</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
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
