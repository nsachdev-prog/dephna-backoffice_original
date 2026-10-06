<?php

include('settings.php');

if ($_REQUEST['error']) {
    $resultMessage = $_REQUEST['error']."<br>".$_REQUEST['error_description'];
} else {

    $action = $_REQUEST['action'];

    if ($action) {

        // open database
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if(!$dbcnx->connect_error) {

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

                if ($action == "checkAccessToken") {

                    // see if access token has expired
                    $now = time();
                    $expires = $lastupd + $expires_in - 60; // 1 min buffer

                    if ($expires < $now) {

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
                                $resultMessage .= "Error: {$result['error']}<br>Description: {$result['error_description']}";
                            } else {
                                $now = time();
                                $access_token = $_REQUEST['access_token'];
                                $token_type   = $_REQUEST['token_type'];
                                $expires_in   = $_REQUEST['expires_in'];
                                $sql = "UPDATE AdobeSignAuth SET
                                    access_token  = '$access_token',
                                    token_type    = '$token_type',
                                    expires_in    = '$expires_in',
                                    lastupd       = '$now'
                                    WHERE ID = '1'
                                ";
                                if ($result = $dbcnx->query($sql)) {
                                    $resultMessage .= "Access Token Refreshed.<br>";
                                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Token','Adobe Sign','$now')");
                                } else {
                                    $error .= "Could NOT Update Record AdobeSignAuth table.<br>" . $dbcnx->error . "<br>";
                                }
                            }

                        } else {
                            $resultMessage .= "Error: False<br>Description: Nothing was returned from Adobe Sign.";
                        }

                    } else {
                        $resultMessage .= "Access Token Still Valid."; 
                    }

                }

                elseif ($action == "getAccessCode") {

                    $postdata = array(
                        'redirect_uri'  => 'https://dephna.net/backoffice/AdobeSign.php?action=returnAccessCode',
                        'response_type' => 'code',
                        'client_id'     => $client_id,
                        'scope'         => 'user_read:account user_write:account user_login:self agreement_read:account agreement_write:account agreement_send:account'
                    );
                    $postdata = http_build_query($postdata);

                    print "<script type=\"text/javascript\">\n";
                    print "	window.location=\"https://secure.eu1.echosign.com/public/oauth?$postdata\"\n";
                    print "</script>\n";

                }
            
                elseif ($action == "returnAccessCode") {

                    $access_code      = $_REQUEST['code'];
                    $web_access_point = $_REQUEST['web_access_point'];
                    $api_access_point = $_REQUEST['api_access_point'];

                    $now = time();
                    $sql = "UPDATE AdobeSignAuth SET
                        access_code      = '$access_code',
                        api_access_point = '$api_access_point',
                        web_access_point = '$web_access_point',
                        lastupd          = '$now'
                        WHERE ID = '1'
                    ";
                    if ($result = $dbcnx->query($sql)) {
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Code','Adobe Sign','$now')");
                        $resultMessage .= "Access Code Saved.<br>";
                    } else {
                        $resultMessage .= "Could NOT Update AdobeAuth table.<br>" . $dbcnx->error . "<br>";
                    }

                    // get access token

                    $postdata = http_build_query(
                        array(
                            'code'          => $access_code,
                            'client_id'     => $client_id,
                            'client_secret' => $client_secret,
                            'redirect_uri'  => 'https://dephna.net/backoffice/AdobeSign.php?action=returnAccessToken',
                            'grant_type'    => 'authorization_code'
                        )
                    );

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_URL, $api_access_point."oauth/token");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
                    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                    $result = curl_exec($ch);

                    if ($result !== FALSE) {
                        $result = json_decode($result, TRUE);
                        if ($result['error']) {
                            $resultMessage .= "Error: {$result['error']}<br>Description: {$result['error_description']}";
                        } else {
                            $now = time();
                            $sql = "UPDATE AdobeSignAuth SET
                                access_token  = '{$result['access_token']}',
                                refresh_token = '{$result['refresh_token']}',
                                token_type    = '{$result['token_type']}',
                                expires_in    = '{$result['expires_in']}',
                                lastupd       = '$now'
                                WHERE ID = '1'
                            ";
                            if ($result = $dbcnx->query($sql)) {
                                $resultMessage .= "Access Token Saved.<br>";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Token','Adobe Sign','$now')");
                            } else {
                                $error .= "Could NOT Update Record AdobeSignAuth table.<br>" . $dbcnx->error . "<br>";
                            }
                        }

                    } else {
                        $resultMessage .= "Error: False<br>Description: Nothing was returned from Adobe Sign.";
                    }

                } else {
                    $resultMessage = "No Action Returned<br>";
                }

            } else {
                $resultMessage = "Could Not Record From Database!<br>" . $dbcnx->error;
            }

            $dbcnx->close();

        } else {
            $resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
        }

    } else {
        $resultMessage .= "No Action Requested";
    }

}

if ($resultMessage) {
    print "<p align=\"center\">$resultMessage</p>\n";
}

?>