<?php
$page = "Fire_Alarms";
$subpage = "Points";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
			
  			$sql = "SELECT * FROM fam_points WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename   = $row['sitename'];
					$title      = $row['title'];
					$details    = $row['details'];
					$notes      = $row['notes'];
					$lastupd    = $row['lastupd'];
					$updby      = $row['updby'];
					$lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
						
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location Code:</b></td>\n";
					print "	<td>$title</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location Details:</b></td>\n";
					print "	<td>$details</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
					print "	<td>$notes</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					
					if ($accessRights[$page] > 1) {
						print "<p align=\"center\"><a href=\"fam-points-addedit.php?id=$id\">Edit Point</a></p>\n";
					}
					
				} else {
					$resultMessage = "<b>Fire alarm point not found!</b>";
				}
			} else {
				$resultMessage = "<b>Page called without id</b>";
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
