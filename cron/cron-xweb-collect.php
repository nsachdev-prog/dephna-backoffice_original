#!/usr/bin/php

<?php
// runs every 5 to 10 mins on each site/subsite. argv[1] = siteid argv[2] = xsub
// collects data from xweb and saves it to xweb_history1
// checks for callouts and sends emails

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$cronjob = 1;
chdir(__DIR__);
require_once "../PHPMailer/src/SMTP.php";
require_once "../PHPMailer/src/PHPMailer.php";
require_once "../PHPMailer/src/Exception.php";
include_once('../public_html/backoffice/settings.php');

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$siteid = $argv[1];
	// check if xsub is set.  If not, default to 1
	// xsub is used to differentiate between multiple xweb devices on the same site.
	if ($argv[2]) { $xsub = $argv[2]; } else { $xsub = 1; }

	if ($siteid) {

		include('../public_html/backoffice/PaxGetAccessToken.php');
		$pax_access_token = $access_token;
		$pax_api_access_point = $api_access_point;

		// get site details
		$sql = "SELECT title, xwEngineerDefault, xwEngineer1name, xwEngineer1email, xwEngineer1phone, xwEngineer1text,
					   xwEngineer2name, xwEngineer2email, xwEngineer2phone, xwEngineer2text, xwOOOWK, xwOOOWE, xwOOOBH
			FROM sites
			WHERE ID = '$siteid'
		";
		if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
			$row = $result->fetch_assoc();
			$sitename          = $row['title'];
			$xwEngineerDefault = $row['xwEngineerDefault']; // 1 = engineer 1 at all times.  2 = engineer 2 when out of hours (+weekends and bank holidays)
			$xwOOOWK 		 = $row['xwOOOWK']; // out of hours working days
			$xwOOOWE 		 = $row['xwOOOWE']; // out of hours weekends
			$xwOOOBH 		 = $row['xwOOOBH']; // out of hours bank holidays

			if (!$xwOOOWK) { $xwOOOWK = "00:00-00:00"; }
			if (!$xwOOOWE) { $xwOOOWE = "00:00-00:00"; }
			if (!$xwOOOBH) { $xwOOOBH = "00:00-00:00"; }

			// get bank holidays uk/england and wales
			$bankholidays = file_get_contents("https://www.gov.uk/bank-holidays.json");
			$bankholidays = json_decode($bankholidays, true);
			$bankholidays = $bankholidays['england-and-wales']['events'];

			// is today a bank holiday?
			$bankholiday = 0;
			foreach ($bankholidays as $thisbankholiday) {
				$bankholidaydate = $thisbankholiday['date'];
				if ($bankholidaydate == date("Y-m-d")) {
					$bankholiday = 1;
				}
			};

			// is today a weekend?
			$dayofweek = date("w");
			if (($dayofweek == 0) OR ($dayofweek == 6)) {
				// weekend
				$weekend = 1;
			} else {
				$weekend = 0;
			}

			$useEngineer2 = 0;

			// set engineer details
			if ($xwEngineerDefault == 2) {
				if ($bankholiday) {
					// bank holiday
					list($xwOOOFr,$xwOOOTo) = explode("-",$xwOOOBH);
				} elseif ($weekend) {
					// weekend
					list($xwOOOFr,$xwOOOTo) = explode("-",$xwOOOWE);
				} else {
					// working day
					list($xwOOOFr,$xwOOOTo) = explode("-",$xwOOOWK);
				}

				// if $xwOOOFr is greater than $xwOOOTo then it is overnight
				if ($xwOOOFr > $xwOOOTo) {
					// overnight
					if ((date("H:i") >= $xwOOOFr) OR (date("H:i") <= $xwOOOTo)) {
						$useEngineer2 = 1;
					}
				} else {
					// normal
					if (($xwOOOFr <= date("H:i")) AND ($xwOOOTo >= date("H:i"))) {
						$useEngineer2 = 1;
					}
				}
			}

			if ($useEngineer2) {
				$xwEngineername  = $row['xwEngineer2name'];
				$xwEngineeremail = $row['xwEngineer2email'];
				$xwEngineerphone = $row['xwEngineer2phone'];
				$xwEngineertext  = $row['xwEngineer2text'];
			} else {
				$xwEngineername  = $row['xwEngineer1name'];
				$xwEngineeremail = $row['xwEngineer1email'];
				$xwEngineerphone = $row['xwEngineer1phone'];
				$xwEngineertext  = $row['xwEngineer1text'];
			}

			$wa_messages = array();  // array to hold whatsapp messages to be sent at the end of the script

			include('../public_html/backoffice/XWebGetAccessToken.php');
			if ($access_token) {

				// Get sensor ids from XWebAuth
				$sql = "SELECT * FROM XWebAuth WHERE siteid = '$siteid' AND xsub = '$xsub'";
				if ($xresult = $dbcnx->query($sql) AND $xresult->num_rows) {
					$xrow = $xresult->fetch_assoc();
				}

				// get cold room settings
				$sql = "SELECT * FROM cr_settings WHERE ID = '1'";
				if ($sresult = $dbcnx->query($sql) AND $sresult->num_rows) {
					$srow = $sresult->fetch_assoc();
					$maxDiffFreezer  = $srow['maxDiffFreezer'];
					$maxDiffChiller  = $srow['maxDiffChiller'];
					$evapDiffFreezer = $srow['evapDiffFreezer'];
					$evapDiffChiller = $srow['evapDiffChiller'];
					$evapOverFreezer18 = $srow['evapOverFreezer18'];
					$evapOverFreezer20 = $srow['evapOverFreezer20'];
					$evapOverChiller = $srow['evapOverChiller'];
					$evapOverDefrostMins = $srow['evapOverDefrostMins'];
					$evapOverDefrostTimes = $srow['evapOverDefrostTimes'];
					$evapGasDiff     = $srow['evapGasDiff'];
					$evapGasTestTime = $srow['evapGasTestTime'];
					$CODelaySetting  = $srow['CODelay'];
					$sendWhatsappPower = $srow['sendWhatsappPower'];
					$sendWhatsappGasLeak = $srow['sendWhatsappGasLeak'];
					$sendWhatsappOther = $srow['sendWhatsappOther'];
				} else {
					$maxDiffFreezer  = 10;
					$maxDiffChiller  = 5;
					$evapDiffFreezer = 8;
					$evapDiffChiller = 4;
					$CODelaySetting  = 3;
					$resultMessage .= "Could Not Get Cold Room Settings.<br>";
				}

				// Get runtime data from xweb
				$postdata = http_build_query(
					array(
						'auth'     => $access_token,
						'action'   => 'runtime'
					)
				);
		
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
				curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
				curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
				$result = curl_exec($ch);
		
				if ($result !== FALSE) {
					$result = json_decode($result, TRUE);
					if ($result['status'] == "ok") {

						// check if flagged is set.  If it is, reset it to 0 and fldate to 0
						// $flagged is set when the xweb device is not responding.  It is reset when the xweb device responds again.
						if ($flagged) {
							$dbcnx->query("UPDATE XWebAuth SET flagged = '0', fldate = '0' WHERE ID = '$xid'");
						}

						$incidents = array();
						$inccount  = 1;
						$CRTestMessages = array();
						$CRCount = 0;

						$datetime = date("Y-m-d H:i");
						$now = time();

						// loop through each device, process and save the data to xweb_history1
						foreach($result['runtime']['devices'] AS $device) {

							$xstatus  = 0;
							$thisevapOverDefrostTimes = $evapOverDefrostTimes;
							$deviceid = $device['id'];
							$points   = $device['points'];
							$data = array();
							foreach($points AS $point) {
								$data[$point['id']] = $point['value'];
							}
							$unitname = str_replace($xrow['prefix'],"",$device['name']);
							$unitname = trim($unitname);
							$sql = "SELECT b.custid AS custref, u.ID AS unitid, u.setpoint, u.engineermode, u.nocallout, u.nocalloutid, u.nocalloutto, u.approvedoff, u.evapOverFreezer18, u.evapOverFreezer20, u.evapOverDefrostTimes, u.toggleRoomProd,
											c.compname, c.ColdRoomContact, c.ColdRoomPhone, c.username, c.telephone, c.email, c.aemails
								FROM bookings AS b
								LEFT JOIN users AS c ON c.custref = b.custid
								LEFT JOIN units AS u ON u.ID = b.unitid
								WHERE b.siteid = '$siteid' AND u.title LIKE '%$unitname%'
							";
							if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
								$urow = $uresult->fetch_assoc();
								$custref      = $urow['custref'];
								$compname     = $urow['compname'];
								$unitid       = $urow['unitid'];
								$nocallout    = $urow['nocallout'];
								$nocalloutid  = $urow['nocalloutid'];
								$nocalloutto  = $urow['nocalloutto'];
								$engineermode = $urow['engineermode'];
								$approvedoff  = $urow['approvedoff'];
								$toggleRoomProd = $urow['toggleRoomProd'];
								$ColdRoomContact = $urow['ColdRoomContact'];
								$ColdRoomPhone   = $urow['ColdRoomPhone'];
								$username        = $urow['username'];
								$telephone       = $urow['telephone'];
								$email           = $urow['email'];
								$aemails         = $urow['aemails'];
								if ($urow['evapOverFreezer18']) { $evapOverFreezer18 = $urow['evapOverFreezer18']; }
								if ($urow['evapOverFreezer20']) { $evapOverFreezer20 = $urow['evapOverFreezer20']; }
								if ($urow['evapOverDefrostTimes']) {
									$thisevapOverDefrostTimes = $urow['evapOverDefrostTimes'];
									$defrostText = "Unit";
								} else {
									$defrostText = "Default";
								}
								if (!$ColdRoomContact) { $ColdRoomContact = $username; }
								if (!$ColdRoomPhone)   { $ColdRoomPhone = $telephone; }
								if (!$aemails) { $aemails = $email; }

							} else {
								$custref  = 0;
								$compname = "Empty";
								$aemails  = "";
								$ColdRoomContact = "";
								$ColdRoomPhone   = "";
								// try and get a unit id directly from units
								$sql = "SELECT ID, engineermode, nocallout, nocalloutto, approvedoff
										FROM units
										WHERE siteid = '$siteid' AND title LIKE '%$unitname%'
								";
								if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
									$urow = $uresult->fetch_assoc();
									$unitid = $urow['ID'];
									$nocallout = $urow['nocallout'];
									$nocalloutto = $urow['nocalloutto'];
									$engineermode = $urow['engineermode'];
									$approvedoff = $urow['approvedoff'];
								} else {
									$unitid = 0;
									$nocallout = 0;
									$nocalloutto = 0;
									$engineermode = 0;
									$approvedoff = 0;
								}
							}

							// do we need to reset nocallout?
							if ($nocallout) {
								if ($nocalloutto < $datetime) {
									// nocallout has expired
									if ($unitid) {
										$sql = "UPDATE `units` SET `nocallout` = '0', `nocalloutid` = '0', `nocalloutfrom` = '0', `nocalloutto` = '0' WHERE `ID` = '$unitid'";
										if ($dbcnx->query($sql)) {
											$sql = "INSERT INTO xweb_actions_notes VALUES (
												null,
												'$nocalloutid',
												'$siteid',
												'$unitid',
												'$custref',
												'$datetime',
												'',
												'No Call Out Expired',
												'$now',
												'Cron'
											)";
											$dbcnx->query($sql);
										} else {
											$action .= "Could not reset no callout";
										}
									} else {
										$action .= "Could not reset no callout";
									}
								}
							}

							$onoff       = $data[$xrow['onoff']];
							$setpoint    = $data[$xrow['setpoint']];
							$temperature = $data[$xrow['temperature']];
							$product     = $data[$xrow['product']];
							$evaporator  = $data[$xrow['evaporator']];
							$product     = $data[$xrow['product']];
							$doorswitch  = $data[$xrow['doorswitch']];
							$dooropen    = $data[$xrow['dooropen']];
							$doorheater  = $data[$xrow['doorheater']];
							$defrost     = $data[$xrow['defrost']];
							$keyboard    = $data[$xrow['keyboard']];
							$light       = $data[$xrow['light']];

							// check for missing data
							if ($setpoint == "") {
								$setpoint = 0;
							}
							if ($temperature == "") {
								$temperature = 0;
							}
							if ($product == "") {
								$product = 0;
							}
							if ($evaporator == "") {
								$evaporator = 0;
							}
							if ($product == "") {
								$product = 0;
							}
							if ($doorswitch == "") {
								$doorswitch = 0;
							}
							if ($dooropen == "") {
								$dooropen = 0;
							}
							if ($doorheater == "") {
								$doorheater = 0;
							}
							if ($defrost == "") {
								$defrost = 0;
							}
							if ($keyboard == "") {
								$keyboard = 0;
							}
							if ($light == "") {
								$light = 0;
							}

							if ($onoff != 1 AND $onoff != 0) {
								// onoff is not 1 or 0. set it to 0
								$onoff = 0;
								$xstatus = 20;
							}

							// get paxton last access time and location if custref is not empty
							if ($custref != "Empty") {
								$paxtonResult = "";
								if ($pax_access_token) {

									$headers = array(
										'Authorization: bearer '.$pax_access_token,
										'Accept: application/json'
									);

									$postdata = http_build_query(
										array(
											'query' => "SELECT TOP 1 firstName, surname, lastAccessTime, lastKnownLocation
													FROM UsersEx
													WHERE field4_50 = '$custref'
													ORDER BY lastAccessTime DESC"
										)
									);

									$ch = curl_init();
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $pax_api_access_point."api/v1/customquery/querydb/?$postdata");
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLINFO_HEADER_OUT, true);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
									curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
									$lastseen = curl_exec($ch);
							
									if ($lastseen !== FALSE) {

										if ($lastseen = json_decode($lastseen, TRUE)) {

											if ($lastseen) {

												$usernm = $lastseen[0]['firstName'];
												if ($lastseen[0]['surname']) {
													$usernm .= " {$lastseen[0]['surname']}";
												}
												if (strlen($usernm) > 14) { $usernm = substr($usernm, 0, 14); }
												$lastAccessTime    = $lastseen[0]['lastAccessTime'];
												$lastKnownLocation = $lastseen[0]['lastKnownLocation'];
												list($lkdate,$lktime) = explode("T",$lastAccessTime);
												$paxtonResult = "$usernm $lastKnownLocation $lkdate $lktime";

											}

										} else {
											$paxtonResult = "Unknown";
										}

									} else {
										$paxtonResult = "Unknown";
									}

								} else {
									$paxtonResult = "Unknown";
								}

							} else {
								$paxtonResult = "Nobody";
							}

							// reset nocallout if unit is empty and nocallout is set
							if (($nocallout > 0) AND ($compname == "Empty")) {
								// Unit is empty and no callout is set
								$sql = "UPDATE `units` SET `nocallout` = '0', `nocalloutfrom` = '0', `nocalloutto` = '0' WHERE `ID` = '$unitid'";
								if ($dbcnx->query($sql)) {
									$sql = "INSERT INTO `xweb_actions` VALUES (
										null,
										'$datetime',
										'$siteid',
										'$unitid',
										'$unitname',
										'$deviceid',
										'$custref',
										'$compname',
										'7',
										'Cron',
										'0',
										'',
										'No Callout Reset',
										'$paxtonResult',
										'0',
										'0',
										'',
										'No'
									)";
									$dbcnx->query($sql);
								} else {
									$action .= "Could not reset no callout";
								}
							}

							if ($setpoint > -5) {
								$maxDiff  = $maxDiffChiller;
								$evapDiff = $evapDiffChiller;
							} else {
								$maxDiff  = $maxDiffFreezer;
								$evapDiff = $evapDiffFreezer;
							}

							// Are we using the product sensor or room sensor for temperature score?
							$originalTemperature = $temperature; // save the original temperature for logging
							if ($toggleRoomProd == 1) {
								// product sensor will be used for temperature score
								$temperature = $product;
								$roomprodText = "Product";
							} else {
								$roomprodText = "Room";
							}

							$colhead = "<tr><td><i>Date/Time</i></td><td><i>Eng M</i></td><td align=\"right\"><i>Set Point</i></td><td align=\"right\"><i>$roomprodText&deg;</i></td><td align=\"right\"><i>Evap&deg;</i></td><td><i>Defrost</i></td><td><i>Door</i></td></tr>\r\n";

							// get the last temp, prod, defrost, door and temp unchanged scores
							$sql = "SELECT temperature, product, score, dscore, fscore, spcscore, odcscore, setpoint, CODelay
									FROM xweb_history1
									WHERE siteid = '$siteid'
									AND unitid = '$unitid'
									ORDER BY xdatetime DESC
									LIMIT 0,1
							";
							if (($lastresult = $dbcnx->query($sql)) AND ($lastresult->num_rows)) {
								$lastrow = $lastresult->fetch_assoc();
								if ($toggleRoomProd == 1) {
									$previousTemperature  = $lastrow['product'];      // last temperature
								} else {
									$previousTemperature  = $lastrow['temperature'];  // last temperature
								}
								$lastsetpoint     = 0;    // last setpoint no longer used
								$lasttscore       = $lastrow['score'];        // last temperature score
								$lastdscore       = $lastrow['dscore'];       // last door open score
								$lastfscore       = $lastrow['fscore'];       // last defrost score
								$lastodcscore     = $lastrow['odcscore'];     // last door open control: fan and compressor
								$CODelay          = $lastrow['CODelay'];
							} else {
								$previousTemperature  = 0;
								$lastsetpoint     = 0;
								$lasttscore       = 0;
								$lastdscore       = 0;
								$lastfscore       = 0;
								$lastodcscore     = 0;
								$CODelay          = 0;
							}

							if ($temperature == $previousTemperature) {
								$tscore = $lasttscore;
							} elseif ($temperature > $previousTemperature) {
								$tscore = $lasttscore + 1;
							} elseif ($temperature < $previousTemperature) {
								$tscore = 0;
							}

							if ($doorswitch == 1) {
								$dscore = $lastdscore + 1;
							} else {
								$dscore = 0;
							}

							if ($lastfscore > 0) {
								$fscore = $lastfscore - 1;
							} elseif ($defrost == 1) {
								$fscore = 10;
							} else {
								$fscore = 0;
							}

							if ($lastodcscore > 0) {
								if ($temperature < $previousTemperature) {
									$tscore = $lasttscore - 1;
								}
								elseif ($temperature > $previousTemperature) {
									$tscore = $lasttscore + 1;
								} else {
									$tscore = $lasttscore;
								}
								$odcscore = $lastodcscore - 1;
							} else {
								$odcscore = 0;
							}
	
							// save it
							$sql = "INSERT INTO xweb_history1 VALUES (
								null,
								'$datetime',
								'$deviceid',
								'$siteid',
								'$xsub',
								'$sitename',
								'$unitid',
								'$unitname',
								'$custref',
								'$engineermode',
								'$nocallout',
								'$approvedoff',
								'$onoff',
								'$keyboard',
								'$setpoint',
								'$originalTemperature',
								'$evaporator',
								'$product',
								'$doorswitch',
								'$dooropen',
								'$doorheater',
								'$light',
								'$defrost',
								'$xstatus',
								'$paxtonResult',
								'$tscore',
								'$dscore',
								'$fscore',
								'0',
								'0',
								'$odcscore',
								'0',
								'0'
							)";
							if ($dbcnx->query($sql)) {
								$xid = $dbcnx->insert_id;

								$tempdiff = ABS($setpoint - $temperature);

								// see if unit is on test
								$sql = "SELECT `ID`, `testUntil` FROM `xweb_test_headers` WHERE `status` = '0' AND `siteid` = '$siteid' AND `unitid` = '$unitid'";
								if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
									$trow = $tresult->fetch_assoc();
									$testid = $trow['ID'];
									$testUntil = $trow['testUntil'];
								} else {
									$testid = 0;
									$testUntil = 0;
								}

								$data1 = array(
									'site' => $sitename,
									'unit' => $unitname,
									'cust' => $custref,
									'we'   => $weekend,
									'bh'   => $bankholiday,
									'oo'   => $useEngineer2,
									'onoff' => $onoff
								);
								$data1 = json_encode($data1,TRUE);

								// check if callout already active
								$sql = "SELECT * FROM xweb_actions
										WHERE siteid = '$siteid'
										AND unitid = '$unitid'
										AND xstatus = '1'
										AND cleared = '0'
								";
								if (($nresult = $dbcnx->query($sql)) AND ($nresult->num_rows)) {
									$calloutactive = 1;
								} else {
									$calloutactive = 0;
								}

								if (($engineermode == 0) AND ($onoff == 1)) {
									// Engineer mode is off and power is on
									// check some stuff
	
									if ($keyboard == 0) {
										// keyboard lock is off. switch it on
										$postdata = http_build_query(
											array(
												'auth'   => $access_token,
												'action' => 'send_command',
												'device'  => $deviceid,
												'command' => '17'
											)
										);
				
										$ch = curl_init();
										curl_setopt($ch, CURLOPT_POST, 1);
										curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
										curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
										curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
										$oresult = curl_exec($ch);
				
										if ($oresult !== FALSE) {
											$oresult = json_decode($oresult, TRUE);
											if ($oresult['status'] == "ok") {
												$action = "KeyB Locked";
												$xstatus = 3;
											} else {
												$action = "KeyB Lock Fail";
												$xstatus = 33;
											}
										} else {
											$action = "KeyB Lock Fail";
											$xstatus = 33;
										}
										// log it
										$sql = "UPDATE `xweb_history1` SET `xstatus` = '$xstatus' WHERE ID = '$xid'";
										$dbcnx->query($sql);
										$sql = "INSERT INTO `xweb_actions` VALUES (
											null,
											'$datetime',
											'$siteid',
											'$unitid',
											'$unitname',
											'$deviceid',
											'$custref',
											'$compname',
											'$xstatus',
											'Cron',
											'0',
											'',
											'$action',
											'$paxtonResult',
											'0',
											'0',
											'',
											'No'
										)";
										$dbcnx->query($sql);
									}
	
									if ($compname == "Empty") {
										// power is on but unit is empty. switch it off
										$postdata = http_build_query(
											array(
												'auth'   => $access_token,
												'action' => 'send_command',
												'device'  => $deviceid,
												'command' => '14'
											)
										);
				
										$ch = curl_init();
										curl_setopt($ch, CURLOPT_POST, 1);
										curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
										curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
										curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
										$oresult = curl_exec($ch);
				
										if ($oresult !== FALSE) {
											$oresult = json_decode($oresult, TRUE);
											if ($oresult['status'] == "ok") {
												$action = "Pwr Off Empty";
												$xstatus = 2;
											} else {
												$action = "Pwr Off Fail";
												$xstatus = 22;
											}
										} else {
											$action = "Pwr Off Fail";
											$xstatus = 22;
										}
										// log it
										$sql = "UPDATE xweb_history1 SET xstatus = '$xstatus' WHERE ID = '$xid'";
										$dbcnx->query($sql);
										$sql = "INSERT INTO `xweb_actions` VALUES (
											null,
											'$datetime',
											'$siteid',
											'$unitid',
											'$unitname',
											'$deviceid',
											'$custref',
											'$compname',
											'$xstatus',
											'Cron',
											'0',
											'',
											'$action',
											'$paxtonResult',
											'0',
											'0',
											'',
											'No'
										)";
										$dbcnx->query($sql);
									}
								}

								if ($testUntil > 0) {
									// test mode is on
									// check if test mode has expired
									if ($testUntil < $now) {
										// test mode has expired
										// switch it off
										$sql = "UPDATE `xweb_test_headers` SET `status` = '1' WHERE `ID` = '$testid'";
										if ($dbcnx->query($sql)) {
											$CRTestMessages[$CRCount]['sitename'] = $sitename;
											$CRTestMessages[$CRCount]['unitname'] = $unitname;
											$CRTestMessages[$CRCount]['siteid'] = $siteid;
											$CRTestMessages[$CRCount]['unitid'] = $unitid;
											$CRTestMessages[$CRCount]['testid'] = $testid;
											$CRCount++;
											$sql = "INSERT INTO `xweb_actions` VALUES (
												null,
												'$datetime',
												'$siteid',
												'$unitid',
												'$unitname',
												'$deviceid',
												'$custref',
												'$compname',
												'0',
												'Cron',
												'0',
												'',
												'Test Mode Off',
												'',
												'0',
												'0',
												'',
												'No'
											)";
											$dbcnx->query($sql);

											if ($compname == "Empty") {
												// swith the power off
												$postdata = http_build_query(
													array(
														'auth'   => $access_token,
														'action' => 'send_command',
														'device'  => $deviceid,
														'command' => '14'
													)
												);
												$ch = curl_init();
												curl_setopt($ch, CURLOPT_POST, 1);
												curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
												curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
												curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
												$oresult = curl_exec($ch);
											}
										}
									} else {
										// add line to xweb_test_lines
										$sql = "INSERT INTO `xweb_test_lines` VALUES (
											null,
											'$testid',
											'$datetime',
											'$engineermode',
											'$nocallout',
											'$approvedoff',
											'$onoff',
											'$keyboard',
											'$setpoint',
											'$temperature',
											'$evaporator',
											'$doorswitch',
											'$dooropen',
											'$doorheater',
											'$light',
											'$defrost'
										)";
										$dbcnx->query($sql);
									}
								}

								if (!$engineermode AND $compname != "Empty" AND $approvedoff == $onoff ) {
									// 'approved off' is not set but the power is off
									// or
									// 'approved off' is set but the power is on
									// toggle power

									if ($onoff == 1) {
										$cmd = 14;
										$powerreason = "Approved off is set but could not switch the power off!";
									} else {
										$cmd = 15;
										$powerreason = "Power is off and could not switch it back on!";
									}

									$postdata = http_build_query(
										array(
											'auth'   => $access_token,
											'action' => 'send_command',
											'device'  => $deviceid,
											'command' => $cmd
										)
									);
			
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_POST, 1);
									curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
									curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
									curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
									$oresult = curl_exec($ch);
			
									$powerfault = 1;
									if ($oresult !== FALSE) {
										$oresult = json_decode($oresult, TRUE);
										if ($oresult['status'] == "ok") {
											$action = "Pwr Tgl";
											$xstatus = 8;
											$powerfault = 0;
										} else {
											$action = "Pwr Tgl Fail";
											$xstatus = 18;
										}
									} else {
										$action = "Pwr Tgl Fail";
										$xstatus = 18;
									}

									if ($powerfault) {
										if (!$calloutactive AND !$nocallout) { // call out
											$incidents[$inccount]['message']  = "<p><b>$sitename</b></p>\r\n";
											$incidents[$inccount]['message'] .= "<p><b>Unit $unitname - $compname</b></p>\r\n";
											$incidents[$inccount]['message'] .= "<p>Last Seen: $paxtonResult</p>\r\n";
											$incidents[$inccount]['message'] .= "<p><b>Power Approval Fault</b></p>\r\n";
											$incidents[$inccount]['message'] .= "<p>$powerreason</p>\r\n";
											$incidents[$inccount]['data'] = $data1;
											$inccount++;
										}
										
										if ($sendWhatsappPower) {
											// send a whatsapp message
											$wa_messages[] = "$sitename\n$unitname - $compname\nPower Fault\nSP:$setpoint $roomprodText:$temperature EV:$evaporator\n$powerreason\nClient Contact:\n$ColdRoomContact\n$ColdRoomPhone";
										}
										
										// update history record with status
										$sql = "UPDATE xweb_history1 SET xstatus = '1', CODelay = '0' WHERE ID = '$xid'";
										$dbcnx->query($sql);

										// log it
										$sql = "INSERT INTO `xweb_actions` VALUES (
											null,
											'$datetime',
											'$siteid',
											'$unitid',
											'$unitname',
											'$deviceid',
											'$custref',
											'$compname',
											'1',
											'Cron',
											'0',
											'',
											'Callout-PF',
											'$paxtonResult',
											'0',
											'0',
											'',
											'No'
										)";
										if ($dbcnx->query($sql)) {
											$aid = $dbcnx->insert_id;
											$sql = "INSERT INTO `xweb_actions_notes` VALUES (
												null,
												'$aid',
												'$siteid',
												'$unitid',
												'$custref',
												'$datetime',
												'Power Fault',
												'$powerreason<br>Engineer $xwEngineername emailed on $xwEngineeremail',
												'$now',
												'Cron'
											)";
											$dbcnx->query($sql);
										}
									} else {
										$sql = "UPDATE xweb_history1 SET xstatus = '$xstatus' WHERE ID = '$xid'";
										$dbcnx->query($sql);
									}
								}

								if (!$engineermode AND !$calloutactive AND !$nocallout) {
									// engineer mode is off
									// no callout is not set (We can call out)
									// a callout is not currently active

									if ($onoff == 1 AND $compname != "Empty") {
										// power is on
										// unit is occupied
										// get some history
										$sql = "SELECT *
												FROM xweb_history1
												WHERE siteid = '$siteid' AND unitid = '$unitid'
												ORDER BY xdatetime DESC
												LIMIT 0,24
										";
										if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
											$temp = "";
											$defrostWasOn = 0;
											$DoorWasOpen = 0;
											$DoorWasOpenLastHour = 0;
											$possGasLeak = 0;
											$setpointChanged = 0;
											$CODelayCount = 0;
											$n = 1;
											$evapRise = 0;
											$tempHasFallen = 0;
											$previousTemp = 0;
											$latestTemp = 0;
											$readings = $evapGasTestTime / 5; // number of readings to test for gas leak
											while ($urow = $uresult->fetch_assoc()) {
												if ($urow['setpoint'] != $setpoint) {
													$setpointChanged = 1; // setpoint changed in the last 2 hours
												}
												if ($urow['CODelay'] > 0) {
													$CODelayCount++;
												}
												if ($urow['defrost'] == 1) {
													$defrosttxt = "ON";
													if ($n < 13) {
														$defrostWasOn++; // defrost on in the last hour
													}
												} else {
													$defrosttxt = "OFF";
												}
												if ($urow['doorswitch'] == 1) {
													$thisdoor = "Open";
													$DoorWasOpen++; // door open in the last two hours
													if ($n < 13) {
														$DoorWasOpenLastHour++; // door open in the last hour
													}
												} else {
													$thisdoor = "Closed";
												}
												if ($urow['engineermode'] == 1) { $engineermodetxt = "ON"; } else { $engineermodetxt = "OFF"; }
												$thissetpoint = $urow['setpoint'];
												if ($toggleRoomProd == 1) {
													$thistemperature = $urow['product'];
												} else {
													$thistemperature = $urow['temperature'];
												}
												if ($n <= $readings) {
													if ($urow['evaporator'] - $evapGasDiff >= $setpoint) {
														$possGasLeak++; // possible gas leak
													} 
												}
												if ($n < 13) { // last hour
													if ($n < 6) { // last 25 mins
														if ($n == 1) { 
															$evapLow = $urow['evaporator']; // most recent evap temp
															$latestTemp = $thistemperature; // most recent temp
														}
														if ($n == 2) {
															$previousTemp = $thistemperature; // 2nd most recent temp
															if ($latestTemp < $previousTemp) {
																$tempHasFallen = 1; // temp has fallen since latest reading
															}
														}
														if ($n == 3) {
															$previousTemp = $thistemperature; // 3rd most recent temp
															if ($latestTemp < $previousTemp) {
																$tempHasFallen = 1; // temp has fallen since latesrt reading
															}
														}
														elseif ($urow['evaporator'] < $evapLow)  { $evapLow = $urow['evaporator']; } // progressively older evap temp
													}
													// prepare history of last hour in case we need it
													$temp .= "<tr><td>{$urow['xdatetime']}</td><td>$engineermodetxt</td><td align=\"right\">$thissetpoint</td><td align=\"right\">$thistemperature</td><td align=\"right\">{$urow['evaporator']}</td><td>$defrosttxt</td><td>$thisdoor</td></tr>\r\n";
												}
												$n++;
											}
											if (ABS($evaporator - $evapLow) > $evapDiff) {
												// the evaporator has risen sharply
												$evapRise = 1;
											}
											if (($possGasLeak == $readings) AND !$defrostWasOn AND !$DoorWasOpenLastHour AND !$setpointChanged) {
												// possible gas leak
												// no defrost in the last hour
												// door not open in the last hour
												// setpoint not changed in the last 2 hours
												// do call out
												$incidents[$inccount]['message']  = "<p><b>$sitename</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p><b>Unit $unitname - $compname</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p><b>Possible Gas Leak - $possGasLeak / $readings readings above threshold</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p>Last Seen: $paxtonResult</p>\r\n";
												$incidents[$inccount]['message'] .= "<p>Last 12 readings:</p>\r\n";
												$incidents[$inccount]['message'] .= "<table class=\"table table-striped table-bordered\">\r\n";
												$incidents[$inccount]['message'] .= "<tr><th>Date/Time</th><th>Eng Mode</th><th>Setpoint</th><th>$roomprodText Temp</th><th>Evaporator</th><th>Defrost</th><th>Door</th></tr>\r\n";
												$incidents[$inccount]['message'] .= $temp;
												$incidents[$inccount]['message'] .= "</table>\r\n";
												$incidents[$inccount]['data'] = $data1;

												if ($sendWhatsappGasLeak) {
													// send a whatsapp message
													$wa_messages[] = "$sitename\n$unitname - $compname\nPossible Gas Leak\nSP:$setpoint $roomprodText:$temperature EV:$evaporator\nEvaporator Temp: $evaporator\nSetpoint: $setpoint\nLast Seen: $paxtonResult";
													$incidents[$inccount]['message'] .= "<p><b>Whatsapp Message Sent</b></p>\r\n";
												} else {
													$incidents[$inccount]['message'] .= "<p><b>Send Whatsapp Message Not Set</b></p>\r\n";
												}
												$inccount++;

												// update history record with status
												$sql = "UPDATE xweb_history1 SET xstatus = '1', CODelay = '0' WHERE ID = '$xid'";
												$dbcnx->query($sql);

												// log it
												$sql = "INSERT INTO `xweb_actions` VALUES (
													null,
													'$datetime',
													'$siteid',
													'$unitid',
													'$unitname',
													'$deviceid',
													'$custref',
													'$compname',
													'1',
													'Cron',
													'0',
													'',
													'Callout-GL',
													'$paxtonResult',
													'0',
													'0',
													'',
													'No'
												)";
												if ($dbcnx->query($sql)) {
													$aid = $dbcnx->insert_id;
													$sql = "INSERT INTO `xweb_actions_notes` VALUES (
														null,
														'$aid',
														'$siteid',
														'$unitid',
														'$custref',
														'$datetime',
														'Possible Gas Leak Detected',
														'Evaporator Temp: $evaporator<br>Setpoint: $setpoint<br>$roomprodText Temperature: $temperature<br>Engineer $xwEngineername emailed on $xwEngineeremail',
														'$now',
														'Cron'
													)";
													$dbcnx->query($sql);
												}
											}
											$uresult->free();
										}
											
										// check if defrost needs to go on
										if (!$defrostWasOn AND
											   (($setpoint < -5 AND $setpoint > -20) AND ($evaporator < $evapOverFreezer18))
											OR (($setpoint <= -20) AND ($evaporator < $evapOverFreezer20))
											OR (($setpoint > -5) AND ($evaporator < $evapOverChiller))
										) {
											// set parameter MdF to evapOverDefrostMins
											$parameters = array(
												'section' => 1,
												'label'   => 'MdF',
												'value'   => $evapOverDefrostMins
											);
											$targets = array();
											$targets[] = $deviceid;
											$postdata = http_build_query(
												array(
													'auth'       => $access_token,
													'action'     => 'write_parameters'
												)
											);
											$postdata .= "&targets=" . json_encode($targets);
											$postdata .= "&parameters=" . json_encode($parameters);
											$ch = curl_init();
											curl_setopt($ch, CURLOPT_POST, 1);
											curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
											curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
											curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
											$dfresult = curl_exec($ch);
											if ($dfresult !== FALSE) {
												$dfresult = json_decode($dfresult, TRUE);
												if ($dfresult['status'] == "ok") {

													// send active defrost command
													$postdata = http_build_query(
														array(
															'auth'   => $access_token,
															'action' => 'send_command',
															'device'  => $deviceid,
															'command' => '16'
														)
													);
							
													$ch = curl_init();
													curl_setopt($ch, CURLOPT_POST, 1);
													curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
													curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
													curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
													$oresult = curl_exec($ch);
							
													if ($oresult !== FALSE) {
														$oresult = json_decode($oresult, TRUE);
														if ($oresult['status'] == "ok") {
															$action = "Dfrst Evap";
															$xstatus = 4;
															$fscore = 10;
															$defrostWasOn++;
															// add defrost to history
															// update history record with status
															$sql = "UPDATE xweb_history1 SET defrost = '1', fscore = '$fscore', xstatus = '$xstatus' WHERE ID = '$xid'";
															$dbcnx->query($sql);
														} else {
															$action = "Could not Defrost";
														}
													} else {
														$action = "No reply from Xweb Defrost";
													}
													// log it
													$sql = "INSERT INTO `xweb_actions` VALUES (
														null,
														'$datetime',
														'$siteid',
														'$unitid',
														'$unitname',
														'$deviceid',
														'$custref',
														'$compname',
														'$xstatus',
														'Cron',
														'0',
														'',
														'$action',
														'$paxtonResult',
														'0',
														'0',
														'',
														'No'
													)";
													$dbcnx->query($sql);

												} elseif ($dfresult['status'] == "error") {
													$resultMessage .= "Error: <br />{$dfresult['message']}<br />";
												} else {
													$resultMessage .= "Error: <br />Unknown Response from XWeb setting defrost length.<br /> . $dfresult<br />";
												}

											} else {
												$resultMessage .= "Error: <br />No Response from XWeb setting defrost length.<br />";
											}
										}

										if (($tempdiff > $maxDiff) AND (!$defrostWasOn) AND (!$setpointChanged) AND (!$DoorWasOpen)) {
											// temperature is greater than allowed
											// defrost is off and door is closed
											// setpoint has not been changed in the last 2 hours

											if ($fscore == 2) {
												// we tried a defost 40 mins ago
												// try a restart

												// switch off first
												$postdata = http_build_query(
													array(
														'auth'   => $access_token,
														'action' => 'send_command',
														'device'  => "$deviceid",
														'command' => "14"
													)
												);
						
												$ch = curl_init();
												curl_setopt($ch, CURLOPT_POST, 1);
												curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
												curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
												curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
												$oresult = curl_exec($ch);
						
												if ($oresult !== FALSE) {
													$oresult = json_decode($oresult, TRUE);
													if ($oresult['status'] == "ok") {
														// switch back on again
														$postdata = http_build_query(
															array(
																'auth'   => $access_token,
																'action' => 'send_command',
																'device'  => "$deviceid",
																'command' => "15"
															)
														);
														$ch = curl_init();
														curl_setopt($ch, CURLOPT_POST, 1);
														curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
														curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
														curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
														curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
														curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
														$oresult = curl_exec($ch);
														if ($oresult !== FALSE) {
															$oresult = json_decode($oresult, TRUE);
															if ($oresult['status'] == "ok") {
																$action = "Restarted";
																$xstatus = 9;
																$sql = "UPDATE xweb_history1 SET xstatus = '$xstatus' WHERE ID = '$xid'";
																$dbcnx->query($sql);
															} else {
																$action = "Restart Fail";
																$xstatus = 19;
																$sql = "UPDATE xweb_history1 SET xstatus = '$xstatus' WHERE ID = '$xid'";
																$dbcnx->query($sql);
															}
														} else {
															$action = "Restart No Reply";
														}
														
													} else {
														$action = "Restart Failed";
													}
												} else {
													$action = "Restart No Reply";
												}
												// log it
												$sql = "INSERT INTO `xweb_actions` VALUES (
													null,
													'$datetime',
													'$siteid',
													'$unitid',
													'$unitname',
													'$deviceid',
													'$custref',
													'$compname',
													'$xstatus',
													'Cron',
													'0',
													'',
													'$action',
													'$paxtonResult',
													'0',
													'0',
													'',
													'No'
												)";
												$dbcnx->query($sql);
	
											}
											
											elseif (!$DoorWasOpen AND !$defrostWasOn AND !$evapRise) {

												if ($CODelay > ($CODelaySetting-1)) {

													// but did the teperature go down?
													if ($tempHasFallen) {
														// temperature has fallen
														// update history record with  call out delay
														$CODelay--;
														$sql = "UPDATE `xweb_history1` SET `xstatus` = '11', `CODelay` = '$CODelay' WHERE `ID` = '$xid'";
														$dbcnx->query($sql);
													} else {

														// defrost and restart didn't work
														// do call out
														$incidents[$inccount]['message']  = "<p><b>$sitename</b></p>\r\n";
														$incidents[$inccount]['message'] .= "<p><b>Unit $unitname - $compname</b></p>\r\n";
														$incidents[$inccount]['message'] .= "<p>Last Seen: $paxtonResult</p>\r\n";
														$incidents[$inccount]['message'] .= "<p>$roomprodText Temperature is well above set point and is still rising.</p>\r\n";
														$incidents[$inccount]['message'] .= "<table cellpadding=\"3\">$colhead\r\n$temp\r\n</table>\r\n";
														$incidents[$inccount]['data'] = $data1;
														$thisincident = ">$roomprodText Temperature is well above set point and is still rising.<br><table cellpadding=\"3\">$colhead\n$temp\n</table>";

														if ($sendWhatsappOther) {
															$wa_messages[] = "$sitename\n$unitname - $compname\nTemperature Failure\nSP:$setpoint $roomprodText:$temperature EV:$evaporator\n$roomprodText Temperature is well above set point and is still rising.\nClient Contact:\n$ColdRoomContact\n$ColdRoomPhone";
															$incidents[$inccount]['message'] .= "<p><b>Whatsapp Message Sent</b></p>\r\n";
														} else {
															$incidents[$inccount]['message'] .= "<p><b>Send Whatsapp Message Not Set</b></p>\r\n";
														}
														$inccount++;

														// update history record with status
														$sql = "UPDATE `xweb_history1` SET `xstatus` = '1', `CODelay` = '0' WHERE ID = '$xid'";
														$dbcnx->query($sql);

														// log it
														$sql = "INSERT INTO `xweb_actions` VALUES (
															null,
															'$datetime',
															'$siteid',
															'$unitid',
															'$unitname',
															'$deviceid',
															'$custref',
															'$compname',
															'1',
															'Cron',
															'0',
															'',
															'Callout-TF',
															'$paxtonResult',
															'0',
															'0',
															'',
															'No'
														)";
														if ($dbcnx->query($sql)) {
															$aid = $dbcnx->insert_id;
															$sql = "INSERT INTO `xweb_actions_notes` VALUES (
																null,
																'$aid',
																'$siteid',
																'$unitid',
																'$custref',
																'$datetime',
																'Temperature Failure',
																'$thisincident<br>Engineer $xwEngineername emailed on $xwEngineeremail',
																'$now',
																'Cron'
															)";
															$dbcnx->query($sql);
														}
													}
												} else {
													// update history record with  call out delay
													$CODelay++;
													$sql = "UPDATE `xweb_history1` SET `xstatus` = '11', `CODelay` = '$CODelay' WHERE `ID` = '$xid'";
													$dbcnx->query($sql);
												}
											}
										}

										elseif (($dscore > 24) OR ($odcscore > 0)) {

											// door has been open for more than two hours
											// or we are we are testing the door open control
											// try changing door open control if we're not already trying it
											if ($odcscore == 0) {
												// change it

												$parameters = array();
												$parameters[] = array(
													'section' => 1,
													'label'   => 'Odc',
													'value'   => 'no'
												);
										
												$targets = array();
												$targets[] = $deviceid;

												$postdata = http_build_query(
													array(
														'auth'   => $access_token,
														'action' => 'write_parameters'
													)
												);

												$postdata .= "&targets=" . json_encode($targets);
												$postdata .= "&parameters=" . json_encode($parameters);

												$ch = curl_init();
												curl_setopt($ch, CURLOPT_POST, 1);
												curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
												curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
												curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
												$oresult = curl_exec($ch);

												if ($oresult !== FALSE) {
													$oresult = json_decode($oresult, TRUE);
													if ($oresult['status'] == "ok") {
														$sql = "UPDATE `xweb_history1` SET `odcscore` = '5', xstatus = '10', score = '5' WHERE ID = '$xid'";
														$dbcnx->query($sql);
														$action = "Odc=no";
														$xstatus = 10;
													}
													elseif ($oresult['status'] == "error") {
														$action = "Odc=Failed";
													}
										
												} else {
													$action = "Odc=No Reply";
												}
												// log it
												$sql = "INSERT INTO `xweb_actions` VALUES (
													null,
													'$datetime',
													'$siteid',
													'$unitid',
													'$unitname',
													'$deviceid',
													'$custref',
													'$compname',
													'$xstatus',
													'Cron',
													'0',
													'',
													'$action',
													'$paxtonResult',
													'0',
													'0',
													'',
													'No'
												)";
												$dbcnx->query($sql);
												
											} elseif ($odcscore == 1 AND $tscore < 3) {

												// Temperature went down after changing 'door open control'
												// switch must be faulty
												// do call out

												$incidents[$inccount]['message']  = "<p><b>$sitename</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p><b>Unit $unitname - $compname</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p>Last Seen: $paxtonResult</p>\r\n";
												$incidents[$inccount]['message'] .= "<p>Door switch is faulty</p>\r\n";
												$incidents[$inccount]['message'] .= "<table cellpadding=\"3\">$colhead\r\n$temp\r\n</table>\r\n";
												$incidents[$inccount]['data'] = $data1;
												$inccount++;
												$thisincident = "Door switch is faulty.<br><table cellpadding=\"3\">$colhead\n$temp\n</table>";

												// update history record with status
												$sql = "UPDATE `xweb_history1` SET `xstatus` = '1', score = '0' WHERE ID = '$xid'";
												$dbcnx->query($sql);

												// log it
												$sql = "INSERT INTO `xweb_actions` VALUES (
													null,
													'$datetime',
													'$siteid',
													'$unitid',
													'$unitname',
													'$deviceid',
													'$custref',
													'$compname',
													'1',
													'Cron',
													'0',
													'',
													'Callout-DSF',
													'$paxtonResult',
													'0',
													'0',
													'',
													'No'
												)";
												if ($dbcnx->query($sql)) {
													$aid = $dbcnx->insert_id;
													$sql = "INSERT INTO `xweb_actions_notes` VALUES (
														null,
														'$aid',
														'$siteid',
														'$unitid',
														'$custref',
														'$datetime',
														'Engineer $xwEngineername emailed on $xwEngineeremail',
														'$thisincident',
														'$now',
														'Cron'
													)";
													$dbcnx->query($sql);
												}
											}
										} else {
											// reset CODelay
											$sql = "UPDATE `xweb_history1` SET `xstatus` = '0', `CODelay` = '0' WHERE ID = '$xid'";
											$dbcnx->query($sql);
										}

									}
									
									elseif (($nocallout == 0)
										AND (($onoff == "") OR ($onoff == 0))
										AND ($compname != "Empty")
										AND ($engineermode == 0)
										AND ($approvedoff == 0)
										AND (!$calloutactive)
									) {
										// no call out is off
										// Could not get data
										// unit is occupied
										// engineer mode is off
										// approved off is off
										// call out is not active

										// get some history
										$lastHour = date("Y-m-d H:i",strtotime("-1 Hour"));
										$nodata = 0;
										$sql = "SELECT ID
												FROM xweb_history1
												WHERE siteid = '$siteid'
												  AND unitid = '$unitid'
												  AND engineermode = '0'
												  AND approvedoff = '0'
												  AND onoff = '0'
												  AND xdatetime > '$lastHour'
										";
										if ($uresult = $dbcnx->query($sql) AND $uresult->num_rows) {
											$nodata = $uresult->num_rows;
											if ($nodata > 8) {

												$incidents[$inccount]['message'] .= "<p><b>$sitename</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p><b>Unit $unitname - $compname</b></p>\r\n";
												$incidents[$inccount]['message'] .= "<p>Last Seen: $paxtonResult</p>\r\n";
												$incidents[$inccount]['message'] .= "<p>Power off many times in the last hour.</p>";
												$incidents[$inccount]['data'] = $data1;
												$thisincident = "Power off many times in the last hour.";

												if ($sendWhatsappOther) {
													// send whatsapp message
													$wa_messages[] = "$sitename\n$unitname - $compname\nPower Off\nSP:$setpoint $roomprodText:$temperature PRD:$product EV:$evaporator\nClient Contact:\n$ColdRoomContact\n$ColdRoomPhone";
													$incidents[$inccount]['message'] .= "<p><b>Whatsapp Message Sent</b></p>\r\n";
												} else {
													$incidents[$inccount]['message'] .= "<p><b>Send Whatsapp Message Not Set</b></p>\r\n";
												}
												$inccount++;

												// update history record with status
												$sql = "UPDATE `xweb_history1` SET `xstatus` = '1', `CODelay` = '0' WHERE ID = '$xid'";
												$dbcnx->query($sql);

												// log it
												$sql = "INSERT INTO `xweb_actions` VALUES (
													null,
													'$datetime',
													'$siteid',
													'$unitid',
													'$unitname',
													'$deviceid',
													'$custref',
													'$compname',
													'1',
													'Cron',
													'0',
													'',
													'Callout-PF',
													'$paxtonResult',
													'0',
													'0',
													'',
													'No'
												)";
												if ($dbcnx->query($sql)) {
													$aid = $dbcnx->insert_id;
													$sql = "INSERT INTO `xweb_actions_notes` VALUES (
														null,
														'$aid',
														'$siteid',
														'$unitid',
														'$custref',
														'$datetime',
														'Power Off',
														'$thisincident<br>Engineer $xwEngineername emailed on $xwEngineeremail',
														'$now',
														'Cron'
													)";
													$dbcnx->query($sql);
												}
											}
										}
									}

									// check how many times defrost has been on in the last 3 hours
									$dcount = 0;
									$defrosts = 0;
									$lastThreeHours = date("Y-m-d H:i",strtotime("-3 Hour"));
									$sql = "SELECT ID, defrost
											FROM xweb_history1
											WHERE siteid = '$siteid'
											  AND unitid = '$unitid'
											  AND xdatetime > '$lastThreeHours'
									";	
									if ($dresult = $dbcnx->query($sql) AND $dresult->num_rows) {
										while ($drow = $dresult->fetch_assoc()) {
											if ($drow['defrost']) {
												$dcount++;
												if ($dcount == 1) {
													$defrosts++;
												}
											} else {
												$dcount = 0;
											}
										}
									}
									if ($defrosts > $thisevapOverDefrostTimes) {
										$incidents[$inccount]['message'] .= "<p><b>$sitename</b></p>\r\n";
										$incidents[$inccount]['message'] .= "<p><b>Unit $unitname - $compname</b></p>\r\n";
										$incidents[$inccount]['message'] .= "<p>Last Seen: $paxtonResult</p>\r\n";
										$incidents[$inccount]['message'] .= "<p>Defrost has been on $defrosts times in the last 3 hours.<br>$defrostText threshold is set to $thisevapOverDefrostTimes.</p>";
										$incidents[$inccount]['data'] = $data1;
										$thisincident = "Defrost has been on many times in the last 3 hours.";

										if ($sendWhatsappOther) {
											// send whatsapp message
											$wa_messages[] = "$sitename\n$unitname - $compname\nDefrost On\nSP:$setpoint $roomprodText:$temperature PRD:$product EV:$evaporator\nClient Contact:\n$ColdRoomContact\n$ColdRoomPhone";
											$incidents[$inccount]['message'] .= "<p><b>Whatsapp Message Sent</b></p>\r\n";
										} else {
											$incidents[$inccount]['message'] .= "<p><b>Send Whatsapp Message Not Set</b></p>\r\n";
										}
										$inccount++;

										// update history record with status
										$sql = "UPDATE `xweb_history1` SET `xstatus` = '1', `CODelay` = '0' WHERE ID = '$xid'";
										$dbcnx->query($sql);

										// log it
										$sql = "INSERT INTO `xweb_actions` VALUES (
											null,
											'$datetime',
											'$siteid',
											'$unitid',
											'$unitname',
											'$deviceid',
											'$custref',
											'$compname',
											'1',
											'Cron',
											'0',
											'',
											'Callout-DO',
											'$paxtonResult',
											'0',
											'0',
											'',
											'No'
										)";
										if ($dbcnx->query($sql)) {
											$aid = $dbcnx->insert_id;
											$sql = "INSERT INTO `xweb_actions_notes` VALUES (
												null,
												'$aid',
												'$siteid',
												'$unitid',
												'$custref',
												'$datetime',
												'Defrost On',
												'$thisincident<br>Engineer $xwEngineername emailed on $xwEngineeremail<br>$defrosts > $thisevapOverDefrostTimes using $defrostText Settings',
												'$now',
												'Cron'
											)";
											$dbcnx->query($sql);
										}
									}
								}

							} else {
								$resultMessage .= "Could not add new record to database<br>" . $dbcnx->error . "<br>\r\n";
							}
						}
					}
					elseif ($result['status'] == "error") {
						$resultMessage .= $result['status'] . "<br>" . $result['error'] . "<br>" . $result['message'] . "<br>\r\n";
					}
		
				} else {
					$resultMessage .= "Error: <br>XWeb NOT Responding.<br>\r\n";
					if (!$flagged) {
						$now = time();
						$dbcnx->query("UPDATE XWebAuth SET flagged = '1', fldate = '$now' WHERE ID = '$xid'");
						if ($sendWhatsappPower) {
							// send whatsapp message
							$wa_messages[] = "$sitename\nXweb not responding\nPotential power failure to building.\n\n";
						}
					}
				}

			} else {
				$resultMessage .= "Could NOT get access token<br>" . $AuthResultMessage . "<br>\r\n";
				if (!$flagged) {
					$now = time();
					$dbcnx->query("UPDATE XWebAuth SET flagged = '1', fldate = '$now' WHERE ID = '$xid'");
					if ($sendWhatsappPower) {
						// send whatsapp message
						$wa_messages[] = "$sitename\nXweb not responding\nPotential power failure to building.\n\n";
					}
				}
			}

		} else {
			$resultMessage .= "Could NOT get site<br>" . $dbcnx->error . "<br>\r\n";
		}

	} else {
		$resultMessage .= "Site id not present<br>\r\n";
	}

	$dbcnx->close();
} else {
	$resultMessage .= "Could not connect to database!<br>" . $dbcnx->connect_error;
}

if ($incidents) {
	// send email to engineer
	if ($xwEngineeremail) {
		$subject = "Callout Required at Dephna Estates";
		if ($useEngineer2) {
			$subject .= " $xwEngineeremail";
		}
		$from = "noreply@dephna.com";
		$fromname = "Dephna Robot";

		foreach ($incidents AS $key => $incident) {
		
			$message  = "<p><b>Engineer Needed</b></p>\r\n";
			$message .= $incident['message'] . "\r\n";
			$message .= "<p>[data]" . $incident['data'] . "[enddata]</p>\r\n";
			$message .= "<p>$xwEngineertext</p>\r\n";

			$mail = new PHPMailer(true);

			try {

				include('SMTP.php');

				$mail->setFrom($from, $fromname);
				$mail->addAddress($xwEngineeremail,$xwEngineername);
				$mail->addReplyTo($from, $fromname);
				$mail->addCC("support@dephna.com", "Dephna Support");
				// $mail->addCC("david@davidsherwood.co.uk", "David Sherwood");
				$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body    = $message;

				$mail->send();

			} catch (Exception $e) {
				$resultMessage .= "Email FAILED to $xwEngineeremail Mailer Error: {$mail->ErrorInfo}";
			}
		}
	}
}

if ($wa_messages) {
	$wa_result = "";
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
		$responseData = json_decode($response, true);
		if ($responseData && isset($responseData['result']) && $responseData['result'] === 'queued') {
			// okay, message queued successfully
			// $wa_result .= "<p>" . $wa_message . "<br>Response: " . $response . "</p>\n";
			// Response: {"id":"bbd059ee-2c0e-4e5f-8c24-e582e8c26230","result":"queued"}
		} else {
			$wa_result .= "<p>" . $wa_message . "<br>Response: " . $response . "</p>\n";
		}
		curl_close($ch);
	}
}

if ($CRTestMessages) {
	foreach ($CRTestMessages AS $CRTestMessage) {
		$datetime = date("Y-m-d H:i");
		$sitename = $CRTestMessage['sitename'];
		$unitname = $CRTestMessage['unitname'];
		$unitid = $CRTestMessage['unitid'];
		$siteid = $CRTestMessage['siteid'];
		$testid = $CRTestMessage['testid'];
		$subject = "Cold Room Test $sitename - $unitname";

		$message .= "<p><b>Cold Room Test Ready For Inspection</b></p>\r\n";
		$message .= "<p>Site: $sitename<br>Unit: $unitname</p>\r\n";
		$message .= "<p><a href=\"https://www.dephna.net/backoffice/coldrooms-xweb-tests-view.php?siteid=$siteid&unitid=$unitid&testid=$testid\">Click here to view the test</a></p>\r\n";
		$message .= "<p>\r\n";
		$message .= "</p>\r\n";

		$mail = new PHPMailer(true);

		try {
			include('SMTP.php');

			$mail->setFrom($from, "noreply@dephna.com", "Dephna Robot");
			$mail->addAddress("support@dephna.com", "Dephna Support");
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body    = $message;

			$mail->send();

		} catch (Exception $e) {
			$resultMessage .= "Email FAILED to support Mailer Error: {$mail->ErrorInfo}";
		}
	}
}

if ($resultMessage) {
	$datetime = date("Y-m-d H:i");
	$subject = "XWeb Collect Error $sitename $xsub";
	$mail = new PHPMailer(true);
	try {
		include('SMTP.php');

		$mail->setFrom("noreply@dephna.com", "Dephna Robot");
		$mail->addAddress("support@dephna.com", "Dephna Support");
		// $mail->addAddress("david@davidsherwood.co.uk", "David Sherwood");
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = "<p><b>XWeb Collect Error $sitename $xsub $datetime</b></p><p>$resultMessage</p>";

		$mail->send();

	} catch (Exception $e) {
		echo "Email FAILED to support Mailer Error: {$mail->ErrorInfo}";
	}
}
if ($wa_result) {
	$datetime = date("Y-m-d H:i");
	$subject = "XWeb Collect Whatsapp Error $sitename $xsub";
	$mail = new PHPMailer(true);
	try {
		include('SMTP.php');

		$mail->setFrom("noreply@dephna.com", "Dephna Robot");
		$mail->addAddress("david@davidsherwood.co.uk", "David Sherwood");
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = "<p><b>XWeb Collect Whatsapp Error $sitename $xsub $datetime</b></p>$wa_result";

		$mail->send();

	} catch (Exception $e) {
		echo "Email FAILED to support Mailer Error: {$mail->ErrorInfo}";
	}
}
?>
