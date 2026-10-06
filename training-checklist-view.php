<?php
$page = "Training";
$subpage = "Checklist";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			if ($id) {

  			$sql = "SELECT * FROM training_checklist WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title   = $row['title'];
					$details = $row['details'];
					
					print "<p align=\"center\" class=\"heading\">$title</p>\n";
					print "<blockquote><b>Details</b><br>$details</blockquote>\n";

				} else {
					$resultMessage = "<b>Checkbox not Found</b>";
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
		print "<p align=\"center\"><a href=\"training-checklist-list.php\">Return</a></p>\n";
	
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
