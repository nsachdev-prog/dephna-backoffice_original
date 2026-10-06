<?php
include('settings.php');
include('session.php');
?>

<html>
<head>
<title>Update Rayleigh Meters</title>
<style>
body {
    font-family:arial,verdana,helvetica,sans-serif;
	color:#333333;
	font-size:11px;
}
</style>
</head>
<body>

<?php
print "<p>Update Rayleigh Meters</b></p>\n";

if ($_SESSION['user']) {
    
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

        $now = time();

        $filename = "meters-import.csv";
        if(file_exists($filename)) {
            
            $resultMessage .= "Current Rayleigh Meters That Exist<br /><br />";

            $lines = file($filename);
            for ($n=1; $n < count($lines); $n++) {
                list($meter,$title,$sensor,$sub,$siteid,$sitename,$unit,$commodity) = explode(",",$lines[$n]);
                $meter     = trim($meter);
                $title     = trim($title);
                $sensor    = trim($sensor);
                $sub       = trim($sub);
                $siteid    = trim($siteid);
                $sitename  = trim($sitename);
                $unit      = trim($unit);
                $commodity = trim($commodity);

                if ($meter) {
                    // check if exists
                    $sql = "SELECT `ID`, `collector`
                            FROM `UtlCollectors`
                            WHERE `siteid` = '$siteid'
                                AND `unit` LIKE '%$unit'
                                AND `commodity` = '$commodity'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $tcol = $row['collector'];
                        $resultMessage .= "Record $sitename - $tcol - $unit - $commodity Found<br />";
                    } else {
                        $resultMessage .= "Record $sitename - $tcol - $unit - $commodity Could <b>NOT</b> be Found<br />";
                    }
                }
            }

        } else {
            $resultMessage .= "File $filename Not Found!<br />";
        }
        $dbcnx->close();
    } else {
        $resultMessage .= "Could Not Connect To Database!<br />" . $dbcnx->connect_error;
    }
} else {
	$resultMessage .= "You are not logged in!";
}
if ($resultMessage) {
    print "<p>$resultMessage</p>\n";
}
?>

</body>
</html>
