<?php
$page = "BKManager";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Additional Services</p>\n";

		$bkaid = $_REQUEST['bkaid'];

		if ($bkaid) {

			include('bka-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Delete</p>\n";

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT b.*, u.title AS unitname, c.compname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						LEFT JOIN users AS c ON c.custref = b.custref
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$compname = $row['compname'];
					$unitid   = $row['unitid'];
					$unitname = $row['unitname'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];

					print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
				
					$delete = $_GET['delete'];
					$now = time();

					print "<p align=\"center\"><b>$title</b></p>\n";

					if ($delete) {

						$sql = "DELETE FROM bka_anc WHERE bkaid = '$bkaid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$rdb .= $sql.";\n";
							$resultMessage .= "Ancillaries Deleted<br />";
							$sql = "DELETE FROM bka_head WHERE ID = '$bkaid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= $sql.";\n";
								$resultMessage .= "Header Deleted<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','BKAID $bkaid','$page','$now')");
							} else {
								$resultMessage = "Could NOT Delete Header<br />" . $dbcnx->error;
							}
						} else {
							$resultMessage = "Could NOT Delete Ancillaries<br />" . $dbcnx->error;
						}

						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

					} else {

						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\"><a href=\"bka-delete.php?bkaid=$bkaid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bk-manager.php\">No DON'T Delete</a></p>\n";
						
					}
				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
