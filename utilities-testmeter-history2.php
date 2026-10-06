<?php
$page = "Utilities";
$subpage = "TestMeter";
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

			// get filtering
			$temp = $_REQUEST['temp'];
			if (!$temp) { $temp = 12; }

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

				// print filter form
                print "<form name=\"addform\" method=\"post\" action=\"utilities-testmeter-history2.php\">\n";
                print "<input type=\"hidden\" name=\"mid\" value=\"$mid\" />\n";
                print "<input type=\"hidden\" name=\"alert\" value=\"$alert\" />\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td style=\"font-size:1.2em\"><b>Temperature</b></td>\n";
                print "<td><input type=\"text\" name=\"temp\" value=\"$temp\" class=\"element_small\" /></td>\n";
                print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";

				print "<p align=\"center\">$notes</p>\n";

                print "<p align=\"center\">";
				print "<a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert\">By Date</a> | ";
                print "By Weather | ";
                print "<a href=\"utilities-testmeter-history3.php?mid=$mid&amp;alert=$alert\">High Current</a>";
				print "</p>";

                print "<p align=\"center\"><a href=\"utilities-testmeters-list.php\">Meters On Test</a>\n";

				$sql = "SELECT * FROM `UtlRegisters`
						WHERE `typeid` = '$typeid' AND `report` = '1'
						ORDER BY `start`
				";
				$registers = $dbcnx->query($sql);
				if (($registers) && ($registers->num_rows)) {

                    // get all dates and hours from weather_data where abs temperature = abs temp entered. data is serialized.
                    $sql = "SELECT * FROM weather_data";
                    $weatherData = array(); // array to store all weather data
                    if ($result = $dbcnx->query($sql)) {
                        $n = 0;
                        while ($row = $result->fetch_assoc()) {
                            $data = unserialize($row['data']);
                            foreach($data['hourly']['temperature_2m'] as $index => $temperature) {
                                if (($temperature > $temp) AND ($temperature < ($temp + 1))) {
                                    $weatherData[$n]['datetime'] = $data['hourly']['time'][$index];
                                    $weatherData[$n]['temperature'] = $temperature;
                                    $n++;
                                }
                            }
                        }
                    }

                    if ($weatherData) {
                        print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #999999;white-space: nowrap;\"><b>Date</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
                        while ($row = $registers->fetch_assoc()) {
                            print "	<td style=\"border-bottom:1px solid #999999\"><b>".$row['title']."</b></td>\n";
                        }
                        print "<td style=\"border-bottom:1px solid #999999\"><b>Outside Temp</b></td>\n";
                        print "</tr>\n";
                        // get the readings for the meter
                        foreach ($weatherData AS $data) {
                            list($date, $time) = explode("T", $data['datetime']);
                            list($hour, $minute) = explode(":", $time);
                            $temperature = $data['temperature'];
                            $sql = "SELECT * FROM `TestReadings` WHERE `MeterID` = '$mid' AND `date` = '$date' AND `time` LIKE '$hour%'";
                            $result = $dbcnx->query($sql);
                            if (($result) && ($result->num_rows)) {

                                while ($row = $result->fetch_assoc()) {
                                    $date = $row['Date'];
                                    $date = date("d-m", strtotime($date));
                                    $time = $row['Time'];
                                    $time = substr($time, 0, 5);
                                    $recData   = $row['Data'];
                                    $recData = unserialize($recData);

                                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                    print "	<td style=\"white-space: nowrap;\">$date</td>\n";
                                    print "	<td>$time</td>\n";

                                    $registers->data_seek(0);

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
                                            if (($read > $alert) AND (strpos($title, "Current") !== FALSE)) {
                                                $style = "color:red;text-align:right;";
                                            } else {
                                                $style = "text-align:right;";
                                            }
                                            print "	<td style=\"$style\">".number_format($read,'3','.','')."</td>\n";
                                        } else {
                                            print "	<td style=\"$style\">>&nbsp;</td>\n";
                                        }
                                    }

                                    print "	<td style=\"text-align:right;\">$temperature&deg;</td>\n";
                                    print "</tr>\n";

                                }
                            }
                        }
                        print "<tr>\n";
                        print "	<td style=\"border-top:1px solid #999999\" colspan=\"".($registers->num_rows+3)."\">&nbsp;</td>\n";
                        print "</tr>\n";

                        print "</table>\n";
                    } else {
                        $resultMessage .= "Weather Data Not Found For $temp&deg;<br>" . $dbcnx->error;
                    }


				} else {
					$resultMessage .= "Registers not found<br>" . $dbcnx->error;
				}

			} else {
				$resultMessage .= "Meter not found<br>" . $dbcnx->error;
			}

			// jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
            print "	});\n";
            print "</script>\n";

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
