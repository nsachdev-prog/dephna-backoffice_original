<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Departments";
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
			$deptid = $_REQUEST['deptid'];
			if ($deptid) {
				$heading = "Update Department";
			} else {
				$heading = "New Department";
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

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

						if ($_REQUEST['posted']) {

							$name = $_POST['name'];

							if (!$name) { $erfirstName = "Please enter a name"; $e++; }

							if (!$e) {

								$name  = addslashes($name);
								
								$postdata = http_build_query(
									array(
										'id'   => $deptid,
										'name' => $name
									)
								);

								$headers = array(
									'Authorization: bearer '.$access_token,
									'Accept: application/json'
								);

								$ch = curl_init();
								if ($deptid) {
									curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
								} else {
									curl_setopt($ch, CURLOPT_POST, 1);
								}
								curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/departments/".$deptid);
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($ch, CURLINFO_HEADER_OUT, true);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
								curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                $result = curl_exec($ch);
                                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
								curl_close($ch);
					
								if ($result !== FALSE) {

                                    if ($deptid) {
                                        if ($httpcode == 204) {
											$resultMessage .= "Department Updated<br />";
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Dept $name','$page','$now')");
                                        } elseif ($httpcode == 400) {
                                            $resultMessage .= "Invalid request - department or id is invalid<br />";
                                        } elseif ($httpcode == 404) {
                                            $resultMessage .= "The specified department could not be found<br />";
                                        } elseif ($httpcode == 502) {
                                            $resultMessage .= "Remote service is not available<br />";
                                        }
                                    } else {
                                        if ($result = json_decode($result, TRUE)) {
                                            if ($result) {
												$resultMessage .= "Department Added<br />";
												$now = time();
												$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Dept $name','$page','$now')");
                                            } else {
                                                $resultMessage .= "Nothing was returned from Paxton.<br />";
                                            }
                                        } else {
                                            $resultMessage .= "Json Failed.<br />";
                                        }
                                    }
				
								} else {
									$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
								}

							}

						}

						elseif ($deptid) {

							$headers = array(
								'Authorization: bearer '.$access_token,
								'Accept: application/json'
							);

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/departments/".$deptid);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
							$result = curl_exec($ch);
							curl_close($ch);
					
							if ($result !== FALSE) {

								if ($result = json_decode($result, TRUE)) {

									if ($result) {                                        
										
										$name  = $result['name'];

									} else {
										$resultMessage .= "Nothing was returned from Paxton.<br />";
									}
								} else {
									$resultMessage .= "Json Failed.<br />";
								}
							} else {
								$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
							}

						}

						if (!$resultMessage OR $e) {

							print "<form name=\"addform\" method=\"post\" action=\"pax-departments-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"deptid\" value=\"$deptid\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print " <td><b>Department Name</b></td>\n";
							print "	<td><input name=\"name\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$name\" />";
							if ($ername) { print "<br /><span class=\"redtext\">$ername</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "</table>\n";

							print "	<span class=\"notclicked\">\n";
							print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
							print "	</span>\n";

							print "	<span class=\"uploading\">\n";
							print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
							print "	</span>\n";

							print "</form>\n";

							print "<script>\n";
							print "  $('.uploading').hide();\n";
							print "  	$('form').submit(function () {\n";
							print " 	$('.uploading').show();\n";
							print "  	$('.notclicked').hide();\n";
							print "  });\n";
							print "</script>\n";

						}

					} else {
						$resultMessage .= "Could NOT get access token<br />";
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
	print "<a href=\"pax-departments-list.php?siteid=$siteid\">Return To Departments | </a>";
	print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
	print "</p>\n";
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
