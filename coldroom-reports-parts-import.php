<?php
include('settings.php');
include('session.php');
print "<p><b>Import coldroom-reports-parts.csv</p>\n";

if ($_SESSION['user']) {
	
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();
        $filename = "coldroom-reports-parts.csv";
        $file = fopen($filename, "r");
        $i = 0;
        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            if ($i) {
                list($group, $part, $afcost, $dephnacost) = $row;
                $group = $dbcnx->real_escape_string($group);
                $part = $dbcnx->real_escape_string($part);
                $dephnacost = trim($dephnacost);
                $sql = "INSERT INTO cr_reports_parts VALUES (
                    null,
                    '$group',
                    '$part',
                    '$afcost',
                    '$dephnacost',
                    '$now',
                    '{$_SESSION['username']}'
                )";
                if ($dbcnx->query($sql)) {
                    $resultMessage .= "Imported $part<br>";
                } else {
                    $resultMessage .= "Could Not Import $part<br>" . $dbcnx->error;
                }
            }
            $i++;
        }
        fclose($file);

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
