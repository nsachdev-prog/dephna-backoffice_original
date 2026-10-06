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

				if ($hrid) {

					print "	<p align=\"center\"><b>Delete Holiday Request</b></p>\n";
					$sql = "SELECT * FROM hol_requests WHERE `ID`='$hrid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$sid    = $row['sid'];
						$fyear  = $row['fyear'];
						$fmonth = $row['fmonth'];
						$fday   = $row['fday'];
						$tyear  = $row['tyear'];
						$tmonth = $row['tmonth'];
						$tday   = $row['tday'];

						$sql = "SELECT `username` FROM `staff` WHERE `ID`='$sid'";
						$sresult = $dbcnx->query($sql);
						if (($sresult) && ($sresult->num_rows)) {
							$srow = $sresult->fetch_assoc();
							$username = $srow['username'];
						} else {
							$username = "Not Found";
						}

						$fday   = sprintf("%02s",$fday);
						$fmonth = sprintf("%02s",$fmonth);
						$tday   = sprintf("%02s",$tday);
						$tmonth = sprintf("%02s",$tmonth);

						if ($_GET['delete']) {
							$sql = "DELETE FROM hol_requests WHERE ID='$hrid'";
							$dresult = $dbcnx->query($sql);
							if ($dresult) {
								$resultMessage = "<b>Record Successfully Deleted</b>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$username Holiday Request','$page','$now')");
							} else {
								$resultMessage = "<b>Record Could Not Be Deleted</b><br />" . $dbcnx->error;
							}
						} else {
							print "<table align=\"center\" cellspacing=\"6px\">\n";
							print "<tr>\n";
							print "	<td align=\"right\"><b>Request From:</b></td>\n";
							print "	<td>$username</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td align=\"right\"><b>From (first Day):</b></td>\n";
							print "	<td>".sprintf("%02s",$fday)."/".sprintf("%02s",$fmonth)."/$fyear</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td align=\"right\"><b>To (last Day):</b></td>\n";
							print "	<td>".sprintf("%02s",$tday)."/".sprintf("%02s",$tmonth)."/$tyear</td>\n";
							print "</tr>\n";
							print "</table>\n";
							print "<p align=\"center\"><a href=\"holiday-request-delete.php?hrid=$hrid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"holiday-request-view.php\">No DON'T Delete</a></p>\n";
						}
					} else {
						$resultMessage = "<b>Could not get record from database</b><br />" . $dbcnx->error;
					}
				} else {
					$resultMessage = "<b>Page called without hrid</b>";
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
print "<p align=\"center\"><a href=\"holiday-request-view.php\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
?>
