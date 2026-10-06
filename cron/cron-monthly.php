#!/usr/bin/php

<?php

// cron-monthly.php
// monthly tidy up

$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');
$now = time();

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    // cleaer TestReadings older than 2 months
	$clearbefore = date("Y-m-d",strtotime("-2 Months"));
	$sql = "DELETE FROM `TestReadings` WHERE `Date` < '$clearbefore'";
	if ($dbcnx->query($sql)) {
		$deleted = $dbcnx->affected_rows;
		$resultMessage .= "Deleted $deleted TestReadings prior to $clearbefore<br />";
	} else {
		$resultMessage .= "Could NOT Delete any TestReadings prior to $clearbefore<br />";
	}

	// clear UtlHighReadings older than 2 months
	$clearbefore = date("Y-m-d",strtotime("-2 Months"));
	$sql = "DELETE FROM `UtlHighReadings` WHERE `Date` < '$clearbefore'";
	if ($dbcnx->query($sql)) {
		$deleted = $dbcnx->affected_rows;
		$resultMessage .= "Deleted $deleted UtlHighReadings prior to $clearbefore<br />";
	} else {
		$resultMessage .= "Could NOT Delete any UtlHighReadings prior to $clearbefore<br />";
	}

	// clear UtlReadings older than 2 years
	$clearbefore = date("Y-m-d",strtotime("-2 Years"));
	$sql = "DELETE FROM `UtlReadings` WHERE `xdatetime` < '$clearbefore'";
	if ($dbcnx->query($sql)) {
		$deleted = $dbcnx->affected_rows;
		$resultMessage .= "Deleted $deleted UtlReadings prior to $clearbefore<br />";
	} else {
		$resultMessage .= "Could NOT Delete any UtlReadings prior to $clearbefore<br />";
	}

	// clear actions_log older than 2 years
	$clearbefore = strtotime("-2 Years");
	$sql = "DELETE FROM `actions_log` WHERE `when` < $clearbefore";
	if ($dbcnx->query($sql)) {
		$deleted = $dbcnx->affected_rows;
		$resultMessage .= "Deleted $deleted actions_log prior to " . date("Y-m-d H:i:s",$clearbefore) . "<br />";
	} else {
		$resultMessage .= "Could NOT Delete any actions_logs prior to " . date("Y-m-d H:i:s",$clearbefore) . "<br />";
	}

	$dbcnx->close();
	
} else {
	$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
}

echo $resultMessage;

/* $mailto   = "David Sherwood <david@dsi3.net>";
$from     = "Dephna Robot <robot@dephna.com>";
$mailsjt  = "Dephna Cron Monthly";
$mime_boundary = md5(uniqid(time()));
$headers .= "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
$message  = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "<p><b>Dephna Cron Monthly</b></p>\r\n";
$message .= $resultMessage;
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";
mail($mailto, $mailsjt, $message, $headers); */

?>
