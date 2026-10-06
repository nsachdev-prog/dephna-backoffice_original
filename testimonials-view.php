<?php
$page = "Testimonials";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p class=\"heading\" align=\"center\">TESTIMONIALS</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM testimonials ORDER BY thisdate";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				Print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				Print "<tr>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Title</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Section</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Visible</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
				Print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id = $row['ID'];
					$thisdate  = $row['thisdate'];
					$title     = $row['title'];
					$section   = $row['section'];
					$visible   = $row['visible'];
					$thisdate  = date("d/m/y",$thisdate);

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$thisdate</td>\n";
					print "	<td>$title</td>\n";
					print "	<td>$section</td>\n";
					print "	<td>$visible</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"testimonials-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"testimonials-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"6\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"testimonials-addedit.php\">Add New Testimonial</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
