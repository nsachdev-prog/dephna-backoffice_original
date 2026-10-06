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

        if ($_REQUEST['id']) {
            $id = $_REQUEST['id'];
        }
        print "<p align = \"center\" class=\"larger\">Delete Evacuation Drill</p>\n";
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

            // get drill
            $sql = "SELECT d.*, s.title AS sitename
                    FROM fam_drills AS d
                    LEFT JOIN sites AS s ON d.siteid=s.ID
                    WHERE d.ID = '$id'
            ";
            $result = $dbcnx->query($sql);
            if ($result->num_rows) {
                $row = $result->fetch_assoc();
                $date = $row['date'];
                $time = $row['time'];
                $site = $row['sitename'];
                $siteid = $row['siteid'];
                print "<p align=\"center\" class=\"larger\">$site</p>\n";
                print "<p align=\"center\" class=\"larger\">$date $time</p>\n";

                if ($_REQUEST['delete']) {
                    $sql = "DELETE FROM fam_drills_lines WHERE drillid = '$id'";
                    if ($dbcnx->query($sql)) {
                        $resultMessage .= "Drill Items Deleted<br>";
                        $sql = "DELETE FROM fam_drills WHERE ID = '$id'";
                        if ($dbcnx->query($sql)) {
                            $resultMessage .= "Drill Deleted<br>\n";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Drill $date $time','$page','$now')");
                        } else {
                            $resultMessage .= "Drill NOT Deleted<br>" . $dbcnx->error . "<br>\n";
                        }

                    } else {
                        $resultMessage .= "Drill NOT Deleted<br>" . $dbcnx->error . "<br>\n";
                    }
                } else {
                    print "<p align=\"center\"><b>Are you sure you want to delete this drill?</b></p>\n";
                    print "<p align=\"center\"><a href=\"fam-drills-delete.php?id=$id&delete=1\">Yes Delete</a> | <a href=\"fam-drills-list.php\">No DON'T Delete</a></p>\n";
                }
            } else {
                $resultMessage = "Drill not found!";
            }

		} else {
			$resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
        print "<p align=\"center\"><a href=\"fam-drills-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>