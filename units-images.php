<?php
$page = "Sites";
$subpage = "Units";
$subpage = "Images";
$id = $_REQUEST['id'];
$unitid = $_REQUEST['unitid'];
$pic = $_REQUEST['pic'];
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		print "<p align=\"center\"class=\"heading\">SITES</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) && ($unitid)) {

				# get site title
				$sql = "SELECT title FROM sites WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				# get unit title and images
  				$sql = "SELECT title, images FROM units WHERE ID = '$unitid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title  = $row['title'];
					$images = $row['images'];
					if ($images) { $images = explode("|",$images); }

					if ($_FILES['image']['tmp_name']) {
						$allowed = array("jpg","jpeg","gif","png");
						$ext = strtolower(substr($_FILES['image']['name'],strrpos($_FILES['image']['name'],'.')+1));
						if (in_array($ext,$allowed) === FALSE) { $erimage = "The file is not an image"; $e++; }
						elseif (!getimagesize($_FILES['image']['tmp_name'])) { $erimage = "The file is not an image"; $e++; }
						elseif ($_FILES['image']['size'] > 200000) { $erimage = "The file is bigger than 200k"; $e++; }
						if (!$e) {
							$filename = date("YmdHis") . "." . $ext;
							if (!copy($_FILES['image']['tmp_name'], "$image_path/$filename")) {
								$resultMessage = "Uploaded image could not be saved<br>\n";
							} else {
								chmod("$image_path/$filename", 0666);
								$images[] = $filename;
								$now = time();
								$newimages = implode("|",$images);
								$dbcnx->query("UPDATE units SET images='$newimages' WHERE ID='$unitid'");
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitetitle - $title - Image','Units','$now')");
							}
						}
					}

					elseif ($_GET['delete']) {
						$filename = "$image_path/$images[$pic]";
						if (!unlink($filename)) {
							$resultMessage = "Image could not be deleted\n";
						} else {
							$newimages = array();
							for ($i=0; $i < count($images); $i++) {
								if ($i != $pic) {
									$newimages[] = $images[$i];
								}
							}
							$images = $newimages;
							$newimages = implode("|",$newimages);
							$now = time();
							$dbcnx->query("UPDATE units SET images='$newimages' WHERE ID='$unitid'");
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitetitle - $title - Image','Units','$now')");
						}
					}

					elseif ($_GET['default']) {
						$newimages = array();
						$newimages[0] = $images[$pic];
						for ($i=0; $i < count($images); $i++) {
							if ($i != $pic) {
								$newimages[] = $images[$i];
							}
						}
						$images = $newimages;
						$newimages = implode("|",$newimages);
						$dbcnx->query("UPDATE units SET images='$newimages' WHERE ID='$unitid'");
					}

				} else {
					$resultMessage = "<b>Unit Not Found</b><br />" . $dbcnx->error;
				}


				// print Submenu
				include('units-submenu.php');
				print "<p align=\"center\" class=\"larger\">Unit - $sitetitle - $title</p>\n";
				print "<p align=\"center\">$menustr</p>\n";

				if (!$resultMessage) {
					if ($images) {
						print "<table cellspacing=\"5\" align=\"center\">\n";
						print "	<tr>\n";
						for ($i=0; $i<count($images); $i++) {
							print "		<td align=\"left\"><a href=\"$image_path/$images[$i]\" target=\"_blank\"><img src=\"$image_path/$images[$i]\" height=\"100\"></a><br>\n";
							print "		<a href=\"units-images.php?delete=1&amp;id=$id&amp;unitid=$unitid&amp;pic=$i\">delete</a><br>\n";
							if ($i==0) {
								print "			default<br>\n";
							} else {
								print "			<a href=\"units-images.php?default=1&amp;id=$id&amp;unitid=$unitid&amp;pic=$i\">make default</a></td>\n";
							}
						}
						print "	</tr>\n";
						print "	<tr>\n";
						print "	</table>\n";
					} else {
						print "<p class=\"bodytext\" align=\"center\">No images have been uploaded for this unit.</p>\n";
					}
					print "<p>&nbsp;</p>\n";
					print "<p align=\"center\"><b>Add New Image</b>\n";
					print "<p align=\"center\">Your image must be jpg, jpeg, gif or png and no more than 200k<br />For best results it should be 400 pixels wide.\n";
					print "<form method=\"post\" enctype=\"multipart/form-data\" action=\"units-images.php\">\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
		  	 		print "<table align=\"center\" cellspacing=\"2\">\n";
					print "	<tr>\n";
					print "		<td>Image: </td>\n";
					if ($erimage) { $class = "element_error"; } else { $class = "element"; }
					print "		<td><input name=\"image\" type=\"file\" class=\"$class\" size=\"30\" /></td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Upload\" /></td>\n";
					print "	</tr>\n";
					print "</table>\n";
					print "</form>\n";
				}

			} else {
				$resultMessage = "<b>Page called without site or unit id</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"units-view.php?id=$id\">Units</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
