<?php
$page = "Reporting";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		print "<p align=\"center\" class=\"heading\">PERFORMANCE REPORTS</p>\n";
		print "<p align=\"center\" class=\"heading\">&nbsp;</p>\n";
		print "<p align=\"center\" class=\"heading\"><a href=\"reports-revenue.php\">Reports By Revenue</a></p>\n";
		print "<p align=\"center\" class=\"heading\"><a href=\"reports-space.php?ty=All\">Reports By Space</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
