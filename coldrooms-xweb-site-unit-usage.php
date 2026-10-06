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
            $month = $_REQUEST['month'];
            if (!$month) { $month = date("Y-m",strtotime("-1 Month")); }
            $fr = $month . "-01 00:00";
            $to = date("Y-m-t 23:59",strtotime($fr));
            $prevmon = date("Y-m",strtotime($fr . "-1 Month"));
            $nextmon = date("Y-m",strtotime($fr . "+1 Month"));

            $xfr = strtotime($fr);
            $xto = strtotime($to);
            $wfr = $month . "-01";
            $wto = date("Y-m-t",strtotime($wfr));

            $dates = array();
            $dates[0] = array(
                "xfr" => strtotime($prevmon . "-01 00:00"),
                "xto" => strtotime(date("Y-m-t 23:59",strtotime($prevmon . "-01 00:00"))),
                "wfr" => $prevmon . "-01",
                "wto" => date("Y-m-t",strtotime($prevmon . "-01"))
            );
            $dates[1] = array( 
                "xfr" => $xfr,
                "xto" => $xto,
                "wfr" => $wfr,
                "wto" => $wto
            );

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

                        print "<p align=\"center\" class=\"larger\">$sitename - $unitname - LIVE USAGE</p>\n";

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

                        print "<p align=\"center\" class=\"larger\">DP$custref - $compname</p>\n";

                        include_once('XWebGetAccessToken.php');

                        if ($access_token) {

                            // print filter form
                            print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb-site-unit-usage.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\">\n";
                            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                            print "<tr>\n";
                            print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-site-unit-usage.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;month=$prevmon\" class=\"displayloader\"><b>&lt;&lt;</b></a></td>\n";
                            print "<td style=\"font-size:1.2em\"><b>Month</b></td>\n";
                            print "<td><input type=\"text\" id=\"datepicker\" name=\"month\" value=\"$month\" class=\"element_small\" /></td>\n";
                            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                            print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-xweb-site-unit-usage.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\"><b>&bull;</b></a></td>\n";
                            print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-site-unit-usage.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;month=$nextmon\" class=\"displayloader\"><b>&gt;&gt;</b></a></td>\n";
                            print "</tr>\n";
                            print "</table>\n";
                            print "</form>\n";

                            print "<p align=\"center\">";
                            print "<a href=\"coldrooms-xweb-site-unit-params.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Parameters</a> | ";
                            print "<a href=\"coldrooms-xweb-site-unit.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Return To Unit</a> | ";
                            print "<a href=\"coldrooms-xweb-site.php?siteid=$siteid\" class=\"displayloader\">Return To Site</a> | ";
                            print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
                            print "</p>";

                            foreach ($dates AS $key => $date) {

                                $xfr = $date['xfr'];
                                $xto = $date['xto'];
                                $wfr = $date['wfr'];
                                $wto = $date['wto'];

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
                                    if ($result = json_decode($result, TRUE)) {
                                        if ($result['status'] == "ok") {

                                            $dooropen = 0;
                                            $doorqty = 0;
                                            $doorduration = 0;
                                            $lighton = 0;
                                            $lightqty = 0;
                                            $lightduration = 0;
                                            $defroston = 0;
                                            $defrostqty = 0;
                                            $defrostduration = 0;
                                            $fanon = 0;
                                            $fanqty = 0;
                                            $fanduration = 0;

                                            $temperature = 0;
                                            $setpoint = 0;

                                            foreach($result['data'] AS $data) {

                                                $timestamp = $data['timestamp'];

                                                // colate sensors
                                                if ($data[$xrow['doorswitch']] == 1) {
                                                    if ($dooropen == 0) {
                                                        $dooropen = 1;
                                                        $doorqty++;
                                                        $doorstart = $timestamp;
                                                    }
                                                } else {
                                                    if ($dooropen == 1) {
                                                        $dooropen = 0;
                                                        $doorstop = $timestamp;
                                                        $doorduration = $doorduration + ($doorstop - $doorstart);
                                                    }
                                                }
                                                if ($data[$xrow['light']] == 1) {
                                                    if ($lighton == 0) {
                                                        $lighton = 1;
                                                        $lightqty++;
                                                        $lightstart = $timestamp;
                                                    }
                                                } else {
                                                    if ($lighton == 1) {
                                                        $lighton = 0;
                                                        $lightstop = $timestamp;
                                                        $lightduration = $lightduration + ($lightstop - $lightstart);
                                                    }
                                                }
                                                if ($data[$xrow['defrost']] == 1) {
                                                    if ($defroston == 0) {
                                                        $defroston = 1;
                                                        $defrostqty++;
                                                        $defroststart = $timestamp;
                                                    }
                                                } else {
                                                    if ($defroston == 1) {
                                                        $defroston = 0;
                                                        $defroststop = $timestamp;
                                                        $defrostduration = $defrostduration + ($defroststop - $defroststart);
                                                    }
                                                }
                                                if ($data[$xrow['fan']] == 1) {
                                                    if ($fanon == 0) {
                                                        $fanon = 1;
                                                        $fanqty++;
                                                        $fanstart = $timestamp;
                                                    }
                                                } else {
                                                    if ($fanon == 1) {
                                                        $fanon = 0;
                                                        $fanstop = $timestamp;
                                                        $fanduration = $fanduration + ($fanstop - $fanstart);
                                                    }
                                                }
                                                $temperature = $temperature + $data[$xrow['temperature']];
                                                $setpoint = $setpoint + $data[$xrow['setpoint']];
        
                                            }

                                            $dates[$key]['doorqty'] = $doorqty;
                                            $dates[$key]['lightqty'] = $lightqty;
                                            $dates[$key]['defrostqty'] = $defrostqty;
                                            $dates[$key]['fanqty'] = $fanqty;

                                            // convert $doorduration to hours minutes and seconds
                                            $hours = floor($doorduration / 3600);
                                            $mins = floor(($doorduration - ($hours * 3600)) / 60);
                                            $secs = floor($doorduration % 60);
                                            $dates[$key]['doorduration'] = sprintf("%02d:%02d:%02d", $hours, $mins, $secs);

                                            // convert $lightduration to hours minutes and seconds
                                            $hours = floor($lightduration / 3600);
                                            $mins = floor(($lightduration - ($hours * 3600)) / 60);
                                            $secs = floor($lightduration % 60);
                                            $dates[$key]['lightduration'] = sprintf("%02d:%02d:%02d", $hours, $mins, $secs);

                                            // convert $defrostduration to hours minutes and seconds
                                            $hours = floor($defrostduration / 3600);
                                            $mins = floor(($defrostduration - ($hours * 3600)) / 60);
                                            $secs = floor($defrostduration % 60);
                                            $dates[$key]['defrostduration'] = sprintf("%02d:%02d:%02d", $hours, $mins, $secs);

                                            // convert $fanduration to hours minutes and seconds
                                            $hours = floor($fanduration / 3600);
                                            $mins = floor(($fanduration - ($hours * 3600)) / 60);
                                            $secs = floor($fanduration % 60);
                                            $dates[$key]['fanduration'] = sprintf("%02d:%02d:%02d", $hours, $mins, $secs);

                                            // calculate average temperature
                                            $dates[$key]['temperature'] = $temperature / count($result['data']);

                                            // calculate average setpoint
                                            $dates[$key]['setpoint'] = $setpoint / count($result['data']);

                                            // get weather data
                                            $weatherData = file_get_contents("https://api.open-meteo.com/v1/forecast?latitude=51.5085&longitude=-0.1257&daily=temperature_2m_max,temperature_2m_min&timezone=Europe%2FLondon&start_date=$wfr&end_date=$wto");
                                            $weatherData = json_decode($weatherData, true);
                                            $weatherData = $weatherData['daily'];
                                            $dates[$key]['weatherMax'] = max($weatherData['temperature_2m_max']);
                                            $dates[$key]['weatherMin'] = min($weatherData['temperature_2m_min']);
                                            $dates[$key]['weatherMean'] = (max($weatherData['temperature_2m_max']) + min($weatherData['temperature_2m_min'])) / 2;

                                        }

                                        elseif ($result['status'] == "error") {

                                            $resultMessage .= "<br />" . $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "<br />";

                                        }

                                    } else {
                                        $resultMessage .= "Error: <br />No JSON string returned from XWeb (or string unreadable).<br />Returned: $result<br />";
                                    }
                        
                                } else {
                                    $resultMessage .= "Error: <br />Nothing was returned from XWeb.<br />";
                                }

                                /* print "<pre>";
                                print_r($result);
                                print "</pre>";

                                print "<pre>";
                                print_r($dates);
                                print "</pre>";

                                print "<pre>";
                                print_r($weatherData);
                                print "</pre>"; */

                            }

                            $monthtext0 = date("F Y",strtotime($dates[0]['wfr']));
                            $monthtext1 = date("F Y",strtotime($dates[1]['wfr']));

                            print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

                            print "<tr>\n";
                            print " <td>&nbsp;</td>\n";
                            print "	<td colspan=\"3\" align=\"center\"><b>$monthtext0</b></td>\n";
                            print "	<td colspan=\"3\" align=\"center\"><b>$monthtext1</b></td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\"><b>Sensor</b></td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>H:M:S</b></td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>H:M:S</b></td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                            print "</tr>\n";

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print " <td align=\"left\"><b>Door Open</b></td>\n";
                            print " <td align=\"right\">" . $dates[0]['doorduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[0]['doorqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . $dates[1]['doorduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[1]['doorqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print "</tr>\n";

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print " <td align=\"left\"><b>Light On</b></td>\n";
                            print " <td align=\"right\">" . $dates[0]['lightduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[0]['lightqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . $dates[1]['lightduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[1]['lightqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print "</tr>\n";

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print " <td align=\"left\"><b>Defrost On</b></td>\n";
                            print " <td align=\"right\">" . $dates[0]['defrostduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[0]['defrostqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . $dates[1]['defrostduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[1]['defrostqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print "</tr>\n";

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print " <td align=\"left\"><b>Fan On</b></td>\n";
                            print " <td align=\"right\">" . $dates[0]['fanduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[0]['fanqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . $dates[1]['fanduration'] . "</td>\n";
                            print " <td align=\"right\">" . $dates[1]['fanqty'] . "</td>\n";
                            print " <td>&nbsp;</td>\n";
                            print "</tr>\n";

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print " <td align=\"left\"><b>Set Point</b></td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . number_format($dates[0]['setpoint'],2) . "</td>\n";
                            print " <td><i>Av</i></td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . number_format($dates[1]['setpoint'],2) . "</td>\n";
                            print " <td><i>Av</i></td>\n";
                            print "</tr>\n";

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print " <td align=\"left\"><b>Room Temp</b></td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . number_format($dates[0]['temperature'],2) . "</td>\n";
                            print " <td><i>Av</i></td>\n";
                            print " <td>&nbsp;</td>\n";
                            print " <td align=\"right\">" . number_format($dates[1]['temperature'],2) . "</td>\n";
                            print " <td><i>Av</i></td>\n";
                            print "</tr>\n";
                    
                            print "<tr>\n";
                            print "<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                            print "</tr>\n";

                            print "</table>\n";

                            print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";

                            print "<tr>\n";
                            print " <td colspan=\"4\" align=\"center\"><b>Outside Temperature &deg;C</b><br></td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print " <td align=\"center\"><b>Month</b></td>\n";
                            print " <td align=\"center\"><b>Max</b></td>\n";
                            print " <td align=\"center\"><b>Min</b></td>\n";
                            print " <td align=\"center\"><b>Mean</b></td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print " <td align=\"center\">" . date("F Y",strtotime($dates[0]['wfr'])) . "</td>\n";
                            print " <td align=\"center\">" . number_format($dates[0]['weatherMax'], 1) . "</td>\n";
                            print " <td align=\"center\">" . number_format($dates[0]['weatherMin'], 1) . "</td>\n";
                            print " <td align=\"center\">" . number_format($dates[0]['weatherMean'], 1) . "</td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print " <td align=\"center\">" . date("F Y",strtotime($dates[1]['wfr'])) . "</td>\n";
                            print " <td align=\"center\">" . number_format($dates[1]['weatherMax'], 1) . "</td>\n";
                            print " <td align=\"center\">" . number_format($dates[1]['weatherMin'], 1) . "</td>\n";
                            print " <td align=\"center\">" . number_format($dates[1]['weatherMean'], 1) . "</td>\n";
                            print "</tr>\n";

                            print "</table><br>\n";

                            $dates[0]['monthtext'] = $monthtext0;
                            $dates[1]['monthtext'] = $monthtext1;

                            print "<table align=\"center\">";
                            print "<tr>";
                            print "<td>";
                            print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb-site-unit-usage-pdf.php\" target=\"_blank\">\n";
                            print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
                            print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
                            print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
                            print "<input type=\"hidden\" name=\"dates\" value=\"" . urlencode(serialize($dates)) . "\" />\n";
                            print "<input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Print PDF\" />\n";
                            print "</form>\n";
                            print "</td>";
                            print "</tr>";
                            print "</table>";
                            
                        } else {
                            $resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
                        }
                    

                } else {
                    $resultMessage .= "Could NOT get site<br />" . $result->error . "<br />";
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
    // jquery date picker script
    print "<script type=\"text/javascript\">\n";
    print "	$( document ).ready(function() {\n";
    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm'});\n";
    print "	});\n";
    print "</script>\n";

    print "<p align=\"center\">";
    print "<a href=\"coldrooms-xweb-site-unit-params.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Parameters</a> | ";
    print "<a href=\"coldrooms-xweb-site-unit.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Return To Unit</a> | ";
    print "<a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub\" class=\"displayloader\">Return To Site</a> | ";
    print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
    print "</p>";

    print "</div>\n";
    
} else {
	include ('loggedout.php');
}

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
