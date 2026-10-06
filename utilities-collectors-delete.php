<?php
$page = "Utilities";
$subpage = "Collectors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DELETE COUNTER</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; }
			if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
			$qString = "site=$site&amp;ty=$ty&amp;pa=$pa";

			$id  = $_GET['id'];
			$sql = "SELECT * FROM UtlCollectors WHERE ID='$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$sitename  = $row['sitename'];
				$collector = $row['collector'];
				$counter   = $row['counter'];
				$unit      = $row['unit'];
				$delete = $_GET['delete'];
				if ($delete) {
					$sql = "DELETE FROM UtlCollectors WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Counter successfully deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Counter $sitename $collector $counter $unit','$subpage','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				} else {
					print "<p align=\"center\"><b>Site: $sitename Collector: $collector Counter: $counter Unit: $unit</b></p>\n";
					print "<p align=\"center\"><a href=\"utilities-collectors-delete.php?$qString&amp;id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"utilities-collectors-view.php\">No DON'T Delete</a></p>\n";
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
		print "<p align=\"center\"><a href=\"utilities-collectors-view.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
