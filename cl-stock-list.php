<?php
$page = "Cleaning";
$subpage = "Stock";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING - STOCK LEVELS</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($accessRights[$page] > 2) {
				if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
				if ($lo) { $search = "WHERE site = '$lo'"; }

				# filter form
				print "<form name=\"form1\" method=\"post\" action=\"cl-stock-list.php\">\n";
				print "<table class=\"filterBlock\" align=\"center\">\n";
				print "	<tr>\n";
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
				print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				print "	</tr>\n";
				print "</table>\n";
				print "</form>\n";
			} else {
				# get the users site
				$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$row = $uresult->fetch_assoc();
					$site = $row['site'];
					$search = "WHERE site = '$site'";
				}
			}

			$sql = "SELECT st.ID, st.site, st.level, st.threslow, st.threshigh, pr.prodname
					FROM cl_stock st
					JOIN cl_products pr ON pr.ID = st.prodid
					$search
					ORDER BY pr.prodname
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"70%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Product Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Level</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Low</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>High</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td align=\"left\">{$row['prodname']}</td>\n";
					print "	<td align=\"left\">{$row['site']}</td>\n";
					print "	<td align=\"left\">{$row['level']}</td>\n";
					print "	<td align=\"left\">{$row['threslow']}</td>\n";
					print "	<td align=\"left\">{$row['threshigh']}</td>\n";
					print "	<td align=\"center\"><a href=\"cl-stock-addedit.php?stid={$row['ID']}&amp;lo=$lo\">Edit</a></td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"cl-stock-delete.php?stid={$row['ID']}&amp;lo=$lo\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"7\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Stock Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($accessRights[$page] > 2) {
			print "<p align=\"center\"><a href=\"cl-stock-addedit.php\">Add New Stock</a></p>\n";
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
