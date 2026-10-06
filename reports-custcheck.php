<?php
$page = "Reporting";
$subpage = "Unused Customer Accounts";
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
            
			$sql = "SELECT custref, compname
				    FROM users
                    WHERE custref NOT IN (SELECT custref FROM inv_headers)
                      AND custref NOT IN (SELECT custid FROM bookings)
					  AND custref NOT IN (SELECT custref FROM vbookings)
                      AND custref NOT IN (SELECT custref FROM bk_manager)
                      AND custref NOT IN (SELECT custref FROM bka_head)
                    ORDER BY compname
            ";
            
            print "<p align=\"center\">These customers have never invoiced.<br>They have no bookings or orders in BKManager.</p>\n";
            
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"2\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                
                # row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
                    $custref   = $row['custref'];
                    $compname  = $row['compname'];
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>DP$custref</td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";
					
				}

                print "<tr>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                
				print "</table>\n";

                print "	<p align=\"center\">Total Found: $result->num_rows</p>\n";

			} else {
				print "	<p align=\"center\">No Customers Found<br>" . $dbcnx->error . "</p>\n";
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
