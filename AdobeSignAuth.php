<?php

include('settings.php');

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    $sql = "SELECT * FROM AdobeSignAuth WHERE ID = '1'";
    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
        $row = $result->fetch_assoc();
        $access_code      = $row['access_code'];
        $application_id   = $row['application_id'];
        $client_secret    = $row['client_secret'];
        $web_access_point = $row['web_access_point'];

        $postdata = http_build_query(
            array(
                'code'          => $access_code,
                'client_id'     => $application_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => 'https://dephna.net/backoffice/AdobeSignAuth.php',
                'grant_type'    => 'authorization_code'
            )
        );
        $options = array(
            'http' => array(
                'method'           => 'POST',
                'protocol_version' => '1.1',
                'header'           => 'Content-Type: application/x-www-form-urlencoded\r\n' .
                                      'Connection: close\r\n' .
                                      'Accept: application/json',
                'content'          => $postdata
            ),
            'ssl' => array(
                'verify_peer'      => 'false',
                'verify_peer_name' => 'false'
            )
        );
        $context = stream_context_create($options);
        $return  = file_get_contents("$web_access_point/oauth/token?", false, $context);
        if ($return !== FALSE) {
            $return = json_decode($return);
            $now = time();
            $sql = "UPDATE AdobeSignAuth SET
                access_token  = '{$return['access_token']}',
                refresh_token = '{$return['refresh_token']}',
                token_type    = '{$return['token_type']}',
                expires_in    = '{$return['expires_in']}',
                lastupd       = '$now'
                WHERE ID='1'
            ";
            if ($result = $dbcnx->query($sql)) {
                $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','Access Code','Adobe Sign','$now')");
            } else {
                $error .= "Could NOT Update Record AdobeAuth table.<br>" . $dbcnx->error . "<br>";
            }

        } else {
            $error .= "Return was false.<br>";
        }

    } else {
        $error .= "Could not get data from table.<br>";
    }

    $dbcnx->close();
    
} else {
    $error = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
}

$w = stream_get_wrappers();
echo 'openssl: ',  extension_loaded  ('openssl') ? 'yes':'no', "\n";
echo 'http wrapper: ', in_array('http', $w) ? 'yes':'no', "\n";
echo 'https wrapper: ', in_array('https', $w) ? 'yes':'no', "\n";
echo 'wrappers: ', var_export($w);

print "$error<br>\n";
print "$web_access_point<br>\n";
print "<pre>\n";
print_r ($return);
print_r ($options);
print "</pre>\n";

?>