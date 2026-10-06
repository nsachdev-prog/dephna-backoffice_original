<?php
// get api access token
include_once('AdobeSignGetAccessToken.php');
if ($access_token) {
    $headers = array(
        'Authorization: Bearer '.$access_token
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/events");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result !== FALSE) {
        $result = json_decode($result, TRUE);
        if ($result['error']) {
            $resultMessage .= "Error: {$result['error']}<br>Description: {$result['error_description']}";
        } else {
            print "<p>&nbsp;</p>\n";
            print "<p align=\"center\" class=\"larger\">Adobe Sign Events</p>\n";
            print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
            print "<tr>\n";
            print "	<td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #666\"><b>Event</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #666\"><b>Details</b></td>\n";
            print "</tr>\n";
            print "<style>\n";
            print ".r0 { background-color:#fff }\n";
            print ".r1 { background-color:#ddd }\n";
            print "</style>\n";
            foreach($result AS $key1 => $value1) {
                foreach($value1 AS $key2 => $value2) {
                    list($date,$time) = explode("T",$value2['date']);
                    list($y,$m,$d) = explode("-",$date);
                    $time = str_replace("Z", "", $time);
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$d-$m-$y $time</td>\n";
                    print "	<td>{$value2['type']}</td>\n";
                    print "	<td>{$value2['description']}</td>\n";
                    print "</tr>\n";
                }
            }
            print "<tr>\n";
            print " <td style=\"border-top:1px solid #666\" colspan=\"3\">&nbsp;</td>\n";
            print "</tr>\n";
            print "</table>\n";
        }
    } else {
        $resultMessage .= "FALSE<br>Nothing was returned from Adobe Sign.<br>";
    }
} else {
    $resultMessage .= "Could NOT get API Access Token<br>\n";
}
$resultMessage = $ASresultMessage . $resultMessage;
?>
