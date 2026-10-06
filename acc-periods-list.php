<?php
$page = "Accounts";
$subpage = "Returns";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";
	if ($accessRights[$page]) {

        print "<p align=\"center\"><span class=\"larger\">Accounting Periods</span><br>(Financial Years)</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT *
					FROM acc_periods
					ORDER BY period_start
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"50%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>From</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>To</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"58px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"58px\"></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

                    $id           = $row['ID'];
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];

                    list($sy,$sm,$sd) = explode("-",$period_start);
                    list($ey,$em,$ed) = explode("-",$period_end);
                    
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sd-$sm-$sy</td>\n";
                    print "	<td>$ed-$em-$ey</td>\n";
                    print "	<td align=\"center\"><a href=\"acc-reports-pandl.php?id=$id\">Select</a></td>\n";
					print "	<td align=\"center\"><a href=\"acc-periods-addedit.php?id=$id\">Edit</a></td>\n";
					print "	<td align=\"center\"><a href=\"acc-periods-provisions.php?id=$id\">Provisions</a></td>\n";
					print "	<td align=\"center\"><a href=\"acc-periods-download.php?id=$id\">Download</a></td>\n";
                    print "	<td align=\"center\"><a href=\"acc-periods-delete.php?id=$id\">Delete</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"7\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Periods Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"acc-reports-revenue.php\">Revenue by Unittype</a></p>\n";
		print "<p align=\"center\"><a href=\"acc-reports-occupancy.php\">Occupancy by Month</a></p>\n";
		print "<p align=\"center\"><a href=\"acc-reports-occupancy2.php\">Occupancy Length</a></p>\n";
		if ($accessRights[$page] > 2) {
			print "<p align=\"center\"><a href=\"acc-periods-addedit.php\">Add New Period</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
