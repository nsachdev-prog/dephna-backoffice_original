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

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Internet</p>\n";
            print "<p align=\"center\">$menustr</p>\n";
            
            print "<p align=\"center\" class=\"larger\">Update Status</p>\n";


			if ($_POST['posted']) {

				$customer    = strip_tags($_POST['customer']);
				$icepassword = strip_tags($_POST['icepassword']);

				if (!$icepassword) {
					$ericepassword = "Please give a password"; $e++;
				} else {
					$passlen = strlen($icepassword);
					if (!preg_match('/[A-Z]/', $icepassword) OR
						!preg_match('/[a-z]/', $icepassword) OR
						!preg_match('/[0-9]/', $icepassword)) {
						$ericepassword = "Invalid password Format"; $e++;
					} elseif ($passlen < 10 OR $passlen > 15) {
						$ericepassword = "Invalid password Size $passlen"; $e++;
					}
				}

				if ($customer) {
					list($custref,$compname) = explode("--",$customer);
				}

				if (!$e) {

					$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$api_access_point = $row['api_access_point'];
						$access_token     = $row['access_token'];

						if ($vlan AND $icepassword AND $custref) {
							// only update password

							$postdata = http_build_query(
								array(
									'apikey'   => $access_token,
									'action'   => 'resetpassword',
									'id'       => $custref,
									'password' => $icepassword
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
								$resultMessage = "Password Updated<br />";
							} elseif ($httpcode == 400) {
								 $resultMessage .= $result;
							} elseif ($httpcode == 401) {
								$resultMessage = "Unauthorised<br />";
							} else {
								$resultMessage = "Unknown error code $httpcode<br />";
							}

						}

					} else {
						$resultMessage .= "Could not get access token<br />";
					}

				}

			} else {

				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
				}

			}

			if (!$resultMessage OR $e) {

				print "<form name=\"addform\" method=\"post\" action=\"bookings-vlans-status.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"customer\" value=\"$custref--$compname\" />\n";
				print "<input type=\"hidden\" name=\"vlan\" value=\"$vlan\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Customer</b></td>\n";
				print "	<td>DP$custref - $compname</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td align=\"right\"><b>VLAN</b></td>\n";
				print "	<td>$vlan</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>&nbsp;</td>\n";
				print "	<td><i>**Password Auto Generated**</i><br />Passwords must be between 10 and 15 characters and<br />have at least 1 Upper Case, 1 Lower Case and 1 Number</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Password</b></td>\n";
				print "	<td><input name=\"icepassword\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$icepassword\" />";
				if ($ericepassword) { print "<br /><span class=\"redtext\">$ericepassword</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>&nbsp;</td>\n";
				print "	<td><a href=\"bookings-vlans-addedit.php?vlan=$vlan&amp;custref=$custref\">Regenerate Password</a></td>\n";
				print "</tr>\n";

				print "</table>\n";

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
