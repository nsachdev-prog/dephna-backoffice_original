<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Defrost";
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
			$unitid = $_REQUEST['unitid'];
			$deviceid = $_REQUEST['deviceid'];
            $onoff  = $_REQUEST['onoff'];
            if ($_REQUEST['defrost']) { $defrost = 1; } else { $defrost = 0; } // form button
            if ($siteid) {

                // get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
                } else {
                    $sitename = "Unknown";
                }

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

                print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";
                print "<p align=\"center\" class=\"larger\">Manual Defrost</p>\n";

                print "<p align=\"center\" class=\"redtext\">Remember, if defrosting manually,<br>it will not show in the history until the next time data is collected. Up to 5 mins.</p>\n";

                if ($defrost) {

                    $defrost_time = $_REQUEST['defrost_time'];

                    include('XWebGetAccessToken.php');
			        if ($access_token) {

                        // set parameter MdF to 20 mins
                        $parameters = array(
                            'section' => 1,
                            'label'   => 'MdF',
                            'value'   => $defrost_time
                        );
                        $targets = array();
                        $targets[] = $deviceid;
                        $postdata = http_build_query(
                            array(
                                'auth'       => $access_token,
                                'action'     => 'write_parameters'
                            )
                        );
                        $postdata .= "&targets=" . json_encode($targets);
                        $postdata .= "&parameters=" . json_encode($parameters);
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
                        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                        $dfresult = curl_exec($ch);
                        curl_close($ch);
                        if ($dfresult === FALSE) {
                             $resultMessage .= "Error:<br />No Response from XWeb setting defrost length.<br />";
                        } else {
                            $dfresult = json_decode($dfresult, TRUE);
                            if ($dfresult['status'] != "ok") {
                                if ($dfresult['status'] == "error") {
                                    $resultMessage .= "Error Setting Defrost Time:<br />{$dfresult['error']}<br />";
                                } else {
                                    $resultMessage .= "Error Setting Defrost Time:<br />Unknown Response from XWeb setting defrost time.<br />{$dfresult['status']}<br />{$dfresult['error']}<br />";
                                }
                            } else {
                                $resultMessage .= "Defrost time set to $defrost_time mins.<br />";
                            }
                        }

                        // parameter set, now send defrost command
                        $postdata = http_build_query(
                            array(
                                'auth'   => $access_token,
                                'action' => 'send_command',
                                'device'  => $deviceid,
                                'command' => '16'
                            )
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
                        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                        $oresult = curl_exec($ch);
                        curl_close($ch);

                        if ($oresult !== FALSE) {
                            $oresult = json_decode($oresult, TRUE);
                            if ($oresult['status'] == "ok") {
                                $action = "Dfrst Manual $defrost_time mins";
                                $resultMessage .= "Success:<br />XWeb Defrost command sent successfully.<br />";
                            } else {
                                $action = "Could Not Defrost";
                                $resultMessage .= "Error:<br />Xweb did NOT accept the Defrost command<br />{$oresult['status']}<br />{$oresult['error']}<br />";

                            }
                        } else {
                            $action = "No reply from Xweb Defrost";
                            $resultMessage .= "Error:<br />No Response from XWeb sending Defrost command.<br />";
                        }
                        // log it
                        $datetime = date("Y-m-d H:i");
                        $sql = "INSERT INTO `xweb_actions` VALUES (
                            null,
                            '$datetime',
                            '$siteid',
                            '$unitid',
                            '$unitname',
                            '$deviceid',
                            '$custref',
                            '$compname',
                            '5',
                            '{$_SESSION['username']}',
                            '0',
                            '',
                            '$action',
                            '',
                            '0',
                            '0',
                            '',
                            'No'
                        )";
                        if ($dbcnx->query($sql)) {
                            $resultMessage .= "Command logged successfully.<br />";
                        } else {
                            $resultMessage .= "Could not log the XWeb Defrost command.<br />" . $dbcnx->error . "<br />";
                        }

                    } else {
                        $resultMessage .= "Error:<br />Could not get XWeb Access Token.<br />";
                    }

                } else {
                    // print the form button
                    // center the button
                    print "<div style=\"text-align:center\">\n";
                    print "<span class=\"notclicked\">\n";
                    print "<form method=\"post\" action=\"coldrooms-xweb-hist-defrost.php\">\n";
                    print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\">\n";
                    print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\">\n";
                    print "<input type=\"hidden\" name=\"deviceid\" value=\"$deviceid\">\n";
                    print "<input type=\"hidden\" name=\"onoff\" value=\"$onoff\">\n";
                    print "<input type=\"hidden\" name=\"defrost\" value=\"1\">\n";
                    // select defrost time
                    print "<select name=\"defrost_time\">\n";
                    print "<option value=\"5\">5 mins</option>\n";
                    print "<option value=\"10\">10 mins</option>\n";
                    print "<option value=\"20\" SELECTED>20 mins</option>\n";
                    print "<option value=\"30\">30 mins</option>\n";
                    print "<option value=\"40\">40 mins</option>\n";
                    print "</select>\n";
                    print "<input type=\"submit\" value=\"Defrost Now\">\n";
                    print "</form>\n";
                    print "</span>\n";
                    print "</div>\n";

                    print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

                    // spinner
                    print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";
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
    // return to history page
    print "<p align=\"center\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;onoff=$onoff\">Return to History</a></p>";

} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
