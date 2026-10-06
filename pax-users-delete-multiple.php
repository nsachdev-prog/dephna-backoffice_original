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
            $delete = $_REQUEST['delete'];
            $expire = $_REQUEST['expire'];
            if ($siteid) {

                $twoWeeksAgo = date("Y-m-d",strtotime("-14 Days"));
                $today = date("Y-m-d");
                $newexpires = date("Y-m-d",strtotime("-1 Day"));

                // get filtering if any
                $search = array();
                if ($_REQUEST['us']) { $us = $_REQUEST['us']; $search[] = "firstName LIKE '%$us%'"; }
                if ($_REQUEST['de']) { $de = $_REQUEST['de']; $search[] = "departmentName LIKE '%$de%'"; }
                $search[] = "departmentName != '(none)'";

                if ($search) {
                    $search = "AND " . implode(" AND ", $search);
                }

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
                    $srow = $sresult->fetch_assoc();
                    $sitename = $srow['title'];
                    print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
                    include('pax-submenu.php');
                    print "<p align=\"center\">$menustr</p>\n";
                    print "<p align=\"center\" class=\"larger\">Users Expire or Delete</p>\n";

                    include_once('PaxGetAccessToken.php');

                    if ($access_token) {

                        $headers = array(
                            'Authorization: bearer '.$access_token,
                            'Accept: application/json'
                        );

                        $postdata = http_build_query(
                            array(
                                'query' => "SELECT *
                                        FROM UsersEx
                                        WHERE firstName > '' $search
                                        ORDER BY firstName"
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

                                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

                                // row background colours
                                print "<style>\n";
                                print ".r0 { background-color:#fff }\n";
                                print ".r1 { background-color:#ddd }\n";
                                print "</style>\n";

                                foreach ($result AS $id => $user) {

                                    $userid = $user['userID'];
                                    $paxcnm = $user['field1_100'];
                                    $custref = $user['field4_50'];
                                    if (strlen($paxcnm) > 15) { $paxcnm = substr($paxcnm, 0, 15); }

                                    $usernm = $user['firstName'];
                                    if ($user['surname']) {
                                        $usernm .= " {$user['surname']}";
                                    }
                                    if (strlen($usernm) > 20) { $usernm = substr($usernm, 0, 20); }
                                    $department = $user['departmentName'];
                                    if (strlen($department) > 15) { $department = substr($department, 0, 15); }
                                    $deptid     = $user['departmentID'];

                                    $LastAccessTime    = $user['lastAccessTime'];
                                    $LastKnownLocation = $user['lastKnownLocation'];
                                    list($lkdate,$lktime) = explode("T",$LastAccessTime);

                                    list($expired,$etime) = explode("T",$user['expiryDate']);
                                        if (($expired) AND ($expired < $today)) {
                                            $expired = 1;
                                        } else {
                                            $expired = 0;
                                        }

                                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";

                                    if ($department != "Staff"
                                        AND $department != "Platinum Cleani"
                                        AND $department != "Platinium Clean"
                                        AND $department != "Veolia"
                                        AND $department != "Bio Net Pest Co"
                                        AND $department != "Village Heating"
                                        AND $department != "Valtur/stabilit"
                                        AND $department != "Neighbours"
                                    ) {
                                        // check orders for custref
                                        $sql = "SELECT ID FROM bookings WHERE custid = '$custref'";
                                        if (($bresult = $dbcnx->query($sql)) AND ($bresult->num_rows)) {
                                            // keep
                                        } else {
                                            if ($lkdate > $twoWeeksAgo) {
                                                // keep
                                            } else {

                                                if ($delete) {

                                                    $postdata = http_build_query(
                                                        array(
                                                            'purge' => '1'
                                                        )
                                                    );
                        
                                                    $headers = array(
                                                        'Authorization: bearer '.$access_token,
                                                        'Accept: application/json'
                                                    );
                        
                                                    $ch = curl_init(); 
                                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                                    curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
                                                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                                    $result = curl_exec($ch);
                                                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                                    curl_close($ch);

                                                    print " <td>$usernm</td>\n";
                                                    print " <td>$paxcnm</td>\n";
                                                    print " <td>$department</td>\n";
                                                    if ($httpcode == 204) {
                                                        print " <td>Deleted</td>\n";
                                                    } elseif ($httpcode == 404) {
                                                        print " <td>The specified user could not be found</td>\n";
                                                    } elseif ($httpcode == 502) {
                                                        print " <td>Remote service is not available</td>\n";
                                                    }

                                                } elseif ($expire) {

                                                    if (!$expired) {

                                                        $headers = array(
                                                            'Authorization: bearer '.$access_token,
                                                            'Accept: application/json'
                                                        );
                                                        $postdata = http_build_query(
                                                            array(
                                                                'id'         => $userid,
                                                                'expiryDate' => $newexpires
                                                            )
                                                        );
                                                        $ch = curl_init();
                                                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                                        curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
                                                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                                        $u2result = curl_exec($ch);
                                                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                                        curl_close($ch);

                                                        print " <td>$usernm</td>\n";
                                                        print " <td>$paxcnm</td>\n";
                                                        print " <td>$department</td>\n";
                                                        if ($u2result !== FALSE) {
                                                            if ($httpcode == 200) {
                                                                print "<td>Expired</td>\n";
                                                            } else {
                                                                print "<td>Error Code $httpcode</td>";
                                                            }
                                                        } else {
                                                            print "<td>Error Code $httpcode</td>";
                                                        }

                                                    } else {
                                                        print " <td>$usernm</td>\n";
                                                        print " <td>$paxcnm</td>\n";
                                                        print " <td>$department</td>\n";
                                                        print " <td>Already Expired</td>\n";
                                                    }

                                                } else {
                                                    print " <td>$usernm</td>\n";
                                                    print " <td>$paxcnm</td>\n";
                                                    print " <td>$department</td>\n";
                                                    print " <td>Will Be Expired or Deleted</td>\n";
                                                }
                                            }
                                        }
                                    }
                                    
                                    print "</tr>\n";
                                }

                                print "<tr>\n";
                                print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                print "</tr>\n";

                                print "</table>\n";

                                if (!$delete AND !$expire) {
                                    print "<p align=\"center\">Expire or Delete All These Users</p>\n";
                                    print "<p align=\"center\" class=\"redtext\">ARE YOU SURE?</p>\n";
                                    print "<p align=\"center\">";
                                    print "<a href=\"pax-users-delete-multiple.php?siteid=$siteid&amp;us=$us&amp;de=$de&amp;expire=1\">Yes EXPIRE</a> | ";
                                    print "<a href=\"pax-users-delete-multiple.php?siteid=$siteid&amp;us=$us&amp;de=$de&amp;delete=1\">Yes DELETE</a> | ";
                                    print "<a href=\"pax-users-list.php?siteid=$siteid\">No DON'T Expire or Delete</a>";
                                    print "</p>\n";
                                }
                                print "<p align=\"center\"><a href=\"pax-users-list.php?siteid=$siteid\">Return</a></p>";

                            } else {
                                $resultMessage .= "Nothing Found.<br />";
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
