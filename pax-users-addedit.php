<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Users";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">PAXTON</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$userid = $_REQUEST['userid'];
			$copy   = $_REQUEST['copy'];
			$fobreqid = $_REQUEST['fobreqid'];
			if ($userid) {
				$heading = "Update User";
			} else {
				if ($copy) {
					$heading = "Copy User";
				} else {
					$heading = "New User";
				}
			}

			if ($siteid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">$heading</p>\n";

					include('PaxGetAccessToken.php');

					if ($access_token) {

						if ($_REQUEST['posted']) {

							$firstName          = $_POST['firstName'];
							$middleName         = $_POST['middleName'];
							$lastName           = $_POST['lastName'];
							$oldpin             = $_POST['oldpin'];
							$newpin             = $_POST['newpin'];
							$telephone          = $_POST['telephone'];
							$extension          = $_POST['extension'];
							$isAntiPassbackUser = $_POST['isAntiPassbackUser'];
							$isAlarmUser        = $_POST['isAlarmUser'];
							$isLockdownExempt   = $_POST['isLockdownExempt'];
							$edate              = $_POST['edate'];
							$adate              = $_POST['adate'];
							$department         = $_POST['department'];
							$level              = $_POST['level'];
							$newtokens          = $_POST['newtokens'];
							$customer           = $_POST['customer'];
							$imagename          = $_POST['imagename'];
							$mantoken           = $_POST['mantoken'];

							if (!$customer) { $ercustomer = "Please select a customer"; $e++; }
							if (!$department) { $erdepartment = "Please select a department"; $e++; }
							if (!$firstName) { $erfirstName = "Please enter a first name"; $e++; }
							if ($edate) {                 
								if (!validateDate($edate)) {
									$eredate = "Invalid Date"; $e++;
								}
							}
							if ($adate) {   
								if (!validateDate($adate)) {
									$eradate = "Invalid Date"; $e++;
								}
							}
							if ($newpin) {
								$arrpin = str_split($newpin);
								$arrpin = array_unique($arrpin);
								$newpin = implode("",$arrpin);
								if (!is_numeric($newpin)) {
									$erpin = "Must be four unique numbers"; $e++;
								} elseif ($newpin < 1000) {
									$erpin = "Must be four unique numbers"; $e++;
								} elseif (strlen($newpin) != 4) {
									$erpin = "Must be four unique numbers"; $e++;
								} elseif (in_array($newpin, array('1234','2345','3456','4567','5678','6789','9876','8765','7654','6543','5432','4321'))) {
									$erpin = "PIN cannot be consecutive numbers"; $e++;
								}
							} else {
								if ($oldpin) {
									$newpin = $oldpin;
								}
							}

							if ($department == "No Department") {
								$department = "";
							} else {
								list($deptid,$deptnm) = explode("--",$department);
							}

							list($levelid,$levelnm) = explode("--",$level);

							if ($customer == "No Customer") {
								$custid = 0;
								$custref = 0;
								$custnm = 0;
							} else {
								list($custid,$custref,$custnm) = explode("--",$customer);
							}

							if ($_FILES['image']['tmp_name']) {
								if (exif_imagetype($_FILES['image']['tmp_name']) != IMAGETYPE_JPEG) {
									$erimage = "The file is not a jpg"; $e++;
								}
							}

							if ($mantoken) {
								trim($mantoken);
								if (!is_numeric($mantoken)) {
									$ermantoken = "Must be numeric"; $e++;
								}
							}

							if (!$e) {

								$firstName  = addslashes($firstName);
								$middleName = addslashes($middleName);
								$lastName   = addslashes($lastName);
								$telephone  = addslashes($telephone);
								$extension  = addslashes($extension);
								$custnm     = addslashes($custnm);

								$postdata = http_build_query(
									array(
										'id'                 => $userid,
										'firstName'          => $firstName,
										'middleName'         => $middleName,
										'lastName'           => $lastName,
										'expiryDate'         => $edate,
										'activateDate'       => $adate,
										'pin'                => $newpin,
										'telephone'          => $telephone,
										'extension'          => $extension,
										'isAntiPassbackUser' => $isAntiPassbackUser,
										'isAlarmUser'        => $isAlarmUser,
										'isLockdownExempt'   => $isLockdownExempt,
										'customFields' => array(
											array(
												'id' => '1',
												'value' => $custnm
											),
											array(
												'id' => '3',
												'value' => $custid
											),
											array(
												'id' => '4',
												'value' => $custref
											)
										)
									)
								);

								$headers = array(
									'Authorization: bearer '.$access_token,
									'Accept: application/json'
								);

								$ch = curl_init();
								if ($userid) {
									curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
								} else {
									curl_setopt($ch, CURLOPT_POST, 1);
								}
								curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($ch, CURLINFO_HEADER_OUT, true);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
								$result = curl_exec($ch);
								$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
								curl_close($ch);
					
								if ($result !== FALSE) {

									if ($userid) {
										if ($httpcode == 200) {
											$resultMessage .= "User Updated<br>";
											$success = 1;
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $firstName $lastName','$page','$now')");
										} elseif ($httpcode == 400) {
											$resultMessage .= "<span class=\"redtext\">Invalid - PIN, User or ID Rejected by Paxton</span><br>";
										} elseif ($httpcode == 404) {
											$resultMessage .= "<span class=\"redtext\">The specified user could not be found.</span><br>";
										} elseif ($httpcode == 502) {
											$resultMessage .= "<span class=\"redtext\">Remote service is not available for user.</span><br>";
										} else {
											$resultMessage .= "<span class=\"redtext\">Unknown Return Code $httpcode for user.</span><br>";
										}
									} else {
										if ($result = json_decode($result, TRUE)) {
											if ($result) {
												$userid = $result['id'];
												$success = 1;
												$resultMessage .= "User Added<br>";
												$now = time();
												$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename $firstName $lastName','$page','$now')");
											} else {
												$resultMessage .= "<span class=\"redtext\">Nothing was returned from Paxton for user.</span><br>";
											}
										} else {
											$resultMessage .= "<span class=\"redtext\">Json Failed for user</span>.<br>";
										}
									}
				
								} else {
									$resultMessage .= "<span class=\"redtext\">Error: <br>Nothing was returned from Paxton.</span><br>";
								}

								if ($userid) {

									// upload image if selected
									if ($_FILES['image']['tmp_name']) {
										$imagename = date("YmdHis") . "-$custref" . ".jpg";
										if (!copy($_FILES['image']['tmp_name'], "../../dis2_db/img/pax/$imagename")) {
											$resultMessage .= "<span class=\"redtext\">The image could not be saved in CAM</span><br>";
											$imagename = "";
										} else {
											chmod("../../dis2_db/img/pax/$imagename", 0666);
											list($width, $height, $type, $attr) = getimagesize("../../dis2_db/img/pax/$imagename");
											if (($width > 800) OR ($height > 800)) {
												// too big
												if ($width > $height) {
													$scale = $width/800;
													$new_width = 800;
													$new_height = $height/$scale;
												} else {
													$scale = $height/800;
													$new_height = 800;
													$new_width = $width/$scale;
												}
												$temp_im = imageCreateFromJPEG("../../dis2_db/img/pax/$imagename");
												$new_im  = imagecreatetruecolor($new_width,$new_height);
												imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
												imagejpeg($new_im, "../../dis2_db/img/pax/$imagename", 60);
												imageDestroy($temp_im);
												imageDestroy($new_im);
											}
										}
									}

									if ($imagename) {

										$data   = file_get_contents("../../dis2_db/img/pax/$imagename");
										$base64 = base64_encode($data);
										unlink("../../dis2_db/img/pax/$imagename");
										$postdata = http_build_query(
											array(
												'base64Data' => $base64
											)
										);
										$ch = curl_init();
										curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
										curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid."/image");
										curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
										curl_setopt($ch, CURLINFO_HEADER_OUT, true);
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
										$iresult = curl_exec($ch);
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
										curl_close($ch);
										if ($httpcode == 204) {
											$resultMessage .= "Image Updated<br>";
										} elseif ($httpcode == 400) {
											$resultMessage .= "<span class=\"redtext\">Image Rejectd</span><br>";
										} elseif ($httpcode == 404) {
											$resultMessage .= "<span class=\"redtext\">The specified user could not be found</span><br>";
										} elseif ($httpcode == 502) {
											$resultMessage .= "<span class=\"redtext\">Remote service is not available for image</span><br>";
										} else {
											$resultMessage .= "<span class=\"redtext\">Unknown Return Code $httpcode for image</span><br>";
										}
										$iresult = json_decode($iresult, TRUE);
									}

									// update the department association
									$postdata = http_build_query(
										array(
											'id'   => $deptid,
											'name' => $deptnm
										)
									);
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
									curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid."/departments");
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLINFO_HEADER_OUT, true);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
									$result = curl_exec($ch);
									$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
									curl_close($ch);
									if ($httpcode == 204) {
										$resultMessage .= "Department Updated<br>";
									} elseif ($httpcode == 400) {
										$resultMessage .= "<span class=\"redtext\">Invalid request - department rejected.</span><br>";
									} elseif ($httpcode == 404) {
										$resultMessage .= "<span class=\"redtext\">The specified department could not be found</span><br>";
									} elseif ($httpcode == 502) {
										$resultMessage .= "<span class=\"redtext\">Remote service is not available for depatment</span><br>";
									} else {
										$resultMessage .= "<span class=\"redtext\">Unknown Return Code $httpcode for department</span><br>";
									}
								
									// update the access level association
									$postdata = http_build_query(
										array(
											'accessLevels' => array(
												$levelid
											),
											"individualPermissions" => array(
												'id' => '',
												'timezoneId' => ''
											)
										)
									);
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
									curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid."/doorpermissionset");
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLINFO_HEADER_OUT, true);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
									curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
									$result = curl_exec($ch);
									$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
									curl_close($ch);
									if ($httpcode == 204) {
										$resultMessage .= "Access Level Updated<br>";
									} elseif ($httpcode == 400) {
										$resultMessage .= "<span class=\"redtext\">Invalid request - access level rejected.</span><br>";
									} elseif ($httpcode == 404) {
										$resultMessage .= "<span class=\"redtext\">The specified access level could not be found</span><br>";
									} elseif ($httpcode == 502) {
										$resultMessage .= "<span class=\"redtext\">Remote service is not available for access level</span><br>";
									} else {
										$resultMessage .= "<span class=\"redtext\">Unknown Return Code $httpcode for access level</span><br>";
									}

									if ($mantoken) {
										$newtokens[] = "Keyfob--$mantoken--0";
									}
								
									foreach ($newtokens AS $newtoken) {
										if ($newtoken) {
											list($toktype,$tokvalue,$tokislost) = explode("--",$newtoken);
											$postdata = http_build_query(
												array(
													'tokenType'  => $toktype,
													'tokenValue' => $tokvalue,
													'isLost'     => $tokislost
												)
											);
											$ch = curl_init();
											curl_setopt($ch, CURLOPT_POST, 1);
											curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid."/tokens");
											curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
											curl_setopt($ch, CURLINFO_HEADER_OUT, true);
											curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
											curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
											$result = curl_exec($ch);
											$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
											curl_close($ch);

											if ($httpcode == 201) {
												$resultMessage .= "New Token Created<br>";
											} elseif ($httpcode == 400) {
												$resultMessage .= "<span class=\"redtext\">Invalid Request - User or ID is Invalid</span><br>";
											} elseif ($httpcode == 502) {
												$resultMessage .= "<span class=\"redtext\">Remote service is not available for user</span><br>";
											} else {
												$resultMessage .= "<span class=\"redtext\">Unknown Return Code $httpcode adding new token</span><br>";
											}
										}
									}
								}
								if ($fobreqid AND $success) {
									// srf it
									// get key fob rate and details
									$sql = "SELECT * FROM ancillaries WHERE ID = '51'";
									$anresult = $dbcnx->query($sql);
									if (($anresult) AND ($anresult->num_rows)) {
										$anrow = $anresult->fetch_assoc();
										$anctitle = $anrow['title'];
										$ancrate  = $anrow['rate'];
										$ancvat   = $anrow['mvat'];
										$repgrp   = $anrow['ancgroup'];
									}

									$y = date("Y");
									$m = date("m");
									$d = date("d");

									$sql = "INSERT INTO srf VALUES (
										null,
										'$y',
										'$m',
										'$d',
										'$y',
										'$m',
										'$d',
										'$siteid',
										'$sitename',
										'$custref',
										'$custnm',
										'$ancrate',
										'$ancvat',
										'$anctitle $firstName $lastName',
										'',
										'{$_SESSION['username']}',
										'$now'
									)";
									
									if ($dbcnx->query($sql)) {
										$sid = $dbcnx->insert_id;
										$resultMessage .= "SRF Record Added<br>";
									} else {
										$resultMessage = "Could NOT add SRF Record to Database<br>" . $dbcnx->error . "<br>";
									}
									$processed = date("Y-m-d");
									$sql = "UPDATE pax_user_requests SET userid = '$userid', processed = '$processed', procby = '{$_SESSION['username']}' WHERE ID = '$fobreqid'";
									if ($result = $dbcnx->query($sql)) {
										$resultMessage .= "Fob Request Marked as Processed<br>";
									} else {
										$resultMessage .= "Could NOT Mark Fob Request as Processed<br>" . $dbcnx->error . "<br>";
									}
								}
							}
						}

						elseif ($userid) {

							// get user details
							$headers = array(
								'Authorization: bearer '.$access_token,
								'Accept: application/json'
							);

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$cresult = curl_exec($ch);
							curl_close($ch);
					
							if ($cresult !== FALSE) {

								if ($cresult = json_decode($cresult, TRUE)) {

									if ($cresult) {                                        
										list($edate,$etime) = explode("T",$cresult['expiryDate']);
										list($adate,$atime) = explode("T",$cresult['activateDate']);
										$firstName  = $cresult['firstName'];
										$middleName = $cresult['middleName']; 
										$lastName   = $cresult['lastName'];
										$oldpin     = $cresult['pin'];
										$telephone  = $cresult['telephone'];
										$extension  = $cresult['extension'];
										$hasImage   = $cresult['hasImage'];
										$isAntiPassbackUser = $cresult['isAntiPassbackUser'];
										$isAlarmUser        = $cresult['isAlarmUser'];
										$isLockdownExempt   = $cresult['isLockdownExempt'];
										$custid             = $cresult['customFields'][2]['value'];
										$custref            = $cresult['customFields'][3]['value'];
									} else {
										$resultMessage .= "User details not returned from Paxton.<br>";
									}
								} else {
									$resultMessage .= "User details Json Failed.<br>";
								}
							} else {
								$resultMessage .= "User details error: <br>Nothing was returned from Paxton.<br>";
							}

							if ($hasImage) {
								// Get the image
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/image");
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($ch, CURLINFO_HEADER_OUT, true);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
								curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
								$iresult = curl_exec($ch);
								curl_close($ch);
								if ($iresult !== FALSE) {
									$oldimage = base64_encode($iresult);
								}
							}

							// get user department
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/departments");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$result = curl_exec($ch);
							curl_close($ch);
							if ($result !== FALSE) {
								if ($result = json_decode($result, TRUE)) {
									if ($result) {
										$deptid = $result[0]['id'];
										$deptnm = $result[0]['name'];
									}
								}
							}

							// get user access level
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/doorpermissionset");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$result = curl_exec($ch);
							curl_close($ch);
							if ($result !== FALSE) {
								if ($result = json_decode($result, TRUE)) {
									if ($result) {
										$levelid = $result['accessLevels'][0];
									}
								}
							}

							// get user tokens
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/tokens");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$oldtokens = curl_exec($ch);
							curl_close($ch);
							if ($oldtokens !== FALSE) {
								if ($oldtokens = json_decode($oldtokens, TRUE)) {
									// got some
								}
							}
						}

						if (!$resultMessage OR $e) {

							// get a list of CAM customers
							$sql = "SELECT compname, custref, ID FROM users WHERE level='2' ORDER BY compname";
							$usersresult = $dbcnx->query($sql);

							// get a list of departments
							$headers = array(
								'Authorization: bearer '.$access_token,
								'Accept: application/json'
							);
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/departments");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$departments = curl_exec($ch);
							curl_close($ch);
							if ($departments !== FALSE) {
								if ($departments = json_decode($departments, TRUE)) {
									$columns = array_column($departments, 'name');
                                    array_multisort($columns, SORT_ASC, $departments);
								} else {
									$resultMessage .= "Json failed getting departments<br>";
								}
							} else {
								$resultMessage .= "Nothing returned getting departments<br>";
							}

							// get a list of levels
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$levels = curl_exec($ch);
							curl_close($ch);
							if ($result !== FALSE) {
								if ($levels = json_decode($levels, TRUE)) {
									// got it
								} else {
									$resultMessage .= "Json failed getting access levels<br>";
								}
							} else {
								$resultMessage .= "Nothing returned getting access levels<br>";
							}
							
							print "<form name=\"addform\" enctype=\"multipart/form-data\" method=\"post\" action=\"pax-users-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"oldpin\" value=\"$oldpin\" />\n";
							if ($copy) {
								print "<input type=\"hidden\" name=\"copy\" value=\"1\" />\n";
							}
							if ($fobreqid) {
								print "<input type=\"hidden\" name=\"fobreqid\" value=\"$fobreqid\" />\n";
							}
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print " <td><b>First Name</b></td>\n";
							print "	<td><input name=\"firstName\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$firstName\" />";
							if ($erfirstName) { print "<br><span class=\"redtext\">$erfirstName</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>Middle Name</b></td>\n";
							print "	<td><input name=\"middleName\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$middleName\" />";
							if ($ermiddleName) { print "<br><span class=\"redtext\">$ermiddleName</span>"; }
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>Last Name</b></td>\n";
							print "	<td><input name=\"lastName\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$lastName\" />";
							if ($erlastName) { print "<br><span class=\"redtext\">$erlastName</span>"; }
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>Activate Date</b></td>\n";
							print "	<td><input type=\"text\" id=\"datepicker1\" name=\"adate\" value=\"$adate\" class=\"element\" />\n";
							if ($eradate) { print "<br><span class=\"redtext\">$eradate</span>"; }
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>Expires Date</b></td>\n";
							print "	<td><input type=\"text\" id=\"datepicker\" name=\"edate\" value=\"$edate\" class=\"element\" />\n";
							if ($eredate) { print "<br><span class=\"redtext\">$eredate</span>"; }
							print "</tr>\n";

							if ($oldpin) {
								print "<tr>\n";
								print " <td><b>PIN</b></td>\n";
								print " <td>$oldpin</td>\n";
								print "</tr>\n";
							}

							print "<tr>\n";
							print " <td>&nbsp;</td>\n";
							print " <td class=\"smalltext\">";
							print "PIN must be four unique numbers and<br>cannot be consecutive e.g. 1234 or 4321";
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>New PIN</b></td>\n";
							print "	<td><input name=\"newpin\" type=\"text\" class=\"element_small\" maxlength=\"4\" value=\"$newpin\" />";
							if ($erpin) { print "<br><span class=\"redtext\">$erpin</span>"; }
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>Telephone</b></td>\n";
							print "	<td><input name=\"telephone\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$telephone\" />";
							if ($ertelephone) { print "<br><span class=\"redtext\">$ertelephone</span>"; }
							print "</tr>\n";

							print "<tr>\n";
							print " <td><b>Extension</b></td>\n";
							print "	<td><input name=\"extension\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$extension\" />";
							if ($erextension) { print "<br><span class=\"redtext\">$erextension</span>"; }
							print "</tr>\n";

							print "<tr>\n";
							print "	<td><b>Is Anti-Passback User</b></td>\n";
							print "	<td><select name=\"isAntiPassbackUser\" class=\"element\">\n";
							print "		<option value=\"1\""; if ($isAntiPassbackUser == 1) { print " SELECTED"; } print ">Yes</option>\n";
							print "		<option value=\"0\""; if ($isAntiPassbackUser == 0) { print " SELECTED"; } print ">No</option>\n";
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td><b>Is Alarm User</b></td>\n";
							print "	<td><select name=\"isAlarmUser\" class=\"element\">\n";
							print "		<option value=\"0\""; if ($isAlarmUser == 0) { print " SELECTED"; } print ">No</option>\n";
							print "		<option value=\"1\""; if ($isAlarmUser == 1) { print " SELECTED"; } print ">Yes</option>\n";
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td><b>Is Lockdown Exempt</b></td>\n";
							print "	<td><select name=\"isAlarmUser\" class=\"element\">\n";
							print "		<option value=\"0\""; if ($isLockdownExempt == 0) { print " SELECTED"; } print ">No</option>\n";
							print "		<option value=\"1\""; if ($isLockdownExempt == 1) { print " SELECTED"; } print ">Yes</option>\n";
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";

							if ($usersresult) {
								print "<tr>\n";
								print "	<td><b>CAM Customer</b></td>\n";
								print "	<td><select name=\"customer\" class=\"element\">\n";
								print "		<option></option>\n";
								while ($urow = $usersresult->fetch_assoc()) {
									$thisuserid  = $urow['ID'];
									$thiscustref = $urow['custref'];
									$thiscustnm  = $urow['compname'];
									print "		<option value=\"$thisuserid--$thiscustref--$thiscustnm\""; if ($thisuserid == $custid) { print " SELECTED"; } print ">$thiscustnm</option>\n";
								}
								print "	</select>";
								if ($ercustomer) { print "<br><span class=\"redtext\">$ercustomer</span>"; }
								print "</td>\n";
								print "</tr>\n";
							}

							if ($departments) {
								print "<tr>\n";
								print "	<td><b>Department</b></td>\n";
								print "	<td><select name=\"department\" class=\"element\">\n";
								print "		<option></option>\n";
								foreach ($departments AS $did => $thisdept) {
									$thisid = $thisdept['id'];
									$thisnm = $thisdept['name'];
									print "		<option value=\"$thisid--$thisnm\""; if ($thisid == $deptid) { print " SELECTED"; } print ">$thisnm</option>\n";
								}
								print "	</select>";
								if ($erdepartment) { print "<br><span class=\"redtext\">$erdepartment</span>"; }
								print "</td>\n";
								print "</tr>\n";
							}

							if ($levels) {
								print "<tr>\n";
								print "	<td><b>Access Level</b></td>\n";
								print "	<td><select name=\"level\" class=\"element\">\n";
								foreach ($levels AS $aid => $thislevel) {
									$thislid = $thislevel['id'];
									$thislnm = $thislevel['name'];
									print "		<option value=\"$thislid--$thislnm\""; if ($thislid == $levelid) { print " SELECTED"; } print ">$thislnm</option>\n";
								}
								print "	</select>";
								print "</td>\n";
								print "</tr>\n";
							}

							if ($fobreqid) {
								// get request notes and image.
								$sql = "SELECT notes, imagename FROM pax_user_requests WHERE ID = '$fobreqid'";
								if (($fresult = $dbcnx->query($sql)) AND ($fresult->num_rows)) {
									$frow = $fresult->fetch_assoc();
									$notes     = $frow['notes'];
									$imagename = $frow['imagename'];
									print "<tr>\n";
									print "	<td valign=\"top\"><b>Image</b></td>";
									print "	<td><img src=\"getimage.php?dr=pax&amp;im=$imagename\" width=\"100\" /><br>Leave New Image blank to keep this one.</td>\n";
									print "</tr>\n";
									print "<input type=\"hidden\" name=\"imagename\" value=\"$imagename\" />\n";
								}
							}

							elseif ($oldimage) {
								print "<tr>\n";
								print "	<td valign=\"top\"><b>Image</b></td>";
								$src = 'data:image/jpeg;base64,'.$oldimage;
								print "	<td><img src=\"" . $src . "\" width=\"100\" /><br>Leave New Image blank to keep this one.</td>\n";
								print "</tr>\n";
							}
							
							print "<tr>\n";
							print "	<td><b>New Image (jpg)</b></td>\n";
							print "	<td><input type=\"file\" name=\"image\" class=\"element\" />";
							if ($erimage) { print "<br><span class=\"redtext\">$erimage</span>"; }
							print "</td>\n";
							print "</tr>\n";

							if ($oldtokens) {	
								print "<tr><td colspan=\"2\" align=\"center\" class=\"larger\"><p>Existing Tokens</p></td></tr>\n";
								foreach($oldtokens AS $tid => $tok) {
									$tokenid = $tok['id'];
									$tokenType = $tok['tokenType'];
									$tokenValue = $tok['tokenValue'];
									$isLost = $tok['isLost'];
									if ($isLost) {
										$isLost = "Yes";
									} else {
										$isLost = "No";
									}
									print "<tr>\n";
									print " <td align=\"right\">$tokenType</td>\n";
									print " <td>$tokenValue Is Lost: $isLost</td>\n";
									print "</tr>\n";
								}
							}

							if ($notes) {
								print "<tr>\n";
								print "<td colspan=\"2\"><br><b>Fob Request Notes</b><br>$notes</td>\n";
								print "</tr>\n";
							}

							if ($copy) {
								if ($newtokens) {
									print "<tr><td colspan=\"2\" align=\"center\" class=\"larger\"><p>Tokens</p></td></tr>\n";
									foreach ($newtokens AS $thistoken) {
										list($toktype,$tokvalue,$tokislost) = explode("--", $thistoken);
										print "<tr>\n";
										print "<td align=\"right\">$toktype</td>\n";
										print "<td><input type=\"checkbox\" name=\"newtokens[]\" value=\"$thistoken\" checked />$tokvalue</td>\n";
										print "</tr>\n";
									}
								}
							} else {

								print "<tr><td colspan=\"2\" align=\"center\" class=\"larger\"><p>New Token</p></td></tr>\n";
								print "<tr>\n";
								print "<td valign=\"top\">Manual Token Number</td>\n";
								print "<td><input type=\"text\" name=\"mantoken\" class=\"element_small\" maxlength=\"30\" value=\"$mantoken\" /> (Will override any selection)";
								if ($ermantoken) { print "<br><span class=\"redtext\">$ermantoken</span>"; }
								print "</td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "<td>&nbsp;</td>\n";
								print "<td><input type=\"radio\" name=\"newtokens[]\" value=\"0\""; if ($thistoken == $newtoken) { print " CHECKED"; } print " />Unselected</td>\n";
								print "</tr>\n";

								$sql = "SELECT p.siteid, s.title AS sitename
										FROM PaxAuth AS p
										LEFT JOIN sites AS s ON s.ID = p.siteid
								";
								if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {

									$savesiteid = $siteid;

									while ($trow = $tresult->fetch_assoc()) {

										$siteid   = $trow['siteid'];
										$sitename = $trow['sitename'];
										$access_token = "";
										include('PaxGetAccessToken.php');

										if ($access_token) {

											$tpostdata = http_build_query(
												array(
													'max' => '1'
												)
											);
											$headers = array(
												'Authorization: bearer '.$access_token,
												'Accept: application/json'
											);

											// get latest unknown token from this site
											$ch = curl_init();
											curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/events/latestunknowntokens/?".$tpostdata);
											curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
											curl_setopt($ch, CURLINFO_HEADER_OUT, true);
											curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
											curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
											$newtokens = curl_exec($ch);
											curl_close($ch);
											if ($newtokens !== FALSE) {
												if ($newtokens = json_decode($newtokens, TRUE)) {

													print "<tr>\n";
													print "<td>Tapped at $sitename</td>\n";
													print "<td>\n";
													foreach ($newtokens AS $tid => $tok) {
														$eventDate = $tok['eventDate'];
														list($date,$time) = explode("T",$eventDate);
														list($time,$junk) = explode("+",$time);
														$thistoken = $tok['tokenNumber'];
														$where     = $tok['where'];
														print "<input type=\"radio\" name=\"newtokens[]\" value=\"Keyfob--$thistoken--0\""; if ($thistoken == $newtoken) { print " CHECKED"; } print " />$date $time $thistoken - $where\n";
													}
													print "</td>\n";
													print "</tr>\n";

												} else {
													$resultMessage .= "Json Failed getting tokens for $sitename<br>";
												}
											} else {
												$resultMessage .= "Nothing was returned getting tokens for $sitename<br>";
											}
										} else {
											$resultMessage .= "Could not get access token for $sitename<br>";
										}
									}

									$siteid = $savesiteid;
								}
							}

							print "</table>\n";

							print "	<span class=\"notclicked\">\n";
							print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
							print "	</span>\n";

							print "	<span class=\"uploading\">\n";
							print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
							print "	</span>\n";

							print "</form>\n";

							// print jquery date picker
							print "<script type=\"text/javascript\">\n";
							print "	$( document ).ready(function() {\n";
							print "		$( \"#datepicker1\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
							print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
							print "	});\n";
							print "</script>\n";

							print "<script>\n";
							print "  $('.uploading').hide();\n";
							print "  	$('form').submit(function () {\n";
							print " 	$('.uploading').show();\n";
							print "  	$('.notclicked').hide();\n";
							print "  });\n";
							print "</script>\n";

						}

					} else {
						$resultMessage .= "Could NOT get access token<br>";
					}

				} else {
					$resultMessage .= "Could NOT get site<br>" . $dbcnx->error . "<br>";
				}

			} else {
				$resultMessage .= "Page called without siteid.<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br>" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br>";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	if ($AuthResultMessage) {
		print "<p align=\"center\">$AuthResultMessage</p>";
	}
	print "<p align=\"center\">";
	if ($userid) {
		print "<a href=\"pax-users-view.php?siteid=$siteid&amp;userid=$userid\">Return | </a>";
	}
	print "<a href=\"pax-users-list.php?siteid=$siteid\">Return To Users | </a>";
	print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
	print "</p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($pinresult);
print "</pre>"; */

print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
