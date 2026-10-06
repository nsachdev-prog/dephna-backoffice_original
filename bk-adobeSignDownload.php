<?php
// get api access token
include_once('AdobeSignGetAccessToken.php');
if ($access_token) {

    // get audit trail
    $headers = array(
        'Authorization: Bearer '.$access_token,
        'Accept: application/pdf'
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/auditTrail"); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result !== FALSE) {
        // save as file
        $filename = date('YmdHis') . rand(1000,9999) . ".pdf";
        if ($fh = fopen("../../dis2_db/pdf/$filename","w+")) {
            $resultMessage .= "File Openned.<br>";
            if (fwrite($fh,$result)) {
                $resultMessage .= "File Saved.<br>";

                // add to files table
                $now = time();
                $pdffilename = "Dephna-AuditTrail-DP$custref-$bkid.pdf";
                $sql = "INSERT INTO bookings_files VALUES (
                    null,
                    '$custref',
                    '$bkgid',
                    'Audit Trail',
                    '',
                    '$pdffilename',
                    '$filename',
                    '$now',
                    '{$_SESSION['username']}'
                )";
                if ($result = $dbcnx->query($sql)) {
                    $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                    $resultMessage .= "Pdf File record successfully added<br>";
                } else {
                    $resultMessage .= "Could NOT add pdf file record to database<br>" . $dbcnx->error . "<br>";
                }

            } else {
                $resultMessage .= "File Could NOT be Saved.<br>";
            }
        } else {
            $resultMessage .= "File Could NOT be Openned.<br>";
        }
    } else {
        $resultMessage .= "Audit Trail DNL FAILED: Nothing was returned from Adobe Sign.<br>";
    }

    // get signed contract
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/combinedDocument"); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result !== FALSE) {
        // save as file
        $filename = date('YmdHis') . rand(1000,9999) . ".pdf";
        if ($fh = fopen("../../dis2_db/pdf/$filename","w+")) {
            $resultMessage .= "File Openned.<br>";
            if (fwrite($fh,$result)) {
                $resultMessage .= "File Saved.<br>";

                // add to files table
                $now = time();
                $pdffilename = "Dephna-Contract-DP$custref-$bkid.pdf";
                $sql = "INSERT INTO bookings_files VALUES (
                    null,
                    '$custref',
                    '9999',
                    'Contract',
                    '',
                    '$pdffilename',
                    '$filename',
                    '$now',
                    '{$_SESSION['username']}'
                )";
                if ($result = $dbcnx->query($sql)) {
                    $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                    $resultMessage .= "Pdf File record successfully added<br>";
                } else {
                    $resultMessage .= "Could NOT add pdf file record to database<br>" . $dbcnx->error . "<br>";
                }

            } else {
                $resultMessage .= "File Could NOT be Saved.<br>";
            }
        } else {
            $resultMessage .= "File Could NOT be Openned.<br>";
        }
    } else {
        $resultMessage .= "Contract DNL FAILED: Nothing was returned from Adobe Sign.<br>";
    }

} else {
    $resultMessage .= "Could NOT Get Access Token.<br>";
}
?>
