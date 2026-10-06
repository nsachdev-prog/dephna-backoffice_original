<?php
$page = "Fitouts";
$subpage = "Quotes";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
			include('fitouts-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Quotes</p>\n";

			$sql = "SELECT q.ID, q.custref, q.year, q.month, q.day, u.compname, q.updby, s.title AS sitename, q.emailed, q.accepted, q.completed, ufpaid
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					LEFT JOIN sites AS s ON s.ID = q.siteid
					WHERE q.completed = '0'
					ORDER BY q.year, q.month, q.day, u.compname
			";
			
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table align=\"center\" width=\"95%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Started</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>By</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Customer</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Units</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\"><b>Status</b></td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\" width=\"26\">&nbsp;</td>\n";
				print "	    <td style=\"border-bottom:1px solid #666\" width=\"35\">&nbsp;</td>\n";
				print "	</tr>\n";

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$qid       = $row['ID'];
					$custref   = $row['custref'];
					$compname  = $row['compname'];
					$sitename  = $row['sitename'];
					$emailed   = $row['emailed'];
					$accepted  = $row['accepted'];
					$ufpaid    = $row['ufpaid'];
					$completed = $row['completed'];
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$updby     = $row['updby'];

					if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }
					
					// get units
					$sql = "SELECT unitname FROM fitouts_quotes_units WHERE qid = '$qid'";
					if ($uresult = $dbcnx->query($sql) AND $uresult->num_rows) {
						$allnames = array();
						while ($urow = $uresult->fetch_assoc()) {
							$unitname = $urow['unitname'];
							$allnames[] = $unitname;
						}
						$allnames = implode(", ",$allnames);
					} else {
						$allnames = "Not Added Yet";
					}

					if ($completed) {
						$status = "Completed";
					} elseif ($emailed) {
						$status = "Emailed";
					} elseif ($ufpaid) {
						$status = "In Progress";
					} elseif ($accepted) {
						$status = "Accepted";
					} else {
						$status = "New";
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "<td valign=\"top\">$day-$month-$year</td>\n";
					print "<td valign=\"top\">$updby</td>\n";
					print "<td valign=\"top\">$compname</td>\n";
					print "<td valign=\"top\">DP$custref</td>\n";
					print "<td valign=\"top\">$sitename</td>\n";
					print "<td valign=\"top\">$allnames</td>\n";
					print "<td valign=\"top\">$status</td>\n";
					print "<td valign=\"top\"><a href=\"fitouts-quotes-units.php?qid=$qid\">Edit</a></td>\n";
					print "<td valign=\"top\"><a href=\"fitouts-quotes-delete.php?qid=$qid\">Delete</a></td>\n";
					print "</tr>\n";

				}

				$numrows = $result->num_rows;
				print "<tr>\n";
				print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
				print "	<td colspan=\"8\" align=\"right\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";
			} else {
				$resultMessage = "<b>No Quotes Found!</b>";
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
	print "<p align=\"center\"><a href=\"fitouts-quotes-addedit.php\">Add New Fitout Quote</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
