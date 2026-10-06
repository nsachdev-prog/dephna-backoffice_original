<?php
include('settings.php');
include('session.php');
if (isset($_SESSION['user'])) {
    $pdf = $_GET['pdf'];
    $fullpath = "../../dis2_db/pdf/pl/" . $pdf;
    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf . '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    @readfile($fullpath);
}
?>
