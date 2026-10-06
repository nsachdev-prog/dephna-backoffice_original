<?php
$page = "Utilities";
$subpage = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
    include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
    print "<p align=\"center\" class=\"larger\">Meter Types</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM UtlTypes ORDER BY commodity, title";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Title</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Registers Pdf</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
                print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$title     = $row['title'];
					$commodity = $row['commodity'];
					$pdf       = $row['pdf'];
					$pdffilename = $row['pdffilename'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$commodity</td>\n";
					print "	<td>$id</td>\n";
					if ($pdf) {
						print "<td><a href=\"getpdf.php?pdf=$pdffilename&amp;file=$pdf\" target=\"_blank\">$pdffilename</a></td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
                    print "	<td align=\"center\"><a href=\"utilities-types-addedit.php?id=$id\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"utilities-types-addedit.php?id=$id&amp;update=1\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"utilities-types-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"7\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"utilities-types-addedit.php?update=1\">Add New Meter Type</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Meter Type</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
    if ($resultMessage) {
        print "<p align=\"center\">$resultMessage</p>\n";
    }
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
