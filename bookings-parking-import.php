<?php
include('settings.php');
include('session.php');
print "<p><b>Add Parking</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();

        $filename = "bookings-parking.csv";
        if(file_exists($filename)) {
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                list($siteid,
                    $sitename,
                    $unitid,
                    $unitname
                ) = explode(",",$lines[$n]);
                $siteid   = trim($siteid);
                $sitename = trim($sitename);
                $unitid   = trim($unitid);
                $unitname = trim($unitname);

                if ($siteid) {
                    $sql = "INSERT INTO bookings VALUES (
                        null,
                        '$siteid',
                        '$unitid',
                        'Car Parking',
                        'on',
                        '20199',
                        '0',
                        '1609459200',
                        '0',
                        '',
                        '0.00',
                        'Monthly',
                        '0',
                        '0',
                        '0',
                        '{$_SESSION['user']}',
                        '$now',
                        '',
                        '1633046400',
                        'parking',
                        'Low',
                        '0',
                        '0',
                        '0',
                        '0',
                        '0',
                        '0',
                        '0',
                        '0',
                        '0',
                        '',
                        '0',
                        ''
                    )";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $newbkid = $dbcnx->insert_id;
                        $resultMessage .= "Parking $siteid - $unitid Added<br />";
                        list($pre,$unit) = explode(" ",$unitname);
                        $sql = "INSERT INTO parking VALUES (
                            null,
                            '$newbkid',
                            '$unit',
                            '0.00',
                            '',
                            '$siteid',
                            '$sitename',
                            '700',
                            'Dephna Group',
                            '0.00',
                            'Yes',
                            ''
                        )";
                        $ipresult = $dbcnx->query($sql);
                        if ($ipresult) {
                            $resultMessage .= "Parking Bay $bay Added<br />";
                        }
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
