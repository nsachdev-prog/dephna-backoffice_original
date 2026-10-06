<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Reports";
$subpage = "Parts";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\">$submenustr</p>\n";
            print "<p align=\"center\" class=\"heading\">Parts</p>\n";

            $sql = "SELECT * FROM `cr_reports_parts` ORDER BY `group`, `part`";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                $thisgroup = "";
                while ($row = $result->fetch_assoc()) {
                    if ($thisgroup != $row['group']) {
                        if ($thisgroup) {
                            print "<tr>\n";
                            print "	<td colspan=\"5\" style=\"border-top:1px solid #666\">&nbsp;</td>\n";
                            print "</tr>\n";
                        }
                        $thisgroup = $row['group'];
                        print "<tr>\n";
                        print "	<td colspan=\"5\" class=\"larger\">$thisgroup</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #666\"><b>Part</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\"><b>AFCost</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\"><b>DephnaCost</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\" width=\"30\">&nbsp;</td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\" width=\"30\">&nbsp;</td>\n";
                        print "</tr>\n";
                    }
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>{$row['part']}</td>\n";
                    print "	<td>{$row['afcost']}</td>\n";
                    print "	<td>{$row['dephnacost']}</td>\n";
                    print "	<td><a href=\"coldroom-reports-parts-addedit.php?id={$row['ID']}\">Edit</a></td>\n";
					print "	<td><a href=\"coldroom-reports-parts-delete.php?id={$row['ID']}\">Delete</a></td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "	<td colspan=\"5\" style=\"border-top:1px solid #666\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
            } else {
                $resultMessage .= "No Parts Found<br>";
            }

            print "<p align=\"center\"><a href=\"coldroom-reports-parts-addedit.php\">Add New Part</a></p>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
