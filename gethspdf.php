<?php
include('settings.php');
include('session.php');
if (isset($_SESSION['user'])) {
  $dr = $_GET['dr'];
	$pdf = $_GET['pdf'];
  $fullpath = "../$dr/" . $pdf;
  header('Content-type: application/pdf');
  header('Content-Disposition: inline; filename="' . $pdf . '"');
  header('Content-Transfer-Encoding: binary');
  header('Accept-Ranges: bytes');
  @readfile($fullpath);
}
?>
