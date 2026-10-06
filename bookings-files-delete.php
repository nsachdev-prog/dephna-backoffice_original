<?php
$page = "Bookings";
$subpage = "Files";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user'] == 1) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">BOOKINGS - FILES</p>\n";
		
		if ($_REQUEST['bkgid']) {
			$bkgid   = $_REQUEST['bkgid'];
			$custref = $_REQUEST['custref'];
			$id      = $_REQUEST['id'];
			$ret     = $_REQUEST['ret']; // id to return to customer-view else return to bookings-files-list
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				print "<p align = \"center\" class=\"heading\">Delete File</p>\n";
				
				# get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
				}
				
				print "<p align=\"center\" class=\"heading\">$compname</p>\n";
				
				# get units
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
				
				if ($id) {
					
	  			$sql = "SELECT title, pdf, file FROM bookings_files WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$title = $row['title'];
						$pdf   = $row['pdf'];
						$file  = $row['file'];
						
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Title:</b></td>\n";
						print "	<td>$title</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Pdf:</b></td>\n";
						print "	<td>$pdf</td>\n";
						print "</table>\n";
						
						$delete = $_REQUEST['delete'];
						if ($delete) {
							
							# delete the file
							if (is_file("../../dis2_db/pdf/$file")) {
								unlink("../../dis2_db/pdf/$file");
							}

							$sql = "DELETE FROM bookings_files WHERE id='$id'";
							$result = $dbcnx->query($sql);
							
							if ($result) {
								$resultMessage = "<b>File Deleted</b>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','File: BKG$bkgid $title','$page','$now')");
							} else {
								$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
							}
								
						} else {
							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							if ($ret) {
								print "	<a href=\"bookings-files-delete.php?id=$id&delete=1&custref=$custref&bkgid=$bkgid\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"customer-view.php?id=$ret\">No DON'T Delete</a>";
							} else {
								print "	<a href=\"bookings-files-delete.php?id=$id&delete=1&custref=$custref&bkgid=$bkgid\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bookings-files-list.php?bkgid=$bkgid&custref=$custref\">No DON'T Delete</a>";
							}
							print "</p>\n";
						}
							
	
					} else {
						$resultMessage = "<span class=\"redtext\"><b>File Not Found!</b></span><br>" . $dbcnx->error;
					}
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
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
			print "<p align=\"center\"><a href=\"customer-view.php?id=$ret\">Return</a></p>\n";
		} else {
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
