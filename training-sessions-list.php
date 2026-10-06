<?php
$page = "Training";
$subpage = "Sessions";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			# row background colours
			print "<style>\n";
			print ".r0 { background-color:#fff }\n";
			print ".r1 { background-color:#ddd }\n";
			print "</style>\n";
			
			print "<p align = \"center\" class=\"heading\">Active Sessions</p>\n";
			
			print "<p style=\"margin:2% 5%;\"><strong>Active Session Guide</strong><br /><i>Once an active training session has been created, click PRINT to generate the checklist and form. Once training has finished, return here and click COMPLETE to add comments and URL for the scanned training document and to close the session and move it to Competed Sessions.</i></p>\n";
			
			# get some records
			$sql = "SELECT t.ID, t.sid, t.cid, t.started, s.title AS sitename, u.compname AS compname
					FROM training_sessions AS t
					LEFT JOIN sites AS s ON t.sid = s.ID
					LEFT JOIN users AS u ON t.cid = u.ID
					WHERE !t.completed
					ORDER BY t.started DESC, sitename, compname";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Started</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"44px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"55px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				$i = 0;
				
				while ($row = $result->fetch_assoc()) {
					
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$started  = $row['started'];
					list($y,$m,$d,$t) = explode("-",$started);
					$started = "$d-$m-$y";
					$tsref = "TS" . sprintf("%'.04d", $id);
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 17) . "..."; }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$tsref</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$started</td>\n";
					print "<td align=\"center\"><a href=\"training-sessions-print.php?id=$id\" target=\"_blank\">Print</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "<td align=\"center\"><a href=\"training-sessions-addedit.php?id=$id\">Update</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Update</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "<td align=\"center\"><a href=\"training-sessions-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "<td align=\"center\"><a href=\"training-sessions-complete.php?id=$id\">Complete</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\"><b>No Active Training Sessions</b></p>\n";
			}
			
			print "<p align=\"center\"><a href=\"training-sessions-addedit.php\">Create New Training Session</a></p>\n";
			
			print "<p align = \"center\" class=\"heading\">Completed Sessions</p>\n";
			
			# get some records
			$sql = "SELECT t.ID, t.sid, t.cid, t.started, t.completed, t.confirmed, t.url, s.title AS sitename, u.compname AS compname
					FROM training_sessions AS t
					LEFT JOIN sites AS s ON t.sid = s.ID
					LEFT JOIN users AS u ON t.cid = u.ID
					WHERE t.completed
					ORDER BY t.started DESC, sitename, compname";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Started</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Completed</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Signed Off</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"26px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				$i = 0;
				
				while ($row = $result->fetch_assoc()) {
					
					$id        = $row['ID'];
					$sitename  = $row['sitename'];
					$compname  = $row['compname'];
					$started   = $row['started'];
					$completed = $row['completed'];
					$confirmed = $row['confirmed'];
					$url       = $row['url'];
					list($y,$m,$d,$t) = explode("-",$started);
					$started = "$d-$m-$y";
					if ($completed) {
						list($y,$m,$d,$t) = explode("-",$completed);
						$completed = "$d-$m-$y";
					}
					if ($confirmed) {
						list($y,$m,$d,$t) = explode("-",$confirmed);
						$confirmed = "$d-$m-$y";
					}
					$tsref = "TS" . sprintf("%'.04d", $id);
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 17) . "..."; }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$tsref</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$started</td>\n";
					print "	<td>$completed</td>\n";
					print "	<td>$confirmed</td>\n";
					print "<td align=\"center\"><a href=\"training-sessions-view.php?id=$id\">View</a></td>\n";
					if ($url) {
						print "<td align=\"center\"><a href=\"$url\" target=\"_blank\">File</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">File</span></td>\n";
					}
					if (!$confirmed) {
						if ($accessRights[$page] > 1) {
							print "<td align=\"center\"><a href=\"training-sessions-addedit.php?id=$id\">Update</a></td>\n";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Update</span></td>\n";
						}
						if ($accessRights[$page] > 2) {
							print "<td align=\"center\"><a href=\"training-sessions-delete.php?id=$id\">Delete</a></td>\n";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						}
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Update</span></td>\n";
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					if ($confirmed) {
						print "	<td align=\"center\"><span class=\"noAccess\">Sign</span></td>\n";
					} else {
						if (in_array( $_SESSION['user'], array(1,3,6))) {
							print "<td align=\"center\"><a href=\"training-sessions-sign.php?id=$id\">Sign</a></td>\n";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Sign</span></td>\n";
						}
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\"><b>No Active Training Sessions</b></p>\n";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
