<?php
	print "<p align=\"center\" class=\"heading\">Cancel Adobe Sign Contract</p>\n";
	// get agreement id
	$sql = "SELECT agreementID FROM bk_manager WHERE ID = '$bkid'";
	if (($asresult = $dbcnx->query($sql)) && ($asresult->num_rows)) {
		$asrow = $asresult->fetch_assoc();
		$agreementID = $asrow['agreementID'];
		if ($agreementID) {
	
			$cancel = $_GET['cancel'];
			if ($cancel) {

				include_once('bk-adobeSignCancel.php');

				print "<p align=\"center\">$resultMessage</p>\n";
				$resultMessage = "";

			} else {
				print "<p align=\"center\"><b>$compname at $sitename</b></p>\n";
				print "<p align=\"center\"><b>ARE YOU SURE?</b></p>\n";
				print "<p align=\"center\"><a href=\"bk-manager.php?module=cancel&amp;bkid=$bkid&amp;cancel=1\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"bk-manager.php?module=summary&amp;bkid=$bkid\">No DON'T Cancel</a></p>\n";
			}
			print "<p align=\"center\"><a href=\"bk-manager.php?module=summary&amp;bkid=$bkid\">Return</a></p>\n";
		} else {
			print "<p align=\"center\">No Agreement ID Present</p>\n";
		}
	} else {
		print "<p align=\"center\">Could NOT Get Agreement ID</p>\n";
	}
?>
