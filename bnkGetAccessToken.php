<?php
// Barclays Bank API Access Token Retrieval UK
// get current access token and other fields
$sql = "SELECT api_access_point, inst_id, req_id, acc_ids, secret_id, secret_key, access_token, expires_in, refresh_token, refresh_expires_in, lastupd FROM bnkAuth WHERE ID = '1'";
if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
    $row = $result->fetch_assoc();
    $api_access_point = $row['api_access_point'];
    $inst_id          = $row['inst_id'];
    $req_id           = $row['req_id'];
    $acc_ids          = $row['acc_ids'];
    $secret_id        = $row['secret_id'];
    $secret_key       = $row['secret_key'];
    $access_token     = $row['access_token'];
    $expires_in       = $row['expires_in'];
    $refresh_token    = $row['refresh_token'];
    $refresh_expires_in = $row['refresh_expires_in'];
    $lastupd          = $row['lastupd'];

    $acc_ids = explode(',', $acc_ids);
    $acc_id = $acc_ids[0]; // use the first account ID for now

    // see if access token has expired
    $now = time();
    $expires = $lastupd + $expires_in - 60; // 1 min buffer
    $refreshexpires = $lastupd + $refresh_expires_in + 60; // 1 min buffer

    if ($expires < $now) {

        // access token has expired
        // 86400 seconds = 1 day

        if ($refreshexpires < $now) {
            
            // get new access token
            // empty the expired token first
            // 2592000 seconds = 30 days
            $access_token = "";

            // try to refresh the access token from gocarless API
            $apiUrl = $api_access_point . "token/new/";
            $AuthResultMessage = "";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'secret_id'  => $secret_id,
                'secret_key' => $secret_key
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'accept: application/json',
                'Content-Type: application/json'
            ));
            $response = curl_exec($ch);
            $httpresponse = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseData = json_decode($response, TRUE);
            if ($responseData['access']) {
                $access_token = $responseData['access'];
                $expires_in = $responseData['access_expires'];
                $refresh_token = $responseData['refresh'];
                $refresh_expires_in = $responseData['refresh_expires'];
                $lastupd = time();
                // update the access token in the database
                $updateSql = "UPDATE bnkAuth SET access_token = ?, expires_in = ?, refresh_token = ?, refresh_expires_in = ?, lastupd = ? WHERE ID = '1'";
                $stmt = $dbcnx->prepare($updateSql);
                if ($stmt) {
                    $stmt->bind_param('sisii', $access_token, $expires_in, $refresh_token, $refresh_expires_in, $lastupd);
                    if ($stmt->execute()) {
                        $AuthResultMessage .= "Access Token Updated Successfully.<br />";
                    } else {
                        $AuthResultMessage .= "Error updating access token: " . $stmt->error . "<br />";
                    }
                } else {
                    $AuthResultMessage .= "Error preparing SQL statement: " . $dbcnx->error . "<br />";
                }
            } else {
                $AuthResultMessage .= "Error fetching access token: " . $responseData['error'] . "<br /> HTTP Response Code: " . $httpresponse . "<br />";
            }
        } else {
            // use refresh token to get a new access token
            $apiUrl = $api_access_point . "token/refresh/";
            $AuthResultMessage = "";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'refresh' => $refresh_token
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "accept: application/json",
                "Content-Type: application/json"
            ]);
            $response = curl_exec($ch);
            $httpresponse = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseData = json_decode($response);
            if (isset($responseData->access)) {
                $access_token = $responseData->access;
                $expires_in = $responseData->access_expires;
                $lastupd = time();
                // update the access token in the database
                $updateSql = "UPDATE bnkAuth SET access_token = ?, expires_in = ?, lastupd = ? WHERE ID = '1'";
                $stmt = $dbcnx->prepare($updateSql);
                if ($stmt) {
                    $stmt->bind_param('sii', $access_token, $expires_in, $lastupd);
                    if ($stmt->execute()) {
                        $AuthResultMessage .= "Access Token Refreshed Successfully.<br />";
                    } else {
                        $AuthResultMessage .= "Error updating access token: " . $stmt->error . "<br />";
                    }
                } else {
                    $AuthResultMessage .= "Error preparing SQL statement: " . $dbcnx->error . "<br />";
                }
            } else {
                $AuthResultMessage .= "Error refreshing access token: " . $responseData . "<br /> HTTP Response Code: " . $httpresponse . "<br />";
            }
            curl_close($ch);
        }
    } else {
        $AuthResultMessage .= "Access Token is still valid.<br />";
    }
} else {
    $AuthResultMessage .= "Error retrieving access token from database: " . $dbcnx->error . "<br />";
}
