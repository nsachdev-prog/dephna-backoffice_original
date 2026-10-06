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
            print "<p align=\"center\" class=\"heading\">Delete Part</p>\n";

            if ($_REQUEST['id']) {
                $id = $_REQUEST['id'];
                $sql = "SELECT * FROM `cr_reports_parts` WHERE `ID` = $id";
                if ($result = $dbcnx->query($sql)) {
                    if ($result->num_rows) {
                        $row = $result->fetch_assoc();
                        $part = $row['part'];
                        $group = $row['group'];
                        $afcost = $row['afcost'];
                        $dephnacost = $row['dephnacost'];
                        
                        $delete = $_REQUEST['delete'];
                        if ($delete) {
                            $now = time();
                            $sql = "DELETE FROM `cr_reports_parts` WHERE `ID` = $id";
                            if ($result = $dbcnx->query($sql)) {
                                $resultMessage .= "Part Deleted<br>";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','CR Part ID:$id','$page','$now')");
                            } else {
                                $resultMessage .= "Part Could NOT Be Deleted<br>" . $dbcnx->error . "<br>";
                            }
                        } else {
                            print "<p align=\"center\"><b>Are you sure you want to delete this part?</b></p>\n";
                            print "<p align=\"center\" class=\"larger\">$part</p>\n";
                            print "<p align=\"center\"><a href=\"coldroom-reports-parts-delete.php?id=$id&delete=1\">Yes Delete</a> | <a href=\"coldroom-reports-parts-list.php\">No DON'T Delete</a></p>\n";
                        }

                    } else {
                        $resultMessage = "Part Not Found";
                    }
                } else {
                    $resultMessage = "Error: " . $dbcnx->error;
                }
            } else {
                $resultMessage = "Part Not Found";
            }


            $dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
        print "<p align=\"center\"><a href=\"coldroom-reports-parts-list.php\">Return</a></p>\n";
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