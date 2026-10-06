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
            $onoff = $_REQUEST['onoff'];

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

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname - TEST MODE</p>\n";

                    if ($testUntil) {
                        $testUntil = date("H:i", $testUntil);
                        print "<p align=\"center\" class=\"larger\">On Test Until: $testUntil</p>\n";
                    } else {
                        print "<p align=\"center\" class=\"larger\">Not Currently On Test</p>\n";
                    }

                    include_once('XWebGetAccessToken.php');

                    if ($access_token) {

                        if ($_GET['swithoff'] == 1) {

                            

                        } else {

                            print "<p align=\"center\"><b>Are You Sure?</b></p>";
                            print "<p align=\"center\">";
                            print "	<a href=\"coldrooms-xweb-testmode-off.php?siteid=$siteid&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;onoff=$onoff&amp;switchoff=1\">Yes Switch Off</a>&nbsp;|&nbsp;<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;onoff=$onoff\">No DON'T Switch Off</a>";
                            print "</p>\n";

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

	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;onoff=$onoff\" class=\"displayloader\">Return To Unit</a> | ";
	print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\" class=\"displayloader\">Return To Site</a> | ";
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
