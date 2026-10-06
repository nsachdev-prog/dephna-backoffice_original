<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Fitouts";
$subpage = "Templates";
$subsubpage = "List";
include("settings.php");
include("session.php");
include("header.php");

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
    include('fitouts-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
    
    print "<p align=\"center\" class=\"larger\">Templates</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT ID, tref, title FROM fitouts_templates ORDER BY tref, title";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Ref</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">Template</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\"></td>\n";
				print "</tr>\n";

                // row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
                    $id    = $row['ID'];
                    $tref  = $row['tref'];
                    $title = $row['title'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td align=\"left\">$tref</td>\n";
					print "	<td align=\"left\">$title</td>\n";
                    if ($accessRights[$page] > 1) {
					    print "	<td align=\"center\"><a href=\"fitouts-templates-addedit.php?id=$id\">Edit</a></td>\n";
                    } else {
                        print "	<td><span class=\"noAccess\">Edit</span></td>\n";
                    }
                    if ($accessRights[$page] > 2) {
					    print "	<td align=\"center\"><a href=\"fitouts-templates-delete.php?id=$id\">Delete</a></td>\n";
                    } else {
                        print "	<td><span class=\"noAccess\">Delete</span></td>\n";
                    }
					print "</tr>\n";
				}

				print "<tr>\n";
				print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">No Templates Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-templates-addedit.php\">Add Template</a></p>\n";
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
