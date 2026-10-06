<?php
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
    print "<p align=\"center\" class=\"larger\">Delete Meter</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id  = $_GET['id'];
            $siteid = $_GET['siteid'];
			$sql = "SELECT m.*, s.title AS sitename
					FROM UtlMeters AS m
                    LEFT JOIN sites AS s ON s.ID = m.siteid
					WHERE m.ID = '$id'
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$sitename  = $row['sitename'];
				$unitname  = $row['unitname'];
				$commodity = $row['commodity'];
				$delete = $_GET['delete'];
                print "<p align=\"center\"><b>Site: $sitename<br />$unitname - $commodity</b></p>\n";
				if ($delete) {
					$sql = "DELETE FROM UtlMeters WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Meter Deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitename $unitname $commodity','$subpage','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				} else {
					print "<p align=\"center\"><a href=\"utilities-meters-delete.php?id=$id&amp;delete=1&amp;siteid=$siteid\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"utilities-meters-list.php?siteid=$siteid\">No DON'T Delete</a></p>\n";
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
		print "<p align=\"center\"><a href=\"utilities-meters-list.php?siteid=$siteid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
