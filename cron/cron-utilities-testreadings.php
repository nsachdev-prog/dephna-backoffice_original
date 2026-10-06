#!/usr/bin/php

<?php
// This script reads one meters at the given site and saves the results to TestReadings.
// it runs every 10 mins
$cronjob = 1;
chdir(__DIR__);
// set time out
set_time_limit(60);
include('../public_html/backoffice/settings.php');

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	require_once '../Phpmodbus/Phpmodbus/ModbusMaster.php';
	require_once '../Phpmodbus/Phpmodbus/ModbusMasterTcp.php';

	$today = date("Y-m-d");
	$time = date("H:i");
	$now = time();
	$wa_messages = array();

	// get settings
	$sql = "SELECT * FROM TestReadings_settings WHERE ID = '1'";
	if ($result = $dbcnx->query($sql) AND $result->num_rows) {
		$row = $result->fetch_assoc();
		$sendWhatsapp = $row['sendWhatsapp'];
	} else {
		echo "Settings Not Found\n";
	}

	$testmeters = array(
		'1477' => "1200",
		'1478' => "1200",
		'1479' => "1200",
		'1495' => "1200"
	);

	foreach($testmeters AS $mid => $alert) {

		echo "Reading Meter $mid Alert $alert\n";

		// get meter to read
		$sql = "SELECT m.*, s.title AS sitename
				FROM `UtlMeters` AS m
				LEFT JOIN sites AS s ON s.ID = m.siteid
				WHERE m.ID = '$mid'
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			echo "Meter Found\n";

			$row = $result->fetch_assoc();
				$siteid    = $row['siteid'];
				$sitename  = $row['sitename'];
				$gatewayid = $row['gatewayid'];
				$deviceid  = $row['deviceid'];
				$typeid    = $row['typeid'];
				$unitid    = $row['unitid'];
				$unitname  = $row['unitname'];
				$commodity = $row['commodity'];
				$recData   = array();
				$failure   = "";

				echo "Reading $sitename $unitname $commodity\n";

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

						$modbus = new ModbusMaster($ipaddress, $port, 'TCP');

						try {
							if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
								$recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
								echo "Read Multiple Input Registers\n";
							} else {
								$recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
								echo "Read Multiple Registers\n";
							}
						}
						catch (Exception $e) {

							echo $e->getMessage() . "\n";

							sleep(1); // wait 1 second

							try { // try again 1
								if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
									$recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
								} else {
									$recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
								}	
							}
							catch (Exception $e) {

								echo $e->getMessage() . "\n";

								sleep(1); // wait 1 second

								try { // try again 2
									if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
										$recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
									} else {
										$recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
									}	
								}
								catch (Exception $e) {
									echo $e->getMessage() . "\n";
								}
							}
						}
						
						// save results. even if there aren't any
						$recData = serialize($recData);

						$sql = "INSERT INTO TestReadings VALUES (
							null,
							'$today',
							'$time',
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

							$recData = unserialize($recData);

							// find 'Current%' inredata from meter type and if it's over 875 send a message
							$sql = "SELECT 	
								`title`,
								`unit`,
								`start`,
								`length`,
								`divisor`
								FROM `UtlRegisters`
                                WHERE `typeid` = '$typeid' AND `title` LIKE 'Current%'
                                ORDER BY `start`
							";
							$registers = $dbcnx->query($sql);
							if (($registers) && ($registers->num_rows)) {
								while ($reg1 = $registers->fetch_assoc()) {
									$title   = $reg1['title'];
									$unit    = $reg1['unit'];
									$start   = $reg1['start'];
									$length  = $reg1['length'];
									$divisor = $reg1['divisor'];

									if ($length) {

										$read = "";
										if ($unit == "ASCII") {
											for ($n=0; $n<$length; $n++) {
												$reg = $start + $n;
												$thisread = $recData[$reg];
												$thisread = chr($thisread);
												$read .= $thisread;
											}
										} else {
											for ($n=0; $n<$length; $n++) {
												$reg = $start + $n;
												$thisread = dechex($recData[$reg]);
												$thisread = str_pad($thisread, 2, "0", STR_PAD_LEFT);
												$read .= $thisread;
											}

											if ($typeid == 8) { // D340 Meter
												if ($length == 6) {
													$bit1 = substr($read, 8, 4);
													$bit2 = substr($read, 4, 4);
													$bit3 = substr($read, 0, 4);
													$read = $bit1.$bit2; // Ignore byte3. I don't know why this works!
												} else {
													$msb = substr($read, 4, 4);
													$lsb = substr($read, 0, 4);
													$read = $msb.$lsb;
												}
											}

											if (($typeid == 6) OR (($typeid == 7) AND ((strpos($title, "(Float)") !== FALSE) OR $title == "Meter Reading"))) {
												// D35 Meter or Water Meter Float Value)
												$read = unpack('G', hex2bin($read));
												$read = $read[1];
												$read = number_format($read,'3','.','');
											} else {
												$read = hexdec($read);
												if ($read) {
													if ($divisor) {
														$read = $read / $divisor;
													}
												}
											}
										}
										if ($read > $alert) {
											echo "High Current Reading: $title $read $unit\n";
											if ($sendWhatsapp) {
												$wa_messages[] = "$sitename $unitname\nHigh Current Reading:\n$title $read $unit";
											}
										} else {
											echo "Current Reading: $title $read $unit\n";
										}
									}
								}
							}
						} else {
							echo "Failed To Save $sitename $unitname $commodity\n";
							$failure = "Failed To Save Data";
						}

					} else {
						echo "Meter Type Not Found\n";
						$failure = "Meter Type Not Found";
					}

				} else {
					echo "Gateway Not Found\n";
					$failure = "Gateway Not Found";
					$typeid = 0;
				}


		} else {
			echo "No Meter Not Found\n";
		}
	}

	$dbcnx->close();

	if ($wa_messages) {
		foreach ($wa_messages AS $wa_message) {
			$postData = array(
				'group_admin' => $WA_GROUP_ADMIN,
				'group_name' => $WA_GROUP_NAME,
				'message' => $wa_message
			);
			$headers = array(
				'Content-Type: application/json',
				'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
				'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
			);
			$url = $WA_URL . $WA_INSTANCE_ID;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
			$response = curl_exec($ch);
			echo "Response: ".$response;
			curl_close($ch);
		}
	}

} else {
	echo "Could Not Connect To Database!\n";
}
