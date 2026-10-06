<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Meters CSV Import</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();
        $filename = "utilities-meters.csv";
        if(file_exists($filename)) {
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                $lines[$n] = str_replace('"', "", $lines[$n]);
                list($siteid,$gatewayid,$deviceid,$typeid,$unitid,$unitname,$commodity) = explode(",",$lines[$n]);
                if ($siteid) {
                    $commodity = trim($commodity);
                    $sql = "INSERT INTO UtlMeters VALUES (
                        null,
                        '$siteid',
                        '$gatewayid',
                        '$deviceid',
                        '$typeid',
                        '$unitid',
                        '$unitname',
                        '$commodity',
                        '',
                        '',
                        '$now',
                        '{$_SESSION['username']}'
                    )";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "<b>Added $siteid $gatewayid $deviceid $typeid $unitid $unitname $commodity</b><br />";
                    } else {
                        $resultMessage .= "Could not add new record to database<br />" . $dbcnx->error;
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
