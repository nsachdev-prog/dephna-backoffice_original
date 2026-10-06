<?php
$page = "Mailouts";
$subpage = "Notices";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DELETE MAILOUT</p>\n";
	if ($accessRights[$page] > 2) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		$id = $_GET['id'];
		$subject = $_GET['subject'];
		$delete = $_GET['delete'];

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($delete) {

				$sql = "DELETE FROM mailouts WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if ($result) {
					$resultMessage = "<b>Email Successfuly Deleted</b>";
					$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$subject','Mailouts','$now')");
				} else {
					$resultMessage = "<b>Could NOT delete email</b><br>" . $dbcnx->error;
				}

			} else {

				$sql = "SELECT * FROM mailouts WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$subject = $row['subject'];
					print "<p align=\"center\">$subject</p>\n";
					print "<p align=\"center\"><a href=\"mailouts-delete.php?id=$id&amp;delete=1&amp;subject=$subject\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"mailouts-view.php\">No DON'T Delete</a></p>\n";
				} else {
					$resultMessage = "Could not get record from database<br>" . $dbcnx->error;
				}

			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"mailouts-view.php\">Mailouts</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
