<?php
$page = "Bookings";
$virtuals = 1;
include('settings.php');
include('session.php');
include('header.php');
$bkid   = $_REQUEST['bkid'];
$delete = $_REQUEST['delete'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {
		if ($bkid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get booking info
				$sql = "SELECT pkname, sitename, custref FROM vbookings WHERE ID='$bkid'";
				$result = $dbcnx->query($sql);
				$row = $result->fetch_assoc();
				$pkname   = $row['pkname'];
				$sitename = $row['sitename'];
				$custref  = $row['custref'];

				# get customer
				$sql = "SELECT compname FROM users WHERE custref='$custref'";
				$result = $dbcnx->query($sql);
				$row = $result->fetch_assoc();
				$customer = $row['compname'];

				# print heading
				print "<p align=\"center\" class=\"heading\">Delete Virtual Office</p>\n";

				if ($delete) {
					$sql = "DELETE FROM vbookings_an WHERE bkid='$bkid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$sql = "DELETE FROM vbookings WHERE ID='$bkid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Booking Successfully Deleted<br />";
							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitename - $pkname','$page','$now')");
						} else {
							$resultMessage = "Could not delete booking<br />" . $dbcnx->error;
						}
					} else {
						$resultMessage = "Could not delete ancillary charges for this booking<br />" . $dbcnx->error;
					}
				} else {
					if (($customer) && ($pkname)) {
						print "<p align=\"center\"><b>$pkname Virtual Office for $customer at $sitename</b></p>\n";
						print "<p align=\"center\">This will also delete any ancillary charges applied.</p>\n";
						print "<p align=\"center\"><a href=\"bookings-virtuals-delete.php?bkid=$bkid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bookings-virtuals-view.php\">No DON'T Delete</a></p>\n";
					} else {
						$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
					}
				}

				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without booking id";
		}
		if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-virtuals-view.php\">Virtual Offices</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
include('footer.php');
?>
