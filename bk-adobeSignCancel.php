<?php
// get api access token
include_once('AdobeSignGetAccessToken.php');

if ($access_token) {

    if ($bkid) {
        $pdffilename = "Dephna-Contract-DP$custref-$bkid.pdf";
        $table = "bk_manager";
        $tableid = $bkid;
    }
    elseif ($bkaid) {
        $pdffilename = "Dephna-Contract-DP$custref-$bkaid.pdf";
        $table = "bka_head";
        $tableid = $bkaid;
    }
    elseif ($bkrid) {
        $pdffilename = "Dephna-Rename-Agreement-DP$custref-$bkrid.pdf";
        $table = "bk_rename";
        $tableid = $bkrid;
    }
    else {
        $resultMessage .= "Could NOT get PDF filename<br>\n";
    }

    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer '.$access_token
    );
    $data = array(
        "state" => "CANCELLED",
        "agreementCancellationInfo" => array(
            "comment" => "Agreement Cancelled by {$_SESSION['username']}",
            "notifyOthers" => TRUE
        )
    );
    $data = json_encode($data, TRUE);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/state");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result !== FALSE) {
        $result = json_decode($result, TRUE);
        if ($result['code']) {
            $resultMessage .= "Error: {$result['code']}<br>{$result['message']}";
        } else {
            
            // remove from database
            $sql = "UPDATE $table SET transientDocumentId='', agreementID = '' WHERE ID='$tableid'";
            $result = $dbcnx->query($sql);
            if ($result) {
                $rdb .= $sql.";\n";
                $resultMessage .= "Contract Cancelled<br>\n"; 
                $now = time();
                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cancelled','$pdffilename','Adobe Sign','$now')");
            } else {
                $resultMessage .= "Could NOT Update Record<br>\n";
            }
            
        }
    } else {
        $resultMessage .= "Error - Nothing was returned from Adobe Sign.<br>";
    }

} else {
    $resultMessage .= "Could NOT get API Access Token<br>\n";
}
$resultMessage = $ASresultMessage . $resultMessage;
?>
