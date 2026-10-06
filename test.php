<?php
$coldrooms = array();
$id = "236";
$coldrooms[] = "$id--First";
$coldrooms[] = "237--Second";
$coldrooms[] = "238--Third";

foreach ($coldrooms as $coldroom) {
    list($id, $name) = explode('--', $coldroom);
    echo "Cold Room ID: $id, Name: $name<br>";
}
?>
