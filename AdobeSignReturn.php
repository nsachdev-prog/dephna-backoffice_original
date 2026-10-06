<?php

$authorisation = "REDACTED";
$web_access_point = "https://secure.eu1.echosign.com/api/rest/v6/agreements";
$transientDocumentId = "3AAABLblqZhDYYxmeXgz4c6R4e86jbJf7A0JveoRcg2mOyWjQkm4VVtLyYO_CtGDYK5xA8UiqubV1znpWp7M1QlGtcsr9mcZVUUtNqfKzqia88gskwqqSLvr7Y5KLI5JousSVPQ4izB6gMj3XF8n_q-SELPLXVMqR3iA5Bqyv-x20AW6SaVSftt7cxnH8gnSHsdUsyAInHWXqzdCD0rS0UD6BiBQVFNi8x9YAGk2c88oeSbQBWX78RVoRcGPXe-8fbg9vSoeW3k9qU09oFV-GBYaD_KZ3tR4G-8z9nmHn4LoIkEf1jAmimX1uO1yDrUnkf2DZHEPsi2w*";

$data = "
{
    \"fileInfos\": [{
        \"transientDocumentId\": \"$transientDocumentId\"
    }],
    \"name\": \"Test Agreement\",
    \"participantSetsInfo\": [{
        \"memberInfos\": [{
            \"email\": \"david@davidsherwood.co.uk\"
        }],
        \"order\": 1,
        \"role\": \"SIGNER\"
    }],
    \"signatureType\": \"ESIGN\",
    \"state\": \"IN_PROCESS\"
}
";

$data1 = array(
    "fileinfos" => array(
        "transientDocumentId" => "$transientDocumentId",
        "name" => "Test Agreement",
        "participantSetsInfo" => array (
            "memberInfos" => array(
                "email" => "david@davidsherwood.co.uk"
            ),
            "order" => 1,
            "role" => "SIGNER"
        ),
        "signatureType" => "ESIGN",
        "state" => "IN_PROCESS"
    )
);
$data1 = json_encode($data1);

/* $options = array(
    'http' => array(
        'method'  => 'POST',
        'header'  => "Content-type: application/json\r\n" .
        "Authorization: Bearer $authorisation\r\n",
        'content' => "$data"
    )
);

$context = stream_context_create($options);
$result  = file_get_contents($web_access_point, false, $context);
var_dump($result); */
print "<pre>\n";
print $data;
print "<br>";
print json_decode($data1);
print "</pre>\n";

?>
