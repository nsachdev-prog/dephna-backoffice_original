<?php
$page = "Utilities";
$subpage = "Test Meters";
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

			// get meter
			$mid = $_REQUEST['mid'];

			// get alert level
			$alert = $_REQUEST['alert'];

			$sql = "SELECT * FROM UtlMeters WHERE ID = '$mid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$siteid    = $row['siteid'];
				$typeid    = $row['typeid'];
				$unitid    = $row['unitid'];
				$unitname  = $row['unitname'];
				$commodity = $row['commodity'];
				$notes	   = $row['notes'];

				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				}

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname - $commodity</p>\n";

				print "<p align=\"center\">$notes<br>";
                print "June 27th - 6:20pm to 10pm - Data connection Issue";
                print "</p>";

                print "<p align=\"center\">";
				print "<a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert\">By Date</a> | ";
                print "<a href=\"utilities-testmeter-history2.php?mid=$mid&amp;alert=$alert\">By Weather</a> | ";
                print "High Current";
				print "</p>";

                print "<p align=\"center\"><a href=\"utilities-testmeters-list.php\">Meters On Test</a>\n";

				$sql = "SELECT * FROM `UtlRegisters`
						WHERE `typeid` = '$typeid' AND `report` = '1'
						ORDER BY `start`
				";
				$registers = $dbcnx->query($sql);
				if (($registers) && ($registers->num_rows)) {

                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
                    print "<tr>\n";
                    print "	<td style=\"border-bottom:1px solid #999999;white-space: nowrap;\"><b>Date</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
                    while ($row = $registers->fetch_assoc()) {
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>".$row['title']."</b></td>\n";
                    }
                    print "<td style=\"border-bottom:1px solid #999999\"><b>Outside Temp</b></td>\n";
                    print "</tr>\n";

                    $registers->data_seek(0);
                    
                    $sql = "SELECT * FROM `TestReadings` WHERE `MeterID` = '$mid' ORDER BY `Date` DESC, `Time` DESC";
                    $result = $dbcnx->query($sql);
                    if (($result) && ($result->num_rows)) {

                        $lastdate = "";
                        $times = array();

                        while ($row = $result->fetch_assoc()) {
                            $date = $row['Date'];
                            $time = $row['Time'];
                            $time = substr($time, 0, 5);
                            if (in_array($time, $times, TRUE)) {
                                $timestyle = " background-color:#f0f060";
                            } else {
                                $timestyle = "";
                            }
                            $recData = unserialize($row['Data']);

                            $found = 0;
                            $saved = "";

                            while ($reg1 = $registers->fetch_assoc()) {
                                $title   = $reg1['title'];
                                $unit    = $reg1['unit'];
                                $start   = $reg1['start'];
                                $length  = $reg1['length'];
                                $divisor = $reg1['divisor'];

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
                                if ($read == 0) {
                                    $found++;
                                    $saved .= "	<td style=\"text-align:right;\">".number_format($read,'3','.','')."</td>\n";
                                }
                                elseif ($read > $alert) {
                                    $found++;
                                    $saved .= "	<td style=\"color:red;text-align:right;\">".number_format($read,'3','.','')."</td>\n";
                                } else {
                                    $saved .= "	<td style=\"text-align:right;\">".number_format($read,'3','.','')."</td>\n";
                                }

                            }
                            $registers->data_seek(0);
                            if ($found) {
                                $times[] = $time;
                                if ($date != $lastdate AND $lastdate != "") {
                                    print "<tr>\n";
                                    print "	<td style=\"border-top:1px solid #999999;border-bottom:1px solid #999999\" colspan=\"".($registers->num_rows+3)."\">&nbsp;</td>\n";
                                    print "</tr>\n";
                                }
                                $lastdate = $date;
                                print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                print "	<td style=\"text-align:right;\">$date</td>\n";
                                print "	<td style=\"text-align:right;$timestyle\"><a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert&amp;time=$time\">$time</a></td>\n";
                                print $saved;

                                // get outside temperature
                                $sql = "SELECT * FROM `weather_data` WHERE `date` = '$date'";
                                $result2 = $dbcnx->query($sql);
                                if (($result2) && ($result2->num_rows)) {
                                    $row2 = $result2->fetch_assoc();
                                    $wdata = unserialize($row2['data']);
                                    $hour = substr($time, 0, 2);
                                    $hour = intval($hour);
                                    $temp = $wdata['hourly']['temperature_2m'][$hour];
                                    print "	<td style=\"text-align:right;\">$temp</td>\n";
                                } else {
                                    print "	<td style=\"text-align:right;\">&nbsp;</td>\n";
                                }
                                print "</tr>\n";
                                $saved = "";
                                $found = 0;
                            } else {
                                $saved = "";
                                $found = 0;
                            }
                        }
                    }
                    
                    print "<tr>\n";
                    print "	<td style=\"border-top:1px solid #999999\" colspan=\"".($registers->num_rows+3)."\">&nbsp;</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

				} else {
					$resultMessage .= "Registers not found<br>" . $dbcnx->error;
				}

			} else {
				$resultMessage .= "Meter not found<br>" . $dbcnx->error;
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"utilities-testmeters-list.php\">Meters On Test</a>\n";
// print weather data for testing
/* print "<pre>";
print_r($weatherData);
print "</pre>";
print "</td>\n"; */
include('footer.php');
?>
