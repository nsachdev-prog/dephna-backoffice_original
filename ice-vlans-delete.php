<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Doors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">ICE VLANS</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$api_access_point = "https://wifi.dephna.com/dephna";
			$access_token = "REDACTED";

			$vlan = 301;
			
			$postdata = http_build_query(
				array(
					'apikey'   => $access_token,
					'action'   => 'deletetenant',
					'vlan'     => $vlan
				)
			);

			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $api_access_point."/?".$postdata);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			$result = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpcode == 200) {
				print $result;
				$resultMessage .= "Deleted VLAN $vlan<br />";
			} elseif ($httpcode == 400) {
					$resultMessage .= "FAILED code 400<br />";
			} elseif ($httpcode == 401) {
				$resultMessage .= "FAILED Unauthorised<br />";
			} else {
				$resultMessage .= "FAILED code $httpcode<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
} else {
	include ('loggedout.php');
}
print "<p><b>Raw Data From Ice</b></p>";
print "<pre>";
print_r($result);
print "</pre>";
print "<p>vlan: $vlan</p>";
print $api_access_point."/?".$postdata;
print "</td>\n";
include('footer.php');
?>
