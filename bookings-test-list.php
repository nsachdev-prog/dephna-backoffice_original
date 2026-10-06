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
		$sql = "SELECT l.office, h.custref, h.invnumber,h.year, h.month, h.day, s.compname
						FROM inv_lines l
						LEFT JOIN inv_headers h ON h.ID = l.hid
						LEFT JOIN users s ON h.custref = s.custref
						WHERE h.txtype = 'I'
							AND h.crby != 'System'
							AND h.crdate > '$crdate'
							AND (l.unittype = 'Office'
							 OR  l.unittype = 'Kitchen'
							 OR  l.unittype = 'Office V'
							 OR  l.unittype = 'Kitchen V'
							 OR  l.unittype = 'Cold Room'
							 OR  l.unittype = 'Storage')
						ORDER BY h.invnumber
		";
		$result = $dbcnx->query($sql);
		if ($result->num_rows) {
			
			print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
			print "<tr>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Invoice</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Office</b></td>\n";
			
			print "</tr>\n";
			
			# row background colours
			print "<style>\n";
			print ".r0 { background-color:#fff }\n";
			print ".r1 { background-color:#ddd }\n";
			print "</style>\n";
			
			$lastinv = 0;
			
			while ($row = $result->fetch_assoc()) {
				
				$office  = $row['office'];
				$custref = $row['custref'];
				$compname = $row['compname'];
				$invnum  = $row['invnumber'];
				$year    = $row['year'];
				$month   = $row['month'];
				$day     = $row['day'];
				
				if ($invnum == $lastinv) { $inv = "&nbsp;"; } else { $inv = $invnum; }
				
				if (strlen($title) > 40) { $title = substr($title, 0, 37) . "..."; }
				
				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "	<td>$year-$month-$day</td>\n";
				print "	<td>$inv</td>\n";
				print "	<td>DP$custref</td>\n";
				print "	<td>$compname</td>\n";
				print "	<td>$office</td>\n";
				print "</tr>\n";
				
				$lastinv = $invnum;
			}
			print "<tr>\n";
			print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";
			print "</table>\n";
		} else {
			print "<p align=\"center\"><b>No Lines Found</b></p>\n";
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
