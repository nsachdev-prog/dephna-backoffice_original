<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Gateways CSV Import</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();

        $filename = "utilities-gateways1.csv";
        if(file_exists($filename)) {
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                $lines[$n] = str_replace('"', "", $lines[$n]);
                list($siteid,$title,$ipaddress,$port) = explode(",",$lines[$n]);
                $siteid    = trim($siteid);
                $title     = trim($title);
                $ipaddress = trim($ipaddress);
                $port      = trim($port);

                if ($siteid) {
                    $sql = "INSERT INTO UtlGateways VALUES (
                        null,
                        '$siteid',
                        '$title',
                        '$ipaddress',
                        '$port',
                        '',
                        '$now',
                        '{$_SERVER['username']}'
                    )";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "Gateway $siteid - $title - $ipaddress - $port Added<br />";
                    } else {
                        $resultMessage .= "Gateway $siteid - $title - $ipaddress - $port Could <b>NOT</b> be added<br />" . $dbcnx->error;
                    }
                }
            }
        } else {
            $resultMessage .= "File $filename Not Found!<br>";
        }
        $dbcnx->close();
    } else {
        $resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
    }
} else {
	$resultMessage .= "You are not logged in!";
}
if ($resultMessage) {
    print "<p align=\"center\">$resultMessage</p>\n";
}
?>
