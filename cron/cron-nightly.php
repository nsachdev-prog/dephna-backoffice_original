#!/usr/bin/php

<?php

// cron-nightly.php
// nightly tidy up

$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');
$now = time();

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	// cancel terminationCheck if terminationWarning not set
	$sql = "UPDATE `users` SET `terminationCheck` = '' WHERE `terminationCheck` > '0' AND `terminationWarning` = ''";
	if ($dbcnx->query($sql)) {
		$updated = $dbcnx->affected_rows;
		$resultMessage .= "Updated $updated Clients with terminationCheck but no terminationWarning<br />";
	} else {
		$resultMessage .= "Could NOT Update any Clients with terminationCheck but no terminationWarning<br />";
	}

	$dbcnx->close();
	
} else {
	$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
}

echo $resultMessage;

/* $mailto   = "David Sherwood <david@dsi3.net>";
$from     = "Dephna Robot <robot@dephna.com>";
$mailsjt  = "Dephna Cron Nightly";
$mime_boundary = md5(uniqid(time()));
$headers .= "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
$message  = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "<p><b>Dephna Cron Nightly</b></p>\r\n";
$message .= $resultMessage;
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";
mail($mailto, $mailsjt, $message, $headers); */

?>
