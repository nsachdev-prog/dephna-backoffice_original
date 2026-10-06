#!/usr/bin/php7.4

<?php
// print errors console
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// cron-paxton-batteries.php - Written by David Sherwood - dsi3.net
// Client - Dephna Estates.
// Gathers battery status 

$cronjob = 1;
chdir(dirname(__FILE__));
include('../public_html/backoffice/settings.php');
$reportEmail = "david@dsi3.net";

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$now = time();

	// get sites

	$batteries = "";
		
	$sql = "SELECT p.siteid, p.webAccessUrl, p.username, p.password, p.api_access_point, s.title AS sitename
			FROM PaxAuth AS p
			LEFT JOIN sites AS s ON s.ID = p.siteid
            ORDER BY sitename
	";
	if (($siteresult = $dbcnx->query($sql)) AND ($siteresult->num_rows)) {

		$resultMessage = "<table>";

		$resultMessage .= "<tr><td colspan=\"7\"><h2>Paxton Battery Report</h2></td></tr>";

		while ($srow = $siteresult->fetch_assoc()) {

			$siteid     = $srow['siteid'];
			$username   = $srow['username'];
			$password   = $srow['password'];
			$sitename   = $srow['sitename'];
			$webAccessUrl = $srow['webAccessUrl'];
			$api_access_point = $srow['api_access_point'];

			$resultMessage .= "<tr><td colspan=\"7\"><h3>$sitename</h3></td></tr>";
			$resultMessage .= "<tr>\n";
			$resultMessage .= "	<td><b>Location</b></td>\n";
			$resultMessage .= "	<td><b>Description</b></td>\n";
			$resultMessage .= "</tr>\n";

			$access_token = "";

			include('../public_html/backoffice/PaxGetAccessToken.php');

			if ($access_token) {

				$fr = date("Y-m-d",strtotime("-1 Day")) . "T00:00:00";
				$to = date("Y-m-d",strtotime("-1 Day")) . "T23:59:59";
				
				$headers = array(
					'Authorization: bearer '.$access_token,
					'Accept: application/json'
				);

				$postdata = http_build_query(
					array(
						"query" => "SELECT peripheralName, eventTypeDescription
								FROM EventsEx
								WHERE eventTime >= '$fr' AND eventTime <= '$to' AND eventTypeDescription LIKE '%Battery%'
                                GROUP BY peripheralName, eventTypeDescription
								ORDER BY peripheralName, eventTypeDescription DESC"
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
		
				if ($result !== FALSE) {

					if ($result = json_decode($result, TRUE)) {

						if ($result) {

							foreach ($result AS $id => $event) {

								$peripheralName = $event['peripheralName'];
								$description = $event['eventTypeDescription'];

                                $batteries .= "$siteid,$sitename,";
								$batteries .= "$peripheralName,";
								$batteries .= "$description\n";

								$resultMessage .= "<tr>\n";
								$resultMessage .= " <td valign=\"top\">$peripheralName</td>\n";
								$resultMessage .= " <td valign=\"top\">$description</td>\n";
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

		// output json result for debugging

		if ($batteries) {
			$today = date("Y-m-d");
			$filename   = "$today.csv";
			$local_file = "/home/dephna/dis2_db/bat/$filename";
            $handle  = fopen("$local_file", "w");
            fwrite($handle, $batteries);
            fclose($handle);
		}

	} else {
		$resultMessage = "Could not get sites<br>" . $dbcnx->error . "<br>";
	}
	$dbcnx->close();
} else {
	$resultMessage = "Could not connect to database!<br>" . $dbcnx->connect_error . "<br>";
}

$resultMessage .= "<p>$fr to $to</p>";

if ($resultMessage) { echo $resultMessage; }

// Mail the processing report to $reportEmail

/* $subject = "Paxton Battery Report";
$from = "Robot <robot@dephna.com>";
$mime_boundary = md5(uniqid(time()));

$headers  = "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

$message = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
if ($resultMessage) { $message .= "$resultMessage"; }
// if ($result) { $message .= print_r($result,TRUE); }
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";

mail($reportEmail, $subject, $message, $headers); */
?>
