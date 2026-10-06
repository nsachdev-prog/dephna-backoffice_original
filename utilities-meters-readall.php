<?php
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
            $comm   = $_REQUEST['comm'];
            $gid    = $_REQUEST['gid'];
            $pa     = $_REQUEST['pa'];

            if ($siteid AND ($comm OR $gid)) {

                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['title'];
                }

                print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
                print "<p align=\"center\" class=\"larger\">Live Meter Readings</p>\n";

                $sql = "SELECT * FROM UtlMeters WHERE siteid = '$siteid' AND (commodity = '$comm' OR  gatewayid = '$gid') ORDER BY unitname";
                $result = $dbcnx->query($sql);
                if (($result) && ($result->num_rows)) {

                    $totalmeters = $result->num_rows;
                    $totalreads  = 0;
                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                    print "<tr>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Commodity</b></td>\n";
                    print " <td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Gateway</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Meter Type</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Reading</b></td>\n";
                    print "</tr>\n";
                    require_once '../../Phpmodbus/Phpmodbus/ModbusMaster.php';
                    require_once '../../Phpmodbus/Phpmodbus/ModbusMasterTcp.php';
                    while ($row = $result->fetch_assoc()) {
                        $gatewayid = $row['gatewayid'];
                        $deviceid  = $row['deviceid'];
                        $typeid    = $row['typeid'];
                        $unitid    = $row['unitid'];
                        $unitname  = $row['unitname'];
                        $commodity = $row['commodity'];

                        // get customer in unit from unitid in bookings
                        $sql = "SELECT c.compname
                                FROM bookings AS b
                                LEFT JOIN users AS c ON b.custid = c.custref
                                WHERE b.unitid = '$unitid'
                        ";
                        if ($cresult = $dbcnx->query($sql) AND $cresult->num_rows) {
                            $crow = $cresult->fetch_assoc();
                            $compname = $crow['compname'];
                        } else {
                            $compname = "Empty";
                        }
                        if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }

                        $sql = "SELECT * FROM UtlGateways WHERE ID = '$gatewayid'";
                        $gresult = $dbcnx->query($sql);
                        if (($gresult) && ($gresult->num_rows)) {
                            $grow = $gresult->fetch_assoc();
                            $gtitle    = $grow['title'];
                            $ipaddress = $grow['ipaddress'];
                            $port      = $grow['port'];

                            $sql = "SELECT * FROM UtlTypes WHERE ID = '$typeid'";
                            $tresult = $dbcnx->query($sql);
                            if (($tresult) && ($tresult->num_rows)) {
                                $trow = $tresult->fetch_assoc();
                                $ttitle	= $trow['title'];
                                $first  = $trow['start'];
                                $qty    = $trow['qty'];

                                $modbus = new ModbusMaster($ipaddress, $port, 'TCP');

                                try {

                                    if ($typeid == 8 OR $typeid == 6 OR $typeid == 11) {
                                        $recData = $modbus->readMultipleInputRegisters($deviceid, $first, $qty);
                                    } else {
                                        $recData = $modbus->readMultipleRegisters($deviceid, $first, $qty);
                                    }

                                    $sql = "SELECT * FROM `UtlRegisters` WHERE `typeid` = '$typeid' ORDER BY `start`";
                                    $rresult = $dbcnx->query($sql);
                                    if (($rresult) && ($rresult->num_rows)) {

                                        while ($rrow = $rresult->fetch_assoc()) {
                                            $title	 = $rrow['title'];
                                            $unit	 = $rrow['unit'];
                                            $start   = $rrow['start'];
                                            $length  = $rrow['length'];
                                            $divisor = $rrow['divisor']; 

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
                                                            $read = number_format($read,'3','.','');
                                                        }
                                                    }
                                                }
                                            }

                                            if ($title == "Meter Reading") {

                                                print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                                print "	<td>$unitname</td>\n";
                                                print "	<td>$commodity</td>\n";
                                                print "	<td>$compname</td>\n";
                                                print "	<td>$gtitle</td>\n";
                                                print "	<td>$ttitle</td>\n";
                                                print "	<td align=\"right\">$read</td>\n";
                                                print "</tr>\n";

                                                $totalreads++;
                                                
                                            }

                                        }

                                    }

                                }

                                catch (Exception $e) {
                                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                    print "	<td>$unitname</td>\n";
                                    print "	<td>$commodity</td>\n";
                                    print "	<td>$compname</td>\n";
                                    print "	<td>$gtitle</td>\n";
                                    print "	<td>$ttitle</td>\n";
                                    print "	<td align=\"right\" class=\"redtext\">No Response</td>\n";
                                    print "</tr>\n";
                                }

                            } else {
                                print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                print "	<td>$unitname</td>\n";
                                print "	<td>$commodity</td>\n";
                                print "	<td>$compname</td>\n";
                                print "	<td>$gtitle</td>\n";
                                print "	<td align=\"right\">TID $typeid</td>\n";
                                print "	<td align=\"right\">Meter Type Not Found</td>\n";
                            }

                        } else {
                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$unitname</td>\n";
                            print "	<td>$commodity</td>\n";
                            print "	<td>$compname</td>\n";
                            print "	<td>GID $gatewayid</td>\n";
                            print "	<td align=\"right\">&nbsp;</td>\n";
                            print "	<td align=\"right\">Gateway Not Found</td>\n";
                            print "</tr>\n";
                        }

                    }

                    print "<tr>\n";
                    print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td colspan=\"6\" align=\"right\"><b>Total Meters: $totalmeters</b></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td colspan=\"6\" align=\"right\"><b>Total Readings: $totalreads</b></td>\n";
                    print "</tr>\n";

                    // display total failures
                    $totalfailures = $totalmeters - $totalreads;
                    if ($totalfailures) {
                        print "<tr>\n";
                        print "	<td colspan=\"6\" align=\"right\"><b>Total Failures: $totalfailures</b></td>\n";
                        print "</tr>\n";
                    }

                    print "</table>\n";

                } else {
                    $resultMessage = "No Meters Found<br />" . $dbcnx->error;
                }
            } else {
                $resultMessage = "Page called without siteid and comm parameters";
            }

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	print "<p align=\"center\"><a href=\"utilities-meters-list.php?siteid=$siteid&amp;comm=$comm&amp;gid=$gid&amp;pa=$pa\">Back to Meter List</a></p>\n";
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
