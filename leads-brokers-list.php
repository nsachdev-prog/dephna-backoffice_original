<?php
$page = "Leads";
$subpage = "Brokers";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        print "<p align=\"center\" class=\"heading\">OFFICE LEADS</p>\n";
		include('leads-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
        print "<p align=\"center\" class=\"larger\">Brokers</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT ID, brokername, brokerref
					FROM leads_brokers
					ORDER BY brokername
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Broker Name</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Broker Ref</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>{$row['brokername']}</td>\n";
					print "	<td>{$row['brokerref']}</td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"leads-brokers-addedit.php?bid={$row['ID']}\">Edit</a></td>\n";
						print "	<td align=\"center\"><a href=\"leads-brokers-delete.php?bid={$row['ID']}\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
                print "<tr>\n";
				print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\"></td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Brokers Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($accessRights[$page] > 2) {
			print "<p align=\"center\"><a href=\"leads-brokers-addedit.php\">Add New Broker</a></p>\n";
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
