<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton"; // temporary page auth
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">ICE VLANS</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$api_access_point = $row['api_access_point'];
				$access_token     = $row['access_token'];

				$filename = "ice-dps.csv";
				if(file_exists($filename)) {
					$lines = file($filename);

					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>VLAN</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Tenant</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Result</b></td>\n";
					print "</tr>\n";

					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";
					
					for ($n=1; $n < count($lines); $n++) {
						$lines[$n] = str_replace('"', "", $lines[$n]);
						list($vlan,$site,$siteID,$tenant,$custref,$junk) = explode(",",$lines[$n]);

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print " <td>$vlan</td>\n";
						print " <td>$site</td>\n";
						print " <td>$tenant</td>\n";
						print " <td>$custref</td>\n";

						$custref = trim($custref);

						$postdata = http_build_query(
							array(
								'apikey' => $access_token,
								'action' => 'updateid',
								'vlan'   => $vlan,
								'newid'  => $custref
							)
						);

						$ch = curl_init(); 
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."/?".$postdata);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						$result = curl_exec($ch);
						$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);

						if ($httpcode == 200) {
							print " <td>Changed</td>\n";
						} elseif ($httpcode == 400) {
							print " <td>FAILED</td>\n";
						} elseif ($httpcode == 401) {	
							print " <td>UNAUTHORISED</td>\n";
						} else {
							print " <td>Unknown error code $httpcode</td>\n";
						}

						print "</tr>\n";
					}
					print "<tr>\n";
					print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";

				} else {
					$resultMessage .= "File not found<br />";
				}

			} else {
				$resultMessage .= "Could not get access token<br />";
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
} else {
	include ('loggedout.php');
}
print "<p>$result</p>";
print "<p>Sent: ". $api_access_point."/?".$postdata . "</p>";
print "</td>\n";
include('footer.php');
?>
