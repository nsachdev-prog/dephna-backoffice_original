<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
    print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
    	
	if ($accessRights[$page]) {

        // open database
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if(!$dbcnx->connect_error) {

            $siteid = $_REQUEST['siteid'];
            
            if ($siteid) {

                include_once('xweb-settings.php');

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['title'];
                    print "<p align=\"center\" class=\"larger\">$sitename</p>\n";

                    include_once('XWebGetAccessToken.php');

                    if ($access_token) {

                        $postdata = http_build_query(
                            array(
                                'auth'     => $access_token,
                                'action'   => 'runtime'
                            )
                        );
                
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
                        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                        $result = curl_exec($ch);
                        curl_close($ch);
                
                        if ($result !== FALSE) {
                            $result = json_decode($result, TRUE);
                            if ($result['status'] == "ok") {

                                foreach($result['runtime']['devices'] AS $device) {
                                    $deviceid = $device['id'];
                                    $commands = $device['commands'];
                                    $unit = str_replace($xwebsites[$siteid]['prefix'],"",$device['name']);
                                    $sql = "SELECT b.custid AS custref, c.compname, u.setpoint, b.unitid
                                        FROM bookings AS b
                                        LEFT JOIN users AS c ON c.custref = b.custid
                                        LEFT JOIN units AS u ON u.ID = b.unitid
                                        WHERE b.siteid = '$siteid' AND u.title LIKE '%$unit%'
                                    ";
                                    if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
                                        $urow = $uresult->fetch_assoc();
                                        $compname = $urow['compname'];
                                        $custref  = $urow['custref'];
                                        $unitid   = $urow['unitid'];
                                        $setpoint = $urow['setpoint'];
                                    } else {
                                        $compname = "Empty";
                                        $setpoint = 0;
                                        $unitid   = 0;
                                    }
                                    if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }

                                    $lines .= "<tr><td>$unit</td>\n";
                                    if ($unit >= "C43" AND $unit <= "C79") {
                                        if ($device['points'][$xwebsites[$siteid]['onoff']]['value'] == 1) {
                                            
                                            if ($device['points'][$xwebsites[$siteid]['keyboard']]['value'] == 1) {
                                                $lines .= "<td>Already Set</td>\n";
                                            } else {
                                                
                                                if ($deviceid) {
                                                    $postdata = http_build_query(
                                                        array(
                                                            'auth'   => $access_token,
                                                            'action' => 'send_command',
                                                            'device'  => $deviceid,
                                                            'command' => $commands[3][id]
                                                        )
                                                    );
                            
                                                    $ch = curl_init();
                                                    curl_setopt($ch, CURLOPT_POST, 1);
                                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                                    curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
                                                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
                                                    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                                                    $result = curl_exec($ch);
                                                    curl_close($ch);
                            
                                                    if ($result !== FALSE) {
                                                        $result = json_decode($result, TRUE);
                                                        if ($result['status'] == "ok") {
                                                            $cmdresult = "Okay";
                                                        } else {
                                                            $cmdresult = "Failed {$result['error']}";
                                                        }
                                                    } else {
                                                        $cmdresult = "Failed";
                                                    }

                                                    /* print "<pre>";
                                                    print_r($result);
                                                    print "</pre>"; */
                            
                                                    $lines .= "<td>$cmdresult</td>\n";

                                                }
                                            }      
                                        } else {
                                            $lines .= "<td>Device is off</td>\n";
                                        }
                                        $lines .= "</tr>\n";
                                    }
                                }
                                $resultMessage .= "<table>$lines</table>";
                            }
                            elseif ($result['status'] == "error") {

                                $resultMessage .= "<br />" . $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "<br />";

                            }
                
                        } else {
                            $resultMessage .= "Error: <br />Nothing was returned from XWeb.<br />";
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
    print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return</a></p>";
} else {
	include ('loggedout.php');
}
/* print "<p><b>Raw Data From XWeb</b></p>";
print "<pre>";
print_r($result);
print "</pre>"; */
print "</td>\n";
include('footer.php');
?>
