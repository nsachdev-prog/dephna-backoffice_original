<?php
$page = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">UNIT TYPES</p>\n";
	print "<p align=\"center\" class=\"larger\">Delete Unit Type</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];

			$sql = "SELECT title FROM types WHERE ID='$id'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$title = $row['title'];
				print "<p align=\"center\" class=\"larger\">$title</p>\n";

				// check if it's in use
				$inuse = 0;
				$inuseby = array();
				$sql = "SELECT ID FROM inv_lines WHERE unittype = '$title'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$inuse++; $inuseby[] = "Invoicing";
				}
				$sql = "SELECT ID FROM bookings WHERE unittype = '$title'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$inuse++; $inuseby[] = "Bookings";
				}
				$sql = "SELECT ID FROM bkm_units WHERE unittype = '$title'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$inuse++; $inuseby[] = "BK Manager";
				}
				if ($inuse) {
					$inuseby = implode(", ",$inuseby);
					$resultMessage = "<b>CANNOT DELETE</b><br>The Unittype is in use in the following sections...<br>$inuseby";
				} else {
					if ($delete) {
						$sql = "DELETE FROM types WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Unit Type Successfuly Deleted";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$title','Unit Types','$now')");
						} else {
							$resultMessage = "Could not delete Unit Type<br />" . $dbcnx->error;
						}

					} else {
						print "<p align=\"center\"><a href=\"types-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"types-view.php\">No DON'T Delete</a></p>\n";
					}
				}
			} else {
				$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"types-view.php\">Unit Types</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
