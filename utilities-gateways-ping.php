<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Gateways";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Ping Gateway Port</p>\n";
	if ($accessRights[$page]) {

		$id     = $_REQUEST['id'];

		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT u.*, s.title AS sitename
						FROM UtlGateways AS u
						LEFT JOIN sites AS s ON s.ID = u.siteid
						WHERE u.ID = '$id'
				";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
					$ipaddress = $row['ipaddress'];
					$port      = $row['port'];

					print "<p align=\"center\" class=\"larger\">$sitename<br />$ipaddress $port</p>\n";

                    $waitTimeoutInSeconds = 2;
                    if($fp = fsockopen($ipaddress,$port,$errCode,$errStr,$waitTimeoutInSeconds)){   
                        $resultMessage = "<span><b>Okay, Port Responded!</b></span><br />";
                    } else {
                        $resultMessage = "<span class=\"redtext\"><b>IP or Port Not Reachable!</b></span><br />$errCode: $errStr<br />";
                    } 
                    fclose($fp);

				}
				
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}

		} else {
			$resultMessage .= "Page called without gateway id<br />";
		}

	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"utilities-gateways-list.php\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
?>
