<?php
$page = "Training";
$subpage = "Checklist";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		print "<p align = \"center\" class=\"heading\">Checklist</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_GET['move']) {
				$move = $_GET['move'];
				$pos  = $_GET['pos'];
				if ($move == "up") {
					$moveto = $pos-1;
					$movethat = "pos=pos+1";
					$otherone = $pos-1;
					$moveback = "pos='$moveto'";
				} else {
					$moveto = $pos+1;
					$movethat = "pos=pos-1";
					$otherone = $pos+1;
					$moveback = "pos='$moveto'";
				}
				$sql = "UPDATE training_checklist SET pos='0' WHERE pos='$pos'";
				$result = $dbcnx->query($sql);
				$sql = "UPDATE training_checklist SET $movethat WHERE pos='$otherone'";
				$result = $dbcnx->query($sql);
				$sql = "UPDATE training_checklist SET $moveback WHERE pos='0'";
				$result = $dbcnx->query($sql);
			}
			
			# get some records
			$sql = "SELECT * FROM training_checklist ORDER BY pos";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Title</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				Print "	<td align=\"center\" colspan=\"2\" style=\"border-bottom:1px solid #999999\" width=\"36px\"><b>Move</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				$n = 0;
				$last = $result->num_rows;
				while ($row = $result->fetch_assoc()) {
					
					$id       = $row['ID'];
					$title    = $row['title'];
					$details  = $row['details'];
					$pos      = $row['pos'];
					if (strlen($details) > 40) { $details = substr($details, 0, 37) . "..."; }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$details</td>\n";
					if ($n > 0) {
						print "		<td align=\"center\" width=\"18px\"><a href=\"training-checklist-list.php?id=$id&amp;move=up&amp;pos=$pos\"><b>&nbsp;&#9650&nbsp;</b></a></td>\n";
					} else {
						print "		<td align=\"center\" width=\"18px\">&nbsp;</td>\n";
					}
					if ($n < $last-1) {
						print "		<td align=\"center\" width=\"18px\"><a href=\"training-checklist-list.php?id=$id&amp;move=dn&amp;pos=$pos\"><b>&nbsp;&#9660&nbsp;</b></a></td>\n";
					} else {
						print "		<td align=\"center\" width=\"18px\">&nbsp;</td>\n";
					}
					print "<td align=\"center\"><a href=\"training-checklist-view.php?id=$id\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "<td align=\"center\"><a href=\"training-checklist-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "<td align=\"center\"><a href=\"training-checklist-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
					$n++;
				}
				print "<tr>\n";
				print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\"><b>No Checkboxes Found</b></p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"training-checklist-addedit.php\">Create New Checkbox</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
