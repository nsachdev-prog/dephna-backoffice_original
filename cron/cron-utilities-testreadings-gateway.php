#!/usr/bin/php

<?php
// This script reads the meter readings from the meters in the database and saves them to the database
// It is designed to be run as a cron job
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
$cronjob = 1;
chdir(__DIR__);

// set time out
set_time_limit(60);

include('../public_html/backoffice/settings.php');

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	require_once '../Phpmodbus/Phpmodbus/ModbusMaster.php';
	require_once '../Phpmodbus/Phpmodbus/ModbusMasterTcp.php';

	$gateways = array(
		 '8' => "55",
         '9' => "55",
        '10' => "55",
        '11' => "55",
        '12' => "55",
        '13' => "55",
        '14' => "55",
        '15' => "55",
        '16' => "55",
        '17' => "55",
        '18' => "55",
        '19' => "55",
        '20' => "55",
        '21' => "55",
        '22' => "55",
        '23' => "55",
        '24' => "55",
        '25' => "55"
	);

	foreach($gateways AS $gatewayid => $alert) {

        // get gateway details
        echo "Gateway: $gatewayid\n";

        $sql = "SELECT g.*, s.title AS sitename
                FROM UtlGateways AS g
                LEFT JOIN sites AS s ON g.siteid = s.ID
                WHERE g.ID = '$gatewayid'
        ";
        if ($gresult = $dbcnx->query($sql) AND $gresult->num_rows) {
            $grow = $gresult->fetch_assoc();
            $ipaddress = $grow['ipaddress'];
            $port      = $grow['port'];
            $gtitle    = $grow['title'];
            $siteid    = $grow['siteid'];
            $sitename  = $grow['sitename'];

            echo "Site: $sitename  Gateway: $gtitle\n";

            // get meters to read
            $sql = "SELECT * FROM `UtlMeters` WHERE gatewayid = '$gatewayid' AND commodity = 'Electricity'";
            if ($mresult = $dbcnx->query($sql) AND $mresult->num_rows) {

                while ($mrow = $mresult->fetch_assoc()) {
                    $siteid    = $mrow['siteid'];
                    $deviceid  = $mrow['deviceid'];
                    $typeid    = $mrow['typeid'];
                    $unitid    = $mrow['unitid'];
                    $unitname  = $mrow['unitname'];
                    $commodity = $mrow['commodity'];
                    $recData   = array();
                    $failure   = "";

                    if ($unitname == "S1A" OR $unitname == "S2" OR $unitname == "Dephna") {
                        $alert = 800;
                    } else {
                        $alert = 55;
                    }

                    echo "Reading $unitname $commodity\n";

                    // get register start address and qty
                    $sql = "SELECT `title`, `start`, `qty` FROM `UtlTypes` WHERE `ID` = '$typeid'";
                    if (($tresult = $dbcnx->query($sql)) && ($tresult->num_rows)) {
                        $trow  = $tresult->fetch_assoc();
                        $start  = $trow['start'];
                        $qty    = $trow['qty'];
                        $ttitle = $trow['title'];

                        echo "Type $ttitle\n";

                        $modbus = new ModbusMaster($ipaddress, $port, 'TCP');

                        try {
                            if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
                                $recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
                                echo "Read Multiple Input Registers\n";
                            } else {
                                $recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
                                echo "Read Multiple Registers\n";
                            }
                        }
                        catch (Exception $e) {

                            echo $e->getMessage() . "\n";

                            sleep(1); // wait 1 second

                            try { // try again 1
                                if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
                                    $recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
                                } else {
                                    $recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
                                }	
                            }
                            catch (Exception $e) {

                                echo $e->getMessage() . "\n";

                                sleep(1); // wait 1 second

                                try { // try again 2
                                    if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
                                        $recData = $modbus->readMultipleInputRegisters($deviceid, $start, $qty);
                                    } else {
                                        $recData = $modbus->readMultipleRegisters($deviceid, $start, $qty);
                                    }	
                                }
                                catch (Exception $e) {
                                    echo $e->getMessage() . "\n";
                                }
                            }
                        }

                        // find 'Current%' inredata from meter type and if it's over 875 send a message
                        $sql = "SELECT 	
                            `title`,
                            `unit`,
                            `start`,
                            `length`,
                            `divisor`
                            FROM `UtlRegisters`
                            WHERE `typeid` = '$typeid' AND `title` LIKE 'Current%'
                            ORDER BY `start`
                        ";
                        $registers = $dbcnx->query($sql);
                        if (($registers) && ($registers->num_rows)) {
                            $highReading = array();
                            while ($reg1 = $registers->fetch_assoc()) {
                                $title   = $reg1['title'];
                                $unit    = $reg1['unit'];
                                $start   = $reg1['start'];
                                $length  = $reg1['length'];
                                $divisor = $reg1['divisor'];

                                if ($length) {

                                    $read = "";
                                    if ($unit == "ASCII") {
                                        for ($n=0; $n<$length; $n++) {
                                            $reg = $start + $n;
                                            $thisread = $recData[$reg];
                                            $thisread = chr($thisread);
                                            $read .= $thisread;
                                        }
                                    } else {
                                        for ($n=0; $n<$length; $n++) {
                                            $reg = $start + $n;
                                            $thisread = dechex($recData[$reg]);
                                            $thisread = str_pad($thisread, 2, "0", STR_PAD_LEFT);
                                            $read .= $thisread;
                                        }

                                        if ($typeid == 8) { // D340 Meter
                                            if ($length == 6) {
                                                $bit1 = substr($read, 8, 4);
                                                $bit2 = substr($read, 4, 4);
                                                $bit3 = substr($read, 0, 4);
                                                $read = $bit1.$bit2; // Ignore byte3. I don't know why this works!
                                            } else {
                                                $msb = substr($read, 4, 4);
                                                $lsb = substr($read, 0, 4);
                                                $read = $msb.$lsb;
                                            }
                                        }

                                        if (($typeid == 6) OR (($typeid == 7) AND ((strpos($title, "(Float)") !== FALSE) OR $title == "Meter Reading"))) {
                                            // D35 Meter or Water Meter Float Value)
                                            $read = unpack('G', hex2bin($read));
                                            $read = $read[1];
                                            $read = number_format($read,'3','.','');
                                        } else {
                                            $read = hexdec($read);
                                            if ($read) {
                                                if ($divisor) {
                                                    $read = $read / $divisor;
                                                }
                                            }
                                        }
                                    }
                                    if ($read > $alert) {
                                        $highReading[] = $read;
                                        echo "High Reading: $title $read $unit\n";
                                    } else {
                                        echo "Current Reading: $title $read $unit\n";
                                    }
                                }
                            }
                            if (count($highReading)) {
                                $today = date("Y-m-d");
                                $time = date("H:i:s");
                                $now = time();
                                $sql = "INSERT INTO UtlHighReadings VALUES (
                                    null,
                                    '$today',
                                    '$time',
                                    '0',
                                    '$siteid',
                                    '$unitid',
                                    '$unitname',
                                    '$commodity',
                                    '$typeid',
                                    '{$highReading[0]}',
                                    '{$highReading[1]}',
                                    '{$highReading[2]}',
                                    '$now',
                                    'Robot'
                                )";
                                if ($dbcnx->query($sql)) {
                                    echo "Saved $sitename $unitname $commodity\n";
                                } else {
                                    echo "Failed to Save $sitename $unitname $commodity\n" . $dbcnx->error . "\n";
                                }
                            }
                        }

                    } else {
                        echo "Meter Type Not Found\n";
                        $failure = "Meter Type Not Found";
                    }

                }

            } else {
                echo "No Meters Found On Gateway\n";
            }

        } else {
            echo "Gateway $gatewayid Not Found\n";
        }
	}

	$dbcnx->close();

} else {
	echo "Could Not Connect To Database!\n";
}
