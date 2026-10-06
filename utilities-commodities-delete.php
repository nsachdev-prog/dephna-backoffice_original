<?php
$page = "Utilities";
$subpage = "Commodities";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DELETE COMMODITY</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			$id  = $_GET['id'];
			$sql = "SELECT * FROM UtlCommodities WHERE ID='$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$sitename	= $row['sitename'];
				$custref	= $row['custref'];
				$commodity	= $row['commodity'];
				if ($custref != "Default") { $custref = "DP" . $custref; }
				print "<p align=\"center\"><b>Site: $sitename Commodity: $commodity Cust Ref: $custref</b></p>\n";
				$delete = $_GET['delete'];
				if ($delete) {
					$sql = "DELETE FROM UtlCommodities WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Commodity successfully deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitename $custref $commodity','$subpage','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				} else {
					print "<p align=\"center\"><a href=\"utilities-commodities-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"utilities-commodities-view.php\">No DON'T Delete</a></p>\n";
				}
			} else {
				$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"utilities-commodities-view.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
