<?php

// AdobeSignGetAccessToken.php
// Included every time we are going to connect to the API
// Gets the fields we will need from the database
// Checks that the access token has not expired
// If it has, uses the refresh token to get a new one

// get current access token and other fields
$sql = "SELECT * FROM AdobeSignAuth WHERE ID = '1'";
if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
    $row = $result->fetch_assoc();
    $client_id        = $row['client_id'];
    $client_secret    = $row['client_secret'];
    $access_code      = $row['access_code'];
    $api_access_point = $row['api_access_point'];
    $web_access_point = $row['web_access_point'];
    $access_token     = $row['access_token'];
    $refresh_token    = $row['refresh_token'];
    $token_type       = $row['token_type'];
    $expires_in       = $row['expires_in'];
    $lastupd          = $row['lastupd'];

    // see if access token has expired
    $now = time();
    $expires = $lastupd + $expires_in - 60; // 1 min buffer

    if ($expires < $now) {

        // empty the expired token
        $access_token = "";

        // try to refresh the access token
        $postdata = http_build_query(
            array(
                'refresh_token' => $refresh_token,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'refresh_token'
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $api_access_point."oauth/refresh");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        $result = curl_exec($ch);

        if ($result !== FALSE) {
            $result = json_decode($result, TRUE);
            if ($result['error']) {
                $ASresultMessage .= "Error: {$result['error']}<br>Description: {$result['error_description']}";
            } else {
                $now = time();
                $access_token = $result['access_token'];
                $token_type   = $result['token_type'];
                $expires_in   = $result['expires_in'];
                $sql = "UPDATE AdobeSignAuth SET
                    access_token  = '$access_token',
                    token_type    = '$token_type',
                    expires_in    = '$expires_in',
                    lastupd       = '$now'
                    WHERE ID = '1'
                ";
                if ($result = $dbcnx->query($sql)) {
                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Token','Adobe Sign','$now')");
                } else {
                    $ASresultMessage .= "Could NOT Update Record AdobeSignAuth table.<br>" . $dbcnx->error . "<br>";
                }
            }

        } else {
            $ASresultMessage .= "Error: False<br>Nothing was returned from Adobe Sign.<br>";
        }

    }

} else {
    $ASresultMessage .= "Could NOT get AdobeSignAuth table.<br>" . $dbcnx->error . "<br>";
}
?>