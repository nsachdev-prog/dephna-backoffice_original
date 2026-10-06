<?php
$page = "Fire_Alarms";
$subpage = "Points";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Delete Fire Alarm Point</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  				$sql = "SELECT * FROM fam_points WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
					$title    = $row['title'];
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location Code:</b></td>\n";
					print "	<td>$title</td>\n";
					print "</tr>\n";

					print "</table>\n";

					$delete = $_REQUEST['delete'];
					if ($delete) {
						$sql = "DELETE FROM fam_tests WHERE pointid = '$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "Fire Alarm Tests Deleted<br>";
						}
						$sql = "DELETE FROM fam_points WHERE ID = '$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "Fire Alarm Point Deleted<br>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Point: $title','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b><br>This will also delete all past tests.</p>";
						print "<p align=\"center\">";
						print "	<a href=\"fam-points-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"fam-points-view.php?id=$id\">No DON'T Delete</a>";
						print "</p>\n";
					}

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Point Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fam-points-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
