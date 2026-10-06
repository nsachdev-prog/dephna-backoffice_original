<?php
$page = "Maintenance";
$subpage = "Groups";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
print "<p align=\"center\" class=\"heading\">MAINTENANCE - DELETE GROUP</p>\n";
include('maintenance-submenu.php');
print "<p align=\"center\">$menustr</p>\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {
		$gid = $_REQUEST['gid'];
		if ($gid) {
			
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get group name
				$sql = "SELECT title FROM mnt_groups WHERE ID='$gid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$grtitle = $row['title'];
					$delete = $_GET['delete'];
					if ($delete) {
						# delete types
						$sql = "DELETE FROM mnt_types WHERE gid='$gid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							# delete group
							$sql = "DELETE FROM mnt_groups WHERE ID='$gid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "Group successfully deleted";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Group $grtitle','$subpage','$now')");
							} else {
								$resultMessage = "Could not delete group<br />" . $dbcnx->error;
							}
						} else {
							$resultMessage = "Could not delete types<br />" . $dbcnx->error;
						}
					} else {
						# see if it's in use..
						$sql = "SELECT `ID` FROM `mnt_tickets` WHERE `group`='$gid' ORDER BY `ID`";
						$tresult = $dbcnx->query($sql);
						if (($tresult) && ($tresult->num_rows)) {
							while ($trow = $tresult->fetch_assoc()) {
								$tid = $trow['ID'];
								$inuse[] = "M" . sprintf("%06s",$tid);
							}
							$inuse = implode("<br />",$inuse);
							print "<p align=\"center\"><b>Cannot Delete</b></p>\n";
							print "<p align=\"center\">Group: $grtitle in use by...</p>\n";
							print "<p align=\"center\">$inuse</p>\n";
						} else {
							print "<p align=\"center\"><b>Group: $grtitle</b></p>\n";
							print "<p align=\"center\">This will also delete any types in this group.</p>\n";
							print "<p align=\"center\"><a href=\"maintenance-groups-delete.php?gid=$gid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"maintenance-groups-view.php\">No DON'T Delete</a></p>\n";
						}
					}
				} else {
					$resultMessage = "Could not get group name<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
			if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
			}
			print "<p align=\"center\"><a href=\"maintenance-groups-view.php\">Return</a></p>\n";
		} else {
			print "<p align=\"center\"><b>Page called without group id</b></p>\n";
		}
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
