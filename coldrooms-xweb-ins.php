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
$xwebsites = array(
    17 => array(
        "prefix" => "PRT",
        "temperature" => 0,
        "onoff" => 19,
        "doorswitch" => 4,
        "dooropen" => 27,
        "defrost" => 13
    ),
    5  => array(
        "prefix" => "",
        "temperature" => 0,
        "onoff" => 21,
        "doorswitch" => 4,
        "dooropen" => 30,
        "defrost" => 13
    ),
    34 => array(
        "prefix" => "BRT",
        "temperature" => 0,
        "onoff" => 19,
        "doorswitch" => 4,
        "dooropen" => 31,
        "defrost" => 13
    ),
    16 => array(
        "prefix" => "ACT",
        "temperature" => 0,
        "onoff" => 23,
        "doorswitch" => 4,
        "dooropen" => 36,
        "defrost" => 13
    ),
    28 => array(
        "prefix" => "190-",
        "temperature" => 0,
        "onoff" => 23,
        "doorswitch" => 4,
        "dooropen" => 36,
        "defrost" => 13
    )
);
if ($_SESSION['user']) {
	
    print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
    
    print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

        // open database
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if(!$dbcnx->connect_error) {

            $siteid = $_REQUEST['siteid'];

            print "<p>Siteid $siteid</p>\n";

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
                            $unitname = str_replace($xwebsites[$siteid]['prefix'],"",$device['name']);
                            $setpoint = $device['points'][3]['value'];
                            $sql = "SELECT ID AS unitid
                                FROM units
                                WHERE siteid = '$siteid' AND title LIKE '%$unitname%'
                            ";
                            if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
                                $urow = $uresult->fetch_assoc();
                                $unitid  = $urow['unitid'];
                            } else {
                                $unitid = 0;
                            }
                            if ($unitid) {

                                $sql = "UPDATE units SET setpoint='$setpoint' WHERE ID='$unitid'";
                                $result = $dbcnx->query($sql);
                                if ($result) {
                                    print "Updated $unitid - $unitname with $setpoint<br />\n";
                                } else {
                                    print "COULD NOT update $unitid - $unitname with $setpoint<br />\n";
                                }

                            } else {
                                print "Unitid NOT Found for $unitname<br />\n";
                            }
                            
                        }

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
    print "<p align=\"center\"><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;device=$device\" class=\"displayloader\">Return</a></p>";
    print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return To Sites</a></p>";
    print "</div>\n";
} else {
	include ('loggedout.php');
}
/* print "<pre>";
print_r($result);
print "</pre>"; */
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...<br /><img src=\"images/ajax-loader.gif\"></p>");
	});
});
</script>
<?php
include('footer.php');
?>
