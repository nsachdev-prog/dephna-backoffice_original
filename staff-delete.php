<?php
$page = "Staff";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">DELETE STAFF</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];

			$sql = "SELECT username, email FROM staff WHERE ID='$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$username = $row['username'];
				$email    = $row['email'];

				if ($delete) {

					$sql = "DELETE FROM staff WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "<b>User successfully deleted</b>";
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$username','$page','$now')");
					} else {
						$resultMessage = "<b>Could not delete user</b><br />" . $dbcnx->error;
					}

				} else {

					print "<p align=\"center\">$username<br />$email</p>\n";
					print "<p align=\"center\"><a href=\"staff-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"staff-view.php\">No DON'T Delete</a></p>\n";

				}

			} else {
				$resultMessage .= "<b>Could not find record in database</b><br />" . $dbcnx->error;
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"<b>You do not have access rights for this function</b></p>\n";
	}
	print "<p align=\"center\"><a href=\"staff-view.php\">Return</a></p>\n";
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
}
print "</td>\n";
include('footer.php');
?>
