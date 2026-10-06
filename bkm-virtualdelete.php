<?php
	print "<p align=\"center\">Delete Virtual for $compname at $sitename</p>\n";
	$delete = $_GET['delete'];
	$vid = $_REQUEST['vid'];
	$sql = "SELECT pkname FROM bkm_virtuals WHERE ID = '$vid'";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		$row = $result->fetch_assoc();
		$pkname = $row['pkname'];
		if ($delete) {

			$sql = "DELETE FROM bkm_vanc WHERE vid='$vid'";
			$result = $dbcnx->query($sql);
			if ($result) {
				$resultMessage .= "Virtual Ancillaries Deleted<br />";
				$success++;
			} else {
				$resultMessage .= "Could not delete ancillaries<br />" . $dbcnx->error . "<br />";
			}

			$sql = "DELETE FROM bkm_virtuals WHERE ID='$vid'";
			$result = $dbcnx->query($sql);
			if ($result) {
				$resultMessage .= "Virtual deleted<br />";
				$success++;
			} else {
				$resultMessage .= "Could not delete Virtual<br />" . $dbcnx->error . "<br />";
			}

			print "<p align=\"center\">$resultMessage</p>\n";
			$resultMessage = "";

		} else {
			print "<p align=\"center\"><b>Delete $bkname Package</b></p>\n";
			print "<p align=\"center\"><a href=\"bk-manager.php?module=virtuals&amp;action=delete&amp;bkid=$bkid&amp;vid=$vid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bk-manager.php?module=virtuals&amp;action=select&amp;bkid=$bkid\">No DON'T Delete</a></p>\n";
		}
	} else {
		$resultMessage = "Could not get virtual";
	}
	print "<p align=\"center\"><a href=\"bk-manager.php?module=virtuals&amp;action=select&amp;bkid=$bkid\">Return</a></p>\n";
?>
