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

			if ($siteid AND $deviceid) {

				// get site
				$sql = "SELECT title, testUntil FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
                    $testUntil = $row['testUntil'];

					// get unit
					$sql = "SELECT title, unittype, setpoint FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unittype = $row['unittype'];
						$unitname = $row['title'];
						$setpoint = $row['setpoint'];
					} else {
						$unitname = "Unknown";
						$unittype = "Unknown";
						$setpoint = 0;
					}

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname - TEST</p>\n";

                    if ($testUntil) {
                        $testUntil = date("H:i", $testUntil);
                        print "<p align=\"center\" class=\"larger\">On Test Until: $testUntil</p>\n";
                    } else {
                        print "<p align=\"center\" class=\"larger\">Not Currently On Test</p>\n";
                    }

                    include_once('XWebGetAccessToken.php');

                    if ($access_token) {

                        if ($_POST['write']) {

                            $params = $_POST['params'];
                            $duration = $_POST['duration'];
                            if ($duration) {
                                if ($duration > 0) {
                                    $uduration = $duration * 3600;
                                    $testUntil = time() + $uduration;
                                    $dbcnx->query("UPDATE units SET testUntil = '$testUntil', `engineermode` = '1' WHERE ID = '$unitid'");
                                    $sql = "INSERT INTO `xweb_actions` VALUES (
                                        null,
                                        '$datetime',
                                        '$siteid',
                                        '$unitid',
                                        '$unitname',
                                        '$deviceid',
                                        '$custref',
                                        '$compname',
                                        '0',
                                        '{$_SESSION['username']}',
                                        '0',
                                        '',
                                        'Test Mode On',
                                        '$paxtonResult',
                                        '',
                                        '',
                                        '',
                                        'No'
                                    )";
                                    $dbcnx->query($sql);
                                } else {
                                    $erduration = "Duration must be greater than 0"; $e++;
                                }
                            } else {
                                $erduration = "Duration must be greater than 0"; $e++;
                            }
                            $parameters = array();
                            foreach ($params AS $label => $value) {
                                if ($label = "SEt") {
                                    if ($value > 3 OR $value < -18) {
                                        $errors .= "Setpoint must be between -18 and 3<br />";
                                    }
                                }
                                if ($value) {
                                    $parameters[] = array(
                                        'section' => 1,
                                        'label'   => $label,
                                        'value'   => $value
                                    );
                                }
                            }

                            if (!$errors AND !$e) {

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

                                // print $postdata;

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
                                        print "<p align=\"center\">Params Updated</p>";
                                        $now = time();
                                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $unitname Params Updated','CR Test','$now')");

                                        // swith the power on
										$postdata = http_build_query(
											array(
												'auth'   => $access_token, 
												'action' => 'send_command',
												'device'  => $deviceid,
												'command' => '15'
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
                                        if ($result !== FALSE) {
                                            $result = json_decode($result, TRUE);
                                            if ($result['status'] == "ok") {
                                                print "<p align=\"center\">Power On</p>";
                                                $now = time();
                                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $unitname Power On','CR Test','$now')");
                                            } else {
                                                $errors .= "Power On Failed<br />";
                                            }
                                        } else {
                                            $errors .= "Power On Failed<br />";
                                        }
                                    }
                                    elseif ($result['status'] == "error") {
                                        print "<p align=\"center\">" . $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "</p>";
                                    }
                        
                                } else {
                                    $resultMessage .= "Error: <br />No Response from XWeb.<br />";
                                }

                            }

                        } else {
                            $setpoint = "";
                        }

                        print "<p align=\"center\">";
                        print "<a href=\"coldrooms-xweb-site-unit.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Return To Unit</a> | ";
                        print "<a href=\"coldrooms-xweb-site.php?siteid=$siteid\" class=\"displayloader\">Return To Site</a> | ";
                        print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
                        print "</p>";

                        if ($errors) {
                            print "<p align=\"center\" class=\"redtext\">$errors</p>\n";
                        }

                        $postdata = http_build_query(
                            array(
                                'auth'     => $access_token,
                                'action'   => 'read_parameters',
                                'device'   => $deviceid
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

                                $columns = array_column($result['parameters'], 'group');
                                array_multisort($columns, SORT_ASC, $result['parameters']);

                                print "<form name=\"writeform\" method=\"post\" action=\"coldrooms-xweb-site-unit-params.php\">\n";
                                print "<input type=\"hidden\" name=\"write\" value=\"1\" />\n";
                                print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
                                print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
                                print "<input type=\"hidden\" name=\"deviceid\" value=\"$deviceid\" />\n";

                                print "<p align=\"center\" class=\"larger\">Test Duration</p>\n";

                                print "<p align=\"center\">";
                                print "<b>Hours From Now</b>: <input name=\"duration\" type=\"text\" class=\"element\" maxlength=\"4\" value=\"$duration\" />";
                                if ($erduration) { print "<br><span class=\"redtext\">$erduration</span>"; }
                                print "</p>\n";

                                print "<p align=\"center\" class=\"larger\">Parameters</p>\n";

                                print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";

                                print "<tr>\n";
                                print "	<td style=\"border-bottom:1px solid #666\"><b>Parameter</b></td>\n";
                                print "	<td style=\"border-bottom:1px solid #666\"><b>Label</b></td>\n";
                                print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Currently</b></td>\n";
                                print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Change To</b></td>\n";
                                print "</tr>\n";

                                $lastgroup = "";
                                $r = 1;

                                foreach ($result['parameters'] AS $parameters) {
                                    $group = $parameters['group'];
                                    if ($group != $lastgroup) {
                                        print "<tr>\n";
                                        print "	<td colspan=\"4\"><span class=\"larger\">$group</td>\n";
                                        print "</tr>";
                                        $r = 1;
                                    }
                                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                    print "	<td>{$parameters['description']}</td>\n";
                                    print "	<td>{$parameters['label']}</td>\n";
                                    print "	<td align=\"right\">{$parameters['value']}</td>\n";
                                    if ($parameters['value_options']) {
                                        print "	<td align=\"right\"><select name=\"params[{$parameters['label']}]\" class=\"element_smaller_select\">\n";
                                        print "		<option></option>\n";
                                        foreach ($parameters['value_options'] AS $option) {
                                            print "		<option value=\"$option\">$option</option>\n";
                                        }
                                        print "	</select>";
                                        print "</td>\n";
                                    } else {
                                        print "	<td align=\"right\"><input name=\"params[{$parameters['label']}]\" type=\"text\" class=\"element_smaller\" /></td>\n";
                                    }
                                    print "</tr>\n";
                                    $lastgroup = $group;
                                }

                                print "<tr>\n";
                                print "	<td style=\"border-top:1px solid #666\" colspan=\"4\">&nbsp;</td>\n";
                                print "</tr>\n";

                                print "</table>\n";
                                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
                                print "</form>\n";


                                /* print "<p align=\"center\"><b>All Parameters</b></p>";
                                print "<p><b>Raw Data From XWeb</b></p>";
                                print "<pre>";
                                print_r($result);
                                print "</pre>"; */

                            }
                            elseif ($result['status'] == "error") {
                                print "<p align=\"center\">" . $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "</p>";
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

	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-site-unit.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$deviceid\" class=\"displayloader\">Return To Unit</a> | ";
	print "<a href=\"coldrooms-xweb-site.php?siteid=$siteid\" class=\"displayloader\">Return To Site</a> | ";
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
