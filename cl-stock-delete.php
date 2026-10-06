<?php
$page = "Cleaning";
$subpage = "Stock";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING - DELETE STOCK</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$stid   = $_REQUEST['stid'];
		$site   = $_REQUEST['site'];
		$lo     = $_REQUEST['lo'];
		$delete = $_REQUEST['delete'];

		if ($stid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get details
				$sql = "SELECT st.site, pr.prodname
						FROM cl_stock st
						JOIN cl_products pr ON pr.ID = st.prodid
						WHERE st.ID='$stid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$thissite = $row['site'];
					$prodname = $row['prodname'];

					if ($delete) {
						$sql = "DELETE FROM `cl_stock` WHERE `ID`='$stid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>$thissite $prodname Successfully Deleted</b>\n";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$thissite: $prodname','$page','$now')");
						} else {
							$resultMessage = "<b>$thissite Stock $stid Could Not Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\">Site:</td>\n";
						print "	<td colspan=\"3\">$thissite</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"right\">Product:</td>\n";
						print "	<td colspan=\"3\">$prodname</td>\n";
						print "</tr>\n";
						print "</table>\n";

						# see if it's on a purchase order
						$sql = "SELECT `ID` FROM `cl_polines` WHERE `stid`='$stid'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							print "<p align=\"center\">";
							print "	<b>Cannot Delete<br>This stock is still in on purchase orders</b>\n";
							print "</p>\n";
						} else {
							print "<p align=\"center\">";
							print "	<a href=\"cl-stock-delete.php?stid=$stid&amp;delete=1&amp;site=$site\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"cl-stock-list.php?site=$site\">No DON'T Delete</a>";
							print "</p>\n";
						}
					}
				} else {
					$resultMessage = "<b>Order Not Found!</b><br>" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without id!</b>\n";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-stock-list.php?lo=$lo\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
