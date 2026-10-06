<?php
$page = "Bookings";
$subpage = "Files";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
		
	print "<p align=\"center\" class=\"heading\">BOOKINGS - TEST - LIST</p>\n";
	
	$crdate = strtotime("2017-03-01");

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		
		# get some records
		$sql = "SELECT u.unittype, u.title AS unitname, s.title AS sitename, b.custid AS custref
						FROM bookings b
						LEFT JOIN units u ON u.ID = b.unitid
						LEFT JOIN sites s ON s.ID = u.siteid
						ORDER BY s.title, u.title
		";
		$result = $dbcnx->query($sql);
		if ($result->num_rows) {
			
			print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
			print "<tr>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Unittype</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
			
			print "</tr>\n";
			
			# row background colours
			print "<style>\n";
			print ".r0 { background-color:#fff }\n";
			print ".r1 { background-color:#ddd }\n";
			print "</style>\n";
			
			while ($row = $result->fetch_assoc()) {
				
				$sitename = $row['sitename'];
				$unitname = $row['unitname'];
				$unittype = $row['unittype'];
				$custref  = $row['custref'];
				
				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "	<td>$sitename</td>\n";
				print "	<td>$unitname</td>\n";
				print "	<td>$unittype</td>\n";
				print "	<td>DP$custref</td>\n";
				print "</tr>\n";
			}
			print "<tr>\n";
			print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";
			print "</table>\n";
		} else {
			print "<p align=\"center\"><b>Nothing Found</b></p>\n";
		}

		$dbcnx->close();
	} else {
		$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
