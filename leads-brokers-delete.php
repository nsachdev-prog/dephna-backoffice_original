<?php
$page = "Leads";
$subpage = "Brokers";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        print "<p align=\"center\" class=\"heading\">OFFICE LEADS</p>\n";
		include('leads-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
        print "<p align=\"center\" class=\"larger\">Delete Broker</p>\n";

		$bid    = $_REQUEST['bid'];
		$delete = $_REQUEST['delete'];

		if ($bid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				// get details
				$sql = "SELECT brokername
						FROM leads_brokers
						WHERE ID='$bid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$brokername = $row['brokername'];

					if ($delete) {
						$sql = "DELETE FROM `leads_brokers` WHERE `ID`='$bid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>$brokername Deleted</b>\n";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$brokername','$subpage','$now')");
						} else {
							$resultMessage = "<b>$brokername Could Not Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>$brokername</b></p>\n";
						print "<p align=\"center\"><a href=\"leads-brokers-delete.php?bid=$bid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"leads-brokers-list.php\">No DON'T Delete</a></p>\n";
					}
				} else {
					$resultMessage = "<b>Broker Not Found!</b><br>" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without id!</b>\n";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"leads-brokers-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
