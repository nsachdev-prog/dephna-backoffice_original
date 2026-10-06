<?php
include('settings.php');

# open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    // delete all R & P types
    // $sql = "DELETE FROM inv_headers WHERE txtype='R' OR txtype='P'";
	// $result = $dbcnx->query($sql);

    $result = $dbcnx->query("SELECT * FROM bookings_an ORDER BY bkid");
    if (($result) AND ($result->num_rows)) {
        $resultMessage .= "<table cellspacing=\"4\">\n";

        while ($row = $result->fetch_assoc()) {

            $bkid     = $row['bkid'];

            $bresult = $dbcnx->query("SELECT 1 FROM bookings WHERE ID = '$bkid'");
            if (($bresult) AND ($bresult->num_rows)) {

                $resultMessage .= "<tr>\n";
                $resultMessage .= "<td>$bkid</td>\n";
                $resultMessage .= "<td>Found</td>\n";
                $resultMessage .= "</tr>\n";

            } else {

                $resultMessage .= "<tr>\n";
                $resultMessage .= "<td>$bkid</td>\n";
                $resultMessage .= "<td>NOT FOUND</td>\n";
                $resultMessage .= "</tr>\n";

            }
        }
        $resultMessage .= "</table>\n";
    }                    
    $dbcnx->close();

} else {
    $resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
}
if ($resultMessage) {
    print "$resultMessage";
}
?>
