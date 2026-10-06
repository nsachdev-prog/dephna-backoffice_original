<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Groups";
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
                    print "<p align=\"center\" class=\"larger\">Users</p>\n";

                    include_once('PaxGetAccessToken.php');

                    if ($access_token) {

                        $headers = array(
                            'Authorization: bearer '.$access_token,
                            'Accept: application/json'
                        );

                        /* $postdata = http_build_query(
                            array(
                                'rowCount' => '50'
                            )
                        ); */

                        $ch = curl_init();
                        // curl_setopt($ch, CURLOPT_POST, 1);
                        // curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/areagroups");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                        $result = curl_exec($ch);
                        curl_close($ch);
                
                        if ($result !== FALSE) {

                            if ($result = json_decode($result, TRUE)) {

                                /* if ($result) {

                                    print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                    print "<tr>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Activated</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Expires</b></td>\n";
                                    print "</tr>\n";

                                    // row background colours
                                    print "<style>\n";
                                    print ".r0 { background-color:#fff }\n";
                                    print ".r1 { background-color:#ddd }\n";
                                    print "</style>\n";

                                    foreach ($result AS $id => $user) {
        
                                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                        print " <td>{$user['firstName']} {$user['lastName']}</td>\n";
                                        print " <td>{$user['activateDate']}</td>\n";
                                        print " <td>{$user['expiryDate']}</td>\n";
                                        print "</tr>\n";

                                    }

                                    print "<tr>\n";
                                    print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                    print "</tr>\n";

                                    print "</table>\n";

                                } else {
                                    $resultMessage .= "Nothing was returned from Paxton.<br />";
                                } */

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
        print "<p align=\"center\"$resultMessage</p>";
    }
    if ($AuthResultMessage) {
        print "<p align=\"center\"$AuthResultMessage</p>";
    }
    print "<p align=\"center\"><a href=\"pax-sites-list.php\">Return</a></p>\n";
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
