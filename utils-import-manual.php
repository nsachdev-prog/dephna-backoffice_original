<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Utils GAS CSV Import</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();

        $filename = "utils-manual3.csv";
        if(file_exists($filename)) {

            $now = time();
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                list($siteid,$sitename,$unit,$custref,$collector,$counter,$sub,$commodity,$previous,$meter,$used,$rate,$charge) = explode(",",$lines[$n]);
                
                $siteid    = trim($siteid);
                $sitename  = trim($sitename);
                $unit      = trim($unit);
                $custref   = trim($custref);
                $collector = trim($collector);
                $counter   = trim($counter);
                $sub       = trim($sub);
                $commodity = trim($commodity);
                $previous  = trim($previous);
                $meter     = trim($meter);
                $used      = trim($used);
                $rate      = trim($rate);
                $charge    = trim($charge);

                $year  = date("Y");
                $month = date("m");
                $day   = date("d");
                
                // commit the charge
                $sql = "INSERT INTO UtlUsedNew VALUES (
                    null,
                    '$year',
                    '$month',
                    '$day',
                    '$siteid',
                    '$sitename',
                    '$unit',
                    '$custref',
                    '$collector',
                    '$collector',
                    '$counter',
                    '$sub',
                    '$commodity',
                    '0',
                    '',
                    '$meter',
                    '$previous',
                    '$used',
                    '$rate',
                    '$charge',
                    '$now',
                    ''
                )";
                if ($dbcnx->query($sql)) {
                    $resultMessage .= "$sitename DP$custref Unit:$unit, $commodity Success<br />\n";
                } else {
                    $resultMessage .= "$sitename DP$custref Unit:$unit, $commodity <b>Faild To Update</b><br />\n";
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
