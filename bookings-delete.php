<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$bkid    = $_REQUEST['bkid'];
$siteid  = $_REQUEST['siteid'];
$unitid  = $_REQUEST['unitid'];
$custref = $_REQUEST['custref'];
$delete  = $_REQUEST['delete'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {
		if (($siteid) && ($unitid) && ($custref)) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				// get site title
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				// get unit title
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unittitle = $row['title'];
				} else {
					$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
				}

				// get customer
				$sql = "SELECT ID, compname FROM users WHERE custref = '$custref'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$customer = $row['compname'];
					$custID   = $row['ID'];
				}

				// is it a parking bay
				$sql = "SELECT ID, reg FROM parking WHERE bkid = '$bkid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$pid = $row['ID'];
					$reg = $row['reg'];
					$bay = $row['bay'];
				}

				// print heading
				print "<p align=\"center\" class=\"heading\">$sitetitle - $unittitle - Delete Booking</p>\n";

				if ($delete) {
					$sql = "DELETE FROM bookings_an WHERE bkid='$bkid'";
					if ($dbcnx->query($sql)) {
						// is it parking
						if ($pid) {
							$sql = "DELETE FROM parking WHERE bkid = '$bkid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Bay $bay Reg $reg Deleted<br />";
							} else {
								$resultMessage .= "Counld NOT Delete Bay $bay Reg $reg<br />";
							}
						}

						$sql = "DELETE FROM bookings WHERE ID='$bkid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "Booking Successfully Deleted<br />";
							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$sitetitle - $unittitle','$page','$now')");
						} else {
							$resultMessage .= "Could not delete booking<br />" . $dbcnx->error;
						}
					} else {
						$resultMessage .= "Could not delete ancillary charges for this booking<br />" . $dbcnx->error;
					}
				} else {
					if ($customer) {
						print "<p align=\"center\"><b>Booking for $customer at $sitetitle - $unittitle</b></p>\n";
						print "<p align=\"center\" class=\"redtext\"><b>ARE YOU SURE?</b></p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"bookings-delete.php\">\n";
						print "<input type=\"hidden\" name=\"delete\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Delete\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
					} else {
						$resultMessage .= "Could not get record from database<br />" . $dbcnx->error;
					}
				}
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage .= "Page called without customer, site or unit id";
		}
		if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref\">Return</a>&nbsp;|&nbsp;<a href=\"customer-view.php?id=$custID\">Customer</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
