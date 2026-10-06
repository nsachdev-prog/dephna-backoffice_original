<?php
$page = "Ancillaries";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DELETE ANCILLARY CHARGE</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];
			$now = time();

			$sql = "SELECT title FROM ancillaries WHERE ID='$id'";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$title = $row['title'];

				print "<p align=\"center\"><b>$title</b></p>\n";

				if ($delete) {

					$sql = "DELETE FROM ancillaries WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$rdb .= $sql.";\n";
						$resultMessage = "Ancillary charge successfully deleted";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$title','$page','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}

				} else {

					# is it in use
					$bkresult = $dbcnx->query("SELECT bkid FROM bookings_an WHERE ancid='$id'");
					if (($bkresult) AND ($bkresult->num_rows)) {
						$inuse++;
					}
					$bkresult = $dbcnx->query("SELECT bkid FROM vbookings_an WHERE ancid='$id'");
					if (($bkresult) AND ($bkresult->num_rows)) {
						$inuse++;
					}
					if ($inuse) {
						print "<p align=\"center\"><b>Cannot delete, ancillary still in use in bookings</b></p>\n";
					} else {
						print "<p align=\"center\"><a href=\"ancillaries-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"ancillaries-view.php\">No DON'T Delete</a></p>\n";
					}

				}
				$dbcnx->close();
			} else {
				$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
			}
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"ancillaries-view.php\">Ancillaries</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
