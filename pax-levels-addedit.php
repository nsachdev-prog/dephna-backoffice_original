<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Levels";
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

			$siteid  = $_REQUEST['siteid'];
			$levelid = $_REQUEST['levelid'];

			if ($levelid) {
				$heading = "Update Access Level";
			} else {
				$heading = "New Access Level";
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
					print "<p align=\"center\" class=\"larger\">Levels</p>\n";
					print "<p align=\"center\" class=\"larger\">$heading</p>\n";
					print "<p align=\"center\">";
					print "<a href=\"pax-levels-list.php?siteid=$siteid\">Return To Levels</a> | ";
					print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
					print "</p>\n";

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

						if ($_POST['posted']) {

							$areas   = $_POST['areas'];
							$levelnm = $_POST['levelnm'];

							if (!$levelnm) {
								$erlevelnm = "Please enter a level name";
								$e++;
							}
							
							if (!$e) {
								$detailRows = array();
								$n=0;
								foreach ($areas AS $aid => $zone) {
									$detailRows[$n]['areaID'] = $aid;
									$detailRows[$n]['timezoneID'] = $zone;
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
								if ($levelid) {
									curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
								} else {
									curl_setopt($ch, CURLOPT_POST, 1);
								}
								curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/".$levelid);
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($ch, CURLINFO_HEADER_OUT, true);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
								curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
								$result = curl_exec($ch);
								$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					
								if ($result !== FALSE) {

									if ($levelid) {
										if ($httpcode == 200) {
											$resultMessage .= "Level Updated<br />";
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Level $levelnm $httpcode','$page','$now')");
										} elseif ($httpcode == 400) {
											$resultMessage .= "Invalid request - level or id is invalid<br />";
										} elseif ($httpcode == 404) {
											$resultMessage .= "The specified access level could not be found<br />";
										} elseif ($httpcode == 502) {
											$resultMessage .= "Remote service is not available for user<br />";
										} else {
											$resultMessage .= "Unknown Return Code $httpcode for user<br />";
										}
									} else {
										if ($result = json_decode($result, TRUE)) {
											if ($result) {
												$levelid = $result['id'];
												$resultMessage .= "Level Added<br />";
												$now = time();
												$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Level $levelnm $httpcode','$page','$now')");
											} else {
												$resultMessage .= "Nothing was returned from Paxton for level.<br />";
											}
										} else {
											$resultMessage .= "Json Failed for level.<br />";
											$resultMessage .= "Return Code $httpcode<br />";
										}
									}
				
								} else {
									$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
								}

							}

						}

						elseif ($levelid) {
							// get current areas and timezones
							$headers = array(
								'Authorization: bearer '.$access_token,
								'Accept: application/json'
							);

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/$levelid/detail");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$result1 = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if ($result1 !== FALSE) {
								if ($result1 = json_decode($result1, TRUE)) {
									if ($result1) {
										$levelnm = $result1['name'];
										foreach ($result1['detailRows'] AS $rid => $row) {
											$areas[$row['areaID']] = $row['timezoneID'];
										}
									}
								}
							}
						}

						if (!$resultMessage OR $e) {

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
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$result = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
							if ($result !== FALSE) {

								if ($result = json_decode($result, TRUE)) {

									if ($result) {

										// get all timezones
										$ch = curl_init();
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/timezones");
										curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
										curl_setopt($ch, CURLINFO_HEADER_OUT, true);
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
										curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
										$timezones = curl_exec($ch);
										if ($timezones !== FALSE) {
											if ($timezones = json_decode($timezones, TRUE)) {
												// good
											}else {
												$resultMessage .= "No Timezones Returned<br />";
											}
										} else {
											$resultMessage .= "Error: <br />Timezones not returned from Paxton.<br />";
										}

										print "<form name=\"addform\" method=\"post\" action=\"pax-levels-addedit.php\">\n";
										print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
										print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
										print "<input type=\"hidden\" name=\"levelid\" value=\"$levelid\" />\n";
										print "<input type=\"hidden\" name=\"levelnm\" value=\"$levelnm\" />\n";
										print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

										print "<tr>\n";
										print " <td><b>Access Level Name</b></td>\n";
										print "	<td><input name=\"levelnm\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$levelnm\" />";
										if ($erlevelnm) { print "<br /><span class=\"redtext\">$erlevelnm</span>"; }
										print "</td>\n";
										print "</tr>\n";

										foreach ($result AS $id => $door) {

											$areaid = $door['areaID'];
											$areanm = $door['areaName'];

											print "<tr class=\"r" . ($i++ % 2) . "\">\n";
											print " <td>$areanm</td>\n";
											print " <td>";
											if ($timezones) {
												print "<select name=\"areas[$areaid]\" class=\"element\">\n";
												foreach ($timezones AS $tid => $zone) {
													$thiszoneid = $zone['id'];
													$thiszonenm = $zone['name'];
													print "		<option value=\"$thiszoneid\""; if ($thiszoneid == $areas[$areaid]) { print " SELECTED"; } print ">$thiszonenm</option>\n";
												}
												print "	</select>";
											}
											print " </td>\n";
											print "</tr>\n";

										}

										print "<tr>\n";
										print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
										print "</tr>\n";

										print "</table>\n";
										print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
										print "</form>\n";
										
									} else {
										$resultMessage .= "Nothing was returned from Paxton.<br />";
									}

								} else {
									$resultMessage .= "No Devices Returned<br />";
								}
					
							} else {
								$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
							}

						}
						
					} else {
						$resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without siteid.<br />";
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
    print "<a href=\"pax-levels-list.php?siteid=$siteid\">Return To Levels</a> | ";
    print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
    print "</p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data</b></p>";
print "<pre>";
print_r($postdata);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
