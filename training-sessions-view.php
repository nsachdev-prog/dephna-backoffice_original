<?php
$page = "Training";
$subpage = "Sessions";
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
			print "<p align=\"center\" class=\"heading\">View Training Session</p>\n";
			
			if ($id) {
				$sql = "SELECT t.*, s.title AS sitename, u.compname AS compname
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
					$completed = $row['completed'];
					$confirmed = $row['confirmed'];
					$notes    = $row['notes'];
					$url      = $row['url'];
					list($y,$m,$d,$t) = explode("-",$started);
					$started = "$d-$m-$y $t";
					if ($completed) {
						list($y,$m,$d,$t) = explode("-",$completed);
						$completed = "$d-$m-$y $t";
					}
					if ($confirmed) {
						list($y,$m,$d,$t) = explode("-",$confirmed);
						$confirmed = "$d-$m-$y $t";
					}

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
					
					if ($completed) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Completed:</b></td>\n";
						print "	<td>$completed</td>\n";
						print "</tr>\n";
					}
					
					if ($confirmed) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Signed Off:</b></td>\n";
						print "	<td>$confirmed</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Comments:</b></td>\n";
					print "	<td>$notes</td>\n";
					print "</tr>\n";
					
					if ($url) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Dropbox:</b></td>\n";
						print "	<td><a href=\"$url\" target=\"_blank\">$url</a></td>\n";
						print "</tr>\n";
					}
		
					print "</table>\n";
					
					print "<p align=\"center\" class=\"heading\">Trainees</p>\n";
					
					# get existing included in this session
					$included = array();
					$sql = "SELECT i.ID, i.clsid, c.name
									FROM training_sessions_included AS i
									LEFT JOIN training_clstaff AS c ON i.clsid = c.ID
									WHERE tsid='$id'
									ORDER BY c.name
					";
					$cresult = $dbcnx->query($sql);
					if ($cresult->num_rows) {
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						while ($crow  = $cresult->fetch_assoc()) {
							$clsid = $crow['ID'];
							$name  = $crow['name'];
							print "<tr>\n";
							print "	<td>$name</td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
					} else {
						print "<p align=\"center\"><b>No Client Staff In List</b></p>\n";
					}
					
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Training Session Not Found!</b></span>";
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
