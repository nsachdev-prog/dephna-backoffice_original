<?php
	print "<p align=\"center\">Delete Parking Bay for $compname at $sitename</p>\n";
	$pid = $_GET['pid'];
	$delete = $_GET['delete'];
	$sql = "SELECT bay FROM bkm_parking WHERE ID='$pid'";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		$row = $result->fetch_assoc();
		$bay = $row['bay'];

		if ($delete) {

			$sql = "DELETE FROM bkm_parking WHERE ID='$pid'";
			$result = $dbcnx->query($sql);
			if ($result) {
				$resultMessage = "Parking Bay $bay Deleted<br />";
				$success++;
			} else {
				$resultMessage .= "Could not delete Parking Bay $bay<br />" . $dbcnx->error;
			}

			print "<p align=\"center\">$resultMessage</p>\n";
			$resultMessage = "";

		} else {
			print "<p align=\"center\"><b>Delete Bay $bay</b></p>\n";
			print "<p align=\"center\"><a href=\"bk-manager.php?module=parking&amp;action=delete&amp;bkid=$bkid&amp;pid=$pid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bk-manager.php?module=parking&amp;action=select&amp;bkid=$bkid\">No DON'T Delete</a></p>\n";
		}
	} else {
		$resultMessage = "Could not get parking bay";
	}
	print "<p align=\"center\"><a href=\"bk-manager.php?module=parking&amp;action=select&amp;bkid=$bkid\">Return</a></p>\n";
?>
