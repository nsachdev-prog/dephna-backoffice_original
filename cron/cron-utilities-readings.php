#!/usr/bin/php7.4

<?php
// This script reads all the meters for the given site and saves the results to utlReadings. argv[1] is the siteid
// failures are saved to utlFailures
// it runs every night in the early hours for each site with a cron job
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$cronjob = 1;
chdir(__DIR__);
// set time out
set_time_limit(120);
include '../public_html/backoffice/settings.php';

// connect to database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	require_once '../Phpmodbus/Phpmodbus/ModbusMaster.php';
	require_once '../Phpmodbus/Phpmodbus/ModbusMasterTcp.php';

	$today = date("Y-m-d");
	$now = time();

	$siteid = $argv[1];

	if ($siteid) {

		echo "Reading Meters For Site $siteid\n";

		// delete existing failures for this site
		$sql = "DELETE FROM UtlFailures WHERE siteid = '$siteid'";
		$dbcnx->query($sql);

		// delete existing readings for this site today
		$sql = "DELETE FROM UtlReadings WHERE xdatetime = '$today' AND siteid = '$siteid'";
		$dbcnx->query($sql);

		// get meters to read
		$sql = "SELECT m.*, s.title AS sitename
				FROM `UtlMeters` AS m
				LEFT JOIN sites AS s ON s.ID = m.siteid
				WHERE m.siteid = '$siteid'
				ORDER BY sitename, m.unitname
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			echo "Meters Found\n";

			$errorRow = "";
			$resultMessage = "";

			while ($row = $result->fetch_assoc()) {
				$mid       = $row['ID'];
				$sitename  = $row['sitename'];
				$gatewayid = $row['gatewayid'];
				$deviceid  = $row['deviceid'];
				$typeid    = $row['typeid'];
				$unitid    = $row['unitid'];
				$unitname  = $row['unitname'];
				$commodity = $row['commodity'];
				$failure   = "";

				$recData = array();

				echo "\nReading $sitename $unitname $commodity\n";

				// get gateway address and port
				$sql = "SELECT * FROM UtlGateways WHERE ID = '$gatewayid'";
				if (($gresult = $dbcnx->query($sql)) && ($gresult->num_rows)) {
					$grow = $gresult->fetch_assoc();
					$ipaddress = $grow['ipaddress'];
					$port      = $grow['port'];
					$gtitle    = $grow['title'];

					echo "Gateway $gtitle\n";

					// get register start address and qty
					$sql = "SELECT `title`, `start`, `qty` FROM `UtlTypes` WHERE `ID` = '$typeid'";
					if (($tresult = $dbcnx->query($sql)) && ($tresult->num_rows)) {
						$trow  = $tresult->fetch_assoc();
						$start  = $trow['start'];
						$qty    = $trow['qty'];
						$ttitle = $trow['title'];

						echo "Type $ttitle\n";

						$modbus = new ModbusMaster($ipaddress, $port, "TCP");

						// pause for a second
						sleep(1);

						try {
							if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
								$recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
								echo "Read Multiple Input Registers\n";
							} else {
								$recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
								echo "Read Multiple Registers\n";
							}
						}
						catch (\Throwable $e) {
							// wait 1 second and try again
							echo "Retry.......\n";
							sleep(2);
							$modbus = new ModbusMaster($ipaddress, $port, "TCP");
							try {
								if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
									$recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
									echo "Read Multiple Input Registers\n";
								} else {
									$recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
									echo "Read Multiple Registers\n";
								}
							}
							catch (\Throwable $e) {
								// Print error information if any
								echo "Error: " . $e->getMessage() . "\n";
								$errorRow .= "<tr><td>$sitename</td><td>$unitname</td><td>$commodity</td><td>$deviceid</td><td>$gtitle</td><td>$ttitle</td></tr>\r\n";
								$failure = "No Response";
							}
						}

						// save results. even if there aren't any
						$recData = serialize($recData);
						$sql = "INSERT INTO UtlReadings VALUES (
							null,
							'$today',
							'$mid',
							'$siteid',
							'$unitid',
							'$unitname',
							'$commodity',
							'$typeid',
							'$recData',
							'$now',
							'Robot'
						)";
						if ($dbcnx->query($sql)) {
							echo "Saved $sitename $unitname $commodity\n";
						} else {
							echo "Failed To Save $sitename $unitname $commodity\n";
							$resultMessage .= "Failed To Save $sitename $unitname $commodity<br />" . $dbcnx->error . "<br />\r\n";
							$failure = "Failed To Save Data";
						}

					} else {
						echo "Meter Type Not Found\n";
						$errorRow .= "<tr><td>$sitename</td><td>$unitname</td><td>$commodity</td><td>$deviceid</td><td>$gtitle</td><td>Meter Type Not Found</td></tr>\r\n";
						$failure = "Meter Type Not Found";
					}

				} else {
					echo "Gateway Not Found\n";
					$errorRow .= "<tr><td>$sitename</td><td>$unitname</td><td>$commodity</td><td>$deviceid</td><td>Gateway Not Found</td><td></td></tr>\r\n";
					$failure = "Gateway Not Found";
					$typeid = 0;
				}

				if ($failure) {
					// save failure
					$xdatetime = date("Y-m-d H:i:s");
					$sql = "INSERT INTO UtlFailures VALUES (
						null,
						'$xdatetime',
						'$mid',
						'$siteid',
						'$gatewayid',
						'$deviceid',
						'$typeid',
						'$unitid',
						'$unitname',
						'$commodity',
						'$failure',
						'$now',
						'Robot'
					)";
					if ($dbcnx->query($sql)) {
						echo "Saved Failure $sitename $unitname $commodity\n";
					} else {
						echo "Failed To Save Failure $sitename $unitname $commodity\n";
						$resultMessage .= "Failed To Save Failure $sitename $unitname $commodity<br />" . $dbcnx->error . "<br />\r\n";
					}
				}
			}

		} else {
			echo "No Meters Found\n";
			$resultMessage .= "No Meters Found!<br />\n";
		}
	} else {
		echo "Site id not present\n";
		$resultMessage .= "Site id not present<br />\r\n";
	}

	$dbcnx->close();

} else {
	echo "Could Not Connect To Database!\n";
	$resultMessage = "Could Not Connect To Database!<br />" . $dbcnx->connect_error;
}

if ($errorRow OR $resultMessage) {
	require "../PHPMailer/src/SMTP.php";
	require "../PHPMailer/src/PHPMailer.php";
	require "../PHPMailer/src/Exception.php";

	$subject = "$sitename Utilities Import Errors";
	$emails = array(
		"support@dephna.com"
	);
	$from = "noreply@dephna.com";
	$fromname = "Dephna Robot";
	$message = "<p>$sitename Import Errors</p>\r\n";
	if ($errorRow) {
		$errorTable = "<table>\r\n";
		$errorTable .= "<tr><td><b>Site</b></td><td><b>Unit</b></td><td><b>Commodity</b></td><td><b>Device</b></td><td><b>Gateway</b></td><td><b>Meter Type</b></td></tr>\r\n";
		$errorTable .= $errorRow;
		$errorTable .= "</table>\r\n";
		$message .= $errorTable;
	} else {
		$message .= "<p>No Import Errors</p>\r\n";
	}
	$message .= "<p>Other Errors</p>\r\n";
	if ($resultMessage) {
		$message .= "<p>$resultMessage</p>\r\n";
	} else {
		$message .= "<p>No Other Errors</p>\r\n";
	}
	
	$mail = new PHPMailer(true);

	try {

		include('SMTP.php');

		$mail->setFrom($from, $fromname);
		foreach ($emails as $email) {
			$mail->addAddress($email);
		}
		$mail->addReplyTo($from, $fromname);
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $message;

		$mail->send();

	} catch (Exception $e) {
		echo "Email FAILED Mailer Error: {$mail->ErrorInfo}\n";
	}
}
