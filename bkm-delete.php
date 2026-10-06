<?php
	print "<p align=\"center\" class=\"heading\">Delete Provisional Booking</p>\n";
	$delete = $_GET['delete'];
	if ($delete) {

		$sql = "DELETE FROM bkm_parking WHERE bkid = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Parking Bays Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Parking Bays<br />" . $dbcnx->error . "<br />";
		}

		$sql = "DELETE FROM bkm_anc WHERE bkid = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Unit Ancillaries Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Ancillaries<br />" . $dbcnx->error . "<br />";
		}

		$sql = "DELETE FROM bkm_units WHERE bkid = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Booked Units Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Units<br />" . $dbcnx->error . "<br />";
		}

		$sql = "DELETE FROM handover WHERE bkid = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Handover Record Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Handover<br />" . $dbcnx->error . "<br />";
		}

		$sql = "DELETE FROM bkm_vanc WHERE bkid = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Virtual Ancillaries Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Virtual Ancillaries<br />" . $dbcnx->error . "<br />";
		}

		$sql = "DELETE FROM bkm_virtuals WHERE bkid = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Ancillaries Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Ancillaries<br />" . $dbcnx->error . "<br />";
		}

		$sql = "DELETE FROM bk_manager WHERE ID = '$bkid'";
		$result = $dbcnx->query($sql);
		if ($result) {
			$resultMessage .= "Main Listing Deleted<br />";
			$success++;
		} else {
			$resultMessage .= "Could NOT delete Main Listing<br />" . $dbcnx->error . "<br />";
		}

		$now = time();
		if ($success == 7) {
			$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','BKID $bkid','$page','$now')");
		} else {
			$resultMessage .= "Problem deleting BKID $bkid";
		}

		print "<p align=\"center\">$resultMessage</p>\n";
		$resultMessage = "";

	} else {
		print "<p align=\"center\"><b>$compname at $sitename</b></p>\n";
		print "<p align=\"center\"><b>ARE YOU SURE?</p>\n";
		print "<p align=\"center\"><a href=\"bk-manager.php?module=delete&amp;bkid=$bkid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bk-manager.php\">No DON'T Delete</a></p>\n";
	}
	print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
?>
