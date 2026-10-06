<?php
$page = "Inventory";
$subpage = "Inventory";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Delete</p>\n";
			
            $custref = $_REQUEST['custref'];
            $id      = $_REQUEST['id'];
            $delete  = $_REQUEST['delete'];
			if ($custref AND $id) {
				
                // get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
                    $compname = $row['compname'];
                    
                    // get units
                    $sql = "SELECT ID, unitname FROM inventory_units WHERE iid = '$id'";
                    $result = $dbcnx->query($sql);
                    if ($result->num_rows) {
                        while ($row = $result->fetch_assoc()) {
                            $uid      = $row['ID'];
                            $units[]  = $row['unitname'];
                        }
                        $units = implode(", ",$units);
                    } else {
                        $units = "No Units Found!";
                    }

                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";
                    print "<p align=\"center\" class=\"larger\">$units</p>\n";
					
					if ($delete) {

                        // delete images
                        $sql = "SELECT * FROM inventory_images WHERE iid='$id'";
                        if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                            while ($row = $result->fetch_assoc()) {
                                $image = $row['image'];
                                if ($image) {
                                    $filename = "../../dis2_db/img/inv/$image";
                                    if (!unlink($filename)) {
                                        $resultMessage .= "Image $image Could NOT Be Deleted.<br />\n";
                                    } else {
                                        $resultMessage .= "Image $image Deleted.<br />\n";
                                    }
                                }
                            }
                        } else {
                            $resultMessage .= "No Images to Delete<br />\n";
                        }

                        // delete image records
                        $sql = "DELETE FROM inventory_images WHERE iid='$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Image Records Deleted<br />\n";
                        } else {
                            $resultMessage .= "Image Records Could NOT Be Deleted<br />\n";
                        }

                        // delete meters
                        $sql = "DELETE FROM inventory_meters WHERE iid='$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Meter Records Deleted<br />\n";
                        } else {
                            $resultMessage .= "Meter Records Could NOT Be Deleted<br />\n";
                        }

                        $sql = "DELETE FROM inventory_units WHERE iid='$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Unit Records Deleted<br />\n";
                        } else {
                            $resultMessage .= "Unit Records Could NOT Be Deleted<br />\n";
                        }

                        $sql = "DELETE FROM inventory WHERE ID='$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Listing Deleted<br />\n";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref $units','$page','$now')");
                        } else {
                            $resultMessage .= "Listing Could NOT Be Deleted<br />\n";
                        }

                    } else {
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"inventory-delete.php?custref=$custref&amp;id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"inventory-list.php\">No DON'T Delete</a>";
                        print "</p>\n";
                    }
					
				} else {
					$resultMessage = "Inventory Not Found!<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without ids<br />";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
