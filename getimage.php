<?php
include('settings.php');
include('session.php');
if (isset($_SESSION['user'])) {
	$im  = $_GET['im'];
	$dr  = $_GET['dr'];
	$ext = strtolower(substr($im,strrpos($im,'.')+1));
	if ($dr) {
		if ($dr == "sup" OR $dr == "lds") {
			$im  = "../../dis2_db/$dr/" . $im;
		} elseif ($dr == "utl") {
			$im  = "../../dis2_db/img/$dr/" . $im;
		} else {
			$im  = "../../dis2_db/img/$dr/" . $im;
		}
	} else {
		$im  = "../db_images/" . $im;
	}
	if (($ext == "jpg") || ($ext == "jpeg")) {
		$image = imagecreatefromjpeg($im);
		header('Content-Type: image/jpeg');
		imagejpeg($image);
		imagedestroy($image);
	}
	elseif ($ext == "gif") {
		$image = imagecreatefromgif($im);
		header('Content-Type: image/gif');
		imagegif($image);
		imagedestroy($image);
	}
	elseif ($ext == "png") {
		$image = imagecreatefrompng($im);
		header('Content-Type: image/png');
		imagepng($image);
		imagedestroy($image);
	}
}
?>
