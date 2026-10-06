<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Meters CSV Import</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();

        $filename = "services.csv";
        if(file_exists($filename)) {
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                $lines[$n] = str_replace('"', "", $lines[$n]);
                list($contractno,$sitename,$servname,$servtype,$eachrate,$qty,$fqcy,$frequency,$dayofweek) = explode(",",$lines[$n]);
                $dayofweek = trim($dayofweek);
                $dayofweek = str_replace("--",",",$dayofweek);
        
                if ($contractno) {
                    $sql = "INSERT INTO services VALUES (
                        null,
                        '1688',
                        '$contractno',
                        '2024-06-01',
                        '$servname',
                        '$servtype',
                        '$sitename',
                        '$fqcy',
                        '$frequency',
                        '$dayofweek',
                        '',
                        '$qty',
                        '',
                        '$eachrate',
                        '',
                        '$now',
                        'Import'
                    )";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "Record $sitename - $contractno - $servtype added<br />";
                    } else {
                        $resultMessage .= "Record $sitename - $contractno - $servtype Could <b>NOT</b> be added<br />" . $dbcnx->error;
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
