<?php
$page = "Invoicing";
$subpage = "Uninvoiced";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	# print heading and submenu
	print "<p align=\"center\" class=\"heading\">INVOICING</p>\n";
	include('invoicing-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 2) {
		
		print "<p align=\"center\">Bookings not included in this months invoice run.</p>\n";
		$year = date("Y");
		$month = date("m");
		print "<p align=\"center\">$year/$month</p>\n";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$sql = "SELECT b.custid, b.siteid, b.unitid, b.ID, u.compname FROM bookings b
					LEFT JOIN users u ON u.custref=b.custid
					WHERE NOT EXISTS (SELECT i.ID FROM inv_headers i WHERE i.custref=b.custid AND i.year='$year' AND i.month='$month' AND i.crby='System')
					ORDER BY u.compname
			";
			if ($result = $dbcnx->query($sql)) {
				if ($result->num_rows) {
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "	<tr>\n";
					print "		<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
					print "	</tr>\n";
					
					while ($row = $result->fetch_assoc()) {
						$custref = $row['custid'];
						$compname = $row['compname'];
						$siteid = $row['siteid'];
						$unitid = $row['unitid'];
						$bkid = $row['ID'];
						print "	<tr>\n";
						print "		<td>DP$custref</td>\n";
						print "		<td>$compname</td>\n";
						print "		<td><a href=\"bookings-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;bkid=$bkid\" target=\"_blank\">View</a></td>\n";
						print "	</tr>\n";
					}
					print "</table>\n";
				} else {
					$resultMessage = "<b>None found!</b>";
				}
				print "<p>&nbsp;</p>\n";
			} else {
				$resultMessage = "<b>Could not get transactions from database</b><br>" . $dbcnx->error;
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
print "</td>\n";
include('footer.php');
?>
