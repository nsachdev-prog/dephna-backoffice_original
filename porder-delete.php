<?php
$page = "Purchase_Orders";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DELETE PURCHASE ORDER</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];
			if ($delete) {

				$sql = "SELECT ponumber FROM porders WHERE ID='$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$ponumber = $row['ponumber'];
					$sql = "DELETE FROM porders WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Purchase Order successfully deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$ponumber','$page','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}

			} else {

				$sql = "SELECT ponumber FROM porders WHERE ID='$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$ponumber = $row['ponumber'];
					print "<p align=\"center\"><b>$ponumber</b></p>\n";
					print "<p align=\"center\"><a href=\"porder-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"porders-view.php\">No DON'T Delete</a></p>\n";
				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}

			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"porders-view.php\">Purchase Orders</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
