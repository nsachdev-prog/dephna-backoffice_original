<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Doors";
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
			if ($siteid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Doors</p>\n";

					print "<p align=\"center\">Note: If you change a unit's lock id in paxton<br>you must also change it in Sites > Units</p>\n";

					print "<p align=\"center\">";
					print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
					print "</p>\n";

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);

						$doorid    = $_REQUEST['doorid'];
						$reinstate = $_REQUEST['reinstate'];
						$doortx    = $_REQUEST['doortx'];

						if ($doorid) {

							$postdata = http_build_query(
								array(
									'doorId' => $doorid
								)
							);

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							if ($reinstate) {
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/commands/door/reinstate");
								$command = "Reinstated";
							} else {
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/commands/door/open");
								$command = "Opened";
							}
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$result = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							curl_close($ch);

							if ($httpcode == 200) {
								$doorresult = "Door $sitename $doortx $command by {$_SESSION['username']}";
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Command','Door $sitename $doortx $command','$page','$now')");
							} elseif ($httpcode == 400) {
								$doorresult = "User or door validation issue";
							} elseif ($httpcode == 502) {
								$doorresult .= "Remote service is not available";
							} else {
								$doorresult .= "Unknown Error Code $httpcode";
							}

						}

						if ($doorresult) {
							print "<p align=\"center\" class=\"redtext\">$doorresult</p>\n";
						}

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/doorgroups");
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
						$result = curl_exec($ch);
						curl_close($ch);
				
						if ($result !== FALSE) {

							if ($result = json_decode($result, TRUE)) {

								if ($result) {

									$columns = array_column($result, 'name');
									array_multisort($columns, SORT_ASC, $result);

									print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

									print "<tr>\n";
									
									foreach ($result AS $id => $doorgroup) {

										$groupid = $doorgroup['id'];
										$groupnm = $doorgroup['name'];
										
										print "<tr>\n";
										print " <td colspan=\"3\" style=\"background-color:#666;color:#fff;text-align:center\">$groupnm</td>\n";
										print "</tr>\n";
										
										// get the doors
										$ch = curl_init();
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/doorgroups/$groupid/doors");
										curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
										curl_setopt($ch, CURLINFO_HEADER_OUT, true);
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
										curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
										$doors = curl_exec($ch);
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
										curl_close($ch);
										
										if ($doors !== FALSE) {

											if ($doors = json_decode($doors, TRUE)) {

												if ($doors) {

													$columns = array_column($doors, 'name');
													array_multisort($columns, SORT_ASC, $doors);
													
													foreach ($doors AS $did => $door) {

														$doorid = $door['id'];
														$doornm = $door['name'];

														print "<tr>\n";
														print " <td>$doornm</td>\n";
														print " <td>$doorid</td>\n";
														print " <td><a href=\"pax-doors-list.php?siteid=$siteid&amp;doorid=$doorid&amp;doortx=$doornm\">Open</a>&nbsp;|&nbsp;<a href=\"pax-doors-list.php?siteid=$siteid&amp;doorid=$doorid&amp;doortx=$doornm&amp;reinstate=1\">Reinstate</a></td>\n";
														print "</tr>\n";

													}

												} else {
													$resultMessage .= "Nothing was returned for doors group $groupid code $httpcode<br />";
												}

											} else {
												print " <td>No Doors Found</td>\n";
												print " <td>&nbsp;</td>\n";
											}

										} else {
											$resultMessage .= "Nothing was returned for doors group $groupid<br />";
										}

									}

									print "</table>\n";

								} else {
									$resultMessage .= "Nothing was returned from Paxton.<br />";
								}

							} else {
								$resultMessage .= "No Doors Found.<br />";
							}
				
						} else {
							$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
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
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($result);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
