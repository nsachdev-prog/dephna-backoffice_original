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
	
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Internet</p>\n";
			print "<p align=\"center\">$menustr</p>\n";
			
			print "<p align=\"center\" class=\"larger\">New Vlan</p>\n";

			if ($_POST['posted']) {

				$customer    = strip_tags($_POST['customer']);
				$site        = strip_tags($_POST['site']);

                if ($customer) {
					list($custref,$compname) = explode("--",$customer);
				} else {
                    $ercustomer = "Please select a customer"; $e++;
                }

                if ($site) {
					list($siteid,$sitename) = explode("--",$site);
				} else {
                    $ersite = "Please select a site"; $e++;
                }

				if (!$e) {

                    print "<p align=\"center\" class=\"larger\">$compname<br />$sitename</p>\n";

                    // do they already have a vlan at this site
                    $sql = "SELECT * FROM IceAuth WHERE ID = '1'";
                    if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
                        $irow = $iresult->fetch_assoc();
                        $iceapi_access_point = $irow['api_access_point'];
                        $iceaccess_token     = $irow['access_token'];

                        $postdata = http_build_query(
                            array(
                                'apikey' => $iceaccess_token,
                                'action' => 'listall'
                            )
                        );

                        $ch = curl_init(); 
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
                        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                        $result = curl_exec($ch);
                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        $tempMessage = "ICE Returned: " . $httpcode . "<br />" . $result . "<br />";

                        if ($httpcode == 200) {
                            if ($result = json_decode($result, TRUE)) {
                                foreach ($result AS $id => $item) {
                                    if ($item['Data']['ID'] == $custref) {
                                        $vlansite = $item['Data']['Site'];
                                    }
                                }
                            } else {
                                $resultMessage .= "$tempMessage Json Decode FAILED getting vlan<br />";
                            }
                        } elseif ($httpcode == 400) {
                            $resultMessage .= "Error 400 Getting VLAN<br />";
                        } elseif ($httpcode == 401) {
                            $resultMessage .= "Unauthorised Getting VLAN<br />";
                        } else {
                            $resultMessage .= "Unknown error code $httpcode Getting VLAN<br />";
                        }

                        if (($vlansite) AND ($vlansite == $icesites[$siteid])) {

                            $resultMessage .= "Customer already has VLAN at this site<br />";

                        } else {

                            // get available vlans at this site
                            $postdata = http_build_query(
                                array(
                                    'apikey' => $iceaccess_token,
                                    'action' => 'listunused'
                                )
                            );
        
                            $ch = curl_init(); 
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
                            // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                            $result = curl_exec($ch);
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            $tempMessage = "ICE Returned: " . $httpcode . "<br />" . $result . "<br />";
            
                            if ($httpcode == 200) {
                                if ($result = json_decode($result, TRUE)) {
                                    // reorg
                                    $columns = array_column($result, 'VLAN');
                                    array_multisort($columns, SORT_ASC, $result);
                                    foreach ($result AS $key => $item) {
                                        if ($item['Site'] == $icesites[$siteid]) {
                                            $vlan = $item['VLAN'];
                                            break;
                                        }
                                    }
                                } else {
                                    $resultMessage .= "$tempMessage Json Decode Failed getting available VLANs<br />";
                                }
                            } elseif ($httpcode == 400) {
                                $resultMessage .= "Error getting available VLAN<br />";
                            } elseif ($httpcode == 401) {
                                $resultMessage .= "Error getting available VLAN - Unauthorised<br />";
                            } else {
                                $resultMessage .= "Unknown error getting available VLAN code $httpcode<br />";
                            }

                            if ($vlan) {

                                // generate ice password
                                $icepassword = randomPassword();
                                // strip non alpha numeric chars from compname
                                $compname = preg_replace('/[^A-Za-z0-9]/', '', $compname);
                                $compname = strtolower($compname);
                                // reduce compname to 18 chars
                                if (strlen($compname) > 18) { $compname = substr($compname, 0, 18); }

                                $postdata = http_build_query(
                                    array(
                                        'apikey'   => $iceaccess_token,
                                        'action'   => 'newtenant',
                                        'tenant'   => $compname,
                                        'id'       => $custref,
                                        'site'     => $icesites[$siteid],
                                        'vlan'     => $vlan,
                                        'password' => $icepassword
                                    )
                                );

                                $ch = curl_init(); 
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
                                // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                $result = curl_exec($ch);
                                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);

                                if ($httpcode == 200) {
                                    $tempMessage = "ICE Returned: " . $result . "<br />";
                                    $result = strip_tags($result);
                                    if ($result = json_decode($result, TRUE)) {
                                        $iceusername = $result['Username'];
                                        $icepassword = $result['Password'];
                                        $resultMessage .= "Internet VLAN Added<br />Username: $iceusername<br />Password: $icepassword<br />";

                                        if ($_POST['mailit']) {
											// start email
                                            $sql = "SELECT username, email, aemails FROM users WHERE custref = '$custref'";
                                            if (($custresult = $dbcnx->query($sql)) && ($custresult->num_rows)) {
                                                $custrow = $custresult->fetch_assoc();
                                                $aemails  = $row['aemails'];
                                                $email    = $row['email'];
                                                $custname = $row['username'];
                                                if (!$aemails) { $aemails = $email; }
                                            
                                                $from = "Dephna Accounts <accounts@dephna.com>";
                                                $mime_boundary = md5(uniqid(time()));
                                                $headers = "From: $from\r\n";
                                                $headers .= "Reply-To: $from\r\n";
                                                $headers .= "Bcc: $from\r\n";
                                                $headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
                                                $headers .= "MIME-Version: 1.0\r\n";
                                                $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
                                                $message  = "--".$mime_boundary."\r\n";
                                                $message .= "Content-type:text/html; charset=iso-8859-1\r\n";
                                                $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

                                                include("email-header.php");
                                                $message .= "<p>Dear $custname,</p>";
                                                $message .= "<p>Please find below your username and password for internet access<br />";
                                                $message .= "Username: $iceusername<br />";
                                                $message .= "Password: $icepassword</p>\n";
                                                $message .= "<p>Kind Regards<br />Dephna Accounts</p>";
                                                include("email-footer.php");

                                                // attach wifi guide
                                                if (is_file("pdf/WiFi-Setup-Guide.pdf")) {
                                                    $fileatt = file_get_contents("pdf/WiFi-Setup-Guide.pdf");
                                                    $data    = chunk_split(base64_encode($fileatt));
                                                    $fileatttype = "application/pdf";
                                                    $message .= "--".$mime_boundary."\r\n";
                                                    $message .= "Content-Type: {$fileatttype}; name=\"WiFi-Setup-Guide.pdf\"\r\n";
                                                    $message .= "Content-Transfer-Encoding: base64\r\n";
                                                    $message .= "Content-Disposition: attachment; filename=\"WiFi-Setup-Guide.pdf\"\r\n\r\n";
                                                    $message .= $data;
                                                    $message .= "\r\n\r\n";
                                                    $resultMessage .= "WiFi-Setup-Guide.pdf attached<br />";
                                                } else {
                                                    $resultMessage .= "WiFi-Setup-Guide.pdf not found<br />"; $e++;
                                                }
                                                
                                                $message .= "--".$mime_boundary."--";

                                                // send email
                                                $emails = explode(",",$aemails);
                                                foreach ($emails AS $to) {
                                                    if (mail($to, $subject, $message, $headers)) {
                                                        $resultMessage .= "Email Successfully Sent to $to<br />";
                                                    } else {
                                                        $resultMessage .= "Email FAILED to send to $to<br />";
                                                    }
                                                }
                                            } else {
                                                $resultMessage .= "Could NOT get customer email address<br />";
                                            }
										} else {
											$resultMessage .= "Email customer not selected<br />";
										}

                                    } else {
                                        $resultMessage .= "Json Decode Failed getting returned username for VLAN<br />$tempMessage<br />VLAN: $vlan<br />U Sent: $compname<br />P Sent: $icepassword<br />S Sent: {$icesites[$siteid]}";
                                    }
                                } elseif ($httpcode == 400) {
                                        $resultMessage .= "Internet VLAN FAILED code 400<br />";
                                } elseif ($httpcode == 401) {
                                    $resultMessage .= "Internet VLAN FAILED Unauthorised<br />";
                                } else {
                                    $resultMessage .= "Internet VLAN FAILED code $httpcode<br />";
                                }
                            } else {
                                $resultMessage .= "$tempMessage Could NOT Get New VLAN<br />";
                            }
                        }
                    }
                }
            }

			if (!$resultMessage OR $e) {

				print "<form name=\"addform\" method=\"post\" action=\"bookings-vlans-create.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                $sql = "SELECT custref, compname, email, aemails FROM users ORDER BY compname";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Client</b>:</td>\n";
                    print "	<td><select name=\"customer\" class=\"element\">\n";
                    while ($row = $result->fetch_assoc()) {
                        $thisref  = $row['custref'];
                        $thiscompname = $row['compname'];
                        print "	<option value=\"$thisref--$thiscompname\""; if ($thisref == $custref) { print " SELECTED"; } print ">$thiscompname</option>\n";
                    }
                    print "	</select>";
                    if ($ercustomer) { print "<br /><span class=\"redtext\">$ercustomer</span>"; }
                    print "</td>\n";
                    print "</tr>\n";
                }

                $sql = "SELECT ID, title FROM sites ORDER BY title";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Site</b></td>\n";
                    print "	<td><select name=\"site\" class=\"element\">\n";
                    while ($row = $result->fetch_assoc()) {
                        $thissiteid   = $row['ID'];
                        $thissitename = $row['title'];
                        print "		<option value=\"$thissiteid--$thissitename\""; if ($thissiteid == $siteid) { print " SELECTED"; } print ">$thissitename</option>\n";
                    }
                    print "		</select>";
                    if ($ersite) { print "<br /><span class=\"redtext\">$ersite</span>"; }
                    print "</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "	<td align=\"right\"><b>Email Client</b></td>\n";
                print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
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
