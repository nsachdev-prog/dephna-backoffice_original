<?php
include('settings.php');
include('session.php');
if (isset($_SESSION['user'])) {
    $pdf = $_GET['pdf'];
    $filename = $_GET['file'];
    $fullpath = "/home/dephna/dis2_db/fit/" . $filename;
    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    @readfile($fullpath);
}
?>
