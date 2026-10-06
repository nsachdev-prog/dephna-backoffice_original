<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bookings";
$subpage = "Vlans";
$internet = 1;
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$vlan    = $_REQUEST['vlan'];
			$custref = $_REQUEST['custref'];
			$status  = $_REQUEST['status'];

			if ($status == "suspend") {
				$statustext = "Restrict";
			} elseif ($status == "active") {
				$statustext = "Unrestrict";
			}

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Internet</p>\n";
			print "<p align=\"center\">$menustr</p>\n";
			
			print "<p align=\"center\" class=\"larger\">Update Status</p>\n";
			print "<p align=\"center\" class=\"larger\">$statustext</p>\n";

			if ($_POST['posted']) {

				$customer = strip_tags($_POST['customer']);
				if ($customer) {
					list($custref,$compname) = explode("--",$customer);
				}

				$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$api_access_point = $row['api_access_point'];
					$access_token     = $row['access_token'];

					if ($vlan AND $status AND $custref) {

						$postdata = http_build_query(
							array(
								'apikey' => $access_token,
								'action' => 'servicestatus',
								'vlan'   => $vlan,
								'status' => $status
							)
						);

						$ch = curl_init(); 
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."/?".$postdata);
						// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						$result = curl_exec($ch);
						$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);

						if ($httpcode == 200) {
							$resultMessage = "$compname<br />$statustext Done<br />";
						} elseif ($httpcode == 400) {
								$resultMessage .= $result;
								$resultMessage .= "<br /><br />Sent: $api_access_point/?$postdata";
						} elseif ($httpcode == 401) {
							$resultMessage = "Unauthorised<br />";
						} else {
							$resultMessage = "Unknown error code $httpcode<br />";
						}

					} else {
						$resultMessage .= "Parameters missing<br />";
					}

				} else {
					$resultMessage .= "Could not get access token<br />";
				}

			} else {

				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
				} else {
					$compname = "Unknown";
				}

			}

			if (!$resultMessage OR $e) {

				print "<form name=\"addform\" method=\"post\" action=\"bookings-vlans-status.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"customer\" value=\"$custref--$compname\" />\n";
				print "<input type=\"hidden\" name=\"vlan\" value=\"$vlan\" />\n";
				print "<input type=\"hidden\" name=\"status\" value=\"$status\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Customer</b></td>\n";
				print "	<td>DP$custref - $compname</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td align=\"right\"><b>VLAN</b></td>\n";
				print "	<td>$vlan</td>\n";
				print "</tr>\n";

				print "</table>\n";

				print "<p align=\"center\" class=\"redtext\">ARE YOU SURE?</p>\n";

				print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

				print "</form>\n";

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
	
	print "<p align=\"center\"><a href=\"bookings-vlans-list.php\">Return</a></td>\n";

	/* print "<p><b>Cust Refs on Ice</b></p>";
	print "<pre>";
	print_r($vlans);
	print "</pre>";

	print "<p><b>Raw Data From Ice</b></p>";
	print "<pre>";
	print_r($result);
	print "</pre>"; */

} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function randomPassword($len = 10) {

	//define character libraries - remove ambiguous characters like iIl|1 0oO
	$sets = array();
	$sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
	$sets[] = 'abcdefghjkmnpqrstuvwxyz';
	$sets[] = '23456789';

	$password = '';
	
	//append a character from each set - gets first 4 characters
	foreach ($sets as $set) {
		$password .= $set[array_rand(str_split($set))];
	}

	//use all characters to fill up to $len
	while(strlen($password) < $len) {
		//get a random set
		$randomSet = $sets[array_rand($sets)];
		
		//add a random char from the random set
		$password .= $randomSet[array_rand(str_split($randomSet))]; 
	}
	
	//shuffle the password string before returning!
	return str_shuffle($password);
}
?>
