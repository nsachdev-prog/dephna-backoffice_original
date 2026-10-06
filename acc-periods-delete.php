<?php
$page = "Accounts";
$subpage = "Delete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		$id = $_REQUEST['id'];
		$delete = $_REQUEST['delete'];

		if ($id) {

			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";
				print "<p align=\"center\" class=\"larger\">Delete Period</p>\n";

                $sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start    = $row['period_start'];
                    $period_end      = $row['period_end'];
					list($sy,$sm,$sd) = explode("-",$period_start);
					list($ey,$em,$ed) = explode("-",$period_end);
					
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td>Period Start</td>\n";
					print "<td>$sd-$sm-$sy</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Period End</td>\n";
					print "<td>$ed-$em-$ey</td>\n";
					print "</tr>\n";
					print "</table>\n";

                    if ($delete) {

                        // delete period
                        $sql = "DELETE FROM acc_periods WHERE ID = '$id'";
                        if ($dbcnx->query($sql)) {
							$rdb .= $sql.";\n";
							$resultMessage .= "Period Deleted.<br>";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$period_start to $period_end','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\">Could NOT delete period.</span><br>" . $dbcnx->error;
						}
						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

                    } else {
                        print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                        print "<p class=\"centered\">";
                        print "	<a href=\"acc-periods-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"acc-periods-list.php\">No DON'T Delete</a>";
                        print "</p>\n";
                    }

                } else {
                    $resultMessage = "<b>Period Not Found!</b><br>";
                }
 
				$dbcnx->close();

			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without id</b>";
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"acc-periods-list.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
