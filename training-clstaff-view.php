<?php
$page = "Training";
$subpage = "Key Fobs";
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
			
			# get filters
			if ($_REQUEST['cl']) { $cl = $_REQUEST['cl']; }
			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
			$qString = "cl=$cl&lo=$lo";
			
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
					$phone    = $row['phone'];
					$photo    = $row['photo'];
					$notes    = $row['notes'];
					
					if ($photo == "on") { $photo = "Yes"; } else { $photo = "No"; }
				
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
					print "	<td align=\"right\"><b>Fob Number:</b></td>\n";
					print "	<td>$fobnum</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Phone Number:</b></td>\n";
					print "	<td>$phone</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Photo Filed?:</b></td>\n";
					print "	<td>$photo</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Notes</b><br />";
					print "$notes</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					print "<p align=\"center\"><b>Completed Training Sessions</b></p>\n";
					
					$sql = "SELECT t.completed
									FROM training_sessions AS t
									LEFT JOIN training_sessions_included AS i ON t.ID = i.tsid
									WHERE t.completed AND i.clsid = '$id'
									ORDER BY t.completed DESC
					";
					$iresult = $dbcnx->query($sql);
					if ($iresult->num_rows) {
						print "<p align=\"center\">";
						while ($irow = $iresult->fetch_assoc()) {
							$completed = $irow['completed'];
							list($y,$m,$d) = explode("-",$completed);
							$completed = "$d-$m-$y";
							print "$completed<br>";
						}
						print "</p>\n";
					} else {
						print "<p align=\"center\">No Training Completed</p>\n";
					}
				} else {
					$resultMessage = "<b>Key Fob not Found</b>";
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
