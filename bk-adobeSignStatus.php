<?php
// get api access token
include('AdobeSignGetAccessToken.php');
if ($access_token) {
    $headers = array(
        'Authorization: Bearer '.$access_token
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result !== FALSE) {
        $result = json_decode($result, TRUE);
        if ($result['error']) {
            $resultMessage .= "Error: {$result['error']}<br>Description: {$result['error_description']}";
        } else {
            $ageementStatus = $result['status'];
        }
    } else {
        $resultMessage .= "FALSE<br>Nothing was returned from Adobe Sign.<br>";
    }
} else {
    $resultMessage .= "Could NOT get API Access Token<br>\n";
}
// $resultMessage .= $ASresultMessage;
?>
