<?php
// get api access token
include_once('AdobeSignGetAccessToken.php');

if ($access_token) {
    
    // get pdf as string
    $byadobesign = 1;
    include_once("srf2-document-pdf.php");
    $pdffilename = "Dephna-SRF-DP$custref-$id.pdf";
    
    if ($fileatt) {

        $fh = fopen('pdf/srftemp.pdf','w+');
        fwrite($fh, $fileatt);
        fclose($fh);
        $filepath = "pdf/srftemp.pdf";
        $cfile = curl_file_create($filepath, mime_content_type($filepath), $pdffilename);
        $headers = array(
            'Authorization: Bearer '.$access_token
        );
        $postdata = array(
            'File'      => $cfile,
            'File-Name' => $pdffilename,
            'Mime-Type' => 'application/pdf'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/transientDocuments");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result !== FALSE) {
            $result = json_decode($result, TRUE);
            if ($result['error']) {
                $resultMessage .= "Error: {$result['error']}<br />Description: {$result['error_description']}";
            } else {
                // $resultMessage .= "<pre>" . print_r($result) . "</pre>";
                $transientDocumentId = $result['transientDocumentId'];
                if ($transientDocumentId) {
                    // save it
                    $sql = "UPDATE inventory SET transientDocumentId='$transientDocumentId' WHERE ID='$id'";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $rdb .= $sql.";\n";
                        $resultMessage .= "Agreement Uploaded, ID Received and Saved<br />\n";
                        // send document for signing

                        $data = array(
                            'fileInfos' => array(
                                array(
                                    'transientDocumentId' => "$transientDocumentId"
                                )
                            ),
                            'name' => "$pdffilename",
                            'participantSetsInfo' => array(
                                array(
                                    'memberInfos' => array(
                                        array(
                                            'email' => "$email"
                                        )
                                    ),
                                    'order' => '1',
                                    'role' => 'SIGNER'
                                )
                            ),
                            'signatureType' => 'ESIGN',
                            'state' => 'IN_PROCESS'
                        );
                        $data = json_encode($data, TRUE);
                        $options = array(
                            'http' => array(
                                'method'  => 'POST',
                                'header'  => "Content-type: application/json\r\n" .
                                "Authorization: Bearer $access_token\r\n",
                                'content' => "$data"
                            )
                        );
                        $context = stream_context_create($options);
                        $result  = file_get_contents($web_access_point."api/rest/v6/agreements", false, $context);
                        if ($result !== FALSE) {
                            $result = json_decode($result, TRUE);
                            if ($result['error']) {
                                $resultMessage .= "Error: {$result['error']}<br />Description: {$result['error_description']}";
                            } else {
                                $agreementID = $result['id'];
                                if ($agreementID) {
                                    $resultMessage .= "Agreement sent for signing.<br />\n";
                                    // save the ID
                                    $sql = "UPDATE srf2 SET agreementID='$agreementID' WHERE ID = '$id'";
                                    $result = $dbcnx->query($sql);
                                    if ($result) {
                                        $rdb .= $sql.";\n";
                                        $resultMessage .= "Agreement ID Saved<br />\n";
                                        $now = time();
                                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Sent','$pdffilename','Adobe Sign','$now')");
                                    } else {
                                        $resultMessage .= "Agreement ID Could NOT be Saved<br />\n";
                                    }
                                } else {
                                    $resultMessage .= "Agreement ID NOT returned.<br />\n";
                                }
                            }
                        } else {
                            $resultMessage .= "FALSE: Nothing was returned from Adobe Sign.<br />";
                        }
                    } else {
                        $resultMessage .= "Could NOT save Document ID<br />\n";
                    }
                } else {
                    $resultMessage .= "Document ID EMPTY<br /><pre>".print_r($result)."</pre><br />\n";
                }
            }
        } else {
            $resultMessage .= "Error: False<br />Description: Nothing was returned from Adobe Sign.<br />";
        }
    } else {
        $resultMessage .= "Could NOT get pdf data<br />\n";
    }

} else {
    $resultMessage .= "Could NOT get API Access Token<br />\n";
}
$resultMessage = $ASresultMessage . $resultMessage;
?>
