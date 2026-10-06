<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('settings.php');
include('session.php');
print "<p><b>Dephna Registers CSV Import</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();
        $id = 16;

        $filename = "../RI-D150.csv";
        if(file_exists($filename)) {
            
            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                list($start,$title,$unit,$length,$divisor) = explode(",",$lines[$n]);
                $divisor = trim($divisor);
                $sql = "INSERT INTO UtlRegisters VALUES (
                    null,
                    '$id',
                    '$title',
                    '$unit',
                    '$start',
                    '$length',
                    '$divisor',
                    '',
                    '$now',
                    '{$_SESSION['username']}'
                )";
                $result = $dbcnx->query($sql);
                if ($result) {
                    $resultMessage .= "Record $title added<br />";
                } else {
                    $resultMessage = "Record $title Could <b>NOT</b> be added<br />" . $dbcnx->error;
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
