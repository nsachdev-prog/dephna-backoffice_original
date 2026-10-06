<?php
$page = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">UNIT TYPES</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM types ORDER BY title";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				Print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				Print "<tr>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Type</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>VAT</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
				Print "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$id     = $row['ID'];
					$title  = $row['title'];
					$vat    = $row['vat'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$vat</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"types-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"types-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"4\">&nbsp;</td>\n";
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
		print "<p align=\"center\"><a href=\"types-addedit.php\">Add Unit Type</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
