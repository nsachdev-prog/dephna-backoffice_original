<?php
$page = "BKManager";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Additional Services</p>\n";

		$bkaid = $_REQUEST['bkaid'];

		if ($bkaid) {

			include('bka-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Cancel Adobe Sign Contract</p>\n";

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT b.*, u.title AS unitname, c.compname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						LEFT JOIN users AS c ON c.custref = b.custref
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$compname = $row['compname'];
					$unitid   = $row['unitid'];
					$unitname = $row['unitname'];
					$lastupd  = $row['lastupd'];
                    $updby    = $row['updby'];
                    $agreementID = $row['agreementID'];

					print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
				
					$cancel = $_GET['cancel'];
					$now = time();

					if ($cancel) {

						include_once('bk-adobeSignCancel.php');

						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

					} else {

						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\"><a href=\"bka-contract-cancel.php?bkaid=$bkaid&amp;cancel=1\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"bka-summary.php?bkaid=$bkaid\">No DON'T Cancel</a></p>\n";
						
					}
				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without id";
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"bka-summary.php?bkaid=$bkaid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
