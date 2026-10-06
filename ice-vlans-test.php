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
            $siteid = 17;

            // get available vlan at this site
            $postdata = http_build_query(
                array(
                    'apikey' => $access_token,
                    'action' => 'listunused'
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
                if ($result = json_decode($result, TRUE)) {
                    // reorg
                    $columns = array_column($result, 'VLAN');
                    array_multisort($columns, SORT_ASC, $result);
                    foreach ($result AS $key => $item) {
                        if ($item['Site'] == $icesites[$siteid]) {
                            $vlan = $item['VLAN'];
                            break;
                        }
                    }
                } else {
                    $resultMessage .= "Json Decode Failed getting available VLAN<br />";
                }
            } elseif ($httpcode == 400) {
                $resultMessage .= "Error getting available VLAN<br />";
            } elseif ($httpcode == 401) {
                $resultMessage .= "Error getting available VLAN - Unauthorised<br />";
            } else {
                $resultMessage .= "Unknown error getting available VLAN code $httpcode<br />";
            }

            if ($vlan) {

                $postdata = http_build_query(
                    array(
                        'apikey'   => $access_token,
                        'action'   => 'newtenant',
                        'tenant'   => 'Test Tenant3',
                        'id'       => '90906',
                        'site'     => $icesites[$siteid],
                        'vlan'     => $vlan,
                        'password' => 'REDACTED'
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
                    $result = strip_tags($result);
                    if ($result = json_decode($result, TRUE)) {
                        $iceusername = $result['Username'];
                        $icepassword = $result['Password'];
                        $resultMessage .= "Internet VLAN Added<br />Username: $iceusername<br />Password: $icepassword<br />";
                    } else {
                        $resultMessage .= "Json Decode Failed getting returned username for VLAN<br />";
                        if (json_last_error() != JSON_ERROR_NONE) {
                            $resultMessage .= printf("JSON Error: %s", json_last_error_msg()) . "<br />";
                        }
                    }
                } elseif ($httpcode == 400) {
                        $resultMessage .= "Internet VLAN FAILED code 400<br />";
                } elseif ($httpcode == 401) {
                    $resultMessage .= "Internet VLAN FAILED Unauthorised<br />";
                } else {
                    $resultMessage .= "Internet VLAN FAILED code $httpcode<br />";
                }

            } else {
                $resultMessage .= "Could NOT get new VLAN<br />";
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

