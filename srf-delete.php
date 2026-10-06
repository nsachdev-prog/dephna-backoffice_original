<?php
$page = "SRF";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DELETE SRF</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];

			if ($delete) {

				$sql = "SELECT custref, amount FROM srf WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref = $row['custref'];
					$amount  = $row['amount'];
					$sql = "DELETE FROM srf WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Record successfully deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref $amount','$page','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				} else {
					$resultMessage = "Could not get site and extention<br />" . $dbcnx->error;
				}

			} else {

				$sql = "SELECT custname, details, amount FROM srf WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custname = $row['custname'];
					$details  = $row['details'];
					$amount   = $row['amount'];
					print "<p align=\"center\"><b>$custname</b><br />$details - $amount</p>\n";
					print "<p align=\"center\"><a href=\"srf-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"srf-view.php\">No DON'T Delete</a></p>\n";
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
		print "<p align=\"center\"><a href=\"srf-view.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
