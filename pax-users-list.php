<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
// set script execution time limit to 20 seconds
set_time_limit(20);
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
            if ($siteid) {

                // get filtering if any
                if ($_REQUEST['us']) { $us = $_REQUEST['us']; $search[] = "(firstName LIKE '%$us%' OR surname LIKE '%$us%')"; }
                if ($_REQUEST['de']) { $de = $_REQUEST['de']; $search[] = "departmentName LIKE '%$de%'"; }
                if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
                if ($az) {
                    if ($az == "n") {
                        $search[] = "UPPER(LEFT(firstName,1)) BETWEEN '1' AND '9'";
                    } else {
                        $search[] = "UPPER(LEFT(firstName,1))='$az'";
                    }
                }
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
                    print "<p align=\"center\" class=\"larger\">Users</p>\n";

                    print "<p align=\"center\">";
                    print "<a href=\"pax-users-addedit.php?siteid=$siteid\">Add New User | </a>";
                    print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
                    print "</p>\n";

                    print "<form name=\"addform\" method=\"post\" action=\"pax-users-list.php?siteid=$siteid&amp;az=$az\">\n";
                    print "<table class=\"filterBlock\" align=\"center\">\n";
                    print "	<tr>\n";

                    print "		<td align=\"right\"><b>User</b></td>\n";
                    print "		<td><input type=\"text\" name=\"us\" value=\"$us\" class=\"element_small\" /></td>\n";

                    print "		<td align=\"right\"><b>Depart</b></td>\n";
                    print "		<td><input type=\"text\" name=\"de\" value=\"$de\" class=\"element_small\" /></td>\n";
                    print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

                    print "		<td><a href=\"pax-users-list.php?siteid=$siteid\"><b>All</b></a></td>\n";

                    print "	</tr>\n";
                    print "</table>\n";
                    print "</form>\n";

                    // print alpha index
                    $alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
                    print "<p align=\"center\"><b>\n";
                    if ($az == "n") {
                        print "<span class=\"larger\">#</span>&nbsp;\n";
                    } else {
                        print "<a href=\"pax-users-list.php?siteid=$siteid&amp;az=n\">#</a>&nbsp;\n";
                    }
                    for ($z = 0; $z < count($alpha); $z++) {
                        if ($alpha[$z] == $az) {
                            print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
                        } else {
                            print "<a href=\"pax-users-list.php?siteid=$siteid&amp;az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
                        }
                    }
                    if ($az) {
                        print "<a href=\"pax-users-list.php?siteid=$siteid\">All</a>&nbsp;\n";
                    } else {
                        print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
                    }
                    print "</b></p>\n";

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
                        // set timeout to 10 seconds
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        // set connect timeout to 5 seconds
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                        $result = curl_exec($ch);
                
                        if ($result !== FALSE) {

                            if ($result = json_decode($result, TRUE)) {

                                if ($result) {

                                    $twoWeeksAgo = date("Y-m-d",strtotime("-14 Days")); 

                                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                    print "<tr>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Department</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Seen</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Exp</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                                    print "</tr>\n";

                                    // row background colours
                                    print "<style>\n";
                                    print ".r0 { background-color:#fff }\n";
                                    print ".r1 { background-color:#ddd }\n";
                                    print "</style>\n";

                                    $today = date("Y-m-d");

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
                                            $expired = "Yes";
                                        } else {
                                            $expired = "&nbsp;";
                                        }

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
                                                $inBookings = "&nbsp;";
                                            } else {
                                                if ($lkdate > $twoWeeksAgo) {
                                                    $inBookings = "?";
                                                } else {
                                                    $inBookings = "X";
                                                }
                                            }
                                        } else {
                                            $inBookings = "&nbsp;";
                                        }

                                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                        print " <td><a href=\"pax-users-view.php?siteid=$siteid&amp;userid=$userid&amp;deptid=$deptid&amp;depart=$depart\">$usernm</a></td>\n";
                                        print " <td>$paxcnm</td>\n";
                                        print " <td>$inBookings</td>\n";
                                        print " <td>$department</td>\n";
                                        print " <td>$lkdate $lktime</td>\n";
                                        print " <td>$LastKnownLocation</td>\n";
                                        print "<td>$expired</td>";
                                        if ($accessRights[$page] > 1) {
                                            print "<td><a href=\"pax-users-addedit.php?siteid=$siteid&amp;userid=$userid\">Update</a></td>";
                                        } else {
                                            print " <td class=\"noaccess\">Update</td>\n"; 
                                        }
                                        if ($accessRights[$page] > 2) {
                                            print "<td><a href=\"pax-users-delete.php?siteid=$siteid&amp;userid=$userid\">Delete</a></td>";
                                        } else {
                                            print " <td class=\"noaccess\">Delete</td>\n"; 
                                        }
                                        print "</tr>\n";

                                    }

                                    print "<tr>\n";
                                    print "<td colspan=\"9\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                    print "</tr>\n";

                                    print "</table>\n";

                                    print "<p align=\"center\">X = Marked (No Bookings, Not Staff, Not Exception)<br />? = No Bookings, Not Staff, Not Exception but Used Recently</p>";

                                    print "<p align=\"center\"><a href=\"pax-users-delete-multiple.php?siteid=$siteid&amp;us=$us&amp;de=$de\">Expire or Delete All Xs</a></p>";

                                } else {
                                    $resultMessage .= "Nothing was returned from Paxton.<br />";
                                }

                            } else {
                                $resultMessage .= "Nothing Found.<br />";
                            }
                
                        } else {
                            $resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
                        }
                        
                    } else {
                        $resultMessage .= "<b>Could Not Get Access Token</b><br />";
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
