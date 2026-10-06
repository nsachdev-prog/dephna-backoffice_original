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
			$deptid = $_REQUEST['deptid'];
			$depart = $_REQUEST['depart'];

			if (($siteid) AND ($userid)) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">User</p>\n";

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

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
						$user = curl_exec($ch);
						curl_close($ch);
				
						if ($user !== FALSE) {

							if ($user = json_decode($user, TRUE)) {

								if ($user) {
									
									list($adate,$atime) = explode("T",$user['activateDate']);
									list($edate,$etime) = explode("T",$user['expiryDate']);

									$firstName   = $user['firstName'];
									$middleName  = $user['middleName']; 
									$lastName    = $user['lastName'];
									$pin         = $user['pin'];
									$telephone   = $user['telephone'];
									$extension   = $user['extension'];
									$pin         = $user['pin'];
									$isAntiPassbackUser = $user['isAntiPassbackUser'];
									$isAlarmUser        = $user['isAlarmUser'];
									$isLockdownExempt   = $user['isLockdownExempt'];
									$hasImage           = $user['hasImage'];
									$custid             = $user['customFields'][2]['value'];
									$custref            = $user['customFields'][3]['value'];

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
											$image = base64_encode($iresult);
										}
									}
									
									if ($custid) {
										$sql = "SELECT compname FROM users WHERE ID = '$custid'";
										if (($cresult = $dbcnx->query($sql)) AND ($cresult->num_rows)) {
											$crow = $cresult->fetch_assoc();
											$compname = $crow['compname'];
										}
									}

									$name = $firstName;
									if ($middleName) { $name .= " $middleName"; }
									if ($lastName) { $name .= " $lastName"; }

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
												$department = $result[0]['name'];
											} else {
												$resultMessage .= "Department details not returned from Paxton.<br />";
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
									$levels = curl_exec($ch);
									curl_close($ch);
									if ($levels !== FALSE) {
										if ($levels = json_decode($levels, TRUE)) {
											if ($levels) {
												$levelid = $levels['accessLevels'][0];
												if (!$levelid) { $levelid = 0; }
												
												// get access level name
												$ch = curl_init();
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
												curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/$levelid");
												curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
												curl_setopt($ch, CURLINFO_HEADER_OUT, true);
												curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
												curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
												$level = curl_exec($ch);
												curl_close($ch);
												if ($level !== FALSE) {
													if ($level = json_decode($level, TRUE)) {
														if ($level) {
															$levelnm = $level['name'];
														}
													}
												}

											} else {
												$resultMessage .= "Access level details not returned from Paxton.<br />";
											}
										} else {
											$resultMessage .= "Access level details Json Failed.<br />";
										}
									} else {
										$resultMessage .= "Access level details error: <br />Nothing was returned from Paxton.<br />";
									}

									print "<p align=\"center\" class=\"larger\">$name</p>\n";

									if ($image) {
										print "	<p align=\"center\"><img src=\"data:image/jpeg;base64," . $image . "\" width=\"200\" /></p>\n";
									}

									print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

									print "<tr>\n";
									print " <td><b>Activated</b></td>\n";
									print " <td>$adate</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Expires</b></td>\n";
									print " <td>$edate</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Telephone</b></td>\n";
									print " <td>$telephone</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Extension</b></td>\n";
									print " <td>$extension</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Is Anti-Passback User</b></td>\n";
									print " <td>"; if($isAntiPassbackUser) { print "Yes"; } else { print "No"; } print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Is Alarm User</b></td>\n";
									print " <td>"; if($isAlarmUser) { print "Yes"; } else { print "No"; } print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Is Lockdown Exempt</b></td>\n";
									print " <td>"; if($isLockdownExempt) { print "Yes"; } else { print "No"; } print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td><b>Has Image</b></td>\n";
									print " <td>"; if($hasImage) { print "Yes"; } else { print "No"; } print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td valign=\"top\"><b>CAM Customer</b></td>\n";
									print " <td>$compname</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td valign=\"top\"><b>PAX Department</b></td>\n";
									print " <td>$department</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td valign=\"top\"><b>Access Level</b></td>\n";
									print " <td>$levelnm</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print " <td valign=\"top\"><b>PIN</b></td>\n";
									print " <td>$pin</td>\n";
									print "</tr>\n";

									print "</table>\n";

									print "<p align=\"center\" class=\"larger\">Tokens</p>\n";

									$newtokens = array();

									// get user tokens
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/tokens");
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLINFO_HEADER_OUT, true);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
									curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
									$tokens = curl_exec($ch);
									curl_close($ch);
									if ($tokens !== FALSE) {
										if ($tokens = json_decode($tokens, TRUE)) {
											if ($tokens) {
												print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
												print "<tr>\n";
												print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
												print "	<td style=\"border-bottom:1px solid #999999\"><b>Value</b></td>\n";
												print "	<td style=\"border-bottom:1px solid #999999\"><b>Is Lost</b></td>\n";
												print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
												print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
												print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
												print "</tr>\n";

												// row background colours
												print "<style>\n";
												print ".r0 { background-color:#fff }\n";
												print ".r1 { background-color:#ddd }\n";
												print "</style>\n";

												foreach($tokens AS $tid => $token) {
													$tokenid = $token['id'];
													$tokenType = $token['tokenType'];
													$tokenValue = $token['tokenValue'];
													$isLost = $token['isLost'];
													if ($isLost) {
														$isLosttxt = "Yes";
													} else {
														$isLosttxt = "";
													}

													$newtokens[] = "$tokenType--$tokenValue--$isLost";

													// see if there is an original fob request with training checklist
													$sql = "SELECT ID FROM pax_user_requests WHERE siteid = '$siteid' AND userid = '$userid'";
													if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
														$urow = $uresult->fetch_assoc();
														$reqid = $urow['ID'];
													} else {
														$reqid = 0;
													}

													print "<tr class=\"r" . ($i++ % 2) . "\">\n";
													print " <td>$tokenType</td>\n";
													print " <td>$tokenValue</td>\n";
													print " <td>$isLosttxt</td>\n";
													if ($reqid) {
														print " <td><a href=\"pax-users-request.php?reqid=$reqid&amp;siteid=$siteid&amp;userid=$userid&amp;deptid=$deptid&amp;depart=$depart\">Request</a></td>\n";
													} else {
														print " <td class=\"noaccess\"><i>Request</i></td>\n";
													}
													if ($accessRights[$page] > 2) {
														if ($isLost) {
															print " <td class=\"noaccess\">Lost</td>\n";
														} else {
															print " <td><a href=\"pax-users-tokens-lost.php?siteid=$siteid&amp;userid=$userid&amp;tokenid=$tokenid\">Lost</a></td>\n";
														}
														print " <td><a href=\"pax-users-tokens-delete.php?siteid=$siteid&amp;userid=$userid&amp;tokenid=$tokenid\">Delete</a></td>\n";
													} else {
														print " <td class=\"noaccess\">Delete</td>\n";
													}
												   print "</tr>\n";

												}

												print "<tr>\n";
												print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
												print "</tr>\n";

												print "</table>\n";

											} else {
												$resultMessage .= "Token details not returned from Paxton.<br />";
											}
										} else {
											$resultMessage .= "No Tokens Found.<br />";
										}
									} else {
										$resultMessage .= "Token details error: <br />Nothing was returned from Paxton.<br />";
									}
									
									// copy to another site

									print "<p align=\"center\" class=\"larger\">Copy To Another Site</p>\n";
									print "<p align=\"center\">You will be taken to the target site.</p>\n";

									print "<table align=\"center\">\n";
									print "<form name=\"addform\" method=\"post\" action=\"pax-users-addedit.php\">\n";
									print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
									print "<input type=\"hidden\" name=\"copy\" value=\"1\" />\n";
									print "<input type=\"hidden\" name=\"firstName\" value=\"$firstName\" />\n";
									print "<input type=\"hidden\" name=\"middleName\" value=\"$middleName\" />\n";
									print "<input type=\"hidden\" name=\"lastName\" value=\"$lastName\" />\n";
									print "<input type=\"hidden\" name=\"telephone\" value=\"$telephone\" />\n";
									print "<input type=\"hidden\" name=\"extension\" value=\"$extension\" />\n";
									print "<input type=\"hidden\" name=\"isAntiPassbackUser\" value=\"$isAntiPassbackUser\" />\n";
									print "<input type=\"hidden\" name=\"isAlarmUser\" value=\"$isAlarmUser\" />\n";
									print "<input type=\"hidden\" name=\"isLockdownExempt\" value=\"$isLockdownExempt\" />\n";
									print "<input type=\"hidden\" name=\"edate\" value=\"$edate\" />\n";
									print "<input type=\"hidden\" name=\"adate\" value=\"$adate\" />\n";
									if ($newtokens) {
										foreach ($newtokens AS $newtoken) {
											print "<input type=\"hidden\" name=\"newtokens[]\" value=\"$newtoken\" />\n";
										}
									}
									print "<input type=\"hidden\" name=\"customer\" value=\"$custid--$custref--$compname\" />\n";
									$sql = "SELECT p.siteid, s.title
											FROM PaxAuth AS p
											LEFT JOIN sites AS s ON s.ID = p.siteid
									";
									if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
										print "<td>Site</td>\n";
										print "<td><select name=\"siteid\" class=\"element_medium\">\n";
										while ($row = $result->fetch_assoc()) {
											$thisid    = $row['siteid'];
											$thissite  = $row['title'];
											if ($thisid != $siteid) {
												print "<option value=\"$thisid\">$thissite</option>\n";
											}
										}
										print "</select>";
										print "</td>\n";
										print "</table>\n";
									}
									print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
									print "</form>\n";

								} else {
									$resultMessage .= "User details not returned from Paxton.<br />";
								}

							} else {
								$resultMessage .= "User details Json Failed.<br />";
							}
				
						} else {
							$resultMessage .= "User details error: <br />Nothing was returned from Paxton.<br />";
						}
						
					} else {
						$resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without siteid/userid.<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	if ($AuthResultMessage) {
		print "<p align=\"center\">$AuthResultMessage</p>";
	}
	print "<p align=\"center\">";
	if ($accessRights[$page] > 1) {
		print "<a href=\"pax-users-addedit.php?siteid=$siteid&amp;userid=$userid\">Update User | </a>";
	}
	if ($accessRights[$page] > 2) {
		print "<a href=\"pax-users-delete.php?siteid=$siteid&amp;userid=$userid\">Delete User | </a>";
	}
	print "<a href=\"pax-users-list.php?siteid=$siteid&amp;deptid=$deptid&amp;depart=$depart\">Return To Users | </a>";
	print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
	print "</p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($user);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
