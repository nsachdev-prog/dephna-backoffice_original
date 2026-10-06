<?php
include('settings.php');
include('session.php');
if (isset($_SESSION['user'])) {
  $dr = $_GET['dr'];
	$pdf = $_GET['pdf'];
	$filename = $_GET['file'];
  if ($dr) {
    $fullpath = "../../dis2_db/$dr/" . $pdf;
  } else {
    $fullpath = "../../dis2_db/pdf/" . $filename;
  }
  header('Content-type: application/pdf');
  header('Content-Disposition: inline; filename="' . $pdf . '"');
  header('Content-Transfer-Encoding: binary');
  header('Accept-Ranges: bytes');
  @readfile($fullpath);
}
?>
