<?php
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

print "<p class=\"heading\" align=\"center\">FORGOT PASSWORD</p>\n";
print "<p>&nbsp;</p>\n";
print "<p align=\"center\">Please contact admin to have your password changed</p>\n";

if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"index.php\">Return to Login</a></p>\n";
print "</td>\n";
include('footer.php');
?>
