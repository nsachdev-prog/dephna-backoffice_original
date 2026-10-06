<?php
$page = "Utilities";
$subpage = "Import Failures";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES IMPORT FSAILURES</p>\n";
	
	if ($accessRights[$page]) {
		include('utilities-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\">Not Written Yet</p>\n";
		
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
