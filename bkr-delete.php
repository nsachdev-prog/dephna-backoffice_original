<?php
$page = "BKManager";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Rename Customer</p>\n";

		$bkrid = $_REQUEST['bkrid'];

		if ($bkrid) {

			include('bkr-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Delete</p>\n";

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT *
                        FROM bk_rename
                        WHERE ID = '$bkrid'
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $custid     = $row['custid'];
                    $custref    = $row['custref'];
                    $compname   = $row['compname'];
                    $newcompname= $row['newcompname'];
                    $changedate = $row['changedate'];
                    $changedate = date('d-m-Y', strtotime($changedate));

                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";

                    print "<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
                    print "<tr><td align=\"right\"><b>Customer Ref:</b></td><td>DP$custref</td></tr>\n";
                    print "<tr><td align=\"right\"><b>New Company Name:</b></td><td>$newcompname</td></tr>\n";
                    print "<tr><td align=\"right\"><b>Change Date:</b></td><td>$changedate</td></tr>\n";

                    print "</table>\n";
				
					$delete = $_GET['delete'];
					$now = time();

					print "<p align=\"center\"><b>$title</b></p>\n";

					if ($delete) {

						$sql = "DELETE FROM bk_rename WHERE ID = '$bkrid'";
						$result = $dbcnx->query($sql);
						if ($result) {
                            $resultMessage .= "<b>Record Deleted</b><br />";
                            $now = time();
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Rename $compname','$page','$now')");
                        } else {
                            $resultMessage = "Could NOT Delete Header<br />" . $dbcnx->error;
                        }

					} else {

						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\"><a href=\"bkr-delete.php?bkrid=$bkrid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bk-manager.php\">No DON'T Delete</a></p>\n";
						
					}
				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
