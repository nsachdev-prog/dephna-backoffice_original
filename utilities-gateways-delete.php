<?php
$page = "Utilities";
$subpage = "Gateways";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
    print "<p align=\"center\" class=\"larger\">Delete Gateway</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id  = $_GET['id'];
			$sql = "SELECT u.*, s.title AS sitename
					FROM UtlGateways AS u
                    LEFT JOIN sites AS s ON s.ID = u.siteid
					WHERE u.ID = '$id'
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$sitename  = $row['sitename'];
				$title     = $row['title'];
				$ipaddress = $row['ipaddress'];
				$port      = $row['port'];
				$delete = $_GET['delete'];
                print "<p align=\"center\"><b>Site: $sitename<br />Gateway Name: $title<br />IP Address: $ipaddress<br />Port: $port</b></p>\n";
				if ($delete) {
					$sql = "DELETE FROM UtlGateways WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Gateway Deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$title $ipaddress:$port','$subpage','$now')");
					} else {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				} else {
					print "<p align=\"center\"><a href=\"utilities-gateways-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"utilities-gateways-list.php\">No DON'T Delete</a></p>\n";
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
		print "<p align=\"center\"><a href=\"utilities-gateways-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
