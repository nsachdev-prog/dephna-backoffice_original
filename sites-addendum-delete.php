<?php
$page = "Sites";
$subpage = "Addendum";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">SITES H&amp;S ADDENDUM</p>\n";
		include('sites-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"heading\">Delete Addendum</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  			$sql = "SELECT a.adate, a.atitle, a.siteid, s.title
  							FROM sites_addendum AS a
								LEFT JOIN sites AS s ON a.siteid = s.ID
  							WHERE a.ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$adate    = $row['adate'];
					$atitle   = $row['atitle'];
					$sitename = $row['title'];
					list($y,$m,$d) = explode("-",$adate);
					$adate = "$d-$m-$y";
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Date:</b></td>\n";
					print "	<td>$adate</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Title:</b></td>\n";
					print "	<td>$atitle</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					$delete = $_REQUEST['delete'];
					if ($delete) {
						$sql = "DELETE FROM sites_addendum WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Addendum Successfully Deleted</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Adden: $atitle','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"sites-addendum-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"sites-addendum-list.php\">No DON'T Delete</a>";
						print "</p>\n";
					}

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Addendum Not Found!</b></span><br>" . $dbcnx->error;
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
		print "<p align=\"center\"><a href=\"sites-addendum-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
