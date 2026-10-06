<?php
$page = "Fitouts";
$subpage = "Images";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		if ($_REQUEST['qid']) { $qid = $_REQUEST['qid']; }
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Quote</p>\n";
			
		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\" class=\"larger\">Customer Images</p>\n";

					if ($_POST['image-posted']) {

						if ($_FILES['newimage']['tmp_name']) {
							if (exif_imagetype($_FILES['newimage']['tmp_name']) == IMAGETYPE_JPEG) {
								$ext = ".jpg";
							}
							elseif (exif_imagetype($_FILES['newimage']['tmp_name']) == IMAGETYPE_PNG) {
								$ext = ".png";
							}
							else {
								$ernewimage = "The file is not a jpg or png image"; $e++;
							}
							if (!$e) {
								$filename = date(YmdHis) . "-" . rand(100,999) . $ext;
								if (!copy($_FILES['newimage']['tmp_name'], "$image_path/$filename")) {
									$ernewimage = "Uploaded image could not be saved"; $e++;
								} else {
									$image = $filename;
									# get next pos
									$sql = "SELECT pos FROM fitouts_quotes_images WHERE qid = '$qid' ORDER BY pos DESC LIMIT 0,1";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$pos = $row['pos'];
										$pos++;
									} else {
										$pos = 1;
									}
								}

								$now = time();
								$sql = "INSERT INTO fitouts_quotes_images VALUES (
								null,
								'$qid',
								'$image',
								'$pos',
								'$now',
								'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Image $image','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Add New Image To Database</span><br>" . $dbcnx->error;
								}
							}
						}
					}

					if ($_GET['move']) {
						$move = $_GET['move'];
						$pos  = $_GET['pos'];
						if ($move == "up") {
							$moveto = $pos-1;
							$movethat = "pos=pos+1";
							$otherone = $pos-1;
							$moveback = "pos='$moveto'";
						} else {
							$moveto = $pos+1;
							$movethat = "pos=pos-1";
							$otherone = $pos+1;
							$moveback = "pos='$moveto'";
						}
						$sql = "UPDATE fitouts_quotes_images SET pos='0' WHERE qid = '$qid' AND pos='$pos'";
						$result = $dbcnx->query($sql);
						$sql = "UPDATE fitouts_quotes_images SET $movethat WHERE qid = '$qid' AND pos='$otherone'";
						$result = $dbcnx->query($sql);
						$sql = "UPDATE fitouts_quotes_images SET $moveback WHERE qid = '$qid' AND pos='0'";
						$result = $dbcnx->query($sql);
					}

					if ($_GET['delete']) {
						$iid = $_GET['iid'];
						$sql = "SELECT image, pos FROM fitouts_quotes_images WHERE ID='$iid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$image = $row['image'];
							$pos   = $row['pos'];
							if ($image) {
								if (is_file("$image_path/$image")) {
									if (unlink("$image_path/$image")) {
										$sql = "DELETE FROM fitouts_quotes_images WHERE ID='$iid'";
										$result = $dbcnx->query($sql);
										if ($result) {
											# close the pos gap
											$sql = "UPDATE fitouts_quotes_images SET pos = pos-1 WHERE qid = '$qid' AND pos > '$pos'";
											$result = $dbcnx->query($sql);
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Image $image','$page','$now')");
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT Delete Image Record from Database</span><br>" . $dbcnx->error;
										}
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT Delete Image.</span><br>";
									}
								} else {
									$resultMessage .= "<span class=\"redtext\">Image Does Not Exist!</span><br>";
								}
							}
						}
					}

		  			$sql = "SELECT * FROM fitouts_quotes_images WHERE qid = '$qid' ORDER BY pos";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Image</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Uploaded</b></td>\n";
						print "	<td align=\"center\" colspan=\"2\" style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">&nbsp;</td>\n";
						print "</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						$n = 0;
						$last = $result->num_rows;
						while ($row = $result->fetch_assoc()) {
							$iid    = $row['ID'];
							$image  = $row['image'];
							$pos    = $row['pos'];
							$lastupd = $row['lastupd'];
							$updby   = $row['updby'];
							$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td><img src=\"getimage.php?im=$image\" width=\"100\" border=\"0\"></td>\n";
							print "<td class=\"smalltext\">$lastupd<br>By $updby</td>\n";
							if ($n > 0) {
								print "		<td align=\"center\" width=\"18px\"><a href=\"fitouts-quotes-images.php?qid=$qid&move=up&pos=$pos\"><b>&nbsp;&#9650&nbsp;</b></a></td>\n";
							} else {
								print "		<td align=\"center\" width=\"18px\">&nbsp;</td>\n";
							}
							if ($n < $last-1) {
								print "		<td align=\"center\" width=\"18px\"><a href=\"fitouts-quotes-images.php?qid=$qid&move=dn&pos=$pos\"><b>&nbsp;&#9660&nbsp;</b></a></td>\n";
							} else {
								print "		<td align=\"center\" width=\"18px\">&nbsp;</td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "	<td align=\"center\"><a href=\"fitouts-quotes-images.php?qid=$qid&iid=$iid&delete=1\">Delete</a></td>\n";
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
							}
							$n++;
						}
						print "<tr>\n";
						print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Images Found</p>";
					}

					print "<p align=\"center\"><b>Add New Customer Image</b></p>\n";

					print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"fitouts-quotes-images.php\">\n";
					print "<input type=\"hidden\" name=\"image-posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
					
					print "<table align=\"center\" cellspacing=\"3\" cellpadding=\"3\">\n";
					if ($ernewimage) { $class = "row-error"; } else { $class = "row"; }
					print "<tr>\n";
					print "<td><input name=\"newimage\" type=\"file\" class=\"element\" maxlength=\"150\" value=\"\" />";
					if ($ernewimage) { print $ernewimage; }
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

					print "</form>\n";

					print "<p align=\"center\">";
					print "<a href=\"fitouts-quotes-items.php?qid=$qid\">Back</a> | ";
					print "<a href=\"fitouts-quotes-contract.php?qid=$qid\">Next</a>";
					print "</p>\n";

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";
	
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fitout Quote Not Found!</b></span><br>" . $dbcnx->error;
				}
				
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Page Called Without ID</b></span><br>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
