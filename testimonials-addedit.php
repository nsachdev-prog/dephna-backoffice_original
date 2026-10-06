<?php
$page = "Testimonials";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$heading = "UPDATE TESTIMONIAL";
	} else {
		$heading = "NEW TESTIMONIAL";
	}
	print "<p class=\"heading\" align=\"center\">$heading</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$title     = $_POST['title'];
				$section   = $_POST['section'];
				$visible   = $_POST['visible'];
				$dateinput = $_POST['dateinput'];
				$details   = $_POST['details'];
				$fimage    = $_POST['fimage'];
				$thisdate   = strtotime($dateinput);

				if (!$title) { $erTitle = "Please add a title for your ref"; $e++; }
				if ($section == "Select") { $erSection = "Please select the section"; $e++; }
				if ($visible == "Select") { $erVisible = "Please select visible yes or no"; $e++; }
				if (!$details) { $erDetails = "Please add a testimonial"; $e++; }
				elseif (strlen($details) > 240) { $erDetails = "Max characters exceeded"; $e++; }

				if ($_FILES['image']['tmp_name']) {
					$allowed = array("jpg","jpeg","gif","png");
					$ext = strtolower(substr($_FILES['image']['name'],strrpos($_FILES['image']['name'],'.')+1));
					if (in_array($ext,$allowed) === FALSE) { $erimage = "The file is not an image"; $e++; }
					elseif (!getimagesize($_FILES['image']['tmp_name'])) { $erimage = "The file is not an image"; $e++; }
					elseif ($_FILES['image']['size'] > 500000) { $erimage = "The file is bigger than 100k"; $e++; }
					if (!$e) {
						$imagefile = date(YmdHis) . "." . $ext;
						if (!copy($_FILES['image']['tmp_name'], "$image_path/$imagefile")) {
							$resultMessage = "Uploaded image could not be saved";
						} else {
							list($width, $height, $type, $attr) = getimagesize($image_path."/".$imagefile);
							if ($width > 120) {
								$scale = $width/120;
								$new_width = 120;
								$new_height = $height/$scale;
								$temp_im = imageCreateFromJPEG($image_path."/".$imagefile);
								$new_im = imagecreatetruecolor($new_width,$new_height);
								imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
								imagejpeg($new_im, $image_path."/".$imagefile, 60);
								imageDestroy($temp_im);
								imageDestroy($new_im);
							}
							if ($fimage) {
								if ($fimage != $imagefile) {
									if (is_file($image_path."/".$fimage)) {
										unlink($image_path."/".$fimage);
									}
								}
							}
						}
					}
					$image = $imagefile;
				} else {
					$image = $fimage;
				}

				if (!$e) {

					$title   = addslashes($title);
					$details = addslashes($details);
					if ($id) {
						$sql = "UPDATE testimonials SET
						visible='$visible',
						title  ='$title',
						section ='$section',
						thisdate ='$thisdate',
						image  ='$image',
						details='$details'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfuly updated";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','Testimonials','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO testimonials VALUES (
						null,
						'$visible',
						'$title',
						'$section',
						'$thisdate',
						'$image',
						'$details'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfuly added";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','Testimonials','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {

				$sql = "SELECT * FROM testimonials WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$visible   = $row['visible'];
					$title     = $row['title'];
					$section   = $row['section'];
					$thisdate  = $row['thisdate'];
					$image     = $row['image'];
					$details   = $row['details'];
					$dateinput = date("l F dS Y",$thisdate);
				} else {
					$resultMessage = "User Not Found<br />" . $dbcnx->error;
				}

			}
			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					if (!$dateinput) { $dateinput = date("l F dS Y",time()); }
					print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"testimonials-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Title:</td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"30\" value=\"$title\" />";
					if ($erTitle) { print "&nbsp;<span class=\"redtext\">$erTitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Date:</td>\n";
					print "	<td><input type=\"text\" id=\"dateinput\" name=\"dateinput\" value=\"$dateinput\" class=\"element\" /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Section:</td>\n";
					print "	<td><select name=\"section\" class=\"element\">\n";
					print "		<option>Select</option>\n";
					print "		<option value=\"Office\""; if ($section == "Office") { print " SELECTED"; } print ">Office</option>\n";
					print "		<option value=\"Kitchen\""; if ($section == "Kitchen") { print " SELECTED"; } print ">Kitchen</option>\n";
					print "	</select>";
					if ($erSection) { print "&nbsp;<span class=\"redtext\">$erSection</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Visible:</td>\n";
					print "	<td><select name=\"visible\" class=\"element\">\n";
					print "		<option>Select</option>\n";
					print "		<option value=\"Yes\""; if ($visible == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
					print "		<option value=\"No\""; if ($visible == "No") { print " SELECTED"; } print ">No</option>\n";
					print "	</select>";
					if ($erVisible) { print "&nbsp;<span class=\"redtext\">$erVisible</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><p>\n";
					if ($image) {
						print "	<img src=\"$image_path/$image\" width=\"120px\" align=\"left\" hspace=\"10px\" />\n";
						print "	<input type=\"hidden\" name=\"fimage\" value=\"$image\" />\n";
					}
					print "The image must be a jpg, jpeg, gif or png and be 120px wide.<br />It should not be more than 500k<br>Leave this blank if you are keeping the existing image.";
					if ($erimage) { print "<br><span class=\"redtext\">$erimage</span>"; }
					print "</p>\n";
					print "	<p>Image jpg: <input type=\"file\" name=\"image\" size=\"40\" class=\"element\" /></p>\n";
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Testimonial: (240 Characters Max)"; if ($erDetails) { print "&nbsp;&nbsp;<span class=\"redtext\">$erDetails</span>"; } print "<br />";
					print "<textarea name=\"details\" wrap=\"virtual\" rows=\"4\" class=\"element_box\">$details</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"testimonials-view.php\">Testimonials</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

?>
