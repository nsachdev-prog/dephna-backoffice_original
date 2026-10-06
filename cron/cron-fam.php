#!/usr/bin/php

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
# cron-fam.php - Written by David Sherwood - dsi3.net
# Client - Mat Blackmore/Dephna Impex.
#
# Allocates fire alarm tests early each monday morning

$cronjob = 1;
chdir(dirname(__FILE__));
include('../public_html/backoffice/settings.php');
require "../PHPMailer/src/SMTP.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/Exception.php";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$now = time();

	// get sites
	$sql = "SELECT ID, title FROM sites ORDER BY title";
	$sitesresult = $dbcnx->query($sql);
	if (($sitesresult) AND ($sitesresult->num_rows)) {
		$resultMessage = "<table>";
		while ($srow = $sitesresult->fetch_assoc()) {
			$siteid   = $srow['ID'];
			$sitename = $srow['title'];

			// skip North Acton, Neasden, whetstone, pr comm & pr personal
			if ($siteid == "2" OR $siteid == "4" OR $siteid == "26" OR $siteid == "27" OR $siteid == "36") {
				continue;
			}

			/* // get site tester
			// Currently hard coded for Platinum all sites 18-05-2020
			// $sql = "SELECT ID, FROM staff WHERE site = '$sitename' AND firetester = 'on' LIMIT 0,1";
			$sql = "SELECT username FROM staff WHERE ID = '137'";
			$staffresult = $dbcnx->query($sql);
			if (($staffresult) AND ($staffresult->num_rows)) {
				$strow = $staffresult->fetch_assoc();
				$staffid  = 137; // $strow['ID'];
				$username = $strow['username']; */

				// get fire points at this site
				$sql = "SELECT ID, title
						FROM fam_points
						WHERE siteid = '$siteid'
				";
				$pointresult = $dbcnx->query($sql);
				if (($pointresult) AND ($pointresult->num_rows)) {
					$testsarray = array();
					while ($prow = $pointresult->fetch_assoc()) {
						$pointid = $prow['ID'];
						$title   = $prow['title'];
						// get latest test for this point
						$sql = "SELECT testdatetime
								FROM fam_tests
								WHERE pointid = '$pointid'
								ORDER BY testdatetime DESC
								LIMIT 0,1
						";
						$testsresult = $dbcnx->query($sql);
						if (($testsresult) AND ($testsresult->num_rows)) {
							$trow = $testsresult->fetch_assoc();
							$datetime = $trow['testdatetime'];
						} else {
							$datetime = "0000-00-00 00:00";
						}
						$testsarray[$datetime] = "$pointid~$title";
					}
					if ($testsarray) {
						ksort($testsarray); // sort ascending
						$values = array_values($testsarray); // store the array values in a variable
						$value = array_shift($values); // get only first value
						list($pointid, $title) = explode("~", $value);
						$sql = "INSERT INTO fam_tests VALUES (
							null,
							'$pointid',
							'0',
							'0',
							'',
							'',
							'0',
							'$now',
							'System'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<tr><td>$sitename</td><td>$title</td><td>Scheduled<br></td></tr>\n";
						} else {
							$resultMessage .= "<tr><td>$sitename</td><td>$title</td><td>Could NOT add record to database</td></tr>\n";
						}
					}
				} else {
					$resultMessage .= "<tr><td>$sitename</td><td>&nbsp;</td><td>No Fire Points Found</td></tr>\n";
				}
			/* } else {
				$resultMessage .= "<tr><td>$sitename</td><td>&nbsp;</td><td>No Testers Assigned</td></tr>\n";
			} */
		}
		$resultMessage .= "</table>";
	} else {
		$resultMessage = "Could not get sites<br>" . $dbcnx->error . "<br>";
	}
	$dbcnx->close();
} else {
	$resultMessage = "Could not connect to database!<br>" . $dbcnx->connect_error . "<br>";
}

// Mail the processing report to $reportEmail

$subject = "Fire Alarm Test Allocations";
$from = "support@dephna.com";
$emails = array(
	"support@dephna.com"
);
if ($resultMessage) { $message = "<p>$resultMessage</p>"; }

$mail = new PHPMailer(true);

try {
	include('SMTP.php');
	$mail->setFrom($from);
	foreach ($emails as $to) {
		$mail->addAddress($to);
	}
	$mail->isHTML(true);
	$mail->Subject = $subject;
	$mail->Body    = $message;
	$mail->send();

} catch (Exception $e) {
	echo "Mailer Error: " . $mail->ErrorInfo;
}

?>
