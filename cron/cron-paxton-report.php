#!/usr/bin/php

<?php
# cron-paxton-report.php - Written by David Sherwood - dsi3.net
# Client - Dephna Estates.
# Gathers access on depatment between given times.

$cronjob = 1;
chdir(dirname(__FILE__));
include('../public_html/backoffice/settings.php');
$reportEmail   = "support@dephna.com";
// $reportEmail = "david@dsi3.net";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$now = time();

	// get sites
		
	$sql = "SELECT p.siteid, p.webAccessUrl, p.username, p.password, p.api_access_point, s.title AS sitename
			FROM PaxAuth AS p
			LEFT JOIN sites AS s ON s.ID = p.siteid
	";
	if (($siteresult = $dbcnx->query($sql)) AND ($siteresult->num_rows)) {

		$resultMessage = "<table>";

		$resultMessage .= "<tr><td colspan=\"7\"><h2>Paxton Report On Platinum, LSS, and M&amp;M</h2></td></tr>";

		while ($srow = $siteresult->fetch_assoc()) {

			$siteid     = $srow['siteid'];
			$username   = $srow['username'];
			$password   = $srow['password'];
			$sitename   = $srow['sitename'];
			$webAccessUrl = $srow['webAccessUrl'];
			$api_access_point = $srow['api_access_point'];

			$resultMessage .= "<tr><td colspan=\"7\"><h3>$sitename</h3></td></tr>";
			$resultMessage .= "<tr>\n";
			$resultMessage .= "	<td><b>Date/Time</b></td>\n";
			$resultMessage .= "	<td><b>Name</b></td>\n";
			$resultMessage .= "	<td><b>Department</b></td>\n";
			$resultMessage .= "	<td><b>Location</b></td>\n";
			$resultMessage .= "	<td><b>Token</b></td>\n";
			$resultMessage .= "	<td><b>Description</b></td>\n";
			$resultMessage .= "	<td><b>Details</b></td>\n";
			$resultMessage .= "</tr>\n";

			include('../public_html/backoffice/PaxGetAccessToken.php');

			if ($access_token) {

				$fr = date("Y-m-d",strtotime("-1 Day")) . "T17:00:00";
				$to = date("Y-m-d") . "T08:00:00";
				
				$headers = array(
					'Authorization: bearer '.$access_token,
					'Accept: application/json'
				);

				$postdata = http_build_query(
					array(
						'query' => "SELECT e.*, u.departmentName
								FROM EventsEx AS e
								LEFT JOIN UsersEx AS u ON e.userID = u.userID
								WHERE e.eventTime >= '$fr' AND e.eventTime <= '$to'
								  AND (u.departmentName LIKE '%Platinum%'
								   OR  u.departmentName LIKE '%LSS%'
								   OR  u.departmentName LIKE '%M&M%')
								ORDER BY u.departmentName, e.eventTime DESC"
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
				curl_close($ch);
		
				if ($result !== FALSE) {

					if ($result = json_decode($result, TRUE)) {

						if ($result) {

							$thisdept = "";

							foreach ($result AS $id => $event) {

								list($date,$time) = explode("T",$event['eventTime']);
								list($time,$junk) = explode("+",$time);
								list($time,$junk) = explode(".",$time);

								$cardNumber = $event['cardNumber'];
								$peripheralName = $event['peripheralName'];
								$department = $event['departmentName'];
								if (strlen($department) > 15) { $department = substr($department, 0, 15); }
								$name = "{$event['firstName']} {$event['surname']}";
								if (strlen($name) > 15) { $name = substr($name, 0, 15); }
								$description = $event['eventTypeDescription'];
								if (strlen($description) > 16) { $description = substr($description, 0, 16); }
								$subDesc = $event['eventSubTypeDescription'];
								if (strlen($subDesc) > 16) { $subDesc = substr($subDesc, 0, 16); }

								if ($department != $thisdept) {
									$resultMessage .= "<tr>\n";
									$resultMessage .= " <td colspan=\"7\" valign=\"top\"><b>$department</b></td>\n";
									$resultMessage .= "</tr>\n";
									$thisdept = $department;
								}

								$resultMessage .= "<tr>\n";
								$resultMessage .= " <td valign=\"top\">$date $time</td>\n";
								$resultMessage .= " <td valign=\"top\">$name</td>\n";
								$resultMessage .= " <td valign=\"top\">$department</td>\n";
								$resultMessage .= " <td valign=\"top\">$peripheralName</td>\n";
								$resultMessage .= " <td valign=\"top\">$cardNumber</td>\n";
								$resultMessage .= " <td valign=\"top\">$description</td>\n";
								$resultMessage .= " <td valign=\"top\">$subDesc</td>\n";
								$resultMessage .= "</tr>\n";

							}

							$resultMessage .= "<tr><td colspan=\"7\">&nbsp;<br></td></tr>";


						} else {
							$resultMessage .= "<tr><td colspan=\"7\">Nothing was returned from Paxton.</td></tr>";
							$resultMessage .= "<tr><td colspan=\"7\">&nbsp;<br></td></tr>";
						}

					} else {
						$resultMessage .= "<tr><td colspan=\"7\">Nothing Found.</td></tr>";
						$resultMessage .= "<tr><td colspan=\"7\">&nbsp;<br></td></tr>";
					}
		
				} else {
					$resultMessage .= "<tr><td colspan=\"7\">Error: <br>Nothing was returned from Paxton.</td></tr>";
					$resultMessage .= "<tr><td colspan=\"7\">&nbsp;<br></td></tr>";
				}


			} else {
				$resultMessage .= "<tr><td colspan=\"7\">Could NOT get Paxton access token<br>" . $AuthResultMessage . "</td></tr>";
				$resultMessage .= "<tr><td colspan=\"7\">&nbsp;<br></td></tr>";
			}

		}
		
		$resultMessage .= "</table>";

	} else {
		$resultMessage = "Could not get sites<br>" . $dbcnx->error . "<br>";
	}
	$dbcnx->close();
} else {
	$resultMessage = "Could not connect to database!<br>" . $dbcnx->connect_error . "<br>";
}

// Mail the processing report to $reportEmail

$subject = "Paxton Report";
$from = "Robot <robot@dephna.com>";
$mime_boundary = md5(uniqid(time()));

$headers  = "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

$message = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
if ($resultMessage) { $message .= "$resultMessage"; }
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";

mail($reportEmail, $subject, $message, $headers);
?>
