<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
// set max execution time to 1 minutes
set_time_limit(60);

print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";

		$custref = $_REQUEST['custref'];
		$id      = $_REQUEST['id'];
		$uid     = $_REQUEST['uid'];
		$iid     = $_REQUEST['iid'];
		$code    = $_REQUEST['code'];
		$now = time();
		$filepath = "../../dis2_db/img/inv";
		
		if ($custref AND $id AND $uid AND $code) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
				
				// get inventory type
				$sql = "SELECT * FROM inventory WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$inout   = $row['inout'];
				}

				// get unitname
				$sql = "SELECT `unitname`, `unittype`, `join` FROM `inventory_units` WHERE `ID` = '$uid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['unitname'];
					$unittype = $row['unittype'];
					$join     = $row['join'];
				} else {
					$resultMessage .= "Could Not Get Unit Details<br />" . $dbcnx->error . "<br />";
				}

				// load image requirements
				$imagesIndex = array();
				$lines = file('inventory-images.csv', FILE_IGNORE_NEW_LINES);
				foreach ($lines as $line)
				{
					list(
						$tunittype,
						$tcode,
						$ttitle,
						$checkIn,
						$checkOut,
						$joined
					) = explode(",",$line);
					$imagesIndex[$tunittype][$tcode]['title'] = $ttitle;
					if ($join) {
						$imagesIndex[$tunittype][$tcode]['required'] = $joined;
					} else {
						if ($inout == "in") {
							$imagesIndex[$tunittype][$tcode]['required'] = $checkIn;
						} elseif ($inout == "out") {
							$imagesIndex[$tunittype][$tcode]['required'] = $checkOut;
						}
					}
				}
				$lines = "";
				
				// get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
				}
				
				$title = $imagesIndex[$unittype][$code]['title'];

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				print "<p align=\"center\" class=\"larger\">$compname Check $inout</p>\n";
				print "<p align=\"center\" class=\"larger\">$unitname - $unittype - Check $inout</p>\n";
				print "<p align=\"center\" class=\"larger\">$title</p>\n";
				
				if ($iid) {
					print "<p align=\"center\" class=\"larger\">Edit Image</p>\n";
				} else {
					print "<p align=\"center\" class=\"larger\">Add New Image</p>\n";
				}

				if ($_POST['posted']) {
	
					if ($_POST['notes'])    { $notes    = strip_tags($_POST['notes']); }
					if ($_POST['details'])  { $details  = strip_tags($_POST['details']); }
					if ($_POST['oldimage']) { $oldimage = strip_tags($_POST['oldimage']); }
					if ($_POST['nodamage']) { $nodamage = strip_tags($_POST['nodamage']); }
					
					if ($_FILES['image']['tmp_name']) {
						if (exif_imagetype($_FILES['image']['tmp_name']) != IMAGETYPE_JPEG) {
							$erimage = "The file is not a jpg"; $e++;
						} else {
							$filename = date("YmdHis") . "-$custref" . "-$id-" . $_SESSION['user'] . ".jpg";
							if (!copy($_FILES['image']['tmp_name'], "$filepath/$filename")) {
								$erimage = "The uploaded image could not be saved"; $e++;
							} else {
								chmod("$filepath/$filename", 0666);
								$restxt = "Created Image";
								if ($inout == "out") { $nodamage = ""; }

								// check the image orientation
								$exif = exif_read_data("$filepath/$filename");
								if (!empty($exif['Orientation']) && in_array($exif['Orientation'], [2, 3, 4, 5, 6, 7, 8])) {
									$temp_im = imagecreatefromjpeg("$filepath/$filename");
									if (in_array($exif['Orientation'], [3, 4])) {
										$temp_im = imagerotate($temp_im, 180, 0);
									}
									if (in_array($exif['Orientation'], [5, 6])) {
										$temp_im = imagerotate($temp_im, -90, 0);
									}
									if (in_array($exif['Orientation'], [7, 8])) {
										$temp_im = imagerotate($temp_im, 90, 0);
									}
									if (in_array($exif['Orientation'], [2, 5, 7, 4])) {
										imageflip($temp_im, IMG_FLIP_HORIZONTAL);
									}
									if (!imagejpeg($temp_im, "$filepath/$filename", 80)) {
										$erimage .= "Reorientataed image could not be saved<br>"; $e++;
									}
								}

								// see if it's too big
								list($width, $height, $type, $attr) = getimagesize("$filepath/$filename");
								if (($width > 800) OR ($height > 800)) {
									if ($width > $height) {
										$scale = $width/1000;
										$new_width = 1000;
										$new_height = $height/$scale;
									} else {
										$scale = $height/1000;
										$new_height = 1000;
										$new_width = $width/$scale;
									}
			
									if ($temp_im = imagecreatefromjpeg("$filepath/$filename")) {

										if ($new_im = imagecreatetruecolor($new_width,$new_height)) {
											imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
											imagejpeg($new_im, "$filepath/$filename", 70);
										} else {
											$erimage .= "Image Could Not Be Resized<br>"; $e++;
										}

									} else {
										$erimage .= "Image Could Not Be Resized<br>"; $e++;
									}

								}

								if (!$e) {
									$image = $filename;
									if ($oldimage) {
										if (is_file("$filepath/$oldimage")) {
											unlink("$filepath/$oldimage");
										}
									}
								}

								// check that it still exists
								if (!is_file("$filepath/$filename")) {
									$erimage .= "Failed to upload. Might be too big."; $e++;
								}

							}
						}
					} else {
						$image = $oldimage;
					}
	
					if (!$e) {
						
						// sanitize details and notes for database
						if ($details) { $details = $dbcnx->real_escape_string($details); }
						if ($notes) { $notes = $dbcnx->real_escape_string($notes); }
						if ($nodamage) { $restxt = "Ticked No Damage"; }
									
						if ($iid) {
							$sql = "UPDATE `inventory_images` SET 
								`no_damage`='$nodamage',
								`image`='$image',
								`details`='$details',
								`notes`='$notes',
								`updby`='{$_SESSION['username']}' 
								WHERE `ID`='$iid' 
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Image Details Updated<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref $unitname $code Image','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Updated Image $unitname $title')");
							} else {
								$resultMessage .= "Could NOT Update Image<br />" . $dbcnx->error . "<br />";
							}
						} else {
							$sql = "INSERT INTO inventory_images VALUES (
								null,
								'$id',
								'$uid',
								'$inout',
								'$code',
								'$title',
								'$nodamage',
								'$image',
								'$details',
								'$notes',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "$restxt<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref $unitname $code $restxt','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','$restxt $unitname $title')");
							} else {
								$resultMessage .= "Could NOT Add Image Record<br />" . $dbcnx->error . "<br />";
							}
						}
					}
				}

				elseif ($iid) {
					print "<p align=\"center\"><b>Edit Image and/or Note</b></p>\n";
					// get current image
					$sql = "SELECT * FROM inventory_images WHERE ID = '$iid'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$nodamage = $row['no_damage'];
						$oldimage = $row['image'];
						$details  = $row['details'];
						$code     = $row['code'];
						$notes    = $row['notes'];
						$lastupd  = $row['lastupd'];
						$updby    = $row['updby'];
						$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
					} else {
						$resultMessage .= "Could NOT Get Image Details<br />" . $dbcnx->error . "<br />";
					}
				}
				
				if (!$resultMessage OR $e) {
						
					print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"inventory-images-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";
					print "<input type=\"hidden\" name=\"iid\" value=\"$iid\" />\n";
					print "<input type=\"hidden\" name=\"code\" value=\"$code\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
					if ($oldimage) { print "<input type=\"hidden\" name=\"oldimage\" value=\"$oldimage\" />\n"; }
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if ($inout == "out" AND !$imagesIndex[$tunittype][$tcode]['required']) {
						// No damage instructions
						print "<tr>\n";
						print "	<td colspan=\"2\"><b>Instructions:</b><br>Click No Damage OR take a photo of any damage you see.</td>\n";
						print "</tr>\n";
						// No damage checkbox
						print "<tr>\n";
						print "	<td align=\"right\"><b>No Damage:</b></td>\n";
						print "	<td><input type=\"checkbox\" name=\"nodamage\"";
						if ($nodamage) { print " checked"; }
						print " /></td>\n";
						print "</tr>\n";
					}

					if ($oldimage) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Replacing:</b></td>";
						print "	<td>$oldimage<br>Leave blank to keep existing image.</td>\n";
						print "</tr>\n";
					}
					
					// set upload file size limit message
					$uploadLimit = ini_get('upload_max_filesize');
					print "<tr>\n";
					print "	<td align=\"right\"><b>New Image (jpg):</b></td>\n";
					print "	<td><input type=\"file\" name=\"image\" class=\"element\" />";
					if ($erimage) { print "<br><span class=\"redtext\">$erimage</span>"; }
					print "<br><span class=\"smalltext\">Maximum file size: $uploadLimit</span>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Customer Notes</b></td>";
					print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
					print "</tr>\n";
				
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Internal Notes</b></td>";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

					print "</form>\n";

					if ($iid) {
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					}
					
					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";
				}
				
				$dbcnx->close();
			} else {
				$resultMessage = "Could Not Connect To Database!<br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without ids";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-images.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
