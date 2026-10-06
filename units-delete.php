<?php
$page = "Sites";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {
		
		$id = $_GET['id'];
		$delete = $_GET['delete'];

		print "<p align=\"center\"class=\"heading\">SITES</p>\n";
		
		print "<p align = \"center\" class=\"larger\">Delete Unit</p>\n";

		if ($id) {
			
			# open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT siteid, title, images FROM units WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$siteid    = $row['siteid'];
					$unittitle = $row['title'];
					$images    = $row['images'];
					
					$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$sitetitle = $row['title'];
						
						print "<p align=\"center\"class=\"larger\">Unit - $sitetitle - $unittitle</p>\n";
						
						# check if unit is in use in bookings
						$sql = "SELECT custid FROM bookings WHERE unitid = '$id'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$custref = $row['custid'];
							$resultMessage .= "<b>Cannot Delete:</b> Booking exists for DP$custref<br />\n";
							$booked++;
						}
						
						# check if unit is in use in bk manager
						$sql = "SELECT ID FROM bkm_units WHERE unitid = '$id'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							$resultMessage .= "<b>Cannot Delete:</b> Booking exists in BK Manager<br />\n";
							$booked++;
						}
						
						if (!$booked) {
							if ($delete) {
								if ($images) { 
									$images = explode("|",$images);
									foreach($images AS $key => $value) {
										$filename = "$image_path/$value";
										if (!unlink($filename)) {
											$resultMessage .= "Image $value could not be deleted<br />\n";
										} else {
											$resultMessage .= "Image $value deleted<br />\n";
										}
									}
								}
								$sql = "DELETE FROM units WHERE ID='$id'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage .= "Unit Successfuly Deleted";
									$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitetitle - $unittitle','Units','$now')");
								} else {
									$resultMessage .= "Could not delete unit<br />" . $dbcnx->error;
								}
							} else {
								print "<p align=\"center\"><a href=\"units-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"units-view.php?id=$siteid\">No DON'T Delete</a></p>\n";
							}
						}
					} else {
						$resultMessage = "Could not get site record from database<br />" . $dbcnx->error;
					}			
				} else {
					$resultMessage = "Could not get unit record from database<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without unit id</b>";
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}	
		print "<p align=\"center\"><a href=\"sites-list.php\">Sites</a>&nbsp;|&nbsp;<a href=\"units-view.php?id=$siteid\">Units</a></p>\n";
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
