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
    
    print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

        // open database
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if(!$dbcnx->connect_error) {

            $siteid = $_REQUEST['siteid'];
            $xsub   = $_REQUEST['xsub'];
            $unitid = $_REQUEST['unitid'];
            $deviceid = $_REQUEST['deviceid'];
            $fr = $_REQUEST['fr'];
            $to = $_REQUEST['to'];
            if (!$fr) { $xfr = strtotime("-2 Hours"); $fr = date("Y-m-d H:i",$xfr); } else { $xfr = strtotime($fr); }
            if (!$to) { $xto = time(); $to = date("Y-m-d H:i",$xto); } else { $xto = strtotime($to); }
            if ($siteid AND $deviceid) {

                // Get sensor ids
				$sql = "SELECT * FROM XWebAuth WHERE siteid = '$siteid' AND xsub = '$xsub'";
				if ($xresult = $dbcnx->query($sql) AND $xresult->num_rows) {
					$xrow = $xresult->fetch_assoc();
				}

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['title'];

                    // get unit
                    $sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unittype = $row['unittype'];
						$unitname = $row['title'];
                    } else {
                        $unitname = "Unknown";
                        $unittype = "Unknown";
                    }

                        print "<p align=\"center\" class=\"larger\">$sitename - $unitname - LIVE<br />$unittype</p>\n";

                        include_once('XWebGetAccessToken.php');

                        if ($access_token) {

                            print "<form id=\"rform\" method=\"post\" action=\"coldrooms-xweb-site-unit.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\">\n";
                            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                            print "<tr>\n";
                            print "<td><b>From</b></td>";
                            print "<td><input type=\"text\" id=\"datetimepicker1\" name=\"fr\" value=\"$fr\" class=\"element_small\" /></td>";
                            print "<td><b>To</b></td>";
                            print "<td><input type=\"text\" id=\"datetimepicker2\" name=\"to\" value=\"$to\" class=\"element_small\" /></td>";
                            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                            print "</tr>\n";
                            print "</table>\n";
                            print "</form>\n";

                            print "<p align=\"center\">";
                            print "<a href=\"coldrooms-xweb-site-unit-usage.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Usage</a> | ";
                            print "<a href=\"coldrooms-xweb-site-unit-params.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Parameters</a> | ";
                            print "<a href=\"coldrooms-xweb-site.php?siteid=$siteid\" class=\"displayloader\">Return To Site</a> | ";
                            print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
                            print "</p>";

                            $postdata = http_build_query(
                                array(
                                    'auth'     => $access_token,
                                    'action'   => 'device_data',
                                    'device'   => $deviceid,
                                    'from'     => $xfr,
                                    'to'       => $xto
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

                                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                    print "<tr>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Power</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>KbLock</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>DoorSw</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>DO</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Heater</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Set Point</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap&deg;</b></td>\n";
                                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Defrost</b></td>\n";
                                    print "</tr>\n";

                                    // row background colours
                                    print "<style>\n";
                                    print ".r0 { background-color:#fff }\n";
                                    print ".r1 { background-color:#ddd }\n";
                                    print "</style>\n";

                                    foreach(array_reverse($result['data']) AS $data) {
                                        
                                        $sql = "SELECT b.custid AS custref, c.compname, u.setpoint
                                            FROM bookings AS b
                                            LEFT JOIN users AS c ON c.custref = b.custid
                                            LEFT JOIN units AS u ON u.ID = b.unitid
                                            WHERE b.siteid = '$siteid' AND b.unitid = '$unitid'
                                        ";
                                        if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
                                            $urow = $uresult->fetch_assoc();
                                            $compname = $urow['compname'];
                                            $custref  = $urow['custref'];
                                        } else {
                                            $compname = "Empty";
                                        }
                                        if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }

                                        $datetime = date("Y-m-d H:i",$data['timestamp']);

                                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                        print " <td>$datetime</td>\n";
                                        print " <td>$compname</td>\n";
                                        if ($data['onoff'] == 1) {
                                            print " <td>ON</td>\n";
                                        } else {
                                            print " <td><span class=\"redtext\">OFF</span></td>";
                                        }
                                        if ($data[$xrow['keyboard']] == 1) {
                                            print " <td>ON</td>\n";
                                        } else {
                                            print " <td><span class=\"redtext\">OFF</span></td>";
                                        }
                                        if ($data[$xrow['doorswitch']] == 1) {
                                            print " <td><span class=\"redtext\">OPEN</span></td>\n";
                                        } else {
                                            print " <td>Closed</td>";
                                        }
                                        if ($data[$xrow['dooropen']] == 1) {
                                            print " <td><span class=\"redtext\">1</span></td>\n";
                                        } else {
                                            print " <td>0</td>";
                                        }
                                        if ($data[$xrow['doorheater']] == 1) {
                                            print " <td>ON</span></td>\n";
                                        } else {
                                            print " <td>OFF</td>";
                                        }
                                        print " <td align=\"right\">{$data[$xrow['setpoint']]}</td>\n";
                                        print " <td align=\"right\">{$data[$xrow['temperature']]}</td>\n";
                                        print " <td align=\"right\">{$data[$xrow['evaporator']]}</td>\n";
                                        if ($data[$xrow['defrost']] == 1) {
                                            print " <td><span class=\"redtext\">ON</span></td>\n";
                                        } else {
                                            print " <td>OFF</td>";
                                        }
                                        print "</tr>\n";
                                    }

                                    print "<tr>\n";
                                    print "<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                    print "</tr>\n";

                                    print "</table>\n";

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
                    $resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
                }

            } else {
                $resultMessage .= "Page called without siteid/unitid/deviceid.<br />";
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
    print "<script type=\"text/javascript\">\n";
    print "	$( document ).ready(function() {\n";
    print " jQuery.datetimepicker.setLocale('en');\n";
    print "		$( \"#datetimepicker1\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
    print "		$( \"#datetimepicker2\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
    print "	});\n";
    print "</script>\n";

    print "<p align=\"center\">";
    print "<a href=\"coldrooms-xweb-site-unit-usage.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Usage</a> | ";
    print "<a href=\"coldrooms-xweb-site-unit-params.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Parameters</a> | ";
    print "<a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub\" class=\"displayloader\">Return To Site</a> | ";
    print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
    print "</p>";

    print "</div>\n";
} else {
	include ('loggedout.php');
}

print "<p><b>Raw Data From XWeb</b></p>";
print "<pre>";
print_r($result);
print "</pre>";

/* $postdata = http_build_query(
    array(
        'auth'     => $access_token,
        'action'   => 'runtime',
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
$result = json_decode($result, TRUE);

print "<p><b>Raw Data From XWeb</b></p>";
print "<pre>";
print_r($result);
print "</pre>"; */


print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
