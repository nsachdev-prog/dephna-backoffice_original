<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p class=\"heading\" align=\"center\">Latest 50 Bookings</p>";
				
			# get bookings
			$sql = "SELECT b.custid, b.datefrom, b.fip, b.prfrom, b.prto, b.unittype, b.rate, u.compname, u.ID
 						FROM bookings AS b
 						LEFT JOIN users AS u ON b.custid = u.custref
						ORDER BY b.fip DESC
						LIMIT 0,50
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Rate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date From</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>FIP</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>PR From</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>PR To</b></td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print ".feature { background-color:#FFFF94 }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$custid   = $row['custid'];
					$userid   = $row['ID'];
					$compname = $row['compname'];
					$unittype = $row['unittype'];
					$rate     = $row['rate'];
					$datefrom = $row['datefrom'];
					$fip      = $row['fip'];
					$prfrom   = $row['prfrom'];
					$prto     = $row['prto'];
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
					
					if ($datefrom) { $datefrom = date("d-m-Y",$datefrom); }
					if ($prfrom) { $prfrom = date("d-m-Y",$prfrom); }
					if ($prto) { $prto = date("d-m-Y",$prto); }
					if ($fip) { $fip = date("m-Y",$fip); }
				
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td><a href=\"customer-ledger.php?id=$userid\" target=\"_blank\">DP$custid</a></td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$unittype</td>\n";
					print "	<td align=\"right\">$rate</td>\n";
					print "	<td>$datefrom</td>\n";
					print "	<td>$fip</td>\n";
					print "	<td>$prfrom</td>\n";
					print "	<td>$prto</td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";	
			} else {
				$resultMessage = "No bookings found";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br />" .$dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">&nbsp;</p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
