<?php
$filename = $_GET['file'];
$fullpath = "/home/dephna/public_html/backoffice/pdf/waste/" . $filename;
header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($fullpath);
?>
