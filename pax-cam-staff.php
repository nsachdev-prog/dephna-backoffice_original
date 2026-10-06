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
			if ($siteid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Depatments Vs Customers</p>\n";

					print "<p align=\"center\">";
					print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
					print "</p>\n";

					print "</b></p>\n";

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);

						$postdata = http_build_query(
							array(
								'query' => "SELECT *
										FROM UsersEx
										WHERE firstName > '' AND active = '1'
										ORDER BY firstName"
							)
						);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						$result = curl_exec($ch);
						curl_close($ch);
				
						if ($result !== FALSE) {

							if ($result = json_decode($result, TRUE)) {

								if ($result) {

									print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
									print "<tr>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Department</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
									print "</tr>\n";

									// row background colours
									print "<style>\n";
									print ".r0 { background-color:#fff }\n";
									print ".r1 { background-color:#ddd }\n";
									print "</style>\n";

									$n = 0;
									$today = date("Y-m-d");

									$headers = array(
										'Authorization: bearer '.$access_token,
										'Accept: application/json'
									);

									foreach ($result AS $id => $user) {

										list($edate,$etime) = explode("T",$user['expiryDate']);
										$department = $user['departmentName'];

										if ((!$edate OR $edate > $today) 
											AND ($department == "Staff"
											))
										{

											$n++;

											$userid = $user['userID'];
											$usernm = $user['firstName'] . " " . $user['surname'];
											$paxcid = $user['field3_50'];

											if (!$paxcid) {

												// get cam customer
												$sql = "SELECT ID, custref, compname FROM users WHERE compname = 'Dephna Group'";
												if (($cresult = $dbcnx->query($sql)) AND ($cresult->num_rows)) {
													$crow = $cresult->fetch_assoc();
													$custid = $crow['ID'];
													$custref = $crow['custref'];
													$compname = $crow['compname'];
												} else {
													$compname = "";
													$custid = 0;
													$custref = 0;
												}

												if ($compname AND $custid AND $custref) {

													$postdata = http_build_query(
														array(
															'id' => $userid,
															'customFields' => array(
																array(
																	'id' => '1',
																	'value' => $compname
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

													$ch = curl_init();
													curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
													curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
													curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
													curl_setopt($ch, CURLINFO_HEADER_OUT, true);
													$result = curl_exec($ch);
													$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
													curl_close($ch);
									
													if ($result !== FALSE) {
					
														if ($httpcode == 200) {
															$message = "User Updated to DP$custref<br />";
															$now = time();
															$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','User $firstName $lastName','$page','$now')");
														} elseif ($httpcode == 400) {
															$message = "user or id is invalid<br />";
														} elseif ($httpcode == 404) {
															$message = "The user could not be found";
														} elseif ($httpcode == 502) {
															$message = "Remote service is not available";
														} else {
															$message = "Return Code DP$custref";
														}

													} else {
														$message = "Nothing Returned From Paxton";
													}
												} else {
													$message = "Not Found";
												}

												print "<tr class=\"r" . ($i++ % 2) . "\">\n";
												print " <td>$usernm</td>\n";
												print " <td>$department</td>\n";
												print " <td>$compname</td>\n";
												print " <td>$message</td>\n";
												print "</tr>\n";

											}

										}

									}

									print "<tr>\n";
									print "<td style=\"border-top:1px solid #999999\">$n</td>\n";
									print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
									print "</tr>\n";

									print "</table>\n";

								} else {
									$resultMessage .= "Nothing was returned from Paxton.<br />";
								}

							} else {
								$resultMessage .= "Json Failed.<br />";
							}
				
						} else {
							$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
						}
						
					} else {
						$resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT get site<br />" . $result->error . "<br />";
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
