#!/usr/bin/php

<?php
// Main handler program for Dephna Auto Invoicing System
// This file was written to run as a cron job on the 1st
// day of each Month.  It uses other files with the prefix
// cron-inv that are also part of this cron job.

$cronjob = 1;
chdir(dirname(__FILE__));
require_once("Archive/Tar.php");
require_once('../tcpdf6.8/tcpdf.php');
include('../public_html/backoffice/settings.php');

$backup_dir = "../dis2_backups";
$db_dir     = "../dis2_db";
$sql_dir    = "../dis2_db/sql";
$csv_dir    = "../dis2_db/csv";
$image_path = "../public_html/db_images";
$genfile    = "$csv_dir/invoices.csv";
$datetime   = date("d/m/Y H:i");
$reportEmail   = "rajive@dephna.com,nimesh@dephna.com,david@davidsherwood.co.uk";
// $reportEmail   = "david@davidsherwood.co.uk";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	// Check to see if we are auto invoicing
	$sql = "SELECT * FROM `auto_invoicing` WHERE `ID`='1'";
	$airesult = $dbcnx->query($sql);
	if (($airesult) && ($airesult->num_rows)) {
		$airow = $airesult->fetch_assoc();
		$auto  = $airow['auto'];
	} else {
		$errorMessage = "Could not get auto invoicing settings";
	}

	if ($auto == "on") {

		// get next invoice number
		$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype`='I' ORDER BY `invnumber` DESC LIMIT 0, 1";
		$result = $dbcnx->query($sql);
		if (($result) && ($result->num_rows > 0)) {
			$row = $result->fetch_assoc();
			$invnum = $row['invnumber'];
			$invnum++;
		} else {
			$errorMessage = "Could not get next invoice number from database<br />";
		}

		// if no errors so far, do the big stuff. Stop if any stage fails.
		if (!$errorMessage) { include("cron-inv-generate.php"); }
		if (!$errorMessage) { include("cron-inv-post.php"); }
		if (!$errorMessage) { include("cron-inv-pdf-mailout.php"); }

	} else {
		$errorMessage = "Auto Invoicing is OFF<br />Nothing to do";
	}

	$dbcnx->close();
} else {
	$errorMessage = "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
}

// Mail the processing report to $reportEmail
$subject = "Auto Invoicing Report";
$from = "Robot <robot@dephna.com>";
$mime_boundary = md5(uniqid(time()));

$headers  = "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

$message = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "<p><b>Auto Invoicing Cron Job - $datetime</b></p>\n";
$message .= $invSummary;
$message .= $postResult;
$message .= $pdfmailoutResult;
if ($errorMessage)  { $message .= "<p><b>Errors That Halted Processing</b></p><p>$errorMessage</p>"; }
if ($resultMessage) { $message .= "<p><b>Messages</b></p><p>$resultMessage</p>"; }
$message .= "\r\n\r\n";

if (!$csvpathname) { $csvpathname = $genfile; $csvfilename = "invoices.csv"; }
if (is_file($csvpathname)) {
	// attach the csv file
	$filestr = file_get_contents($csvpathname);
	$data    = chunk_split(base64_encode($filestr));
	$fileatttype = "text/csv";
	$fileattname = $csvfilename;
	$message .= "--".$mime_boundary."\r\n";
	$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
	$message .= "Content-Transfer-Encoding: base64\r\n";
	$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
	$message .= $data;
	$message .= "\r\n\r\n";
}

$message .= "--".$mime_boundary."--";
mail($reportEmail, $subject, $message, $headers);
?>
