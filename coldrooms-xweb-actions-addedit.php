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
    
    print "<p align=\"center\" class=\"larger\">Actions</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $aid = $_REQUEST['aid'];
            $nid = $_REQUEST['nid'];

			if ($aid) {

                // get action
                $sql = "SELECT * FROM xweb_actions WHERE ID = '$aid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $xdatetime = $row['xdatetime'];
                    $siteid    = $row['siteid'];
                    $unitid    = $row['unitid'];
                    $custref   = $row['custref'];
                    $compname  = $row['compname'];
                    $xstatus   = $row['xstatus'];
                    $adetails  = $row['details'];
                    $startedby = $row['startedby'];
                    $cleared   = $row['cleared'];
                    $clearedby = $row['clearedby'];

                    if ($cleared) {
                        $statustxt = "Cleared";
                    } else {
                        $statustxt = "Ongoing";
                    }

                    // get site
                    $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $sitename = $row['title'];

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

                        print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
                        
                        print "<tr>\n";
                        print " <td align=\"right\"><b>Date/Time</b></td>\n";
                        print " <td>$xdatetime</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>Site/Unit</b></td>\n";
                        print " <td>$sitename - $unitname</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>Type</b></td>\n";
                        print " <td>$adetails</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>Customer</td>\n";
                        print " <td>$compname</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>Status</b></td>\n";
                        print " <td>$statustxt</td>\n";
                        print "</tr>\n";

                        if ($cleared) {

                            print "<tr>\n";
                            print " <td align=\"right\"><b>Cleared On</b></td>\n";
                            print " <td>$cleared</td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print " <td align=\"right\"><b>Cleared By</b></td>\n";
                            print " <td>$clearedby</td>\n";
                            print "</tr>\n";

                        }

                        print "</table>\n";

                        print "<p align=\"center\" class=\"larger\">Add Action</p>\n";

                        if ($_POST['posted']) {

                            $title   = strip_tags($_POST['title']);
                            $details = strip_tags($_POST['details']);
                            $clear   = strip_tags($_POST['clear']);

                            if (!$title AND !$details) {
                                $erdetails = "You must enter something!"; $e++;
                            }

                            if (!$e) {

                                $title   = addslashes($title);
                                $details = addslashes($details);
                                if ($clear) {
                                    $cleared = date("Y-m-d H:i");
                                }
                                $now = time();
                                $datetime = date("Y-m-d H:i");

                                if ($nid) {
                                    $sql = "UPDATE xweb_actions_notes SET 
                                        title='$title',
                                        details='$details',
                                        lastupd='$now',
                                        updby='{$_SESSION['username']}'
                                        WHERE ID = '$nid'
                                    ";
                                    if ($dbcnx->query($sql)) {
                                        $resultMessage .= "Action Note Updated<br />";
									    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Action Note','Xweb','$now')");
                                    } else {
                                        $resultMessage .= "Could not update record<br />" . $dbcnx->error;
                                    }
                                } else {
                                    $sql = "INSERT INTO xweb_actions_notes VALUES (
                                        null,
                                        '$aid',
                                        '$siteid',
                                        '$unitid',
                                        '$custref',
                                        '$datetime',
                                        '$title',
                                        '$details',
                                        '$now',
                                        '{$_SESSION['username']}'
                                    )";
                                    if ($dbcnx->query($sql)) {
                                        $resultMessage .= "Action Note Created<br />";
									    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$unitname Action Note','Xweb','$now')");
                                    } else {
                                        $resultMessage .= "Could not add new record<br />" . $dbcnx->error;
                                    }
                                }

                                if ($clear) {
                                    $sql = "UPDATE xweb_actions SET 
                                        cleared='$cleared',
                                        clearedby='{$_SESSION['username']}'
                                        WHERE ID = '$aid'
                                    ";
                                    if ($dbcnx->query($sql)) {
                                        $resultMessage .= "Action Cleared<br />";
									    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Action Note','Xweb','$now')");
                                    } else {
                                        $resultMessage .= "Could not update record<br />" . $dbcnx->error;
                                    }
                                }

                            }

                        } elseif ($nid) {

                            $sql = "SELECT * FROM xweb_actions_notes WHERE nid = '$aid'";
                            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                                $row = $result->fetch_assoc();
                                $title    = $row['title'];
                                $details  = $row['details'];
                            }

                        }

                        if ((!$resultMessage) || ($e)) {

                            print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb-actions-addedit.php\">\n";
						    print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						    print "<input type=\"hidden\" name=\"aid\" value=\"$aid\" />\n";
                            print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

                            print "<tr>\n";
                            print "	<td align=\"right\" valign=\"top\"><b>Clear</b></td>\n";
                            print "	<td><input type=\"checkbox\" name=\"clear\""; if ($clear) { print " CHECKED"; } print " value=\"1\"></td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print "  <td valign=\"top\" align=\"right\"><b>Short Description</b></td>\n";
                            print "  <td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$title\" />\n";
                            if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
                            print "</td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print "	<td colspan=\"2\"><b>Full Description</b>"; if ($erdetails) { print "<br /><span class=\"redtext\">$erdetails</span>"; } print "<br />";
                            print "<textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
                            print "</tr>\n";

                            print "</table>\n";

                            print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                            print "</form>\n";

                        }
							
					} else {
						$resultMessage .= "Could NOT get site<br />" . $result->error . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT get action<br />" . $result->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without aid.<br />";
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
    print "<p align=\"center\"><a href=\"coldrooms-xweb-actions-view.php?aid=$aid\">Return To Actions</a></p>";
	print "<p align=\"center\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Return To Unit</a></p>\n";
	print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return To Top</a></p>";
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
