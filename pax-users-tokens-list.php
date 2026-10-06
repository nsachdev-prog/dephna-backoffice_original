<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Users";
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
            $userid = $_REQUEST['userid'];
            $deptid = $_REQUEST['deptid'];
            $depart = $_REQUEST['depart'];

            if (($siteid) AND ($userid)) {

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
                    $srow = $sresult->fetch_assoc();
                    $sitename = $srow['title'];
                    print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
                    include('pax-submenu.php');
		            print "<p align=\"center\">$menustr</p>\n";
                    print "<p align=\"center\" class=\"larger\">User - Tokens</p>\n";

                    include_once('PaxGetAccessToken.php');

                    if ($access_token) {

                        // get user details
                        $headers = array(
                            'Authorization: bearer '.$access_token,
                            'Accept: application/json'
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        $result = curl_exec($ch);
                        curl_close($ch);
                
                        if ($result !== FALSE) {

                            if ($result = json_decode($result, TRUE)) {

                                if ($result) {

                                    $firstName=$result['firstName'];
                                    $middleName=$result['middleName']; 
                                    $lastName=$result['lastName'];

                                    $name = $firstName;
                                    if ($middleName) { $name .= " $middleName"; }
                                    if ($lastName) { $name .= " $lastName"; }

                                    print "<p align=\"center\" class=\"larger\">$name</p>\n";

                                    // get user tokens
                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/tokens");
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                    $tokens = curl_exec($ch);
                                    curl_close($ch);
                                    if ($tokens !== FALSE) {
                                        if ($tokens = json_decode($tokens, TRUE)) {
                                            if ($tokens) {
                                                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                                print "<tr>\n";
                                                print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                                                print "	<td style=\"border-bottom:1px solid #999999\"><b>Value</b></td>\n";
                                                print "	<td style=\"border-bottom:1px solid #999999\"><b>Is Lost</b></td>\n";
                                                print "</tr>\n";

                                                // row background colours
                                                print "<style>\n";
                                                print ".r0 { background-color:#fff }\n";
                                                print ".r1 { background-color:#ddd }\n";
                                                print "</style>\n";

                                                foreach($tokens AS $tid => $token) {
                                                   $tokenid = $token['id'];
                          
                                                   $tokenType = $token['tokenType'];
                                                   $tokenValue = $token['tokenValue'];
                                                   $isLost = $token['isLost'];
                                                   if ($isLost) {
                                                       $isLost = "Yes";
                                                   } else {
                                                       $isLost = "";
                                                   }

                                                   print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                                   print " <td>$tokenType</td>\n";
                                                   print " <td>$tokenValue</td>\n";
                                                   print " <td>$isLost</td>\n";
                                                   print "</tr>\n";

                                                }

                                                print "<tr>\n";
                                                print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                                print "</tr>\n";

                                                print "</table>\n";

                                            } else {
                                                $resultMessage .= "Token details not returned from Paxton.<br />";
                                            }
                                        } else {
                                            $resultMessage .= "Token details Json Failed.<br />";
                                        }
                                    } else {
                                        $resultMessage .= "Token details error: <br />Nothing was returned from Paxton.<br />";
                                    }

                                } else {
                                    $resultMessage .= "User details not returned from Paxton.<br />";
                                }

                            } else {
                                $resultMessage .= "User details Json Failed.<br />";
                            }
                
                        } else {
                            $resultMessage .= "User details error: <br />Nothing was returned from Paxton.<br />";
                        }
                        
                    } else {
                        $resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
                    }

                } else {
                    $resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
                }

            } else {
                $resultMessage .= "Page called without siteid/userid.<br />";
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
    print "<p align=\"center\">";
    print "<a href=\"pax-users-list.php?siteid=$siteid&amp;deptid=$deptid&amp;depart=$depart\">Return To Users | </a>";
    print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
    print "</p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($tokens);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
