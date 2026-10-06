<?php
$page = "Utilities";
$subpage = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Delete Meter Types</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id  = $_GET['id'];

			if ($id) {
				$sql = "SELECT * FROM UtlTypes WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title     = $row['title'];
					$commodity = $row['commodity'];
					
					$delete = $_GET['delete'];
					
					if ($delete) {
						$sql = "DELETE FROM UtlRegisters WHERE typeid = '$id'";
						$result = $dbcnx->query($sql);
						$sql = "DELETE FROM UtlTypes WHERE ID = '$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Meter Type $title Deleted";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Meter Type $title','$subpage','$now')");
						} else {
							$resultMessage = "Could not delete record<br />" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Meter Type: $title<br />Commodity: $commodity</b></p>\n";
						print "<p align=\"center\"><a href=\"utilities-types-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"utilities-types-list.php\">No DON'T Delete</a></p>\n";
					}

				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without id<br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"utilities-types-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
