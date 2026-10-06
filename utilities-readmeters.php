<html>
<!--
***********************************************
******** Backoffice CM3.1 Framework ***********
***********************************************
*  Written By: David Sherwood                 *
*              david@davidsherwood.co.uk      *
* Licensed To: Dephna Estates Ltd.            *
*        Term: Lifetime                       *
*    Supplier: Matthew Blackmore Web Design   *
*              mail@matthewblackmore.com      *
***********************************************
**** Do not remove this license statement  ****
***********************************************
-->
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Dephna - Backoffice<?php if ($page) { print " - $page"; } if ($subpage) { print " - $subpage"; } ?></title>
		<link href="style.css?v=<?php echo md5(time()); ?>" rel="stylesheet" type="text/css" />
		<link href="styles/jquery.datetimepicker.css" rel="stylesheet">
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
		<script type="text/javascript" src="//code.jquery.com/jquery-1.10.2.min.js"></script>
		<script type="text/javascript" src="//code.jquery.com/ui/1.10.3/jquery-ui.min.js"></script>
		<script type="text/javascript" src="jscripts/jquery-ui-sliderAccess.js"></script>
		<script type="text/javascript" src="jscripts/jquery.datetimepicker.full.min.js"></script>
	</head>
	<body style="margin: 0px; padding: 0px;background-color: #fff;">

		<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px; padding: 4px; background-color: #0c4173;">
			<tr>
				<td style="text-align:center;">
					<a href="index.php"><img src="images/dephna-logo-mobile.png" alt="Dephna" border="0"></a>
				</td>
			</tr>
		</table>
		<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px;  padding: 4px; background-color: #ccc;">
			<tr>
				<td style="text-align:left;">
					<a href="index.php">Home</a>
				</td>
                <td style="text-align:right;">
					<a href="index.php?logout=1">Logout</a>
				</td>
			</tr>
		</table>
        <table width="100%" cellspacing="0" cellpadding="0" style="border: 0px; padding: 8px;">
            <tr>
                <td><p>&nbsp;</p>

<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Read Meters";
include 'settings.php';
include 'session.php';
if ($_SESSION['user']) {
	# print heading
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	print "<p align=\"center\" class=\"larger\">Read Meters</p>\n";
	if ($accessRights[$page]) {
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$e = 0;
				$erreading = array();
				$used = 0;
				$previous = 0;

				$reading = $_POST['reading'];
				$notes	 = $_POST['notes'];
				$id      = $_POST['id'];
				$reading  = $dbcnx->real_escape_string($reading);
				$id       = $dbcnx->real_escape_string($id);
				$notes    = $dbcnx->real_escape_string($notes);

				if ($notes) {
					$notes = str_replace('\n', "<br>", $notes);
				}

				if ($reading AND !is_numeric($reading)) {
					$erreading[$id] = "Reading must be numeric!"; $e++;
				}

				if (!$e) {
					if ($reading) {
						$sql = "SELECT previous, rate FROM UtlUsedNew WHERE ID = '$id'";
						if ($result = $dbcnx->query($sql)) {
							$row = $result->fetch_object();
							$previous = $row->previous;
							$rate     = $row->rate;
							$used = $reading - $previous;
							$charge = $used * $rate;
							$sql = "UPDATE UtlUsedNew SET used = '$used', charge = '$charge', meter = '$reading', notes = '$notes' WHERE ID = '$id'";
							$dbcnx->query($sql);
						}
					} else {
						$sql = "UPDATE UtlUsedNew SET notes = '$notes' WHERE ID = '$id'";
						$dbcnx->query($sql);
					}
				}
				
			}

			if ($_POST['imageposted']) {
				$e = 0;
				if ($_FILES['image']['tmp_name']) {
					$id = $_POST['id'];
					$id = $dbcnx->real_escape_string($id);
					$filepath = "../../dis2_db/img/utl";
					$oldimage = $_POST['oldimage'];
					$oldimage = $dbcnx->real_escape_string($oldimage);
					$iid = $_POST['iid'];
					$iid = $dbcnx->real_escape_string($iid);
					if (exif_imagetype($_FILES['image']['tmp_name']) != IMAGETYPE_JPEG) {
						$erimage = "The file is not a jpg"; $e++;
					} else {
						$filename = date("YmdHis") . "-$id-" . $_SESSION['user'] . ".jpg";
						if (!copy($_FILES['image']['tmp_name'], "$filepath/$filename")) {
							$erimage = "The uploaded image could not be saved"; $e++;
						} else {
							chmod("$filepath/$filename", 0666);
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
								imageDestroy($temp_im);
							}

							// see if it's too big
							list($width, $height, $type, $attr) = getimagesize("$filepath/$filename");
							if (($width > 1000) OR ($height > 1000)) {
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
										imageDestroy($temp_im);
										imageDestroy($new_im);
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
								// check that it still exists
								if (!is_file("$filepath/$filename")) {
									$erimage .= "Failed to upload. Might be too big."; $e++;
								} else {
									if ($iid) {
										$sql = "UPDATE UtlImages SET savedname = '$image', lastupd = NOW(), updby = '" . $_SESSION['username'] . "' WHERE ID = '$iid'";
										if ($dbcnx->query($sql)) {
											// good
										} else {
											$erimage = "Image could not be uploaded" . $dbcnx->error; $e++;
										}
									} else {
										$sql = "INSERT INTO UtlImages (uid, savedname, lastupd, updby) VALUES ('$id', '$image', NOW(), '" . $_SESSION['username'] . "')";
										if ($dbcnx->query($sql) === TRUE) {
											// good
										} else {
											$erimage = "Image could not be uploaded" . $dbcnx->error; $e++;
										}
									}
								}
							}
						}
					}
				} else {
					$erimage = "No file uploaded"; $e++;
				}

			}

			$year = date("Y");
			$month = date("m");

			// get site filitering
			$sql = "SELECT DISTINCT sitename FROM UtlUsedNew WHERE used < '0' AND year = '$year' AND month = '$month' ORDER BY sitename";
			if ($result = $dbcnx->query($sql)) {
				$sitefilter = "<form name=\"sitefilter\" method=\"post\" action=\"utilities-readmeters.php\">\n";
				$sitefilter .= "<select name=\"site\" onchange=\"document.sitefilter.submit();\">\n";
				$sitefilter .= "<option value=\"\">All Sites</option>\n";
				while ($row = $result->fetch_object()) {
					$sitename = $row->sitename;
					$selected = ($sitename == $_REQUEST['site']) ? " selected" : "";
					$sitefilter .= "<option value=\"$sitename\"$selected>$sitename</option>\n";
				}
				$sitefilter .= "</select>\n";
				$sitefilter .= "</form>\n";
				print "<table align=\"center\"><tr><td><b>Site:</b></td><td>$sitefilter</td></tr></table>\n";
				print "<p>&nbsp;</p>\n";
			}

			$sql = "SELECT u.ID, u.sitename, u.unit, u.commodity, i.ID AS iid, i.savedname, i.lastupd, i.updby, u.previous, u.notes
					FROM UtlUsedNew AS u
					LEFT JOIN UtlImages AS i ON u.ID = i.uid
					WHERE u.used < '0'
					  AND u.year = '$year'
					  AND u.month = '$month'
					  AND u.sitename LIKE '%" . $_REQUEST['site'] . "%'
					  ORDER BY u.sitename, u.unit, u.commodity
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				// print row count
				print "<p align=\"center\">There are " . $result->num_rows . " meters that need reading.</p>\n";
				
				print "<table align=\"center\" cellpadding=\"4\" cellspacing=\"0\">\n";

				while ($row = $result->fetch_object()) {
					$id        = $row->ID;
					$iid       = $row->iid;
					$sitename  = $row->sitename;
					$unitname  = $row->unit;
					$commodity = $row->commodity;
					$previous  = $row->previous;
					$oldimage  = $row->savedname;
					$notes	   = $row->notes;
					$lastupd   = $row->lastupd;
					$updby     = $row->updby;

					$notes = str_replace("<br>", "\n", $notes);

					print "	<form name=\"siteform\" method=\"post\" action=\"utilities-readmeters.php?site=$_REQUEST[site]\">\n";
					print "	<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
					print "	<input type=\"hidden\" name=\"posted\" value=\"1\">\n";

					print "<tr>\n";
					print "<td class=\"larger\" colspan=\"3\" >$sitename $unitname $commodity</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td colspan=\"3\">Previous: $previous</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td colspan=\"2\">\n";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"4\" cols=\"38\">$notes</textarea>\n";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td>\n";
					print "	<input type=\"text\" name=\"reading\">\n";
					if ($erreading[$id]) { print "<br><span class=\"redtext\">$erreading[$id]</span>"; }
					print "	</td>\n";
					print "<td>\n";
					print "	<input type=\"submit\" class=\"button\" value=\"Enter Reading or Notes\">\n";
					print "</td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>";

					print "	</form>\n";
					
					/* print "<tr>\n";
					print "<td>\n";
					print "	<form name=\"siteform\" method=\"post\" action=\"utilities-readmeters.php\" enctype=\"multipart/form-data\">\n";
					print "	<input type=\"hidden\" name=\"imageposted\" value=\"1\">\n";
					print "	<input type=\"hidden\" name=\"oldimage\" value=\"$oldimage\">\n";
					print " <input type=\"hidden\" name=\"iid\" value=\"$iid\">\n";
					print "	<input type=\"file\" name=\"image\">\n";
					print "	<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
					print "</td>\n";
					print "<td>\n";
					print "	<input type=\"submit\" class=\"button\" value=\"Upload Image\">\n";
					print "	</form>\n";
					if ($erimage) {
						print "<br><span class=\"redtext\">$erimage</span>";
					}
					if ($oldimage) {
						print "<td><img src=\"getimage.php?dr=utl&amp;im=$oldimage\" width=\"40\" height=\"18\"></td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					print "</td>\n";
					print "</tr>\n"; */

					print "<tr>\n";
					print "<td colspan=\"3\">&nbsp;</td>\n";
					print "</tr>\n";

				}
				print "</table>\n";

				$result->free();

			} else {
				$resultMessage = "No meters found that need reading.";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
?>

                </td>
            </tr>
	</body>
</html>


