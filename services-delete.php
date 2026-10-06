<?php
$page = "Services";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SERVICES</p>\n";
	print "<p align=\"center\" class=\"larger\">Delete Service</p>\n";
	if ($accessRights[$page]) {
		
		$id = $_REQUEST['id'];
		$lo = $_REQUEST['lo'];
		$y = $_REQUEST['y'];
		$m = $_REQUEST['m'];
		$qString = "lo=$lo&amp;y=$y&amp;m=$m";
		
		if ($id){
		
			# open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT servname, sitename FROM services WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$servname      = $row['servname'];
					$sitename      = $row['sitename'];
					print "<p align=\"center\" class=\"larger\">$sitename - $servname</p>\n";
				} else {
					$resultMessage = "<b>Service Not Found</b><br>" . $dbcnx->error;
				}
				
				if (!$resultMessage) {
			
					if ($_GET['delete']) {
						$sql = "DELETE FROM `services_done` WHERE `sid`='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$sql = "DELETE FROM `services` WHERE `ID`='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>Service Successfully Deleted</b>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitename $servname','$page','$now')");
							} else {
								$resultMessage = "<b>Could NOT Delete Service</b><br>" . $dbcnx->error;
							}
						} else {
							$resultMessage = "<b>Could NOT Delete Receipts</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\" class=\"redtext\"><b>Will also delete any receipts for this service.</b></p>";
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"services-delete.php?id=$id&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"services-list.php?$qString\">No DON'T Delete</a>";
						print "</p>\n";
					}
				}		
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without Service ID</b>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"services-list.php?$qString\">Services List</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
