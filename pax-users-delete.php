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

						if ($_POST['delete']) {

							$purge = $_POST['purge'];
							
							$postdata = http_build_query(
								array(
									'purge' => $purge
								)
							);

							$headers = array(
								'Authorization: bearer '.$access_token,
								'Accept: application/json'
							);

							$ch = curl_init(); 
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
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
				
							if ($httpcode == 204) {
								$name = $_POST['name'];
								$resultMessage = "User $name Deleted From Paxton<br />";
								$sql = "DELETE FROM pax_user_requests WHERE userid = '$userid'";
								if ($result = $dbcnx->query($sql)) {
									$resultMessage = "Any Fob Requests Deleted From CAM<br />";
								}
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','User $name','$page','$now')");
							} elseif ($httpcode == 404) {
								$resultMessage = "The specified user could not be found<br />";
							} elseif ($httpcode == 502) {
								$resultMessage = "Remote service is not available<br />";
							}

						} else {

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
							$result = curl_exec($ch);
							curl_close($ch);
					
							if ($result !== FALSE) {

								if ($result = json_decode($result, TRUE)) {

									if ($result) {
										
										list($adate,$atime) = explode("T",$result['activateDate']);
										list($edate,$etime) = explode("T",$result['expiryDate']);

										$firstName=$result['firstName'];
										$middleName=$result['middleName']; 
										$lastName=$result['lastName'];

										$name = $firstName;
										if ($middleName) { $name .= " $middleName"; }
										if ($lastName) { $name .= " $lastName"; }

										print "<p align=\"center\" class=\"larger\">$name</p>\n";
										print "<p align=\"center\" class=\"larger\">Delete</p>\n";
										print "<p align=\"center\" class=\"redtext\"><b>ARE YOU SURE?</b></p>\n";

										print "<form name=\"addform\" method=\"post\" action=\"pax-users-delete.php\">\n";
										print "<input type=\"hidden\" name=\"delete\" value=\"1\" />\n";
										print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
										print "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
										print "<input type=\"hidden\" name=\"name\" value=\"$name\" />\n";
										print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

										print "<tr>\n";
										print "	<td><b>Purge Paxton Event History</b></td>\n";
										print "	<td><select name=\"purge\" class=\"element_small\">\n";
										print "		<option value=\"0\""; if ($purge == 0) { print " SELECTED"; } print ">No</option>\n";
										print "		<option value=\"1\""; if ($purge == 1) { print " SELECTED"; } print ">Yes</option>\n";
										print "	</select>";
										print "</td>\n";
										print "</tr>\n";
										
										print "</table>\n";

										print "	<span class=\"notclicked\">\n";
										print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Yes Delete\"></p>\n";
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
	print "<a href=\"pax-users-list.php?siteid=$siteid\">Return To Users | </a>";
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
