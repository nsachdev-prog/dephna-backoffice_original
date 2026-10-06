<?php
$page = "Holidays";
date_default_timezone_set('UTC');
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">HOLIDAY REQUESTS</p>\n";
	if ($accessRights[$page]) {
		$hrid = $_REQUEST['hrid'];

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# row background colours
            print "<style>\n";
            print ".r0 { background-color:#fff }\n";
            print ".r1 { background-color:#ddd }\n";
            print "</style>\n";

			if ($accessRights[$page] > 2) {  # Admin (Raj) so get everyone and allow approvals
				print "	<p align=\"center\"><b>All Pending Holiday Requests</b></p>\n";
				$sql = "SELECT * FROM hol_requests ORDER BY fyear DESC, fmonth DESC, fday DESC";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>From</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>To</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Employee</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Rejection Notes</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Status</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
					print "</tr>\n";

					while ($row = $result->fetch_assoc()) {
						$hrid     = $row['ID'];
						$sid      = $row['sid'];
						$fyear    = $row['fyear'];
						$fmonth   = $row['fmonth'];
						$fday     = $row['fday'];
						$tyear    = $row['tyear'];
						$tmonth   = $row['tmonth'];
						$tday     = $row['tday'];
						$notes    = $row['notes'];
						$crdate   = $row['crdate'];
						$rejected = $row['rejected'];

						if ($notes) {
							$notes = stripslashes($notes);
							if (strlen($notes) > 30) { $notes = substr($notes, 0, 30) . "..."; }
						} else {
							$notes = "";
						}

						if ($rejected) {
							$status = "<span class=\"redtext\">Rejected</span>";
						} else {
							$status = "Pending";
						}

						$fday   = sprintf("%02s",$fday);
						$fmonth = sprintf("%02s",$fmonth);
						$tday   = sprintf("%02s",$tday);
						$tmonth = sprintf("%02s",$tmonth);

						$sql = "SELECT `username` FROM `staff` WHERE `ID`='$sid'";
						$sresult = $dbcnx->query($sql);
						if (($sresult) && ($sresult->num_rows)) {
							$srow = $sresult->fetch_assoc();
							$name = $srow['username'];
						} else {
							$name = "Not Found";
						}
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$fday/$fmonth/$fyear</td>\n";
						print "	<td>$tday/$tmonth/$tyear</td>\n";
						print "	<td>$name</td>\n";
						print "	<td>$notes</td>\n";
						print "	<td>$status</td>\n";
						if ($rejected) {
							print "	<td>&nbsp;</td>\n";
						} else {
							print "	<td align=\"center\"><a href=\"holiday-request-process.php?hrid=$hrid\">Process</a></td>\n";
						}
						print "	<td align=\"center\"><a href=\"holiday-request-delete.php?hrid=$hrid\">Delete</a></td>\n";
						print "</tr>\n";
					}
					print "<tr>\n";
                	print " <td style=\"border-top:1px solid #666\" colspan=\"7\">&nbsp;</td>\n";
                	print "</tr>\n";

					print "</table>\n";
				} else {
					print "<p align=\"center\">No Requests Found</p>\n";
				}

			} else { # Only view current users requests and allow to add new ones.
				print "	<p align=\"center\"><b>Holidays Requested by {$_SESSION['username']}</b></p>\n";
				$sql = "SELECT * FROM hol_requests WHERE `sid`='{$_SESSION['user']}' ORDER BY fyear DESC, fmonth DESC, fday DESC";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>From</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>To</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Rejection Notes</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Status</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
					print "</tr>\n";

					while ($row = $result->fetch_assoc()) {
						$hrid     = $row['ID'];
						$fyear    = $row['fyear'];
						$fmonth   = $row['fmonth'];
						$fday     = $row['fday'];
						$tyear    = $row['tyear'];
						$tmonth   = $row['tmonth'];
						$tday     = $row['tday'];
						$notes    = $row['notes'];
						$crdate   = $row['crdate'];
						$rejected = $row['rejected'];

						if ($notes) {
							$notes = stripslashes($notes);
							if (strlen($notes) > 50) { $notes = substr($notes, 0, 50) . "..."; }
						} else {
							$notes = "";
						}

						if ($rejected) {
							$status = "<span class=\"redtext\">Rejected</span>";
						} else {
							$status = "Pending";
						}

						$fday   = sprintf("%02s",$fday);
						$fmonth = sprintf("%02s",$fmonth);
						$tday   = sprintf("%02s",$tday);
						$tmonth = sprintf("%02s",$tmonth);

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$fday/$fmonth/$fyear</td>\n";
						print "	<td>$tday/$tmonth/$tyear</td>\n";
						print "	<td>$notes</td>\n";
						print "	<td>$status</td>\n";
						if ($rejected) {
							print "	<td align=\"center\"><a href=\"holiday-request-form.php?hrid=$hrid\">Change</a></td>\n";
							print "	<td align=\"center\"><a href=\"holiday-request-delete.php?hrid=$hrid\">Delete</a></td>\n";
						} else {
							print "	<td>&nbsp;</td>\n";
							print "	<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
                	print " <td style=\"border-top:1px solid #666\" colspan=\"6\">&nbsp;</td>\n";
                	print "</tr>\n";

					print "</table>\n";
				} else {
					print "<p align=\"center\">No Requests Found</p>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"holiday-request-form.php\">Make New Holiday Request</a></p>\n";
print "</td>\n";
include('footer.php');
?>
