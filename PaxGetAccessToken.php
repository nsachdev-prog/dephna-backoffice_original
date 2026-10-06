<?php
// PaxGetAccessToken.php
// Included every time we are going to connect to the API
// Gets the fields we will need from the database
// Checks that the access token has not expired
// If it has, get a new one

// get current access token and other fields

if ($siteid == 11) { $thissiteid = 34; } else { $thissiteid = $siteid; } // Coronation uses Brittania Way

$sql = "SELECT * FROM PaxAuth WHERE siteid = '$thissiteid'";
if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
	$row = $result->fetch_assoc();
	$username         = $row['username'];
	$password         = $row['password'];
	$clientid         = $row['client_id'];
	$api_access_point = $row['api_access_point'];
	$access_token     = $row['access_token'];
	$expires_in       = $row['expires_in'];
	$refresh_token    = $row['refresh_token'];
	$r_expires_in     = $row['r_expires_in'];
	$lastupd          = $row['lastupd'];

	// see if access token has expired
	$now = time();
	$expires = $lastupd + $expires_in - 60; // - 1 min buffer

	if ($expires < $now) {

		// get new access token
		// empty the expired token first
		$access_token = "";

		// refresh the access token from scratch
		$postdata = array(
			'username'      => $username,
			'password'      => $password,
			'grant_type'    => 'password',
			'client_id'     => $clientid,
			'scope'         => 'offline_access'
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_access_point . "api/v1/authorization/tokens");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		// ssl headers
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$result = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpcode == 200) {
			$result = json_decode($result, true);
			if ($result['access_token']) {
				$now = time();
				$access_token  = $result['access_token'];
				$refresh_token = $result['refresh_token'];
				$sql = "UPDATE PaxAuth SET
					access_token  = '$access_token',
					expires_in    = '1800',
					refresh_token = '$refresh_token',
					r_expires_in  = '7200',
					lastupd       = '$now'
					WHERE siteid  = '$thissiteid'
				";
				if ($result = $dbcnx->query($sql)) {
					// $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Token - SiteID: $siteid','Pax','$now')");
				} else {
					$AuthResultMessage .= "Could NOT Update Record in PaxAuth table.<br />" . $dbcnx->error . "<br />";
				}
			}
			elseif ($result['status'] == "error") {
				$AuthResultMessage .= "Error: {$result['error']}<br />";
			}
 
		} else {
			$AuthResultMessage .= "No Response From Paxton - HTTP(S) Response Code $httpcode<br /><pre>$result</pre><br>{$api_access_point}api/v1/authorization/tokens<br><pre>".print_r($postdata, true)."</pre>";
		}

	}

} else {
	$AuthResultMessage .= "Could NOT get PaxAuth record.<br />" . $dbcnx->error . "<br />";
}
?>