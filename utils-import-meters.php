<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Utils GAS CSV Import</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();

        $filename = "utils-manual1.csv";
        if(file_exists($filename)) {

            $now = time();
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                list($siteid,$sitename,$unit,$custref,$collector,$ourname,$counter,$sub,$commodity,$previous,$meter,$used,$rate,$charge) = explode(",",$lines[$n]);
                
                $siteid    = trim($siteid);
                $sitename  = trim($sitename);
                $unit      = trim($unit);
                $custref   = trim($custref);
                $collector = trim($collector);
                $ourname   = trim($ourname);
                $counter   = trim($counter);
                $sub       = trim($sub);
                $commodity = trim($commodity);
                $previous  = trim($previous);
                $meter     = trim($meter);
                $used      = trim($used);
                $rate      = trim($rate);
                $charge    = trim($charge);
                
                $sql = "INSERT INTO UtlCollectors VALUES (
                    null,
                    '$siteid',
                    '$sitename',
                    '$collector',
                    '$counter',
                    '$sub',
                    '$ourname',
                    '$unit',
                    '$commodity',
                    '0',
                    '$custref',
                    'Imported'
                )";
                $result = $dbcnx->query($sql);
                if ($result) {
                    $resultMessage .= "Imported $collector $sitename $unit<br />";
                    // $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Counter $site $collector $counter $unit','$subpage','$now')");
                } else {
                    $resultMessage = "<b>Could NOT Import $collector $sitename $unit</b><br />" . $dbcnx->error;
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
