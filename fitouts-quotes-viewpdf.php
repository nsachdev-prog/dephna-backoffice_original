<?php
$page = "Fitouts";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];

		if ($qid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

                $output = "View";
                include('fitouts-quotes-pdf.php');

				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
?>