<?php
$page = "Bookings";
$subpage = "Files";
include ("settings.php");
include ("session.php");
include ("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION ['user']) {
	
	if ($accessRights [$page]) {
		
		if ($_REQUEST ['bkgid']) {
			$bkgid = $_REQUEST ['bkgid'];
			$custref = $_REQUEST ['custref'];
			
			print "<p align=\"center\" class=\"heading\">BOOKINGS - FILES</p>\n";
			
			$dbcnx = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
			if (! $dbcnx->connect_error) {
				
				// get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query ( $sql );
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc ();
					$compname = $crow ['compname'];
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
				$uresult = $dbcnx->query ( $sql );
				if ($uresult->num_rows) {
					while ( $urow = $uresult->fetch_assoc () ) {
						$unitids [] = $urow ['unitid'];
						$unitnames [] = $urow ['unitname'];
						if (!$siteid) {
							$siteid = $urow ['siteid'];
						}
						if (!$sitename) {
							$sitename = $urow ['sitename'];
						}
					}
				}
				if ($unitnames) {
					$unitnames = implode ( " - ", $unitnames );
					print "<p align=\"center\"><b>$sitename<br>$unitnames</b></p>\n";
				}
				
				// get some records
				$sql = "SELECT * FROM bookings_files WHERE bkgid = '$bkgid'";
				$result = $dbcnx->query ( $sql );
				if ($result->num_rows) {
					print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Title</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Pdf</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Notes</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
					print "</tr>\n";
					
					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";
					
					while ( $row = $result->fetch_assoc () ) {
						
						$id = $row ['ID'];
						$title = $row ['title'];
						$pdf = $row ['pdf'];
						$file = $row ['file'];
						$details = $row ['details'];
						if (strlen ( $title ) > 40) {
							$title = substr ( $title, 0, 37 ) . "...";
						}
						
						print "<tr class=\"r" . ($i ++ % 2) . "\">\n";
						print "	<td valign=\"top\">$title</td>\n";
						print "	<td valign=\"top\">$pdf</td>\n";
						print "	<td valign=\"top\">$details</td>\n";
						print "<td valign=\"top\" align=\"center\"><a href=\"getpdf.php?pdf=$pdf&file=$file\" target=\"_blank\">View</a></td>\n";
						if ($accessRights [$page] > 1) {
							print "<td valign=\"top\" align=\"center\"><a href=\"bookings-files-addedit.php?custref=$custref&id=$id&bkgid=$bkgid\">Edit</a></td>\n";
						} else {
							print "	<td valign=\"top\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						}
						if ($_SESSION ['user'] == 6 OR $_SESSION ['user'] == 1) {
							print "<td valign=\"top\" align=\"center\"><a href=\"bookings-files-delete.php?custref=$custref&id=$id&bkgid=$bkgid\">Delete</a></td>\n";
						} else {
							print "	<td valign=\"top\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				} else {
					print "<p align=\"center\"><b>No Files Found</b></p>\n";
				}
				
				$dbcnx->close ();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without group ID";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-files-addedit.php?custref=$custref&bkgid=$bkgid\">Upload New File</a></p>\n";
		print "<p align=\"center\"><a href=\"bookings-units-view.php?siteid=$siteid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include ("footer.php");
?>
