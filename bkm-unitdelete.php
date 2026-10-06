<?php
	print "<p align=\"center\">Delete Unit for $compname at $sitename</p>\n";
	$delete = $_GET['delete'];
	$uid = $_REQUEST['uid'];
	$sql = "SELECT unitname FROM bkm_units WHERE ID = '$uid'";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		$row = $result->fetch_assoc();
		$unitname = $row['unitname'];
		if ($delete) {

			$sql = "DELETE FROM bkm_anc WHERE uid='$uid'";
			$result = $dbcnx->query($sql);
			if ($result) {
				$resultMessage .= "Unit Ancillaries Deleted<br />";
				$success++;
			} else {
				$resultMessage .= "Could NOT delete Ancillaries<br />" . $dbcnx->error . "<br />";
			}

			$sql = "DELETE FROM bkm_units WHERE ID='$uid'";
			$result = $dbcnx->query($sql);
			if ($result) {
				$resultMessage .= "Unit $unitname Deleted<br />";
				$success++;
			} else {
				$resultMessage .= "Could NOT delete Unit<br />" . $dbcnx->error . "<br />";
			}

			$sql = "DELETE FROM handover WHERE bkuid = '$uid'";
			$result = $dbcnx->query($sql);
			if ($result) {
				$resultMessage .= "Handover Record Deleted<br />";
				$success++;
			} else {
				$resultMessage .= "Could NOT delete Handover Record<br />" . $dbcnx->error . "<br />";
			}

			print "<p align=\"center\">$resultMessage</p>\n";
			$resultMessage = "";

		} else {
			print "<p align=\"center\"><b>Delete $unitname</b></p>\n";
			print "<p align=\"center\"><a href=\"bk-manager.php?module=units&amp;action=delete&amp;bkid=$bkid&amp;uid=$uid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bk-manager.php?module=units&amp;action=select&amp;bkid=$bkid\">No DON'T Delete</a></p>\n";
		}
	} else {
		$resultMessage = "Could not get unit";
	}
	print "<p align=\"center\"><a href=\"bk-manager.php?module=units&amp;action=select&amp;bkid=$bkid\">Return</a></p>\n";
?>
