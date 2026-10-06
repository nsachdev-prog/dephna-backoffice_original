<?php
$page = "Fitouts";
$subpage = "Items";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Delete Item</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  				$sql = "SELECT title FROM fitouts_items WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title    = $row['title'];
					
					print "<p align = \"center\" class=\"heading\">$title</p>\n";
					
					# check if point is being used in quotes
					$sql = "SELECT ID FROM fitouts_quotes_items WHERE itmid='$id'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						print "<p align=\"center\"><b>Cannot Delete<br>$title</b><br>Still in use in quotes.</p>";
					} else {
						$delete = $_REQUEST['delete'];
						if ($delete) {

                            // delete images
                            $sql = "SELECT * FROM fitouts_items_images WHERE itmid='$id'";
                            if (($iresult = $dbcnx->query($sql)) && ($iresult->num_rows)) {
                                while ($irow = $iresult->fetch_assoc()) {
                                    $image = $irow['image'];
                                    $filename = "$image_path/$image";
                                    if (!unlink($filename)) {
                                        $resultMessage .= "Image $image could not be deleted.<br />\n";
                                    } else {
                                        $resultMessage .= "Image $image deleted.<br />\n";
                                    }
                                }
                                # delete image records
                                $sql = "DELETE FROM fitouts_items_images WHERE itmid='$id'";
                                $result = $dbcnx->query($sql);
                                if ($result) {
                                    $resultMessage .= "Image records deleted.<br />\n";
                                } else {
                                    $resultMessage .= "Image records could not be deleted.<br />\n";
                                }
                            } else {
                                $resultMessage .= "No Images to delete.<br />\n";
                            }

							$sql = "DELETE FROM fitouts_items WHERE ID='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "<b>Fitout Item Successfully Deleted</b>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Point: $title','$page','$now')");
							} else {
								$resultMessage .= "<b>Fitout Item Could NOT Be Deleted</b><br>" . $dbcnx->error;
							}
						} else {
							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"fitouts-items-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"fitouts-items-view.php?id=$id\">No DON'T Delete</a>";
							print "</p>\n";
						}
					}

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Item Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-items-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
