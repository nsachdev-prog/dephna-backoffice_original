<?php
$page = "Training";
$subpage = "Key Fobs";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			# get filters
			if ($_REQUEST['cl']) { $cl = $_REQUEST['cl']; }
			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
			$qString = "cl=$cl&lo=$lo";
			
			print "<p align = \"center\" class=\"heading\">Delete Key Fob</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  			$sql = "SELECT t.*, s.title AS sitename, u.compname AS compname
  							FROM training_clstaff AS t
								LEFT JOIN sites AS s ON t.sid = s.ID
								LEFT JOIN users AS u ON t.cid = u.ID
  							WHERE t.ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$name     = $row['name'];
					$fobnum   = $row['fobnum'];
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Client:</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Name:</b></td>\n";
					print "	<td>$name</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Key Fob Number:</b></td>\n";
					print "	<td>$fobnum</td>\n";
					print "</tr>\n";
					print "</table>\n";
					
					$delete = $_REQUEST['delete'];
					if ($delete) {
						$sql = "DELETE FROM training_clstaff WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Key Fob Deleted</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Key Fob: $fobnum','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						# check not in use
						$sql = "SELECT ID FROM training_sessions_included WHERE clsid='$id'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							print "<p align=\"center\"><b>CANNOT DELETE</b><br>Key Fob In Use</p>";
						} else {
							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"training-clstaff-delete.php?id=$id&delete=1&$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"training-clstaff-view.php?id=$id&$qString\">No DON'T Delete</a>";
							print "</p>\n";
						}
					}
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Training Session Not Found!</b></span><br>" . $dbcnx->error;
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
		print "<p align=\"center\"><a href=\"training-clstaff-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
