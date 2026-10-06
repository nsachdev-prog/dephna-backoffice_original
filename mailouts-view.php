<?php
$page = "Mailouts";
$subpage = "Notices";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MAILOUTS</p>\n";
	if ($accessRights[$page]) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$sql = "SELECT ID, mref, site, title FROM mailouts ORDER BY mref, title";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				Print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				Print "<tr>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\"><b>Mailout Title</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				Print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td align=\"left\">{$row['mref']}</td>\n";
					print "	<td align=\"left\">{$row['site']}</td>\n";
					print "	<td align=\"left\">{$row['title']}</td>\n";
					print "	<td align=\"center\"><a href=\"mailouts-test.php?id={$row['ID']}\">Test</a></td>\n";
					print "	<td align=\"center\"><a href=\"mailouts-addedit.php?id={$row['ID']}\">Edit</a></td>\n";
					print "	<td align=\"center\"><a href=\"mailouts-select.php?id={$row['ID']}\">Send</a></td>\n";
					print "	<td align=\"center\"><a href=\"mailouts-delete.php?id={$row['ID']}\">Delete</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No mailouts found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"mailouts-addedit.php\">Add Mailout</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include("footer.php");
?>
