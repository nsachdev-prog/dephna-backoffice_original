<?php
$page = "Invoicing";
include('settings.php');
include('session.php');
if ($accessRights['Bookings'] > 2) {
	$fl = $_GET['fl'];
	$filename = "../../dis2_db/csv/$fl";
	if(file_exists($filename)) {
		$type = filetype($filename);
		header("Content-type: application/x-tar");
		header("Content-Disposition: attachment; filename=\"$fl\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($filename));
		header('Pragma: no-cache, must-revalidate');
		header('Expires: 0');
		readfile($filename);
	} else {
		die("Could not find the file");
	}
} else {
	die("Access Denied");
}
?>
