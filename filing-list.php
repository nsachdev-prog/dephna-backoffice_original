<?php
$page = "Filing";
$subpage = "Filing";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">FILING</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"filing-list.php\">\n";
			print "<table align=\"center\">\n";
			print "	<tr>\n";

			if ($accessRights[$page] > 2) {
				if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
				if ($lo) { $search[] = "sitename = '$lo'"; }

				print "		<td align=\"right\"><b>Site</b></td>\n";
				print "		<td><select name=\"lo\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				$sql = "SELECT title FROM sites ORDER BY title";
				$sresult = $dbcnx->query($sql);
				if ($sresult->num_rows) {
					while ($srow = $sresult->fetch_assoc()) {
						print "		<option value=\"{$srow['title']}\""; if ($lo == $srow['title']) { print " SELECTED"; } print ">{$srow['title']}</option>\n";
					}
				}
				print "		</select>";
				print "		</td>\n";

			} else {
				# get the users site
				$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$row = $uresult->fetch_assoc();
					$lo = $row['site'];
					$search[] = "sitename = '$lo'";
				}
			}

			if ($_REQUEST['ymd']) { $ymd = $_REQUEST['ymd'];
				list($y,$m,$d) = explode("-",$ymd);
				$search[] = "year = '$y'";
				$search[] = "month = '$m'";
				$search[] = "day = '$d'";
			}
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; } $search[] = "details LIKE '%$se%'";

			print "	<td align=\"right\"><b>Search</b></td>\n";
			print "	<td><input name=\"se\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$se\" /></td>\n";

			print "	<td align=\"right\"><b>Date</b></td>\n";
			print "	<td><input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element_small\" /></td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"filing-list.php\"><b>Reset</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$search = "WHERE " . implode(" AND ",$search);

			print "<p>&nbsp;</p>";

			$sql = "SELECT ID, year, month, day, dephnaref, sitename, details
					FROM filing
					$search
					ORDER BY sitename, dephnaref
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "</tr>\n";

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$date = $row['day']."/".$row['month']."/".$row['year'];
					$details = $row['details'];
					if ($details) {
						$details = stripslashes($details);
						if (strlen($details) > 60) { $details = substr($details, 0, 60); }
					} else {
						$details = "&nbsp;";
					}
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$date</td>\n";
					print "	<td>DR{$row['dephnaref']}</td>\n";
					print "	<td>{$row['sitename']}</td>\n";
					print "	<td>$details</td>\n";
					print "	<td align=\"center\"><a href=\"filing-view.php?id={$row['ID']}\">View</a></td>\n";
					print "	<td align=\"center\"><a href=\"filing-addedit.php?id={$row['ID']}\">Edit</a></td>\n";
					print "	<td align=\"center\"><a href=\"filing-delete.php?id={$row['ID']}\">Delete</a></td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Filing Found!</p>\n";
			}
			$dbcnx->close();

			# print jquery date picker
			print "<script type=\"text/javascript\">\n";
			print "	$( document ).ready(function() {\n";
			print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
			print "	});\n";
			print "</script>\n";

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"filing-addedit.php\">Add New Item</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
