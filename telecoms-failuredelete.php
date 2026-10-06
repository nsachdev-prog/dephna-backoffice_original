<?php
$page = "Telecoms";
$subpage = "Failed Calls";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Delete Call Failures</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$pa = $_GET['pa'];
			$delete = $_GET['delete'];

			$sql = "SELECT extention FROM TelCallFailures WHERE ID='$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$extension = $row['extention'];
				
				print "<p align=\"center\">Delete all failed calls with this extension.</p>\n";
				print "<p align=\"center\" class=\"larger\">Extension $extension</p>\n";

				if ($delete) {

					$sql = "DELETE FROM TelCallFailures WHERE extention ='$extension'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Failed calls successfully deleted";
					} else {
						$resultMessage = "Could not delete records<br />" . $dbcnx->error;
					}

				} else {
					
					print "<p align=\"center\"><b>Are You Sure?</b></p>\n";
					print "<p align=\"center\"><a href=\"telecoms-failuredelete.php?id=$id&amp;delete=1&amp;pa=$pa\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"telecoms-importfailures-view.php?pa=$pa\">No DON'T Delete</a></p>\n";

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
		print "<p align=\"center\"><a href=\"telecoms-importfailures-view.php?pa=$pa\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
