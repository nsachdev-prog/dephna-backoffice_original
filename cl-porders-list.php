<?php
$page = "Cleaning";
$subpage = "Orders";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Orders</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$status = array(
				0 => "Entered",
				1 => "Approved",
				2 => "Ordered",
				3 => "Delivered",
				4 => "Invoiced",
				5 => "Completed"
			);

			$actions = array(
				0 => "Approve Order",
				1 => "Send Order",
				2 => "Log Delivery",
				3 => "Approve Invoice",
				4 => "Complete"
			);

			if ($accessRights[$page] > 2) {
				if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } // location
				if ($lo) { $search = "WHERE site = '$lo'"; }

				// filter form
				print "<form name=\"form1\" method=\"post\" action=\"cl-porders-list.php\">\n";
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
				print "<br>\n";
			} else {
				// get the users site
				$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$row = $uresult->fetch_assoc();
					$site = $row['site'];
					$search = "WHERE site = '$site'";
				}
			}

			// get the users cladmin rights
			$sql = "SELECT cladmin FROM staff WHERE ID='{$_SESSION['user']}'";
			$uresult = $dbcnx->query($sql);
			if ($uresult->num_rows) {
				$row = $uresult->fetch_assoc();
				$cladmin = $row['cladmin'];
			}

			$sql = "SELECT o.ID, o.site, o.orddate, o.ordertotal, o.status, s.suppname
					FROM cl_poheaders AS o
					LEFT JOIN suppliers AS s ON o.suppid = s.ID
					$search 
					ORDER BY o.orddate
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"70%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Ord Num</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Entered</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Supplier</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Total</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Status</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$orddate = $row['orddate'];
					$orddate = date("d-m-Y",$orddate);
					$thisstatus = $row['status'];
					$ordernum = $row['ID'];
					$ordernum = str_pad($ordernum, 5, "0", STR_PAD_LEFT);
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td align=\"left\">$ordernum</td>\n";
					print "	<td align=\"left\">$orddate</td>\n";
					print "	<td align=\"left\">{$row['site']}</td>\n";
					print "	<td align=\"left\">{$row['suppname']}</td>\n";
					print "	<td align=\"left\">{$row['ordertotal']}</td>\n";
					print "	<td align=\"left\">{$status[$thisstatus]}</td>\n";
					if (($thisstatus == 0 OR $thisstatus == 3) AND $cladmin != 'on') {
						print "	<td align=\"center\" class=\"noAccess\">{$actions[$thisstatus]}</td>\n";
					} else {
						if ($thisstatus == 2) {
							print "<td align=\"center\"><a href=\"cl-porders-delivery.php?hid={$row['ID']}\">{$actions[$thisstatus]}</td></a>";
						} else {
							print "<td align=\"center\"><a href=\"cl-porders-update.php?hid={$row['ID']}\">{$actions[$thisstatus]}</td></a>";
						}
					}
					print "	<td align=\"center\"><a href=\"cl-porders-view.php?hid={$row['ID']}\">View</a></td>\n";
					print "	<td align=\"center\"><a href=\"cl-porders-delete.php?hid={$row['ID']}&amp;site=$lo\">Delete</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"9\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Orders Found!<br>" . $dbcnx->error . "</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-porders-generate.php\">New Purchase Order</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
