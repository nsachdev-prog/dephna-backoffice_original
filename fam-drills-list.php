<?php
$page = "Fire_Alarms";
$subpage = "Drills";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

        print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";

        include('fam-submenu.php');
        print "<p align=\"center\">$menustr</p>\n";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

            // visible toggle
            if ($_REQUEST['id'] AND $_REQUEST['v'] !== "") {
                $id = $_REQUEST['id'];
                $v = $_REQUEST['v'];
                $sql = "UPDATE fam_drills SET visible=$v WHERE ID=$id";
                $dbcnx->query($sql);
            }

            print "<p align=\"center\" class=\"larger\">Scheduled Evacuation Drills</p>\n";
            // get list of scheduled drills
            $sql = "SELECT d.*, s.title AS sitename
                    FROM fam_drills_scheduled AS d
                    LEFT JOIN sites AS s ON d.siteid=s.ID
                    WHERE d.date >= CURDATE()
                    ORDER BY d.date, d.timefr
            ";
            $result = $dbcnx->query($sql);
            if ($result->num_rows) {
                print "<table align=\"center\" cellpadding=\"5\" cellspacing=\"0\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Sched By</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                while ($row = $result->fetch_assoc()) {
                    $id = $row['ID'];
                    $date = $row['date'];
                    $timefr = $row['timefr'];
                    $timeto = $row['timeto'];
                    $site = $row['sitename'];
                    $updby = $row['updby'];
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$date</td>\n";
                    print "<td>$site</td>\n";
                    print "<td>$timefr - $timeto</td>\n";
                    print "<td>$updby</td>\n";
                    print "<td><a href=\"fam-drills-schedule-delete.php?id=$id\">Delete</a></td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
            } else {
                print "<p align=\"center\">No drills scheduled</p>\n";
            }
            print "<p align=\"center\"><a href=\"fam-drills-schedule.php\">Schedule a New Drill</a></p>\n";

            print "<p align=\"center\" class=\"larger\">Completed Evacuation Drills</p>\n";
            // get list of drills
            $sql = "SELECT d.*, s.title AS sitename
                    FROM fam_drills AS d
                    LEFT JOIN sites AS s ON d.siteid=s.ID
                    ORDER BY d.date, d.time DESC
            ";
            $result = $dbcnx->query($sql);
            if ($result->num_rows) {
                print "<table align=\"center\" cellpadding=\"5\" cellspacing=\"0\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Signed By</b></td>\n";
                print("<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n");
                print "<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                print "<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                print "<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                while ($row = $result->fetch_assoc()) {
                    $id = $row['ID'];
                    $date = $row['date'];
                    $time = $row['time'];
                    $site = $row['sitename'];
                    $updby = $row['updby'];
                    $visible = $row['visible'];
                    // visible toggle
                    if ($visible) {
                        $visible = "<a href=\"fam-drills-list.php?id=$id&v=0\"><span style=\"color:green\">Visible</span></a>";
                    } else {
                        $visible = "<a href=\"fam-drills-list.php?id=$id&v=1\"><span class=\"redtext\">Hidden</span></a>";
                    }
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$date $time</td>\n";
                    print "<td>$site</td>\n";
                    print "<td>$updby</td>\n";
                    print "<td>$visible</td>\n";
                    print "<td><a href=\"fam-drills-pdf.php?id=$id\" target=\"_blank\">View</a></td>\n";
                    print "<td><a href=\"fam-drills-addedit.php?id=$id\">Edit</a></td>\n";
                    print "<td><a href=\"fam-drills-delete.php?id=$id\">Delete</a></td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
            } else {
                print "<p align=\"center\">No drills found</p>\n";
            }

            print "<p align=\"center\"><a href=\"fam-drills-addedit.php\">Add a New Completed Drill</a></p>\n";

            $dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>