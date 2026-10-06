<?php
$page = "Cleaning";
$subpage = "Orders";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING - DELETE PURCHASE ORDER</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$hid    = $_REQUEST['hid'];
		$site   = $_REQUEST['site'];
		$delete = $_REQUEST['delete'];


		if ($hid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get header
				$sql = "SELECT * FROM cl_poheaders WHERE ID='$hid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$hrow = $result->fetch_assoc();
					$orddate = date("d/m/Y",$hrow['orddate']);
					if ($hrow['deldate']) { $deldate = date("d/m/Y",$hrow['deldate']); } else { $deldate = "&nbsp;"; }
					$thissite = $hrow['site'];

					if ($delete) {
						$sql = "DELETE FROM `cl_polines` WHERE `hid`='$hid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$sql = "DELETE FROM `cl_poheaders` WHERE `ID`='$hid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "$thissite CLOrder $hid Deleted\n";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$thissite POorder $hid','$page','$now')");
							} else {
								$resultMessage = "$thissite CLOrder $hid<br>Order Header Could Not Be Deleted<br>" . $dbcnx->error;
							}
						} else {
							$resultMessage = "$thissite CLOrder $hid<br>Order Lines Could Not Be Deleted<br>" . $dbcnx->error;
						}
					} else {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Order Num:</b></td>\n";
						print "	<td colspan=\"3\"><b>{$hrow['ID']}</b></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"right\">Order Date:</td>\n";
						print "	<td colspan=\"3\">$orddate</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"right\">Ordered By:</td>\n";
						print "	<td colspan=\"3\">{$hrow['crby']}</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"right\">Completed:</td>\n";
						print "	<td colspan=\"3\">$deldate</td>\n";
						print "</tr>\n";
						print "</table>\n";

						print "<p align=\"center\">";
						print "	<a href=\"cl-porders-delete.php?hid=$hid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"cl-porders-list.php?site=$site\">No DON'T Delete</a></p>\n";
					}
				} else {
					$resultMessage = "Order Not Found!<br>" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "Could not connect to database!<br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without id!";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-porders-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
