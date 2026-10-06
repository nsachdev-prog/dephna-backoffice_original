<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
// print heading
print "<p align=\"center\"><b>Confirm Booking</b></p>\n";

if (!$interupted) {

	if ($bkid) {
		if ($custref) {

			// see if there is an adobe agreement ID and status and get invoiced and inventory status
			$sql = "SELECT agreementID, invoiced, inventory FROM bk_manager WHERE ID = '$bkid'";
			if (($adresult = $dbcnx->query($sql)) AND ($adresult->num_rows)) {
				$adrow = $adresult->fetch_assoc();
				$agreementID = $adrow['agreementID'];
				$invoiced    = $adrow['invoiced'];
				$inventory   = $adrow['inventory'];
				if ($agreementID) {
					include('bk-adobeSignStatus.php');
				}
				if (!$inventory) {
					// to cover pre existing unpopulated field
					$inventory = "Yes";
				}
			}

			// get customer details
			$sql = "SELECT ID as custid, username, compname, email, aemails, addr1, addr2, addr3, addr4, telephone FROM users WHERE custref = '$custref'";
			$custresult = $dbcnx->query($sql);
			if (($custresult) && ($custresult->num_rows)) {
				$custrow = $custresult->fetch_assoc();
				$custid    = $custrow['custid'];
				$compname  = $custrow['compname'];
				$custname  = $custrow['username'];
				$email     = $custrow['email'];
				$aemails   = $custrow['aemails'];
				$addr1     = $custrow['addr1'];
				$addr2     = $custrow['addr2'];
				$addr3     = $custrow['addr3'];
				$addr4     = $custrow['addr4'];
				$telephone = $custrow['telephone'];
				if (!$aemails) { $aemails = $email; }

				if ($_POST['action'] == "confirm") {

					if (!$invoiced) {

						$mailit = $_POST['mailit'];
						if ($mailit) {
							$subject  = strip_tags($_POST['subject']);
							$body     = strip_tags($_POST['body']);
							$received = strip_tags($_POST['received']);
							$received = str_replace(',','', $received);
							$received = trim($received);
							if (!$subject) { $ersubject = "No Subject"; $e++; }
							if (!$body)    { $erbody    = "No Message"; $e++; }
							if (!$received) {
								$erreceived = "Enter Received"; $e++;
							} else {
								$received = trim(str_replace(",","",$received));
								if (!is_numeric($received)) {
									$erreceived = "Not Numeric"; $e++;
								}
							}
						}

						if (!$e) {

							if ($newcust) {
								// generate and save new password
								$thispassword = generatePassword();
								$thispassword = $dbcnx->real_escape_string($thispassword);
								$Salt = generateRandomString();
								$Algo = '6';
								$Rounds = '5000';
								$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
								$hashed_password = crypt($thispassword, $CryptSalt);
								$expires = 0;
								$sql = "UPDATE users SET password='$hashed_password', expires='$expires' WHERE custref='$custref'";
								$result = $dbcnx->query($sql);
							}

							// get next invoice number.
							$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype`='I' ORDER BY `invnumber` DESC LIMIT 0, 1";
							$iresult = $dbcnx->query($sql);
							if (($iresult) && ($iresult->num_rows)) {
								$irow = $iresult->fetch_assoc();
								$invnumber = $irow['invnumber'];
								$invnumber++;
							}

							// start invoice header
							$y = date("Y"); $m = date("m"); $d = date("d");
							$now = time();
							$sql = "INSERT INTO inv_headers VALUES (
								null,
								'I',
								'$invnumber',
								'$y',
								'$m',
								'$d',
								'$custref',
								'0',
								'0',
								'No',
								'',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$hid = $dbcnx->insert_id;
								$invoices[] = $invnumber;
							}

							$unitdeposits = array();
							$commdeposits = array();
							$dep_increments = array();
							$paxunits = array();

							// transfer units and ancilaries
							$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid'";
							$uresult = $dbcnx->query($sql);
							if (($uresult) && ($uresult->num_rows)) {
								while ($urow = $uresult->fetch_assoc()) {
									$uid         = $urow['ID'];
									$unitid      = $urow['unitid'];
									$unitname    = $urow['unitname'];
									$unittype    = $urow['unittype'];
									$contstartdate = $urow['datefrom'];
									$dateto      = $urow['dateto'];
									$bkrate      = $urow['rate'];
									$bkper       = $urow['per'];
									$leadmonth   = $urow['lead'];
									$notes       = $urow['notes'];
									$prfrom      = $urow['prfrom'];
									$prto        = $urow['prto'];
									$fip         = $urow['fip'];
									$otype       = $urow['TypeOfOpperation'];
									$risk        = $urow['Risk'];
									$depnotes    = $urow['depnotes'];
									$deptotal    = $urow['deptotal'];
									$unitdeposit = $urow['depunittotal'];
									$commdeposit = $urow['depcommtotal'];
									$increments  = $urow['increments'];
									$electricity = $urow['electricity'];
									$gas         = $urow['gas'];
									$hot         = $urow['hot'];
									$cold        = $urow['cold'];
									$CRPowerIgnore = $urow['CRPowerIgnore'];

									if (!$electricity) { $electricity = 0; }
									if (!$gas) { $gas = 0; }
									if (!$hot) { $hot = 0; }
									if (!$cold) { $cold = 0; }

									if (!$unitdeposit) { $unitdeposit = 0; }
									if (!$commdeposit) { $commdeposit = 0; }
									if (!$increments) { $increments = 0; }

									$fitouts = 0;
									$paxunits[] = $unitname;

									// do we have any util figures
									if (($electricity) OR ($gas) OR ($hot) OR ($cold)) {
										$utilLastUpd = time();
										$utilLastUpdBy = $_SESSION['username'];
									} else {
										$utilLastUpd = 0;
										$utilLastUpdBy = "";
									}

									// save deposits for deposit invoice
									$unitdeposits[$unitname] = $unitdeposit;
									$commdeposits[$unitname] = $commdeposit;
									$totaldeposits += ($unitdeposit + $commdeposit);
									if ($increments) {
										$dep_increments[] = $increments;
									}

									// get handover fitouts if kitchen
									if ($unittype == "Kitchen") {
										$sql = "SELECT ID, unitname FROM handover
												WHERE bkid = '$bkid'
													AND bkuid = '$uid'
													AND fitout = 'on'
										";
										$result = $dbcnx->query($sql);
										if (($result) && ($result->num_rows)) {
											$fitouts = 1;
										}
									}

									// add unit to bookings
									$now = time();
									$sql = "INSERT INTO bookings VALUES (
										null,
										'$siteid',
										'$unitid',
										'$unittype',
										'on',
										'$custref',
										'$bkid',
										'$contstartdate',
										'$dateto',
										'',
										'$bkrate',
										'$bkper',
										'$leadmonth',
										'$prfrom',
										'$prto',
										'{$_SESSION['user']}',
										'$now',
										'$notes',
										'$fip',
										'$otype',
										'$risk',
										'$electricity',
										'$gas',
										'$hot',
										'$cold',
										'0',
										'0',
										'0',
										'0',
										'$utilLastUpd',
										'$utilLastUpdBy',
										'0',
										''
									)";
									$iresult = $dbcnx->query($sql);
									if ($iresult) {
										$newbkid = $dbcnx->insert_id;
										$resultMessage .= "Unit $unitname added<br />";

										if ($unittype == "Car Parking") {
											// add to parking
											// get custid
											$sql = "SELECT ID FROM users WHERE custref='$custref'";
											$cresult = $dbcnx->query($sql);
											if (($cresult) && ($cresult->num_rows)) {
												$crow = $cresult->fetch_assoc();
												$custid  = $crow['ID'];
												$sql = "INSERT INTO parking VALUES (
													null,
													'$newbkid',
													'$unitname',
													'$bkrate',
													'',
													'$siteid',
													'$sitename',
													'$custid',
													'$compname',
													'$bkrate',
													'Yes',
													''
												)";
												$ipresult = $dbcnx->query($sql);
												if ($ipresult) {
													$resultMessage .= "Parking Bay $sitename $bay Added<br />";
												}
											} else {
												$resultMessage .= "Could NOT get custid for parking<br /> . $dbcnx->error" . "<br />";
											}
										}

										// construct datefrom and dateto
										if ($contstartdate) {
											$datefrom = date("d/m/y",$contstartdate);
											if ($bkper == "Monthly") {
												$dateto = strtotime("+1 month -1 day",$contstartdate);
											} elseif ($bkper == "Quarterly") {
												$dateto = strtotime("+3 months -1 day",$contstartdate);
											} elseif ($bkper == "Yearly") {
												$dateto = strtotime("+1 year -1 day",$contstartdate);
											}
											$dateto = date("d/m/y",$dateto);
										}

										// add unit to invoice
										if (($repgrp == "Office") OR ($repgrp == "Office V") OR ($repgrp == "Kitchen") OR ($repgrp == "Kitchen V")) {
											$details1 = "$sitename Licence";
										} else {
											$details1 = "$sitename $unittype";
										}
										$details2 = "$unitname $unittype";

										$sql = "SELECT vat FROM types WHERE title='$unittype'";
										$tvatresult = $dbcnx->query($sql);
										if (($tvatresult) && ($tvatresult->num_rows)) {
											$tvatrow = $tvatresult->fetch_assoc();
											$tvat = $tvatrow['vat'];
										} else {
											$tvat = "No";
										}
										if ($tvat == "Yes") {
											$vattotal = $bkrate/100*$vatRate;
											$vatrate = $vatRate;
										} else {
											$vattotal = 0;
											$vatrate = 0;
										}

										$qty = 1;
										$invnet += $bkrate;
										$linetotal = $bkrate + $vattotal;
										$invvat += $vattotal;
										$invtotal += $linetotal;

										$sql = "INSERT INTO inv_lines VALUES (
											null,
											'$hid',
											'$details1',
											'$details2',
											'$datefrom',
											'$dateto',
											'$qty',
											'$bkrate',
											'$bkrate',
											'$vatrate',
											'$vattotal',
											'$linetotal',
											'$sitename',
											'$unitname',
											'$unittype'
										)";
										$lresult = $dbcnx->query($sql);

										// add ancillaries
										$sql = "SELECT * FROM bkm_anc WHERE uid='$uid' ORDER BY anctitle";
										$anresult = $dbcnx->query($sql);
										if (($anresult) && ($anresult->num_rows)) {
											while ($anrow = $anresult->fetch_assoc()) {
												$ancid    = $anrow['ancid'];
												$anctitle = $anrow['anctitle'];
												$ancqty   = $anrow['qty'];
												$ancrate  = $anrow['rate'];
												$ancvat   = $anrow['vat'];

												if ($ancid == 5) {
													$newinternet++; // internet added so do ice later
												}

												$sql = "INSERT INTO bookings_an VALUES (null,'$newbkid','$custref','$ancid','$anctitle','$ancqty','$ancrate','$ancvat','Month')";
												$ianresult = $dbcnx->query($sql);
												if ($ianresult) {
													$resultMessage .= "Ancillary $anctitle added<br />";

													// add to invoice
													// get report group
													$sql = "SELECT ancgroup FROM ancillaries WHERE title='$anctitle'";
													$gresult = $dbcnx->query($sql);
													if (($gresult) && ($gresult->num_rows)) {
														$grow = $gresult->fetch_assoc();
														$repgrp = $grow['ancgroup'];
													} else {
														$repgrp = "Unknown";
													}

													$linenet = $ancrate*$ancqty;
													$invnet += $linenet;
													if ($ancvat == "Yes") {
														$vattotal = $linenet/100*$vatRate;
														$vatrate = $vatRate;
													} else {
														$vattotal = 0;
														$vatrate = 0;
													}
													$linetotal = $linenet + $vattotal;
													$invvat += $vattotal;
													$invtotal += $linetotal;

													$sql = "INSERT INTO inv_lines VALUES (
														null,
														'$hid',
														'$sitename $repgrp',
														'$anctitle',
														'$datefrom',
														'$dateto',
														'$ancqty',
														'$ancrate',
														'$linenet',
														'$vatrate',
														'$vattotal',
														'$linetotal',
														'$sitename',
														'$anctitle',
														'$repgrp'
													)";
													$lresult = $dbcnx->query($sql);

												} else {
													$resultMessage .= "Could not add ancillary $anctitle<br /> . $dbcnx->error" . "<br />";
												}
											}
										} else {
											$resultMessage .= "No ancillaries on this order<br />";
										}

									} else {
										$resultMessage .= "Could NOT add unit $unitname to live database.<br />Have not added ancillaries or deposit increments<br /> . $dbcnx->error" . "<br />";
									}
								}

							} else {
								$resultMessage .= "No Units Found<br />";
							}

							// post invoice total
							$sql = "UPDATE inv_headers SET invtotal='$invtotal', outstanding='$invtotal' WHERE ID='$hid'";
							$tresult = $dbcnx->query($sql);

							// post deposit invoice if any
							if ($totaldeposits) {
								// start invoice header
								$invnumber++;
								$sql = "INSERT INTO inv_headers VALUES (
									null,
									'I',
									'$invnumber',
									'$y',
									'$m',
									'$d',
									'$custref',
									'$totaldeposits',
									'$totaldeposits',
									'No',
									'',
									'$now',
									'{$_SESSION['username']}'
								)";
								$hresult = $dbcnx->query($sql);
								if ($hresult) {
									$invoices[] = $invnumber;
									$hid = $dbcnx->insert_id;
								}

								if ($unitdeposits) {
									foreach ($unitdeposits AS $key => $value) {
										$check = intval($value);
										if ($check) {
											$sql = "INSERT INTO inv_lines VALUES (
												null,
												'$hid',
												'$sitename Deposit',
												'$key Unit Deposit',
												'',
												'',
												'1',
												'$value',
												'$value',
												'0',
												'0',
												'$value',
												'$sitename',
												'$key',
												'Deposit'
											)";
											$lresult = $dbcnx->query($sql);
										}
									}
								}
								if ($commdeposits) {
									foreach ($commdeposits AS $key => $value) {
										$check = intval($value);
										if ($check) {
											$sql = "INSERT INTO inv_lines VALUES (
												null,
												'$hid',
												'$sitename Deposit',
												'$key Telecoms Deposit',
												'',
												'',
												'1',
												'$value',
												'$value',
												'0',
												'0',
												'$value',
												'$sitename',
												'$key',
												'Deposit'
											)";
											$lresult = $dbcnx->query($sql);
										}
									}
								}
								// add deposit increments
								if ($dep_increments) {
									foreach ($dep_increments AS $increments) {
										$thisincrements = explode("|",$increments);
										foreach ($thisincrements AS $value) {
											list($year,$month,$amount) = explode("-",$value);
											if ($amount) {
												$sql = "INSERT INTO deposits VALUES (null,'$newbkid','$custref','$invnumber','$year','$month','$amount')";
												$depresult = $dbcnx->query($sql);
												if ($depresult) {
													$resultMessage .= "Deposit Increment $month-$year $amount added<br />";
												} else {
													$resultMessage .= "Could NOT add Deposit Increment $month/$year $amount<br />";
												}
											}
										}
									}
								}
							}

							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Confirmed','$compname','$page','$now')");

							if ($inventory == "Yes") {
								// send to inventory
								if ($contstartdate) {
									$txndate = date("Y-m-d",$contstartdate);
								} else {
									$txndate = date("Y-m-d");
								}
								$sql = "INSERT INTO inventory VALUES (
									null,
									'$custref',
									'$aemails',
									'$siteid',
									'$txndate',
									'in',
									'',
									'',
									'',
									'',
									'$newbkid',
									'',
									'0',
									'0',
									'',
									'',
									'$now',
									'BKManager'
								)";
								if ($iresult = $dbcnx->query($sql)) {
									$iid = $dbcnx->insert_id;
									$resultMessage .= "New Inventory Created<br />";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$custref IID$iid','$page','$now')");
									$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$iid','$now','{$_SESSION['user']}','{$_SESSION['username']}','Transferred From BKManager')");
									// create the units
									$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid'";
									$iuresult = $dbcnx->query($sql);
									if (($iuresult) && ($iuresult->num_rows)) {
										while ($iurow = $iuresult->fetch_assoc()) {
											$unitid      = $iurow['unitid'];
											$unitname    = $iurow['unitname'];
											$unittype    = $iurow['unittype'];

											if ($unittype == "Cold Room") {
												if ($CRPowerIgnore != "on") {
													// set to Aproved Off
													$sql = "UPDATE `units` SET `approvedoff` = '1' WHERE `ID` = '$unitid'";
													if ($dbcnx->query($sql)) {
														$resultMessage .= "Cold Room - Approved Off Set<br />";
													} else {
														$resultMessage .= "Cold Room - FAILED to set Approved Off<br />";
													}
												} else {
													$resultMessage .= "Cold Room - Power Ignore is selected so NOT setting Approved Off<br />";
												}
											}

											if ($unittype != "Car Parking") {
											
												$sql = "INSERT INTO inventory_units VALUES (
													null,
													'$iid',
													'$custref',
													'$siteid',
													'$unitid',
													'$unitname',
													'$unittype',
													'',
													'',
													'',
													'$now',
													'BKManager'
												)";
												if ($result = $dbcnx->query($sql)) {
													$resultMessage .= "Unit $unitname Added To Inventory<br />";
													$uid = $dbcnx->insert_id;
													list($pf,$unit) = explode(" ",$unitname);
													$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref IID$iid $unitname','$page','$now')");
													
													// get old meters for this unit
													$sql = "SELECT `ID`, `commodity` FROM `UtlCollectors` WHERE `siteid` = '$siteid' AND `unit` = '$unit'";
													if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

														while ($row = $result->fetch_assoc()) {

															$umid      = $row['ID'];
															$commodity = $row['commodity'];

															// get previous reading
															$sql = "SELECT `meter`, `year`, `month`, `day`
																	FROM `UtlUsedNew`
																	WHERE `siteid` = '$siteid'
																	AND `unit` = '$unit'
																	AND `commodity` = '$commodity'
																	ORDER BY `year` DESC, `month` DESC, `day` DESC
																	LIMIT 0,1
															";
															if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
																$urow = $uresult->fetch_assoc();
																$previous = $urow['meter'];
																$py       = $urow['year'];
																$pm       = $urow['month'];
																$pd       = $urow['day'];
																$prevdate = "$py-$pm-$pd";
															} else {
																$previous = 0;
																$prevdate = "";
															}

															$sql = "INSERT INTO inventory_meters VALUES (
																null,
																'$iid',
																'$uid',
																'in',
																'$custref',
																'$siteid',
																'$unitid',
																'$unit',
																'$unitname',
																'$umid',
																'$commodity',
																'$prevdate',
																'$previous',
																'$previous',
																'',
																'',
																'$now',
																'BKManager'
															)";
															if ($dbcnx->query($sql)) {
																$resultMessage .= "$unitname Old $commodity Added<br />";
															} else {
																$resultMessage .= "Could NOT Add $unitname $commodity Checkin<br />";
															}
														}
													} else {
														$resultMessage .= "No Meters Found in UtlCollectors (Old System) for $unitname<br />";
													}

													// try new meters
													$sql = "SELECT `ID`, `commodity` FROM `UtlMeters` WHERE `siteid` = '$siteid' AND `unitname` = '$unit'";
													if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
														while ($row = $result->fetch_assoc()) {

															$umid      = $row['ID'];
															$commodity = $row['commodity'];

															// get previous reading
															$sql = "SELECT `meter`, `year`, `month`, `day`
																	FROM `UtlUsedNew`
																	WHERE `siteid` = '$siteid'
																	AND `unit` = '$unit'
																	AND `commodity` = '$commodity'
																	ORDER BY `year` DESC, `month` DESC, `day` DESC
																	LIMIT 0,1
															";
															if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
																$urow = $uresult->fetch_assoc();
																$previous = $urow['meter'];
																$py       = $urow['year'];
																$pm       = $urow['month'];
																$pd       = $urow['day'];
																$prevdate = "$py-$pm-$pd";
															} else {
																$previous = 0;
																$prevdate = "";
															}

															$sql = "INSERT INTO inventory_meters VALUES (
																null,
																'$iid',
																'$uid',
																'in',
																'$custref',
																'$siteid',
																'$unitid',
																'$unit',
																'$unitname',
																'$umid',
																'$commodity',
																'$prevdate',
																'$previous',
																'$previous',
																'',
																'',
																'$now',
																'BKManager'
															)";
															if ($dbcnx->query($sql)) {
																$resultMessage .= "$unitname New $commodity Added<br />";
															} else {
																$resultMessage .= "Could NOT Add $unitname $commodity Checkin<br />";
															}
														}
													} else {
														$resultMessage .= "No Meters Found in UtlMeters (New System) for $unitname<br />";
													}
													
												} else {
													$resultMessage .= "Could NOT Add Unit $unitname to Inventory<br />";
												}
											} else {
												$resultMessage .= "$unitname Car Parking Not Added to Inventory<br />";
											}

											// try to import previous check out images
											/* $sql = "SELECT i.*
													FROM inventory_images AS i
													LEFT JOIN inventory_units AS u ON u.ID = i.uid
													WHERE u.unitid = '$unitid' AND i.inout = 'out'
											";
											if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
												while ($row = $result->fetch_assoc()) {
													$code    = $row['code'];
													$title   = $row['title'];
													$image   = $row['image'];
													$details = $row['details'];
													$filename = date("YmdHis-") . rand(1000,9999) . "-$custref" . "-$iid-00" . ".jpg";
													if (copy("../../dis2_db/img/inv/$image", "../../dis2_db/img/inv/$filename")) {
														$resultMessage .= "Previous check out image copied to $filename<br />";
														$sql = "INSERT INTO inventory_images VALUES (
															null,
															'$iid',
															'$uid',
															'in',
															'$code',
															'$title',
															'$filename',
															'$details',
															'',
															'$now',
															'BKManager'
														)";
														if ($dbcnx->query($sql)) {
															$resultMessage .= "Image record added<br />";
														} else {
															$resultMessage .= "Image record could NOT be added<br />";
														}
													} else {
														$resultMessage .= "File $image could NOT be copied to $filename.<br />";
													}
												}
											} */

										}
									} else {
										$resultMessage .= "Could NOT Get Units for New Inventory<br />";
									}
								} else {
									$resultMessage .= "Could NOT Create New Inventory<br /> . $dbcnx->error" . "<br />";
								}
							} else {
								$resultMessage .= "Inventory Not Selected<br />";
							}

							if ($newinternet) {

								$resultMessage .= "Customer has new internet<br />";

								// add new vlan to ice connect

								// do they already have a vlan at this site
								$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
								if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
									$irow = $iresult->fetch_assoc();
									$iceapi_access_point = $irow['api_access_point'];
									$iceaccess_token     = $irow['access_token'];

									$postdata = http_build_query(
										array(
											'apikey' => $iceaccess_token,
											'action' => 'listall'
										)
									);

									$ch = curl_init(); 
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLINFO_HEADER_OUT, true);
									$result = curl_exec($ch);
									$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
									curl_close($ch);

									if ($httpcode == 200) {
										if ($result = json_decode($result, TRUE)) {
											foreach ($result AS $id => $item) {
												if ($item['Data']['ID'] == $custref) {
													$vlansite = $item['Data']['Site'];
												}
											}
										} else {
											$resultMessage .= "Json Decode FAILED getting vlan<br />";
										}
									} elseif ($httpcode == 400) {
										$resultMessage .= "Error 400 Getting VLAN<br />";
									} elseif ($httpcode == 401) {
										$resultMessage .= "Unauthorised Getting VLAN<br />";
									} else {
										$resultMessage .= "Unknown error code $httpcode Getting VLAN<br />";
									}

									if (($vlansite) AND ($vlansite == $icesites[$siteid])) {

										$resultMessage .= "Customer already has VLAN at this site<br />";

									} else {

										// get available vlans at this site
										$postdata = http_build_query(
											array(
												'apikey' => $iceaccess_token,
												'action' => 'listunused'
											)
										);
							
										$ch = curl_init(); 
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
										curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
										curl_setopt($ch, CURLINFO_HEADER_OUT, true);
										$result = curl_exec($ch);
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
										curl_close($ch);
							
										if ($httpcode == 200) {
											if ($result = json_decode($result, TRUE)) {
												// reorg
												$columns = array_column($result, 'VLAN');
												array_multisort($columns, SORT_ASC, $result);
												foreach ($result AS $key => $item) {
													if ($item['Site'] == $icesites[$siteid]) {
														$vlan = $item['VLAN'];
														break;
													}
												}
											} else {
												$resultMessage .= "Json Decode Failed getting available VLAN<br />";
											}
										} elseif ($httpcode == 400) {
											$resultMessage .= "Error getting available VLAN<br />";
										} elseif ($httpcode == 401) {
											$resultMessage .= "Error getting available VLAN - Unauthorised<br />";
										} else {
											$resultMessage .= "Unknown error getting available VLAN code $httpcode<br />";
										}

										if ($vlan) {

											// generate ice password
											$icepassword = randomPassword();
											// strip non alpha numeric chars from compname
											$compname = preg_replace('/[^A-Za-z0-9]/', '', $compname);
											// reduce compname to 18 chars
											if (strlen($compname) > 18) { $compname = substr($compname, 0, 18); }

											$postdata = http_build_query(
												array(
													'apikey'   => $iceaccess_token,
													'action'   => 'newtenant',
													'tenant'   => $compname,
													'id'       => $custref,
													'site'     => $icesites[$siteid],
													'vlan'     => $vlan,
													'password' => $icepassword
												)
											);
					
											$ch = curl_init(); 
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
											curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
											curl_setopt($ch, CURLINFO_HEADER_OUT, true);
											$result = curl_exec($ch);
											$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
											curl_close($ch);
				
											if ($httpcode == 200) {
												$result = strip_tags($result);
												if ($result = json_decode($result, TRUE)) {
													$iceusername = $result['Username'];
													$icepassword = $result['Password'];
													$resultMessage .= "Internet VLAN Added<br />Username: $iceusername<br />Password: $icepassword<br />";
												} else {
													$resultMessage .= "Json Decode Failed getting returned username for VLAN<br />";
												}
											} elseif ($httpcode == 400) {
													$resultMessage .= "Internet VLAN FAILED code 400<br />";
											} elseif ($httpcode == 401) {
												$resultMessage .= "Internet VLAN FAILED Unauthorised<br />";
											} else {
												$resultMessage .= "Internet VLAN FAILED code $httpcode<br />";
											}

										}

									}

								} else {
									$resultMessage .= "Could NOT get ice access token<br />";
								}

							}

							// update paxton
							// see if they have a paxton record at this site
							/* if ($paxunits) {
								$resultMessage .= "Checking for existing Paxton Access Level...<br />";
								include('PaxGetAccessToken.php');
								if ($access_token) {
									$headers = array(
										'Authorization: bearer '.$access_token,
										'Accept: application/json'
									);
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels");
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLINFO_HEADER_OUT, true);
									$result = curl_exec($ch);
									curl_close($ch);
									if ($result !== FALSE) {
										if ($result = json_decode($result, TRUE)) {
											if ($result) {
												$columns = array_column($result, 'name');
												array_multisort($columns, SORT_ASC, $result);
												$levelid = 0;
												foreach ($result AS $id => $level) {
													if ($level['name'] == $compname) {
														$levelid = $level['id'];
														$levelnm = $level['name'];
														$resultMessage .= "Access Level $compname Found<br />";
														break;
													}
												}
												if (!$levelid) {
													$resultMessage .= "Access Level $compname Not Found<br />";
												} else {
													// get area list
													$headers = array(
														'Authorization: bearer '.$access_token,
														'Accept: application/json'
													);
													$postdata = http_build_query(
														array(
															'query' => "SELECT * FROM Areas"
														)
													);
													$ch = curl_init();
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
													curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
													curl_setopt($ch, CURLINFO_HEADER_OUT, true);
													$arresult = curl_exec($ch);
													$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
													curl_close($ch);
													if ($arresult !== FALSE) {
														if ($arresult = json_decode($arresult, TRUE)) {
															if ($arresult) {
																$areaids = array();
																foreach ($arresult AS $id => $door) {
																	$areaid = $door['areaID'];
																	$areanm = $door['areaName'];
																	foreach ($paxunits AS $unit) {
																		// 
																		list($pre,$unit) = explode(" ",$unit);
																		// see if this area name contains the unit or unit group
																		if (strpos($areanm,$unit) !== FALSE) {
																			$areaids[] = $areaid;
																			$resultMessage .= "Area $areanm Found<br />";
																		}
																	}
																	// remove duplicates
																	$areaids = array_unique($areaids);
																}
																if ($areaids) {
																	$resultMessage .= "Updating Access Level...<br />";
																	// add areas to access level
																	$n=0;
																	$detailRows = array();
																	foreach ($areaids AS $aid) {
																		$detailRows[$n]['areaID'] = $aid;
																		$detailRows[$n]['timezoneID'] = 1;
																		$n++;
																	}
																	$headers = array(
																		'Authorization: bearer '.$access_token,
																		'Accept: application/json',
																		'Content-Type: application/json'
																	);
																	$postdata = json_encode(
																		array(
																			'id'   => $levelid,
																			'name' => $levelnm,
																			'detailRows' => $detailRows
																		)
																	);
																	$ch = curl_init();
																	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
																	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
																	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
																	curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/".$levelid);
																	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
																	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
																	$result = curl_exec($ch);
																	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
																	curl_close($ch);
														
																	if ($result !== FALSE) {
																		if ($httpcode == 200) {
																			$resultMessage .= "Level Updated.<br />";
																			$now = time();
																			$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Level $levelnm','$page','$now')");
																		} elseif ($httpcode == 400) {
																			$resultMessage .= "Invalid request - level or id is invalid<br />";
																		} elseif ($httpcode == 404) {
																			$resultMessage .= "The specified access level could not be found<br />";
																		} elseif ($httpcode == 502) {
																			$resultMessage .= "Remote service is not available<br />";
																		} else {
																			$resultMessage .= "Unknown Return Code $httpcode<br />";
																		}
																	} else {
																		$resultMessage .= "Nothing was returned from Paxton.<br />";
																	}
																} else {
																	$resultMessage .= "No Areas Found<br />";
																}
															} else {
																$resultMessage .= "Pxton Area List Empty<br />";
															}
														} else {
															$resultMessage .= "Pxton Area List Decode Failed<br />";
														}
													} else {
														$resultMessage .= "Pxton Area List Curl Failed<br />";
													}
												}
											} else {
												$resultMessage .= "Pxton Json Empty<br />";
											}
										} else {
											$resultMessage .= "Pxton Json Decode Failed<br />";
										}
									} else {
										$resultMessage .= "Pxton Curl Failed<br />";
									}
								} else {
									$resultMessage .= "Pxton Access Token Not Found<br />";
								}
							} else {
								$resultMessage .= "No Paxton Units Saved<br />";
							} */

							if ($mailit) {

								require "../../PHPMailer/src/SMTP.php";
								require "../../PHPMailer/src/PHPMailer.php";
								require "../../PHPMailer/src/Exception.php";

								// replace fields in template with data
								$search  = array("[[custname]]","[[staffname]]","[[login]]","[[password]]","[[received]]");
								$replace = array($custname,$_SESSION['username'],$email,$thispassword,$received);
								$body    = str_replace($search,$replace,$body);

								// start email
								$from = "accounts@dephna.com";
								$fromname = "Dephna Accounts";

								include("email-header.php");
								$message .= nl2br($body);
								include("email-footer.php");

								// send email(s)
								$emails = explode(",",$aemails);

								$mail = new PHPMailer(true);

								try {
									
									include('../../cron/SMTP.php');

									$mail->setFrom($from, $fromname);
									foreach ($emails AS $to) {
										$mail->addAddress($to);
									}
									$mail->addReplyTo($from, $fromname);
									$mail->addBCC($from, $fromname);
									$mail->isHTML(true);
									$mail->Subject = $subject;
									$mail->Body    = $message;

									if ($fitouts) {
										$fileatt = "";
										if (is_file("pdf/Dephna-PreliminaryServicesSchedule.xls")) {
											$fileatt = file_get_contents("pdf/Dephna-PreliminaryServicesSchedule.xls");
											if ($fileatt) {
												$mail->AddStringAttachment($fileatt, "Dephna-PreliminaryServicesSchedule.xls", "base64", "application/vnd.ms-excel"); 
											}
										}
									}

									$mail->send();
									$resultMessage .= "Email Sent to $to<br />";

								} catch (Exception $ee) {
									$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
								}

								if ($newinternet) {

									$subject = "Dephna Internet WiFi";
									$body  = "<p>Dear $custname,</p>\r\n";
									$body .= "<p>Below is your username and password for internet access.</p>\r\n";
									$body .= "<p>Username: $iceusername<br>";
									$body .= "Password: $icepassword</p>\n";
									$body .= "Attached is a pdf with information about our WiFi service.</p>\n";
									$body .= "<p>Kind Regards<br>Dephna IT</p>\r\n";

									$from = "it@dephna.com";
									$fromname = "Dephna IT";

									include("email-header.php");
									$message .= nl2br($body);
									include("email-footer.php");

									$mail = new PHPMailer(true);

									try {
										
										include('../../cron/SMTP.php');

										$mail->setFrom($from, $fromname);
										foreach ($emails AS $to) {
											$mail->addAddress($to);
										}
										$mail->addReplyTo($from, $fromname);
										$mail->addBCC($from, $fromname);
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;

										$fileatt = "";
										if (is_file("pdf/WiFi-Setup-Guide.pdf")) {
											$fileatt = file_get_contents("pdf/WiFi-Setup-Guide.pdf");
											if ($fileatt) {
												$mail->AddStringAttachment($fileatt, "WiFi-Setup-Guide.pdf", "base64", "application/pdf"); 
											}
										}
										
										$mail->send();
										$resultMessage .= "WiFi Email Sent to $to<br />";

									} catch (Exception $ee) {
										$resultMessage .= "WiFi Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
									}

								}

								if ($fitouts) {
									$subject = "Dephna Kitchen Fitout";
									$body  = "<p>Dear $custname,</p>\r\n";
									$body .= "<p>Please find attached a service schedule spreadsheet.</p>";
									$body .= "<p>Would you please indicate your requirements on the spreadsheet like the example given and email back to fitouts@dephna.com along with your floor plan.</p>";
									$body .= "<p>Kind Regards<br>Dephna Fitouts</p>\r\n";

									// start email
									$from = "fitouts@dephna.com";
									$fromname = "Dephna Fitouts";

									include("email-header.php");
									$message .= nl2br($body);
									include("email-footer.php");

									$mail = new PHPMailer(true);

									try {
										
										include('../../cron/SMTP.php');

										$mail->setFrom($from, $fromname);
										foreach ($emails AS $to) {
											$mail->addAddress($to);
										}
										$mail->addReplyTo($from, $fromname);
										$mail->addBCC($from, $fromname);
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;

										$fileatt = "";
										if (is_file("pdf/Dephna-PreliminaryServicesSchedule.xls")) {
											$fileatt = file_get_contents("pdf/Dephna-PreliminaryServicesSchedule.xls");
											if ($fileatt) {
												$mail->AddStringAttachment($fileatt, "Dephna-PreliminaryServicesSchedule.xls", "base64", "application/vnd.ms-excel"); 
											}
										}
										
										$mail->send();
										$resultMessage .= "Fitout Email Sent to $to<br />";

									} catch (Exception $ee) {
										$resultMessage .= "Fitout Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
									}

								}

							} else {
								$resultMessage .= "Email customer not selected<br />";
							}
						}
						// add confirmed date to handover
						$today = date("Y-m-d");
						$sql = "UPDATE handover SET bkconfirmed = '$today' WHERE bkid = '$bkid'";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Handover Updated if Kitchens<br />";
						}
					} // end if !$invoiced

					if (!$e) {
						
						$clearAnyway = $_POST['clearAnyway'];

						if ((($agreementID) AND ($ageementStatus == "SIGNED")) OR ($clearAnyway)) {
		
							// get api access token
							include('AdobeSignGetAccessToken.php');
							if ($access_token) {

								// get audit trail
								$headers = array(
									'Authorization: Bearer '.$access_token,
									'Accept: application/pdf'
								);
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/auditTrail");
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								$result = curl_exec($ch);
								curl_close($ch);
								if ($result !== FALSE) {
									// save as file
									$filename = date("YmdHis") . rand(1000,9999) . ".pdf";
									if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
										if (fwrite($fh,$result)) {

											// add to files table
											$now = time();
											$pdffilename = "Adobe-Sign-AuditTrail-DP$custref-$bkid.pdf";
											$sql = "INSERT INTO bookings_files VALUES (
												null,
												'$custref',
												'$bkid',
												'Audit Trail',
												'',
												'$pdffilename',
												'$filename',
												'$now',
												'{$_SESSION['username']}'
											)";
											if ($result = $dbcnx->query($sql)) {
												$resultMessage .= "Pdf Audit Trail Saved<br />";
											} else {
												$resultMessage .= "Pdf Audit Trail details Could NOT be saved<br />" . $dbcnx->error . "<br />";
											}

										} else {
											$resultMessage .= "Audit Trail Could NOT be Saved.<br />";
											// debug
											$resultMessage1 .= $result;
										}
									} else {
										$resultMessage .= "Audit Trail Could NOT be Openned.<br />";
									}
								} else {
									$resultMessage .= "Audit Trail DNL FAILED: Nothing was returned from Adobe Sign.<br />";
								}

								// get signed contract
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/rest/v6/agreements/$agreementID/combinedDocument");
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								$result = curl_exec($ch);
								curl_close($ch);
								if ($result !== FALSE) {
									// save as file
									$filename = date("YmdHis") . rand(1000,9999) . ".pdf";
									if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
										if (fwrite($fh,$result)) {

											// add to files table
											$now = time();
											$pdffilename = "Dephna-Contract-DP$custref-$bkid.pdf";
											$sql = "INSERT INTO bookings_files VALUES (
												null,
												'$custref',
												'$bkid',
												'Contract',
												'',
												'$pdffilename',
												'$filename',
												'$now',
												'{$_SESSION['username']}'
											)";
											if ($result = $dbcnx->query($sql)) {
												$resultMessage .= "Pdf Contract Saved<br />";
											} else {
												$resultMessage .= "Could NOT Save Pdf Contract Details<br />" . $dbcnx->error . "<br />";
											}

										} else {
											$resultMessage .= "Contract Could NOT be Saved.<br />";
										}
									} else {
										$resultMessage .= "Contract File Could NOT be Openned<br />";
									}
								} else {
									$resultMessage .= "Contract DNL FAILED: Nothing was returned from Adobe Sign<br />";
								}

							} else {
								$resultMessage .= "Could NOT Get Access Token<br />";
							}
						}
							
						if ((!$agreementID) OR ($ageementStatus == "SIGNED") OR ($clearAnyway)) {

							// Delete everything from bk manager
							// delete ancillaries from bk manager
							$sql = "DELETE FROM bkm_anc WHERE bkid='$bkid'";
							$adresult = $dbcnx->query($sql);
							if ($adresult) {
								$resultMessage .= "BK Unit ancillaries deleted<br />";
							} else {
								$resultMessage .= "Could not delete ancillaries<br />";
							}

							// delete units from bk manager
							$sql = "DELETE FROM bkm_units WHERE bkid='$bkid'";
							$dresult = $dbcnx->query($sql);
							if ($dresult) {
								$resultMessage .= "BK Units deleted<br />";
							} else {
								$resultMessage .= "Could not delete BK units<br />";
							}

							// delete bkid from bk manager
							$sql = "DELETE FROM bk_manager WHERE ID='$bkid'";
							$dbkresult = $dbcnx->query($sql);
							if ($dbkresult) {
								$resultMessage .= "BK Main Listing Deleted<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Confirmed Signed','DP$custref ID:$bkid','$page','$now')");
							} else {
								$resultMessage .= "Could not delete BK Main Listing<br />";
							}	

						} else {
							// mark as invoiced
							$sql = "UPDATE bk_manager SET invoiced = 'Yes' WHERE ID = '$bkid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Order marked as invoiced<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Invoiced Unsigned','DP$custref ID:$bkid','$page','$now')");
							} else {
								$resultMessage .= "Could NOT mark order as invoiced<br />";
							}
						}

						// report to screen
						if ($resultMessage) {
							print "<blockquote>\n";
							print "<p>$resultMessage</p>\n";
							print "</blockquote>\n";
							// link to ledger
							print "<p align=\"center\"><a href=\"sl-ledger.php?id=$custid\">View Ledger</a></p>\n";
							// link to add amount
							print "<p align=\"center\"><a href=\"sl-receipt-addedit.php?id=$custid&amount=$received\">Add Receipt to Ledger</a></p>\n";
						}

						// save to notes
						$now = time();
						$mydate = date("Y-m-d");
						$sql = "INSERT INTO ad_notes VALUES (
							null,
							'$mydate',
							'$custref',
							'$resultMessage',
							'note',
							'0',
							'',
							'{$_SESSION['user']}',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Note Saved<br />";
						} else {
							$resultMessage .= "Could NOT Save Note<br />";
						}

						// report email
						$mailto   = "rajive@dephna.com";
						$from     = "robot@dephna.com";
						$mailsjt  = "BKM Confirm Result DP$custref";
						$mime_boundary = md5(uniqid(time()));
						$headers = "From: $from\r\n";
						$headers .= "Reply-To: $from\r\n";
						$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
						$message  = "--".$mime_boundary."\r\n";
						$message .= "Content-type:text/html; charset=utf-8\r\n";
						$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
						$message .= "<p><b>BKM Comfirm Result DP$custref<br />$compname</b></p>\r\n";
						$message .= "<p>$resultMessage</p>";
						if ($ASresultMessage) {
							$message .= "<p>$ASresultMessage</p>";
						}
						if ($resultMessage1) {
							$message .= "<p>$resultMessage1</p>";
						}
						$message .= "<p>By {$_SESSION['username']} " . date("Y-m-d H:i") . "</p>";
						$message .= "\r\n\r\n";
						$message .= "--".$mime_boundary."--";
						mail($mailto, $mailsjt, $message, $headers);
						$resultMessage = "";

					}
					$continue = "none";

				} else {

					// get ans setup email template
					if ($newcust) {
						$template = "BKNewCustomer";
					} else {
						$template = "BKExistingCustomer";
					}

					$sql = "SELECT * FROM `mailouts` WHERE `mref` = '$template'";
					$tresult = $dbcnx->query($sql);
					if (($tresult) && ($tresult->num_rows)) {
						$trow = $tresult->fetch_assoc();
						$subject = $trow['subject'];
						$body    = $trow['html'];
					} else {
						$resultMessage .= "<b>Could not get email template $template.</b>\n";
					}
					$mailit = "on";
				}

				if ((!$continue) OR ($e)) {

					print "<table width=\"420px\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer:</b></td>";
					print "	<td>$compname</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Cust Ref:</b></td>";
					print "	<td>DP$custref</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>";
					print "	<td>$sitename</td>";
					print "</tr>\n";
					print "	</table>\n";

					print "	<span class=\"notclicked\">\n";

					if (!$invoiced) {

						print "<p align=\"center\"><b>Confirm Payment. Transfer to bookings. Send email to customer.</b><br>";
						print "[[fields]] will be replaced automatically.";
						print "</p>\n";
						print "<p align=\"center\" class=\"redtext\"><b>This will make the booking live. One click. It cannot be reversed.</b></p>\n";
						if (($agreementID) AND ($ageementStatus != "SIGNED")) {
							print "<p align=\"center\"><b>The Contract Is NOT SIGNED</b><br>You can still go live and download the signed contract later.</p>\n";
						}
						print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
						print "<input type=\"hidden\" name=\"module\" value=\"confirm\" />\n";
						print "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
						print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";

						print "<table width=\"420px\" align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Subject:</td>\n";
						print "	<td><input type=\"text\" name=\"subject\" value=\"$subject\" class=\"element\" />";
						if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">Message:"; if ($erbody) { print "&nbsp;<span class=\"redtext\">$erbody</span>"; } print "<br>";
						print "<textarea name=\"body\" wrap=\"virtual\" rows=\"8\" class=\"element_box\">$body</textarea></td>\n";
						print "</tr>\n";

						print "<tr><td colspan=\"2\">\n";

						print "	<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Amount Received:</td>\n";
						print "	<td><input type=\"text\" name=\"received\" value=\"$received\" class=\"element\" />";
						if ($erreceived) { print "&nbsp;<span class=\"redtext\">$erreceived</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Email Client</td>\n";
						print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
						print "</tr>\n";

						print "	</table>\n";

						print "</td></tr>\n";

						print "</table>\n";

						print "	<p align=\"center\"><input type=\"submit\" value=\"Send &amp; Go Live\" class=\"button\" /></p>\n";

						print "</form>\n";

					} else {

						print "<p align=\"center\"><b>Already Invoiced</b><br>Download Contract</p>\n";

						if ($ageementStatus == "SIGNED") {

							print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
							print "<input type=\"hidden\" name=\"module\" value=\"confirm\" />\n";
							print "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
							print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
							print "<p align=\"center\"><input type=\"submit\" value=\"Download and Clear\" class=\"button\" /></p>\n";
							print "</form>\n";

						} else {

							print "<p align=\"center\"><b>Contract Not Signed Yet!</b><br>Please encourage the client to sign it.</p>";

							print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
							print "<input type=\"hidden\" name=\"module\" value=\"confirm\" />\n";
							print "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
							print "<input type=\"hidden\" name=\"clearAnyway\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
							print "<p align=\"center\"><input type=\"submit\" value=\"Download and Clear Anyway\" class=\"button\" /></p>\n";
							print "</form>\n";

						}
						
					}

					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

				}

				print "<script>\n";
				print "  $('.uploading').hide();\n";
				print "  	$('form').submit(function () {\n";
				print " 	$('.uploading').show();\n";
				print "  	$('.notclicked').hide();\n";
				print "  });\n";
				print "</script>\n";

			} else {
				$resultMessage .= "Customer not found!<br />";
			}
		} else {
			$resultMessage .= "Customer Not Yet Selected<br />";
		}
	} else {
		$resultMessage .= "No Units Added Yet<br />";
	}
} else {
	$resultMessage .= "Invoicing Not Available<br />The monthly invoicing run was interupted<br />";
}

function generateRandomString($length = 16) {
	return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/* function generatePassword($length = 8) {
	return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyz', ceil($length/strlen($x)) )),1,$length);
} */

function generatePassword($length = 10, $characters = "lower_case,upper_case,numbers,special_symbols") {
	$symbols = array();
	$passwords = array();
	$used_symbols = '';
	$pass = '';
	$symbols["lower_case"] = 'abcdefghijklmnopqrstuvwxyz';
	$symbols["upper_case"] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$symbols["numbers"] = '1234567890';
	$symbols["special_symbols"] = '!?~@#-_+<>[]{}';
	$characters = explode(",",$characters); // get characters types to be used for the passsword
	foreach ($characters as $key=>$value) {
		$used_symbols .= $symbols[$value]; // build a string with all characters
	}
	$symbols_length = strlen($used_symbols) - 1; //strlen starts from 0 so to get number of characters deduct 1
	for ($i = 0; $i < $length; $i++) {
		$n = rand(0, $symbols_length); // get a random character from the string with all characters
		$pass .= $used_symbols[$n]; // add the character to the password string
	}
	return $pass; // return the generated password
}

function randomPassword($len = 10) {
	// define character libraries - remove ambiguous characters like iIl|1 0oO
	$sets = array();
	$sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
	$sets[] = 'abcdefghjkmnpqrstuvwxyz';
	$sets[] = '23456789';
	$password = '';
	//append a character from each set - gets first 4 characters
	foreach ($sets as $set) {
		$password .= $set[array_rand(str_split($set))];
	}
	//use all characters to fill up to $len
	while(strlen($password) < $len) {
		//get a random set
		$randomSet = $sets[array_rand($sets)];
		
		//add a random char from the random set
		$password .= $randomSet[array_rand(str_split($randomSet))]; 
	}
	//shuffle the password string before returning!
	return str_shuffle($password);
}
