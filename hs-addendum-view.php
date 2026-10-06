<?php
$page ="Health_&_Safety";
$subpage = "Addendum";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">HEALTH &amp; SAFETY</p>\n";
		include('hs-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
			
  			$sql = "SELECT a.adate, a.atitle, a.siteid, a.details, s.title
  							FROM sites_addendum AS a
								LEFT JOIN sites AS s ON a.siteid = s.ID
  							WHERE a.ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$adate    = $row['adate'];
					$atitle   = $row['atitle'];
					$sitename = $row['title'];
					$details  = $row['details'];
					list($y,$m,$d) = explode("-",$adate);
					$adate = "$d-$m-$y";
					
					print "<table align=\"center\" width=\"500\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
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
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Details:</b></td>\n";
					print "	<td>$details</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					if ($accessRights[$page] > 1) {
						print "<p align=\"center\"><a href=\"hs-addendum-addedit.php?id=$id\">Edit Addendum</a></p>\n";
					}
					
				} else {
					$resultMessage = "<b>Addendum not found!</b>";
				}
			} else {
				$resultMessage = "<b>Page called without id</b>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"hs-addendum-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
