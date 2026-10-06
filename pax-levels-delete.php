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

            $siteid  = $_REQUEST['siteid'];
            $levelid = $_REQUEST['levelid'];

            if (($siteid) AND ($levelid)) {

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
                    $srow = $sresult->fetch_assoc();
                    $sitename = $srow['title'];
                    print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
                    include('pax-submenu.php');
		            print "<p align=\"center\">$menustr</p>\n";
                    print "<p align=\"center\" class=\"larger\">Levels</p>\n";

                    include_once('PaxGetAccessToken.php');

                    if ($access_token) {

                        if ($_REQUEST['delete']) {

                            $headers = array(
                                'Authorization: bearer '.$access_token,
                                'Accept: application/json'
                            );

                            $ch = curl_init(); 
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/".$levelid);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                            $result = curl_exec($ch);
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                
                            if ($httpcode == 204) {
                                $name = $_REQUEST['name'];
                                $resultMessage = "Access Level $name Deleted<br />";
                                $now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Dept $name','$page','$now')");
                            } elseif ($httpcode == 400) {
                                $resultMessage = "Access Level is in use<br />";
                            } elseif ($httpcode == 404) {
                                $resultMessage = "The specified access level could not be found<br />";
                            } elseif ($httpcode == 502) {
                                $resultMessage = "Remote service is not available<br />";
                            }

                        } else {

                            $headers = array(
                                'Authorization: bearer '.$access_token,
                                'Accept: application/json'
                            );

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/accesslevels/".$levelid);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                            $result = curl_exec($ch);
                            curl_close($ch);
                    
                            if ($result !== FALSE) {

                                if ($result = json_decode($result, TRUE)) {

                                    if ($result) {

                                        $name=$result['name'];

                                        print "<p align=\"center\" class=\"larger\">$name</p>\n";
                                        print "<p align=\"center\" class=\"larger\">Delete</p>\n";
                                        print "<p align=\"center\" class=\"redtext\"><b>ARE YOU SURE?</b></p>\n";

                                        print "	<span class=\"notclicked\">\n";
                                        print "	<p align=\"center\"><a href=\"pax-levels-delete.php?siteid=$siteid&amp;levelid=$levelid&amp;name=$name&amp;delete=1\">Yes Delete</a></p>\n";
                                        print "	</span>\n";

                                        print "	<span class=\"uploading\">\n";
                                        print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
                                        print "	</span>\n";

                                        print "</form>\n";

                                        print "<script>\n";
                                        print "  $('.uploading').hide();\n";
                                        print "  	$('form').submit(function () {\n";
                                        print " 	$('.uploading').show();\n";
                                        print "  	$('.notclicked').hide();\n";
                                        print "  });\n";
                                        print "</script>\n";

                                    } else {
                                        $resultMessage .= "Nothing was returned from Paxton.<br />";
                                    }

                                } else {
                                    $resultMessage .= "Json Failed.<br />";
                                }
                    
                            } else {
                                $resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
                            }
                        }
                    } else {
                        $resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
                    }

                } else {
                    $resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
                }

            } else {
                $resultMessage .= "Page called without siteid/levelid.<br />";
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
    print "<a href=\"pax-levels-list.php?siteid=$siteid\">Return To Levels | </a>";
    print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
    print "</p>\n";
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
