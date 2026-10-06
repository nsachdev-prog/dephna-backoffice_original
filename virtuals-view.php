<?php
$page = "Virtuals";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MAINTAIN VIRTUAL PACKAGES</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM virtuals ORDER BY ID";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Title</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Standard Rate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Notes</b> (First 50 Chars)</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id     = $row['ID'];
					$title  = $row['title'];
					$rate   = $row['rate'];
					$notes  = $row['notes'];
					if ($notes) {
						$notes = stripslashes($notes);
						if (strlen($notes) > 50) { $notes = substr($notes, 0, 50) . "..."; }
					} else {
						$notes = "";
					}
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>" . number_format($rate,2) . "</td>\n";
					print "	<td>$notes</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"virtuals-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"virtuals-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"5\">&nbsp;</td>\n";
                print "</tr>\n";

				print "</table>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"virtuals-addedit.php\">Add New Virtual Office</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Virtual Office</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

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
