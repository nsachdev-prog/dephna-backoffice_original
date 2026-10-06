<?php
$page = "Telecoms";
$subpage = "Extensions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Delete Extension</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];

			if ($id) {

				$sql = "SELECT sitename, extention FROM telecoms WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
					$extention = $row['extention'];
					print "<p align=\"center\"><b>$sitename Ext $extention</b></p>\n";

					if ($delete) {

						$sql = "DELETE FROM telecoms WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Extension successfully deleted";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitename Ext $extention','$page','$now')");
						} else {
							$resultMessage = "Could not delete record<br />" . $dbcnx->error;
						}

					} else {

						print "<p align=\"center\"><b>Are You Sure?</b></p>\n";
						print "<p align=\"center\"><a href=\"telecoms-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"telecoms-extensions.php\">No DON'T Delete</a></p>\n";

					}
				} else {
					$resultMessage = "Could not get record from database<br>" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "Page called without id<br>\n";
			}
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"telecoms-extensions.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
