<?php
// XWebGetAccessToken.php
// Included every time we are going to connect to the API
// Gets the fields we will need from the database
// Checks that the access token has not expired
// If it has, gets a new one

if (!$xsub) { $xsub = 1; }

// get current access token and other fields
$sql = "SELECT ID, api_access_point, access_token, expires_in, lastupd, flagged FROM XWebAuth WHERE siteid = '$siteid' AND xsub = '$xsub'";
if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
    $row = $result->fetch_assoc();
    $xid = $row['ID'];
    $api_access_point = $row['api_access_point'];
    $access_token     = $row['access_token'];
    $expires_in       = $row['expires_in'];
    $lastupd          = $row['lastupd'];
    $flagged          = $row['flagged'];

    // see if access token has expired
    $now = time();
    $expires = $lastupd + $expires_in - 60; // 1 min buffer

    if ($expires < $now) {

        // get new access token
        // empty the expired token first
        $access_token = "";

        // try to refresh the access token
        $postdata = http_build_query(
            array(
                'format'   => 'json',
                'version'  => '1',
                'action'   => 'authenticate',
                'username' => 'Admin',
                'password' => 'REDACTED'
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        $result = curl_exec($ch);

        if ($result !== FALSE) {
            $result = json_decode($result, TRUE);
            if ($result['status'] == "ok") {
                $now = time();
                $access_token = $result['auth'];
                $sql = "UPDATE XWebAuth SET
                    access_token  = '$access_token',
                    expires_in    = '900',
                    lastupd       = '$now'
                    WHERE ID  = '$xid'
                ";
                if ($result = $dbcnx->query($sql)) {
                    // $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Token - SiteID: $siteid','XWeb','$now')");
                } else {
                    $AuthResultMessage .= "Could NOT Update Record in XWebAuth table.<br />" . $dbcnx->error . "<br />";
                }
            }
            else {
                $AuthResultMessage .= "Error: {$result['error']}<br />";
            }
 
        } else {
            $AuthResultMessage .= "Error: False<br>Nothing was returned from XWeb.<br />POTENTIAL CAUSE: The XWeb API is down or POWER FAILURE.<br />";
        }

    } else {
        $AuthResultMessage .= "Access Token Still Valid.<br />";
    }

} else {
    $AuthResultMessage .= "Could NOT get XWebAuth record.<br />" . $dbcnx->error . "<br />";
}
/* print "<pre>";
print_r($result);
print "</pre>"; */
?>