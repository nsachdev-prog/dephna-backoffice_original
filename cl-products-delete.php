<?php
$page = "Cleaning";
$subpage = "Products";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING - DELETE PRODUCT</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$prodid = $_REQUEST['prodid'];
		$site   = $_REQUEST['site'];
		$delete = $_REQUEST['delete'];

		if ($prodid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get details
				$sql = "SELECT * FROM `cl_products` WHERE `ID`='$prodid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$prodname = $row['prodname'];

					if ($delete) {
						$sql = "DELETE FROM `cl_products` WHERE `ID`='$prodid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>$prodname Successfully Deleted</b>\n";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Product: $prodname','$page','$now')");
						} else {
							$resultMessage = "<b>$prodname Could Not Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\">Product:</td>\n";
						print "	<td colspan=\"3\">$prodname</td>\n";
						print "</tr>\n";
						print "</table>\n";

						# see if it's stocked at any site still
						$sql = "SELECT `ID` FROM `cl_stock` WHERE `prodid`='$prodid'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							print "<p align=\"center\">";
							print "	<b>Cannot Delete<br>Producted is still stocked at sites</b>\n";
							print "</p>\n";
						} else {
							print "<p align=\"center\">";
							print "	<a href=\"cl-products-delete.php?prodid=$prodid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"cl-products-list.php\">No DON'T Delete</a>";
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
		print "<p align=\"center\"><a href=\"cl-products-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
