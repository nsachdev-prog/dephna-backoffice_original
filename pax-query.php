<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Query";
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

				$deptid = $_REQUEST['deptid'];
				$depart = $_REQUEST['depart'];
				$dstr = "";

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Query</p>\n";
					
					print "<p align=\"center\">";
					print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
					print "</p>\n";

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);

						/* $postdata = http_build_query(
							array(
								'query' => "SELECT
									COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
								FROM
									INFORMATION_SCHEMA.COLUMNS
								WHERE
									TABLE_NAME = 'TokenTypes'
								"
							)
						); */

						// $newexpires = date("Y-m-d",strtotime("-1 Day"));

						$postdata = http_build_query(
							array(
								'query' => "SELECT * FROM TokenTypes"
							)
						);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						$result = curl_exec($ch);
						$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);
						
						$resultMessage .= "Return Code $httpcode<br />";
				
						if ($result !== FALSE) {

							if ($result = json_decode($result, TRUE)) {

								if ($result) {

									/* foreach($result AS $id => $user) {
										print $user['columN_NAME'] . "<br />\n";
									} */
									
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

print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($result);
print "</pre>";

print "</td>\n";
include('footer.php');
?>
