<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Reports";
$subpage = "Delete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
	
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
	
			$id     = $_REQUEST['id']; 
			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];

			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Delete Service Report</p>\n";

			if ($id AND $siteid AND $unitid) {

				// get sitename
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				// get unitname
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
					list($junk,$unitname) = explode(" ",$unitname);
				} else {
					$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
				}

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

                // get report header
                $sql = "SELECT * FROM cr_reports_headers WHERE ID = '$id'";
                if (($result = $dbcnx->query($sql) AND $result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $reportno  = $row['reportno'];
                    $type      = $row['type'];
                    $sdate     = $row['sdate'];
					$image     = $row['image'];
                    list($y,$m,$d) = explode("-",$sdate);
                } else {
                    $resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
                }

                print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";

                print "<tr>\n";
                print "	<td align=\"right\"><b>Report Number</b></td>\n";
                print "	<td>$reportno</td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td align=\"right\"><b>Report Type</b></td>\n";
                print "	<td>$type</td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td align=\"right\"><b>Date</b></td>\n";
                print "	<td>$d-$m-$y</td>\n";
                print "</tr>\n";

                print "</table>\n";
		
				if ($_REQUEST['delete']) {

                    $now = time();

					if ($image) {
						if (is_file("../../dis2_db/img/crr/$image")) {
							if (unlink("../../dis2_db/img/crr/$image")) {
								$resultMessage .= "Image $image Deleted<br>";
							} else {
								$resultMessage .= "Could NOT Delete $image<br>";
							}
						} else {
							$resultMessage .= "Image $image NOT Found<br>";
						}
					}

                    $sql = "DELETE FROM cr_reports_lines WHERE hid = '$id'";
                    if ($dbcnx->query($sql)) {
                        $resultMessage .= "Report Lines Deleted<br>";
                        $sql = "DELETE FROM cr_reports_headers WHERE ID = '$id'";
                        if ($dbcnx->query($sql)) {
                            $resultMessage .= "Report Header Deleted<br>";
							$sql = "DELETE FROM cr_reports_notes WHERE hid = '$id'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Report Notes Deleted<br>";
							}
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','ID:$id Siteid:$siteid Unit:$unitname','$page','$now')");
                        } else {
                            $resultMessage .= "Report Header Could NOT Be Deleted<br>" . $dbcnx->error;
                        }
                    } else {
                        $resultMessage .= "Report Lines Could NOT Be Deleted<br>" . $dbcnx->error;
                    }    
					
				} else {
	
                    print "<p align=\"center\"><b>Are You Sure?</b></p>";
                    print "<p align=\"center\"><a href=\"coldroom-reports-delete.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"coldroom-reports-list.php?siteid=$siteid&amp;unitid=$unitid\">No DON'T Delete</a></p>";
        
				}
				$dbcnx->close();

			} else {
				$resultMessage .= "Page called without either id, siteid or unitid";
			}

		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldroom-reports-list.php?siteid=$siteid&amp;unitid=$unitid\">List</a> | ";
        print "<a href=\"coldroom-reports-sites.php?siteid=$siteid\">Site</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
?>
