<?php
include('settings.php');

# open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    $sql = "SELECT *
            FROM purchases
            WHERE ID > '15101'
              AND ID < '17604'
              AND (txtype = 'I' OR txtype = 'C')  
            ORDER BY year, month, day
    ";
    $result = $dbcnx->query("SELECT * FROM debtors");
    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
        $resultMessage .= "<table cellspacing=\"4\">\n";
        while ($row = $result->fetch_assoc()) {

            $id       = $row['ID'];
            $year     = $row['year'];
            $month    = $row['month'];
            $day      = $row['day'];
            $txtype   = $row['txtype'];
            $ccid     = $row['costcentre'];
            $net      = $row['net'];
            $vat      = $row['vat'];
            $tot      = $row['total'];

            $resultMessage .= "<tr>\n";
            $resultMessage .= "<td>$id</td>\n";
            $resultMessage .= "<td>$year-$month-$day</td>\n";
            $resultMessage .= "<td>$txtype</td>\n";
            $resultMessage .= "<td>$ccid</td>\n";
            $resultMessage .= "<td align=\"right\">$net</td>\n";
            $resultMessage .= "<td align=\"right\">$vat</td>\n";
            $resultMessage .= "<td align=\"right\">$tot</td>\n";

            $sql = "INSERT INTO purchases_lines VALUES (
                    null,
                '$id',
                '$ccid',
                '',
                '$net',
                '$vat',
                '$tot'
            )";
            if ($lresult = $dbcnx->query($sql)) {
                $resultMessage .= "<td>Added</td>";
            } else {
                $resultMessage .= "<td>FAILED</td>";
            }

            $resultMessage .= "</tr>\n";
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
