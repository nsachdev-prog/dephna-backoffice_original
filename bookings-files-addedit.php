<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bookings";
$subpage = "Files";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BOOKINGS - FILES</p>\n";
		
		if ($_REQUEST['bkgid']) {
			$bkgid = $_REQUEST['bkgid'];
			$custref = $_REQUEST['custref'];
			$id  = $_REQUEST['id'];
			$ret = $_REQUEST['ret']; // id to return to customer-view else return to bookings-files-list
			
			if ($id) {
				$heading = "Update File";
			} else {
				$heading = "New File";
			}
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				print "<p align = \"center\" class=\"heading\">$heading</p>\n";
				
				// get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
				}
				
				print "<p align=\"center\" class=\"heading\">$compname</p>\n";
				
				// get units
				$sql = "SELECT b.siteid, b.unitid, u.title AS unitname, s.title AS sitename
								FROM bookings b
								LEFT JOIN units u ON u.ID = b.unitid
								LEFT JOIN sites s ON s.ID = b.siteid
								WHERE b.tempid = '$bkgid'
								ORDER BY u.title
				";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					while ($urow = $uresult->fetch_assoc()) {
						$unitids[] = $urow['unitid'];
						$unitnames[] = $urow['unitname'];
						if (!$siteid) { $siteid = $urow['siteid']; }
						if (!$sitename) { $sitename = $urow['sitename']; }
					}
				}
				if ($unitnames) {
					$unitnames = implode(" - ",$unitnames);
					print "<p align=\"center\"><b>$sitename<br>$unitnames</b></p>\n";
				}
			
				if ($_POST['posted']) {
	
					if ($_POST['title']) {   $title    = strip_tags($_POST['title']); }
					if ($_POST['details']) { $details  = strip_tags($_POST['details']); }
					$oldpdf   = $_POST['oldpdf'];
					$oldfile  = $_POST['oldfile'];
					
					if (!$title) { $ertitle = "Please enter a location code"; $e++; }
					
					if (!$e) {
						if ($_FILES['newpdf']['tmp_name']) {
							if ($_FILES['newpdf']['type'] != "application/pdf") {
								$erpdf = "The file is not a pdf"; $e++;
							} else {
								$pdf  = $_FILES['newpdf']['name'];
								$file = date("YmdHis") . rand(1000,9999) . ".pdf";
								if (!copy($_FILES['newpdf']['tmp_name'], "../../dis2_db/pdf/$file")) {
									$erpdf = "The pdf could not be saved"; $e++;
								} else {
									chmod("../../dis2_db/pdf/$file", 0666);
									if ($oldfile) {
										if (is_file("../../dis2_db/pdf/$oldfile")) {
											unlink("../../dis2_db/pdf/$oldfile");
										}
									}
								}
							}
						} else {
							$pdf = $oldpdf;
							$file = $oldfile;
						}
					}
	
					if (!$e) {
						
						$title   = addslashes($title);
						$details = addslashes($details);
						$details = str_replace('\n',"<br>",$details);
						$now = time();
									
						if ($id) {
							$sql = "UPDATE bookings_files SET
								title='$title',
								details='$details',
								pdf='$pdf',
								file='$file',
								lastupd='$now',
								updby='{$_SESSION['username']}'
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "<b>File Successfully Updated</b><br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','File: BKG$bkgid $title','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
							}
						} else {
							$sql = "INSERT INTO bookings_files VALUES (
								null,
								'$custref',
								'$bkgid',
								'$title',
								'$details',
								'$pdf',
								'$file',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "<b>File Successfully Added</b><br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','File: BKG$bkgid $title','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
							}
						}
					}
				}
	
				elseif ($id) {
	  				$sql = "SELECT * FROM bookings_files WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$title   = $row['title'];
						$details = $row['details'];
						$oldpdf  = $row['pdf'];
						$oldfile = $row['file'];
						$details = str_replace("<br>","\n",$details);
					} else {
						$resultMessage = "<span class=\"redtext\"><b>File Not Found!</b></span><br>" . $dbcnx->error;
					}
				}
	
				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
							
						print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"bookings-files-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"bkgid\" value=\"$bkgid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						if ($oldpdf) { print "<input type=\"hidden\" name=\"oldpdf\" value=\"$oldpdf\" />\n"; }
						if ($oldfile) { print "<input type=\"hidden\" name=\"oldfile\" value=\"$oldfile\" />\n"; }
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Title:</b></td>\n";
						print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$title\" />\n";
						if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
						print "</td>\n";
						print "</tr>\n";
						
						if ($oldpdf) {
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Pdf:</b></td>";
							print "	<td>$oldpdf</td>\n";
							print "</tr>\n";
						}
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>New Pdf:</b></td>\n";
						print "	<td><input type=\"file\" name=\"newpdf\" class=\"element\" />";
						if ($erpdf) { print "<span class=\"redtext\">$erpdf</span>"; }
						print "</td>\n";
						print "</tr>\n";
		
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Details:</b></td>";
						print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
						print "</tr>\n";
	
						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
	
						print "</table>\n";
						print "</form>\n";
						
					}
				}		
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without bkgid";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($ret) {
			// return to customer-view
			print "<p align=\"center\"><a href=\"customer-view.php?id=$ret\">Return</a></p>\n";
		} else {
			// return to bookings-files-list
			print "<p align=\"center\"><a href=\"bookings-files-list.php?custref=$custref&bkgid=$bkgid\">Return</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
