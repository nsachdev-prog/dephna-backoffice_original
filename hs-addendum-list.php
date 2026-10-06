<?php
$page = "Health_&_Safety";
$subpage = "Addendum";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align = \"center\" class=\"heading\">HEALTH &amp; SAFETY</p>\n";
	
	if ($accessRights[$page]) {
		
		include('hs-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
	
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$sql = "SELECT a.ID, a.adate, a.siteid, a.atitle, s.title
							FROM sites_addendum AS a
							LEFT JOIN sites AS s ON a.siteid = s.ID
							ORDER BY a.adate
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
			
				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Date</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Site</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Title</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$id       = $row['ID'];
					$adate    = $row['adate'];
					$siteid   = $row['siteid'];
					$atitle   = $row['atitle'];
					$sitename = $row['title'];
					list($y,$m,$d) = explode("-",$adate);
					$adate = "$d-$m-$y";
					
					# row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$adate</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$atitle</td>\n";
					print "	<td><a href=\"hs-addendum-view.php?id=$id\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"hs-addendum-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"hs-addendum-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";							
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print "<td style=\"border-top:1px solid #999999\" colspan=\"6\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"hs-addendum-addedit.php\">Add Addendum</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
