<?php
$page = "Coldroom_Reports";
$subpage = "Unapproved";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

        if ($homePageSections['ColdRoomReports'] == "on") {

            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
                include('coldroom-reports-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
                print "<p align=\"center\">$submenustr</p>\n";
                print "<p align=\"center\" class=\"larger\">Unapproved</p>\n";

                $sql = "SELECT r.*, s.title AS sitename
                        FROM cr_reports_headers AS r
                        LEFT JOIN sites AS s ON r.siteid = s.ID
                        WHERE r.apprdate = '0'
                        ORDER BY r.crdate DESC
                ";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    print "<table width=\"70%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
                    print "<tr>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
                    print "</tr>\n";
                    while ($row = $result->fetch_assoc()) {
                        $reportid = $row['ID'];
                        $siteid   = $row['siteid'];
                        $unitid   = $row['unitid'];
                        $sitename = $row['sitename'];
                        $unitname = $row['unitname'];
                        $crdate   = $row['crdate'];
                        $crby     = $row['crby'];
                        $crdate   = date('d-m-Y H:i', $crdate);
                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	<td>$crdate</td>\n";
                        print "	<td>$crby</td>\n";
                        print "	<td>$sitename</td>\n";
                        print "	<td>$unitname</td>\n";
                        print "	<td><a href=\"coldroom-reports-view.php?id=$reportid&amp;siteid=$siteid&amp;unitid=$unitid\">View</a></td>\n";
                        print "</tr>\n";
                    }
                    print "<tr>\n";
                    print " <td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                } else {
                    print "<p align=\"center\">No Reports Awaiting Approval.</p>\n";
                }

                $dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "<span class=\"redtext\"><b>Access Denied! (Homepage Rules)</b></span>";
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
