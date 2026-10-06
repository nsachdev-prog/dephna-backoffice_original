<?php
$page = "Home";
$subpage = "Brochures";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align = \"center\" class=\"heading\">BROCHURES</p>\n";

	print "<table align=\"center\" cellpadding=\"4px\">\n";

	foreach(glob('brochures/*.pdf') as $file) {
		list($path,$brochure) = explode("/",$file);
		print "	<tr>\n";
		print "		<td><a href=\"$file\" target=\"_blank\">$brochure</a></td>\n";
		print "	</tr>\n";
	}
	print "</table>\n";

	print "<p align=\"center\"><a href=\"index.php\">Return</a></p>\n";

} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
