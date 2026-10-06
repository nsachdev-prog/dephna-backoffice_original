#!/usr/bin/php

<?php
// gets the previous meter readings for all manual meters and commits them to UtlUsedNew
// it runs once a month for all sites
$cronjob = 1;
chdir(__DIR__);
include_once('../public_html/backoffice/settings.php');

// $reportEmail = "david@davidsherwood.co.uk";
$reportEmail = "rajive@dephna.com,david@davidsherwood.co.uk";

$resultMessage = "<p>Started at " . date("Y-m-d H:i:s") . "</p>\r\n";

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    list($year,$month,$day) = explode("-",date("Y-m-d"));
    $thismonth = "$year-$month";
    list($prevyear,$prevmonth) = explode("-",date("Y-m",strtotime("-1 month")));
    if ($month == "03") {
        $prevmonth = "02"; // feb 28/29 fix
    }
    $now = time();
    $subs = array(
        "Electricity" => "0",
        "Hot" => "1",
        "Cold" => "2",
        "Gas" => "3"
    );
    $reading = 0;
    $failed = 0;
    $commited = 0;
    $today = date("Y-m-d");
    $sql = "SELECT * FROM `UtlCollectors` ORDER BY sitename, unit, sub";
    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
        while ($row = $result->fetch_assoc()) {
            $mid       = $row['ID'];
            $tsiteid   = $row['siteid'];
            $tsitename  = $row['sitename'];
            $collector = $row['collector'];
            $sub       = $row['sub'];
            $title     = $row['title'];
            $unit      = $row['unit'];
            $commodity = $row['commodity'];
            $custref   = $row['custref']; // possibly hard coded into UtlMeters

            if (strlen($title) > 8) { $title = substr($title, 0, 8); }
            
            $udatefrom = "";
            $monthfrom = "";

            // get previous udatefrom and custref if we haven't got it from UtiCollectors
            // $unitPrefixes are in the settings file
            // remove any alphabetic characters from the unit name
			$unitsrch = preg_replace("/[A-Za-z]/", "", $unit); // unit may have two meters with letters
            $pfxunit = $unitPrefixes[$tsitename] . " " . $unitsrch; // unit names in bookings are prefixed
            $sql = "SELECT b.custid, datefrom
                    FROM bookings AS b
                    LEFT JOIN units AS u ON b.unitid = u.ID
                    WHERE u.title = '$pfxunit'
            ";
            if (($custresult = $dbcnx->query($sql)) AND ($custresult->num_rows)) {
                $custrow  = $custresult->fetch_assoc();
                if (!$custref) {
                    $custref  = $custrow['custid'];
                }
                $datefrom = $custrow['datefrom'];
                $monthfrom = date("Y-m",$datefrom);
            }
            

            if ($custref) {

                $previous = 0;
                $prevused = 0;
                $prevcharge = 0;

                if ($monthfrom == $thismonth) {
                    // New customer. Try to get meter reading from this months inventory
                    // a client may have just moved into the unit
                    $sql = "SELECT meter
                            FROM inventory_meters
                            WHERE custref = '$custref'
                            AND `inout` = 'in'
                            AND mid = '$mid'
                    ";
                    if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
                        $prevrow = $prevresult->fetch_assoc();
                        $previous = $prevrow['meter'];
                        $udatefrom = date("Y-m-d",$datefrom);
                    }
                }

                if ($previous == 0) {
                    // try to get previous reading from last month
                    $sql = "SELECT `meter`, `year`, `month`, `day`, `used`, `charge`
                        FROM `UtlUsedNew`
                        WHERE `custref` = '$custref'
                        AND `siteid`    = '$tsiteid'
                        AND `unit`      = '$unit'
                        AND `commodity` = '$commodity'
                        AND `year`      = '$prevyear'
                        AND `month`     = '$prevmonth'
                    ";
                    if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
                        $prevrow = $prevresult->fetch_assoc();
                        $previous = $prevrow['meter'];
                        $prevused = $prevrow['used'];
                        $prevcharge = $prevrow['charge'];
                        $udatefrom = "{$prevrow['year']}-{$prevrow['month']}-{$prevrow['day']}";
                    }
                }

                // get rate
                // try customer rate first
                $sql = "SELECT rate FROM UtlCommodities
                WHERE sitename='$tsitename' AND commodity = '$commodity' AND custref = '$custref'
                ";
                $rateresult = $dbcnx->query($sql);
                if (($rateresult) && ($rateresult->num_rows)) {
                    $raterow = $rateresult->fetch_assoc();
                    $rate = $raterow['rate'];
                } else {
                    // get default rate for site
                    $sql = "SELECT rate FROM UtlCommodities
                        WHERE sitename='$tsitename' AND commodity = '$commodity' AND custref = 'Default'
                    ";
                    $rateresult = $dbcnx->query($sql);
                    if (($rateresult) && ($rateresult->num_rows)) {
                        $raterow = $rateresult->fetch_assoc();
                        $rate = $raterow['rate'];
                    }
                }
                if (!$rate) {
                    $rate = 0;
                }

            } else {
                $custref = "-NF";
                $previous = 0;
                $prevused = 0;
                $prevcharge = 0;
                $rate = 0;
            }

            $used   = $reading - $previous;
            $charge = $used * $rate;

            if ($custref) {
                
                // commit the charge
                $sql = "INSERT INTO UtlUsedNew VALUES (
                    null,
                    '$year',
                    '$month',
                    '$day',
                    '$tsiteid',
                    '$tsitename',
                    '$unit',
                    '$custref',
                    '',
                    '',
                    '0',
                    '{$subs[$commodity]}',
                    '$commodity',
                    '$udatefrom',
                    '$prevused',
                    '$prevcharge',
                    '0',
                    '$previous',
                    '$used',
                    '$rate',
                    '$charge',
                    '',
                    '$now',
                    ''
                )";
                if ($dbcnx->query($sql)) {
                    $commited++;
                    $resultMessage .= "Committed: DP$custref $tsitename $unit $commodity<br>\r\n";
                } else {
                    $failed++;
                    $resultMessage .= "FAILED: DP$custref $tsitename $unit $commodity<br>\r\n";
                }
            }
        }

    } else {
        $resultMessage .= "No Manual Meters Found!<br>\n";
    }

    $dbcnx->close();
} else {
    $resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error . "<br>";
}
// Mail the processing report to $reportEmail
$resultMessage .= "<p>Committed: $commited<br>Failed: $failed</p>";
$resultMessage .= "<p>Finished at " . date("Y-m-d H:i:s") . "</p>\r\n";
$subject = "Utilities Manual Committed Report";
$from = "Robot <robot@dephna.com>";
$mime_boundary = md5(uniqid(time()));

$headers  = "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

$message = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "<p><b>Utilities Manual Committed - $today</b></p>\n";
if ($resultMessage) { $message .= "<p>$resultMessage</p>"; }
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";
mail($reportEmail, $subject, $message, $headers, $returnaddress);
?>
