<?php
include('settings.php');
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    // count unawnsered support tickets
    $sql = "SELECT count(*) as `count` 
        FROM `support_tickets`
        WHERE `status` = 'open'
        AND `replied` = '0'
        AND `flagged` = '0'
        AND `pending` = '0'
        AND `type` = 'Support'
    ";
    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
        $row = $result->fetch_assoc();
        $newsupport = $row['count'];
    } else {
        $newsupport = 0;
    }

    // count unanswered accounts tickets
    $sql = "SELECT count(*) as `count` 
        FROM `support_tickets`
        WHERE `status` = 'open'
        AND `replied` = '0'
        AND `flagged` = '0'
        AND `pending` = '0'
        AND `type` = 'Accounts'
    ";
    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
        $row = $result->fetch_assoc();
        $newaccounts = $row['count'];
    } else {
        $newaccounts = 0;
    }

    // count unawnsered camilla tickets
    $sql = "SELECT count(*) as `count` 
        FROM `support_tickets`
        WHERE `status` = 'open'
        AND `replied` = '0'
        AND `flagged` = '0'
        AND `pending` = '0'
        AND `type` = 'Camilla'
    ";
    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
        $row = $result->fetch_assoc();
        $newcamilla = $row['count'];
    } else {
        $newcamilla = 0;
    }

    // count unanswered flats tickets
    $sql = "SELECT count(*) as `count` 
        FROM `support_tickets`
        WHERE `status` = 'open'
        AND `replied` = '0'
        AND `flagged` = '0'
        AND `pending` = '0'
        AND `type` = 'Flats'
    ";
    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
        $row = $result->fetch_assoc();
        $newflats = $row['count'];
    } else {
        $newflats = 0;
    }
    $dbcnx->close();
} else {
    $newsupport = 0;
    $newaccounts = 0;
    $newcamilla = 0;
    $newflats = 0;
}
$json = array(
    'newsupport' => $newsupport,
    'newaccounts' => $newaccounts,
    'newflats' => $newflats,
    'newcamilla' => $newcamilla
);
echo json_encode($json);
