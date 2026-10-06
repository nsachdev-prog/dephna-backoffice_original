<?php
$telecoms_dir = "../../telecoms";
$fl = $_GET['fl'];
$site = $_GET['site'];
$filename = $telecoms_dir . "/" . $site . "/" . $fl;
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
?>
