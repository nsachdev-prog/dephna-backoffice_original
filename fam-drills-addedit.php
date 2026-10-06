<?php
$page = "Fire_Alarms";
$subpage = "Drills";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

        print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";

        include('fam-submenu.php');
        print "<p align=\"center\">$menustr</p>\n";

        if ($_REQUEST['id']) {
            $id = $_REQUEST['id'];
            $heading = "Update Evacuation Drill";
        } else {
            $heading = "New Evacuation Drill";
        }
        print "<p align = \"center\" class=\"larger\">$heading</p>\n";
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

            $groupnames = array(
                "GFK" => "Ground Floor/Kitchen Corridors",
                "1CR" => "1st Floor/Cold Room Corridors",
                "OFF" => "Office Areas"
            );

            // populare rlines array
            $rlines = array();
            $lines = file('fam-drills-fields.csv', FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line) {
                list($group,$lnum,$title) = explode(",",$line);
                $rlines[$group][$lnum] = array("title" => $title);
            }

            if ($_POST['posted']) {

                $siteid   = $_POST['siteid'];
                $datetime = $_POST['datetime'];
                $e = 0;

                foreach ($rlines as $group => $lines) {
                    foreach ($lines as $lnum => $line) {
                        $field = "{$group}_{$lnum}";
                        $comments = "{$field}_comments";
                        if ($_POST[$field]) {
                            $rlines[$group][$lnum]['item'] = $_POST[$field];
                        } else {
                            $rlines[$group][$lnum]['error'] = "Tick"; $e++;
                        }
                        $rlines_comments[$group][$lnum] = $_POST[$comments];
                    }
                }

                $fire_panel = $_POST['fire_panel'];
                $fire_panel_comments = $_POST['fire_panel_comments'];
                $evacuated = $_POST['evacuated'];
                $evacuated_comments = $_POST['evacuated_comments'];
                $evacuation_time = $_POST['evacuation_time'];
                $notes = $_POST['notes'];

                if (!$siteid) {
                    $ersite = "Please select a site"; $e++;
                }

                if (!$datetime) {
                    $erdatetime = "Please enter a date and time"; $e++;
                }

                if (!$fire_panel) {
                    $erfire_panel = "Tick"; $e++;
                }

                if (!$evacuated) {
                    $erevacuated = "Tick"; $e++;
                }

                if (!$evacuation_time) {
                    $erevacuation_time = "Please enter how long the evacuation took"; $e++;
                }

                if (!$e) {
                    list($date,$time) = explode(" ",$datetime);
                    $notes = addslashes($notes);
                    $now = time();
                    
                    if ($id) {
                        $sql = "UPDATE fam_drills SET
                            siteid='$siteid',
                            `date`='$date',
                            `time`='$time',
                            fire_panel='$fire_panel',
                            fire_panel_comments='$fire_panel_comments',
                            evacuated='$evacuated',
                            evacuated_comments='$evacuated_comments',
                            evacuation_time='$evacuation_time',
                            notes='$notes',
                            lastupd='$now',
                            updby='{$_SESSION['username']}'
                            WHERE ID='$id'
                        ";
                        if ($result = $dbcnx->query($sql)) {
                            $resultMessage .= "Evacuation Drill Updated<br>";
                            // delete all lines for this drill
                            $dbcnx->query("DELETE FROM fam_drills_lines WHERE drillid = '$id'");
                            // add data to fam_drills_lines
                            foreach ($rlines as $group => $lines) {
                                foreach ($lines as $lnum => $line) {
                                    $comments = $rlines_comments[$group][$lnum];
                                    $item = $rlines[$group][$lnum]['item'];
                                    $sql = "INSERT INTO fam_drills_lines VALUES (
                                        null,
                                        '$id',
                                        '$group',
                                        '$lnum',
                                        '$item',
                                        '$comments'
                                    )";
                                    if ($dbcnx->query($sql)) {
                                        $resultMessage .= "Line {$group}_{$lnum} updated<br>";
                                    } else {
                                        $resultMessage .= "<span class=\"redtext\"><b>Could NOT update line in database</b></span><br>" . $dbcnx->error . "<br>";
                                    }
                                }
                            }
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Evacuation Drill','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
                        }
                    } else {
                        $sql = "INSERT INTO fam_drills VALUES (
                            null,
                            '$siteid',
                            '$date',
                            '$time',
                            '$fire_panel',
                            '$fire_panel_comments',
                            '$evacuated',
                            '$evacuated_comments',
                            '$evacuation_time',
                            '$notes',
                            '0',
                            '$now',
                            '{$_SESSION['username']}'
                        )";
                        if ($result = $dbcnx->query($sql)) {
                            $resultMessage .= "<b>Evacuation Drill Created</b><br>";
                            // get the ID of the new record
                            $drillid = $dbcnx->insert_id;
                            // add data to fam_drills_lines
                            foreach ($rlines as $group => $lines) {
                                foreach ($lines as $lnum => $line) {
                                    $comments = $rlines_comments[$group][$lnum];
                                    $item = $rlines[$group][$lnum]['item'];
                                    $sql = "INSERT INTO fam_drills_lines VALUES (
                                        null,
                                        '$drillid',
                                        '$group',
                                        '$lnum',
                                        '$item',
                                        '$comments'
                                    )";
                                    if ($dbcnx->query($sql)) {
                                        $resultMessage .= "Line {$group}_{$lnum} added to database<br>";
                                    } else {
                                        $resultMessage .= "<span class=\"redtext\"><b>Could NOT add line to database</b></span><br>" . $dbcnx->error . "<br>";
                                    }
                                }
                            }
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Evacuation Drill','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
                        }
                    }
                }
            }

            elseif ($id) {

                $sql = "SELECT * FROM fam_drills WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $siteid = $row['siteid'];
                    $date = $row['date'];
                    $time = $row['time'];
                    $fire_panel = $row['fire_panel'];
                    $fire_panel_comments = $row['fire_panel_comments'];
                    $evacuated = $row['evacuated'];
                    $evacuated_comments = $row['evacuated_comments'];
                    $evacuation_time = $row['evacuation_time'];
                    $notes = $row['notes'];
                    $datetime = "$date $time";

                    // get lines
                    $sql = "SELECT * FROM fam_drills_lines WHERE drillid = '$id'";
                    $result = $dbcnx->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $group = $row['group'];
                        $lnum = $row['lnum'];
                        $value = $row['value'];
                        $comments = $row['comments'];
                        $rlines[$group][$lnum]['item'] = $value;
                        $rlines_comments[$group][$lnum] = $comments;
                    }   
                } else {
                    $resultMessage = "<b>Record not found</b>";
                }

            }

            if (!$resultMessage OR $e) {
                print "<form name=\"addform\" method=\"post\" action=\"fam-drills-addedit.php\">\n";
                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                // site
                print "<tr><td align=\"right\"><b>Site:</b></td>\n";
                print "<td align=\"left\">\n";
                print "<select name=\"siteid\">\n";
                print "<option value=\"\">Select Site</option>\n";
                $sql = "SELECT ID, title FROM sites WHERE ID > '27' OR ID < '26' ORDER BY title";
                $sitesresult = $dbcnx->query($sql);
                while ($row = $sitesresult->fetch_assoc()) {
                    print "<option value=\"{$row['ID']}\"";
                    if ($row['ID'] == $siteid) {
                        print " selected";
                    }
                    print ">{$row['title']}</option>\n";
                }
                print "</select>";
                if ($ersite) {
                    print "<br><span class=\"redtext\">$ersite</span>";
                }
                print "</td></tr>\n";

                // date and time use datetimepicker
                print "<tr><td align=\"right\"><b>Date and Time:</b></td>\n";
                print "<td align=\"left\"><input type=\"text\" name=\"datetime\" id=\"datetime\" value=\"$datetime\" class=\"element_small\" />\n";
                if ($erdatetime) {
                    print "<br> <span class=\"redtext\">$erdatetime</span>";
                }
                print "</td>\n";
                print "</tr>\n";

                print "</table>\n";

                print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
                // group heading then checkboxes with comments for each title in rlines
                foreach ($rlines as $group => $lines) {
                    print "<tr><td colspan=\"3\"><br><b>{$groupnames[$group]}</b></td></tr>\n";
                    foreach ($lines as $lnum => $line) {
                        $field = "{$group}_{$lnum}";
                        $comments = "{$field}_comments";
                        print "<tr><td valign=\"top\">{$line['title']}</td>\n";
                        print "<td valign=\"top\"><input type=\"checkbox\" name=\"$field\"";
                        if ($line['item']) {
                            print " checked";
                        }
                        print " />";
                        if ($rlines[$group][$lnum]['error']) {
                            print "<br><span class=\"redtext\">{$rlines[$group][$lnum]['error']}</span>";
                        }
                        print "</td>\n";
                        print "<td valign=\"top\"><input type=\"text\" name=\"$comments\" value=\"{$rlines_comments[$group][$lnum]}\" size=\"50\" class=\"element\" /></td>\n";
                        print "</tr>\n";
                    }
                }

                // space
                print "<tr><td colspan=\"3\">&nbsp;</td></tr>\n";

                // Fire Pannel tickbox and comments
                print "<tr><td valign=\"top\"><b>Fire Panel</b></td>\n";
                print "<td valign=\"top\"><input type=\"checkbox\" name=\"fire_panel\"";
                if ($fire_panel) {
                    print " checked";
                }
                print " />";
                if ($erfire_panel) {
                    print "<br><span class=\"redtext\">$erfire_panel</span>";
                }
                print "</td>\n";
                print "<td valign=\"top\"><input type=\"text\" name=\"fire_panel_comments\" value=\"$fire_panel_comments\" size=\"50\" class=\"element\" /></td>\n";
                print "</tr>\n";

                // tickbox and comments for 'Everyone has evacuated the building'
                print "<tr><td valign=\"top\"><b>Everyone has evacuated the building</b></td>\n";
                print "<td valign=\"top\"><input type=\"checkbox\" name=\"evacuated\"";
                if ($evacuated) {
                    print " checked";
                }
                print " />";
                if ($erevacuated) {
                    print "<br><span class=\"redtext\">$erevacuated</span>";
                }
                print "</td>\n";
                print "<td valign=\"top\"><input type=\"text\" name=\"evacuated_comments\" value=\"$evacuated_comments\" size=\"50\" class=\"element\" /></td>\n";
                print "</tr>\n";

                // text field for 'How long did the evcauation take?'
                print "<tr><td valign=\"top\"><b>How long did the evacuation take?</b></td>\n";
                print "<td valign=\"top\"></td>\n";
                print "<td valign=\"top\"><input type=\"text\" name=\"evacuation_time\" value=\"$evacuation_time\" size=\"50\" class=\"element\" />";
                if ($erevacuation_time) {
                    print "<br><span class=\"redtext\">$erevacuation_time</span>";
                }
                print "</td>\n";
                print "</tr>\n";

                // space
                print "<tr><td colspan=\"3\">&nbsp;</td></tr>\n";

                // general notes in teaxarea
                print "<tr><td colspan=\"3\"><b>General Notes</b><br>\n";
                print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"6\" class=\"element_box\">$notes</textarea></td>\n";
                print "</tr>\n";

                // Completed by
                print "<tr><td colspan=\"3\"><b>Completed by: {$_SESSION['username']}</b></td></tr>\n";


                print "</table>\n";

                print "<p align=\"center\"><input type=\"submit\" value=\"Submit\" /></p>\n";

                print "</form>\n";

                // script to update date and time with datetimepicker at 30 minute intervals
                print "<script type=\"text/javascript\">\n";
                print "	$( document ).ready(function() {\n";
                print " jQuery.datetimepicker.setLocale('en');\n";
                print "		$( \"#datetime\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
                print "	});\n";
                print "</script>\n";
                
            }

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
        print "<p align=\"center\"><a href=\"fam-drills-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>