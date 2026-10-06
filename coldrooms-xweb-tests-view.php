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
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM TESTS</p>\n";
	
	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];
            $testid = $_REQUEST['testid'];

			if ($siteid AND $unitid AND $testid) {

                if ($_POST['posted']) {
                    $result   = $_POST['result'];
                    $comments = $_POST['comments'];
                    $notes    = $_POST['notes'];
                    $light    = $_POST['light'];
                    $doorswitch = $_POST['doorswitch'];
                    $defrost  = $_POST['defrost'];
                    $resdate  = time();
                    $resby    = $_SESSION['username'];
                    if ($comments) {
                        $comments = str_replace('\n', "<br />", $comments);
                        $comments = $dbcnx->real_escape_string($comments);
                    }
                    if ($notes) {
                        $notes = str_replace('\n', "<br />", $notes);
                        $notes = $dbcnx->real_escape_string($notes);
                    }
                    if (!$light) { $light = 0; }
                    if (!$doorswitch) { $doorswitch = 0; }
                    if (!$defrost) { $defrost = 0; }
                    $sql = "UPDATE xweb_test_headers SET
                        `status` = '2',
                        `light` = '$light',
                        `doorswitch` = '$doorswitch',
                        `defrost` = '$defrost',
                        `result` = '$result',
                        `comment` = '$comments',
                        `notes` = '$notes',
                        `resdate` = '$resdate',
                        `resby` = '$resby'
                        WHERE ID = '$testid'
                    ";
                    if ($dbcnx->query($sql)) {
                        $resultMessage .= "Result entered successfully<br />";
                    } else {
                        $resultMessage .= "Error entering result<br />";
                    }
                }

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				}

				// get unit
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
				}

                $statuses = array(
                    '0' => 'Ongoing',
                    '1' => 'Ready',
                    '2' => 'Complete'
                );

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

                // get test
                $sql = "SELECT * FROM xweb_test_headers WHERE ID = '$testid'";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $crdate    = $row['crdate'];
                    $testHours = $row['testHours'];
                    $testUntil = $row['testUntil'];
                    $status    = $row['status'];
                    $light     = $row['light'];
                    $doorswitch = $row['doorswitch'];
                    $defrost   = $row['defrost'];
                    $crby      = $row['crby'];
                    $comments  = $row['comment'];
                    $notes     = $row['notes'];
                    $resdate   = $row['resdate'];
                    $resby     = $row['resby'];
                    $resulttxt = $row['result'];
                    $crdate = date('d/m/Y H:i', $crdate);
                    $testUntil = date('d/m/Y H:i', $testUntil);
                    if ($resdate) { $resdate = date('d/m/Y H:i', $resdate); }
                    if ($light == 1) { $light = 'Yes'; } else { $light = 'No'; }
                    if ($doorswitch == 1) { $doorswitch = 'Yes'; } else { $doorswitch = 'No'; }
                    if ($defrost == 1) { $defrost = 'Yes'; } else { $defrost = 'No'; }

                    print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";
                    print "<tr>\n";
                    print "	<td><b>Started</b></td>\n";
                    print "	<td>$crdate</td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "	<td><b>By</b></td>\n";
                    print "	<td>$crby</td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "	<td><b>Hours</b></td>\n";
                    print "	<td>$testHours</td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "	<td><b>End</b></td>\n";
                    print "	<td>$testUntil</td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "	<td><b>Status</b></td>\n";
                    print "	<td>{$statuses[$status]}</td>\n";
                    print "</tr>\n";
                    if ($status == 2) {
                        print "<tr>\n";
                        print "	<td><b>Light Working</b></td>\n";
                        print "	<td>$light</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Door Switch Working</b></td>\n";
                        print "	<td>$doorswitch</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Defrost Working</b></td>\n";
                        print "	<td>$defrost</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Result</b></td>\n";
                        print "	<td>$resulttxt</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Result Date</b></td>\n";
                        print "	<td>$resdate</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Result By</b></td>\n";
                        print "	<td>$resby</td>\n";
                        print "</tr>\n";
                        if ($comments) {
                            print "<tr>\n";
                            print "	<td valign=\"top\"><b>Comments</b></td>\n";
                            print "	<td valign=\"top\" width=\"200\">$comments</td>\n";
                            print "</tr>\n";
                        }
                        if ($notes) {
                            print "<tr>\n";
                            print "	<td valign=\"top\"><b>Notes</b></td>\n";
                            print "	<td valign=\"top\" width=\"200\">$notes</td>\n";
                            print "</tr>\n";
                        }
                    } else {
                        print "<tr>\n";
                        print "	<td colspan=\"2\">&nbsp;</td>\n";
                        print "</tr>\n";
                    }
                    print "</table>\n";
                    print "<p align=\"center\" class=\"larger\">Test Data</p>\n";
                    // get test details
                    $sql = "SELECT * FROM xweb_test_lines WHERE hid = '$testid' ORDER BY xdatetime DESC";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                        print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>EngMode</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>NoCall</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>ApprOff</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Power</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>KeyLock</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>SetPoint</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Temp</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>DrSwitch</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>DrOpen</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>DrHeat</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Defrst</b></td>\n";
                        print "</tr>\n";
                        while ($row = $result->fetch_assoc()) {
                            $xdatetime = $row['xdatetime'];
                            $engineermode = $row['engineermode'];
                            $nocallout = $row['nocallout'];
                            $approvedoff = $row['approvedoff'];
                            $onoff = $row['onoff'];
                            $keyboard = $row['keyboard'];
                            $setpoint = $row['setpoint'];
                            $temperature = $row['temperature'];
                            $evaporator = $row['evaporator'];
                            $doorswitch = $row['doorswitch'];
                            $dooropen = $row['dooropen'];
                            $doorheater = $row['doorheater'];
                            $light = $row['light'];
                            $defrost = $row['defrost'];
                            $xdatetime = date('H:i', strtotime($xdatetime));
                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$xdatetime</td>\n";
                            if ($engineermode == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($nocallout == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($approvedoff == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($onoff == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($keyboard == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            print "	<td align=\"right\">$setpoint</td>\n";
                            print "	<td align=\"right\">$temperature</td>\n";
                            print " <td align=\"right\">$evaporator</td>\n";
                            if ($doorswitch == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($dooropen == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($doorheater == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($light == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            if ($defrost == 1) {
                                print "	<td><span class=\"redtext\">ON</span></td>\n";
                            } else {
                                print "	<td>Off</td>\n";
                            }
                            print "</tr>\n";
                        }
                        print "<tr>\n";
                        print "	<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                        print "</tr>\n";
                        print "</table>\n";
                    } else {
                        print "<p align=\"center\">No test details yet.</p>";
                    }

                    if ($status == 1 AND $resdate == 0) {
                        print "<p align=\"center\" class=\"larger\">Complete</p>\n";
                        print "<form name=\"resform\" method=\"post\" action=\"coldrooms-xweb-tests-view.php\">\n";
                        print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                        print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
                        print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
                        print "<input type=\"hidden\" name=\"testid\" value=\"$testid\" />\n";
                        print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "	<td><b>Result</b></td>\n";
                        print "	<td>\n";
                        print "		<select name=\"result\" class=\"element\">\n";
                        print "			<option value=\"PASSED\">PASSED</option>\n";
                        print "			<option value=\"FAILED\">FAILED</option>\n";
                        print "		</select>\n";
                        print "	</td>\n";
                        print "</tr>\n";
                        // add checkboxes for light, doorswitch, defrost
                        print "<tr>\n";
                        print "	<td><b>Is the light working?</b></td>\n";
                        print "	<td>\n";
                        print "		<input type=\"checkbox\" name=\"light\" value=\"1\" />\n";
                        print "	</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Is the door switch working?</b></td>\n";
                        print "	<td>\n";
                        print "		<input type=\"checkbox\" name=\"doorswitch\" value=\"1\" />\n";
                        print "	</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Is the defrost working?</b></td>\n";
                        print "	<td>\n";
                        print "		<input type=\"checkbox\" name=\"defrost\" value=\"1\" />\n";
                        print "	</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td valign=\"top\"><b>Comments</b><br>Seen by client</td>\n";
                        print "	<td valign=\"top\"><textarea name=\"comments\" cols=\"50\" rows=\"3\"></textarea></td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td valign=\"top\"><b>Notes</b><br>Internal Only</td>\n";
                        print "	<td valign=\"top\"><textarea name=\"notes\" cols=\"50\" rows=\"3\"></textarea></td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                        print "</tr>\n";
                        print "</table>\n";
                        print "</form>\n";
                    }

                } else {
                    $resultMessage .= "Test Not Found<br />";
                }

			} else {
				$resultMessage .= "Page called without site or unit id<br />";
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
	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Unit</a> | ";
	print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Site</a>";
	print "</p>";
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
