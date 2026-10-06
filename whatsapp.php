<?php
    $INSTANCE_ID = "30";  // TODO: Replace it with your gateway instance ID here
    $CLIENT_ID = "support@dephna.com";  // TODO: Replace it with your Forever Green client ID here
    $CLIENT_SECRET = "REDACTED";   // TODO: Replace it with your Forever Green client secret here

    $postData = array(
    'group_admin' => '+447713999217',  // TODO: Specify the WhatsApp number of the group creator, including the country code
    'group_name' => 'Coldroom issues',    // TODO: Specify the name of the group
    'message' => 'Testing. Please Ignore.'
    );

    $headers = array(
    'Content-Type: application/json',
    'X-WM-CLIENT-ID: '.$CLIENT_ID,
    'X-WM-CLIENT-SECRET: '.$CLIENT_SECRET
    );

    $url = 'http://api.whatsmate.net/v3/whatsapp/group/text/message/' . $INSTANCE_ID;
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);

    echo "Response: ".$response;
    curl_close($ch);
?>
