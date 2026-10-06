<?php
$page = "Training";
$subpage = "Sessions";
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
			
			print "<p align = \"center\" class=\"heading\">Delete Training Session</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  			$sql = "SELECT t.ID, t.sid, t.cid, t.started, t.notes, s.title AS sitename, u.compname AS compname
					FROM training_sessions AS t
					LEFT JOIN sites AS s ON t.sid = s.ID
					LEFT JOIN users AS u ON t.cid = u.ID
					WHERE t.ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$started  = $row['started'];
					$notes    = $row['notes'];
					list($y,$m,$d,$t) = explode("-",$started);
					$started = "$d-$m-$y $t";
					
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
					print "	<td align=\"right\"><b>Started:</b></td>\n";
					print "	<td>$started</td>\n";
					print "</tr>\n";
					print "</table>\n";
					
					$delete = $_REQUEST['delete'];
					if ($delete) {
						$sql = "DELETE FROM training_sessions_included WHERE tsid='$id'";
						$result = $dbcnx->query($sql);
						$sql = "DELETE FROM training_sessions WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Training Session Deleted</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Session: $sitename $compname','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"training-sessions-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"training-sessions-list.php?id=$id\">No DON'T Delete</a>";
						print "</p>\n";
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
		print "<p align=\"center\"><a href=\"training-sessions-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
