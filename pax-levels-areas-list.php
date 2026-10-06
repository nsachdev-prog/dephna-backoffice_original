<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Levels";
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
            $areaid = $_REQUEST['areaid'];

            if ($siteid AND $areaid) {

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
                    $srow = $sresult->fetch_assoc();
                    $sitename = $srow['title'];
                    print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
                    include('pax-submenu.php');
		            print "<p align=\"center\">$menustr</p>\n";
                    print "<p align=\"center\" class=\"larger\">Levels</p>\n";
                    print "<p align=\"center\"><a href=\"pax-sites-list.php\">Return To Sites</a></p>\n";

                    include_once('PaxGetAccessToken.php');

                    if ($access_token) {

                        $headers = array(
                            'Authorization: bearer '.$access_token,
                            'Accept: application/json'
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/$areaid/detail");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        $result = curl_exec($ch);
                        curl_close($ch);
                
                        if ($result !== FALSE) {

                            if ($result = json_decode($result, TRUE)) {

                                if ($result) {

                                    print "<p align=\"center\" class=\"larger\">{$result['name']}</p>\n";

                                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                    print "<tr>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\">ID</td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
                                    print "</tr>\n";

                                    // row background colours
                                    print "<style>\n";
                                    print ".r0 { background-color:#fff }\n";
                                    print ".r1 { background-color:#ddd }\n";
                                    print "</style>\n";

                                    foreach ($result['detailRows'] AS $id => $row) {

                                        $aareaid = $row['areaID'];

                                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                        print " <td>$aareaid</td>\n";

                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/doors/$aareaid");
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                        $doors = curl_exec($ch);
                                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                        curl_close($ch);

                                        if ($doors !== FALSE) {

                                            if ($httpcode == 200) {

                                                if ($doors = json_decode($doors, TRUE)) {
                
                                                    if ($doors) { 
                                                        print " <td>{$doors['Name']}</td>\n";
                                                    }
                                                } else {
                                                    print " <td>Nothing Returned</td>\n";
                                                } 
                                                
                                            } elseif ($httpcode == 404) {
                                                print " <td>Door Not Found</td>\n";
                                            } else {
                                                print " <td>Unknown Return Code $httpcode</td>";
                                            }
                                            
                                        }
                                        print "</tr>\n";
                                    }

                                    print "<tr>\n";
                                    print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
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

                } else {
                    $resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
                }

            } else {
                $resultMessage .= "Page called without siteid/areaid.<br />";
            }

            $dbcnx->close();
        } else {
            $resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
        }
		
	} else {
		$resultMessage .= "Access Denied<br />";
    }
    
    if ($resultMessage) {
        print "<p align=\"center\"$resultMessage</p>";
    }
    if ($AuthResultMessage) {
        print "<p align=\"center\"$AuthResultMessage</p>";
    }
    print "<p align=\"center\"><a href=\"pax-sites-list.php\">Return To Sites</a></p>\n";
} else {
	include ('loggedout.php');
}

print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($result);
print "</pre>";

print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($doors);
print "</pre>";

print "</td>\n";
include('footer.php');
?>
