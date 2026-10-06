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
            $delete  = $_REQUEST['delete'];

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Internet</p>\n";
			print "<p align=\"center\">$menustr</p>\n";

			print "<p align=\"center\" class=\"larger\">Delete Customer From ICE</p>\n";
			
			if ($vlan AND $custref) {

                // get customer
                $sql = "SELECT compname FROM users WHERE custref = '$custref'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $compname = $row['compname'];

                    if ($delete) {

                        $sql = "SELECT * FROM IceAuth WHERE ID = '1'";
                        if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                            $row = $result->fetch_assoc();
                            $api_access_point = $row['api_access_point'];
                            $access_token     = $row['access_token'];

                            $postdata = http_build_query(
                                array(
                                    'apikey' => $access_token,
                                    'action' => 'deletetenant',
                                    'vlan'   => $vlan
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
                                $resultMessage = "$compname Deleted From ICE<br />";
                                $now = time();
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$compname','Internet','$now')");
                            } elseif ($httpcode == 400) {
                                $resultMessage .= $result;
                            } elseif ($httpcode == 401) {
                                $resultMessage = "Unauthorised<br />";
                            } else {
                                $resultMessage = "Unknown error code $httpcode<br />";
                            }

                        } else {
                            $resultMessage .= "Could not get access token<br />";
                        }

                    }

                    else {

                        print "<p align=\"center\" class=\"larger\">$compname</p>\n";
                        print "<p align=\"center\" class=\"redtext\">Are You Sure?</p>\n";
                        print "<p align=\"center\">";
                        print "<a href=\"bookings-vlans-delete.php?vlan=$vlan&amp;custref=$custref&amp;delete=1\">Yes Delete</a> | ";
                        print "<a href=\"bookings-vlans-list.php\">No DON'T Delete</a>";
                        print "</p>\n";

                    }

                } else {
                    $resultMessage .= "Could not get customer details<br />";
                }

			} else {
				$resultMessage .= "Page called without Vlan ID or Custref<br />";
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
