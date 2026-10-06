<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Fob Search";
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

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Fob Search</p>\n";

                    print "<form name=\"addform\" method=\"post\" action=\"pax-fobsearch.php?siteid=$siteid\">";
                    print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2px\">\n";
                    print "<tr>\n";
                    print "<td style=\"font-size:1.2em\"><b>Find Fob Number</b></td>\n";
                    print "<td><input type=\"text\" name=\"find\" value=\"$find\" class=\"element_small\" /></td>\n";
                    print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                    print "</tr>\n";
                    print "</table>";
                    print "</form>\n";

                    $find = $_REQUEST['find'];
                    if ($find) {

                        if ($find) {
							$search = "e.cardNumber LIKE '%$find%'";
						}

					    include_once('PaxGetAccessToken.php');

                        if ($access_token) {

                            $headers = array(
                                'Authorization: bearer '.$access_token,
                                'Accept: application/json'
                            );

                            $postdata = http_build_query(
                                array(
                                    'query' => "SELECT TOP(30) e.*, u.departmentName
                                            FROM EventsEx AS e
                                            LEFT JOIN UsersEx AS u ON e.userID = u.userID
                                            WHERE $search
                                            ORDER BY e.eventTime DESC"
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

                                        print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                        print "<tr>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Department</b></td>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Token</b></td>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Description</b></td>\n";
                                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
                                        print "</tr>\n";

                                        // row background colours
                                        print "<style>\n";
                                        print ".r0 { background-color:#fff }\n";
                                        print ".r1 { background-color:#ddd }\n";
                                        print "</style>\n";

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

                                            if (strpos($peripheralName, "Main Entrance") !== FALSE) {
                                                if ($description == "Access permitted") {
                                                    $ins++;
                                                }
                                                elseif ($subDesc == "With exit button") {
                                                    $outs++;
                                                }
                                            }

                                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                            print " <td valign=\"top\">$date $time</td>\n";
                                            print " <td valign=\"top\">$name</td>\n";
                                            print " <td valign=\"top\">$department</td>\n";
                                            print " <td valign=\"top\">$peripheralName</td>\n";
                                            print " <td valign=\"top\">$cardNumber</td>\n";
                                            print " <td valign=\"top\">$description</td>\n";
                                            print " <td valign=\"top\">$subDesc</td>\n";
                                            print "</tr>\n";

                                        }

                                        print "<tr>\n";
                                        print "<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                        print "</tr>\n";

                                        print "</table>\n";

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
	print "<p align=\"center\"><a href=\"pax-sites-list.php\">Return To Sites</a></p>\n";
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
