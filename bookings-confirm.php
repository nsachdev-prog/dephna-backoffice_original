<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$siteid  = $_REQUEST['siteid'];
$unitid  = $_REQUEST['unitid'];
$custref = $_REQUEST['custref'];
$bkid    = $_REQUEST['bkid'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {
		if (($siteid) && ($unitid) && ($bkid)) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get site title
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				# get unit details
				$sql = "SELECT title, master FROM units WHERE ID = '$unitid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unittitle = $row['title'];
					$master    = $row['master'];
					if ($master) {
						# get units linked to this one
						$sql = "SELECT title FROM units WHERE siteid='$siteid' AND links = '$unittitle'";
						$lresult = $dbcnx->query($sql);
						if (($lresult) && ($lresult->num_rows)) {
							$links = array();
							while ($lrow = $lresult->fetch_assoc()) {
								$thistitle = $lrow['title'];
								$links[] = $thistitle;
							}
							if ($links) {
								$links = implode(", ",$links);
							}
						}
					}
					print "<p align=\"center\" class=\"heading\">$sitetitle - $unittitle - Confirm Booking</p>\n";
					if ($links) { print "<p align=\"center\">Linked to $links</p>\n"; }

					$sql = "UPDATE bookings SET status='on' WHERE ID='$bkid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "</p><p align=\"center\"><b>Booking confirmed</b></a>";
						$resultMessage .= "</p><p align=\"center\"><a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref\">Unit Details</a></a>";
					} else {
						$resultMessage = "</p><p align=\"center\"><b>Could NOT confirm booking</b></a>";
					}

				} else {
					print "<p align=\"center\">Unit not found!</p>\n";
				}

				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without site or unit id";
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-units-view.php?siteid=$siteid&amp;custref=$custref\">Units</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
