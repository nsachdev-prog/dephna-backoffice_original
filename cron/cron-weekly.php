#!/usr/bin/php

<?php

// cron-weekly.php
// weekly tidy up

$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');
$now = time();

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	// clear Xweb history older than 3 weeks
	$clearbefore = date("Y-m-d H:i",strtotime("-3 Weeks"));
	$sql = "DELETE FROM `xweb_history1` WHERE `xdatetime` < '$clearbefore'";
	if ($dbcnx->query($sql)) {
		$deleted = $dbcnx->affected_rows;
		$resultMessage .= "Deleted $deleted Xweb Records prior to $clearbefore<br />";
	} else {
		$resultMessage .= "Could NOT Delete any Xweb Records prior to $clearbefore<br />";
	}

	$dbcnx->close();
	
} else {
	$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
}

echo $resultMessage;

/* $mailto   = "David Sherwood <david@dsi3.net>";
$from     = "Dephna Robot <robot@dephna.com>";
$mailsjt  = "Dephna Cron Weekly";
$mime_boundary = md5(uniqid(time()));
$headers .= "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
$message  = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "<p><b>Dephna Cron Weekly</b></p>\r\n";
$message .= $resultMessage;
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";
mail($mailto, $mailsjt, $message, $headers); */

?>
