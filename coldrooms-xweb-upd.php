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
            $unitid = $_REQUEST['unitid'];
            if ($siteid AND $unitid) {

                if ($_POST['posted']) {
                    $setpoint = strip_tags($_POST['setpoint']);
                    $engineermode = strip_tags($_POST['engineermode']);
                    if (!is_numeric($setpoint)) {
                        $ersetpoint = "Must be numeric"; $e++;
                    }

                    if (!$e) {
                        $sql = "UPDATE units SET
                                setpoint = '$setpoint',
                                engineermode = '$engineermode'
                                WHERE ID = '$unitid'
                        ";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage = "Record successfuly updated";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitetitle - $title','Units','$now')");
                        } else {
                            $resultMessage = "Could not update record<br />" . $dbcnx->error;
                        }
                    }
                }

                // get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['title'];

                    // get unit
                    $sql = "SELECT title, unittype, setpoint, engineermode FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unittype = $row['unittype'];
						$unitname = $row['title'];
                        $setpoint = $row['setpoint'];
                        $engineermode = $row['engineermode'];
                        
                        print "<p align=\"center\" class=\"larger\">$sitename - $unitname<br />$unittype</p>\n";

                        print "<form name=\"updform\" method=\"post\" action=\"coldrooms-xweb-upd.php?siteid=$siteid&amp;unitid=$unitid\">\n";
                        print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                        print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Set Point:</b></td>\n";
                        print "	<td valign=\"top\"><input name=\"setpoint\" type=\"text\" class=\"element_small\" maxlength=\"10\" value=\"$setpoint\" />";
                        if ($ersetpoint) { print "<br /><span class=\"redtext\">$ersetpoint</span>"; }
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Engineer Mode:</b></b></td>\n";
						print "	<td><input type=\"checkbox\" name=\"engineermode\""; if ($engineermode) { print " CHECKED"; } print " value=\"1\"></td>\n";
						print "</tr>\n";

                        print "</table>\n";

                        print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                        print "</form>\n";

					} else {
						$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
					}
                    
                } else {
                    $resultMessage .= "Could NOT get site<br />" . $result->error . "<br />";
                }

            } else {
                $resultMessage .= "Page called without site id or device id<br />";
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
    print "<p align=\"center\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid\" class=\"displayloader\">Return</a></p>";
    print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return To Sites</a></p>";
    print "</div>\n";
} else {
	include ('loggedout.php');
}
/* print "<pre>";
print_r($result);
print "</pre>"; */
include('footer.php');
?>
