<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];
			$id      = $_REQUEST['id'];
            $uid     = $_REQUEST['uid'];
            $delete  = $_REQUEST['delete'];

			if ($custref AND $id AND $uid) {

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				// get customer
				$sql = "SELECT compname
						FROM users
						WHERE custref = '$custref'
				";
				$result = $dbcnx->query($sql);
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
                }
                
                // get unit
                $sql = "SELECT unitname FROM inventory_units WHERE ID = '$uid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
                    $row = $result->fetch_assoc();
                    $unitname = $row['unitname'];
                }
                
                print "<p align=\"center\" class=\"larger\">Delete Unit</p>\n";
                print "<p align=\"center\" class=\"larger\">$compname</p>\n";
                print "<p align=\"center\" class=\"larger\">$unitname</p>\n";
			
				if ($delete) {

                    // delete images
                    $sql = "SELECT * FROM `inventory_images` WHERE `uid` = '$uid'";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                        while ($row = $result->fetch_assoc()) {
                            $image = $row['image'];
                            $filename = "../../dis2_db/img/inv/$image";
                            if (!unlink($filename)) {
                                $resultMessage .= "Image $image Could NOT Be Deleted.<br />\n";
                            } else {
                                $resultMessage .= "Image $image Deleted.<br />\n";
                            }
                        }
                    } else {
                        $resultMessage .= "No Images to Delete<br />\n";
                    }

                    // delete image records
                    $sql = "DELETE FROM `inventory_images` WHERE `uid` = '$uid'";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "Image Records Deleted<br />\n";
                    } else {
                        $resultMessage .= "Image Records Could NOT Be Deleted<br />\n";
                    }

                    // delete meters
                    $sql = "DELETE FROM `inventory_meters` WHERE `uid` = '$uid'";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "Meter Records Deleted<br />\n";
                    } else {
                        $resultMessage .= "Meter Records Could NOT Be Deleted<br />\n";
                    }

                    $sql = "DELETE FROM `inventory_units` WHERE `ID` = '$uid'";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "Unit Record Deleted<br />\n";
                        $dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Deleted Unit $unitname')");
                    } else {
                        $resultMessage .= "Unit Record Could NOT Be Deleted<br />\n";
                    }

                } else {
                    print "<p align=\"center\"><b>Are You Sure?</b></p>";
                    print "<p align=\"center\">";
                    print "	<a href=\"inventory-units-delete.php?custref=$custref&amp;id=$id&amp;uid=$uid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"inventory-units.php?custref=$custref&amp;id=$id\">No DON'T Delete</a>";
                    print "</p>\n";
                }

			} else {
				$resultMessage .= "Page called witout custref or id!<br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br />" . $dbcnx->connect_error . "<br />";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-units.php?custref=$custref&amp;id=$id\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
