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

			// get filtering
			$date = $_REQUEST['date'];
			if (!$date) { $date = date("Y-m-d"); } 
			$nicedate = date("l, jS F Y", strtotime($date));
			$prevday  = date("Y-m-d",strtotime("$date -1 Day"));
			$nextday  = date("Y-m-d",strtotime("$date +1 Day"));
			$time     = $_REQUEST['time'];
			if ($time == "All") {
				$time = "";
			}

			if ($time) {
				$search = "`Time` LIKE '$time%'";
				$getweather = TRUE;
			} else {
				$search = "`Date` = '$date'";
				$getweather = FALSE;
				// get weather data for the day
				$sql = "SELECT `data` AS weatherData FROM `weather_data` WHERE `date` = '$date'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$weatherData = unserialize($row['weatherData']);
				} else {
					$resultMessage .= "Weather Data Not Found<br>" . $dbcnx->error;
				}
			}

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
				print "<p align=\"center\" class=\"larger\">$nicedate</p>\n";

				// print filter form
                print "<form name=\"addform\" method=\"post\" action=\"utilities-testmeter-history.php\">\n";
				print "<input type=\"hidden\" name=\"mid\" value=\"$mid\" />\n";
				print "<input type=\"hidden\" name=\"alert\" value=\"$alert\" />\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert&amp;date=$prevday\"><b>&lt;&lt;</b></a></td>\n";
                print "<td style=\"font-size:1.2em\"><b>Day</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
				print "<td style=\"font-size:1.2em\"><b>&nbsp;OR&nbsp;&nbsp;Time</b></td>\n";
				print "<td><select name=\"time\" class=\"element_small\" />";
				print "<option value=\"\">All</option>";
				// 24 hous in increments of 10 minutes
				for ($i=0; $i<24; $i++) {
					for ($j=0; $j<6; $j++) {
						$hour = str_pad($i, 2, "0", STR_PAD_LEFT);
						$minute = str_pad($j*10, 2, "0", STR_PAD_LEFT);
						$option = "$hour:$minute";
						if ($option == $time) {
							$selected = "selected";
						} else {
							$selected = "";
						}
						print "<option value=\"$option\" $selected>$option</option>";
					}
				}
				print "</select></td>\n";
				print "</td>\n";
                print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                print "<td style=\"font-size:3.6em\"><a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert\"><b>&bull;</b></a></td>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert&amp;date=$nextday\"><b>&gt;&gt;</b></a></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";

				print "<p align=\"center\">$notes</p>\n";

				print "<p align=\"center\">";
				print "By Date | ";
				print "<a href=\"utilities-testmeter-history2.php?mid=$mid&amp;alert=$alert\">By Weather</a> | ";
				print "<a href=\"utilities-testmeter-history3.php?mid=$mid&amp;alert=$alert\">High Current</a>";
				print "</p>";

				print "<p align=\"center\"><a href=\"utilities-testmeters-list.php\">Meters On Test</a>\n";

				$sql = "SELECT * FROM `UtlRegisters`
						WHERE `typeid` = '$typeid' AND `report` = '1'
						ORDER BY `start`
				";
				$registers = $dbcnx->query($sql);
				if (($registers) && ($registers->num_rows)) {

					// get readings
					$sql = "SELECT * FROM `TestReadings`
							WHERE `MeterID` = '$mid'
								AND $search
							ORDER BY `Date` DESC, `Time` DESC
					";
					$result = $dbcnx->query($sql);
					if ($result && $result->num_rows) {

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999;white-space: nowrap;\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
						while ($row = $registers->fetch_assoc()) {
							print "	<td style=\"border-bottom:1px solid #999999\"><b>".$row['title']."</b></td>\n";
						}
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Yesterday</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Outside Temp</b></td>\n";
						print "</tr>\n";

						$totals = array();
						$tempTotal = 0;

						while ($row = $result->fetch_assoc()) {
							$date = $row['Date'];
							$getdate = $date;
							$date = date("d-m", strtotime($date));
							$time = $row['Time'];
							$gettime = $time;
							$time = substr($time, 0, 5);
							$recData   = $row['Data'];

							if ($getweather) {
								$sql = "SELECT `data` AS weatherData FROM `weather_data` WHERE `date` = '$getdate'";
								if ($wresult = $dbcnx->query($sql) AND $wresult->num_rows) {
									$wrow = $wresult->fetch_assoc();
									$weatherData = unserialize($wrow['weatherData']);
								} else {
									$resultMessage .= "Weather Data Not Found<br>" . $dbcnx->error;
								}
							}

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

									$read = getReading($recData,$typeid,$start,$length,$divisor,$unit);
									
									if (($read > $alert) AND (strpos($title, "Current") !== FALSE)) {
										$style = "color:red;text-align:right;";
									} else {
										$style = "text-align:right;";
									}
									print "	<td style=\"$style\">".number_format($read,'3','.','')."</td>\n";
									$totals[$title] += $read;
								} else {
									print "	<td style=\"$style\">&nbsp;</td>\n";
								}
								
							}

							$yesterday = date("Y-m-d", strtotime("-1 day $getdate"));
							$sql = "SELECT `Data` FROM `TestReadings`
									WHERE `MeterID` = '$mid'
										AND `Date` = '$yesterday'
										AND `Time` = '$gettime'
							";
							if ($yresult = $dbcnx->query($sql) AND $yresult->num_rows) {
								$yrow = $yresult->fetch_assoc();
								$yrecData = $yrow['Data'];
								$yread = getReading($yrecData,$typeid,$start,$length,$divisor,$unit);
								if (($yread > $alert) AND (strpos($title, "Current") !== FALSE)) {
									$style = "color:red;text-align:right;";
								} else {
									$style = "text-align:right;";
								}
								print "	<td style=\"$style\">".number_format($yread,'3','.','')."</td>\n";
								$totals['Yesterday'] += $yread;
							} else {
								print "	<td style=\"text-align:right;\">&nbsp;</td>\n";
							}

							// weather outside temperature
							list($hour, $minute) = explode(":", $time);
							$hour = intval($hour);
							$temperature = $weatherData['hourly']['temperature_2m'][$hour];
							if (!$temperature) {
								$temperature = "NA";
							} else {
								$tempTotal += $temperature;
							}
							print "	<td style=\"text-align:right;\">$temperature&deg;</td>\n";
							print "</tr>\n";

						}

						print "<tr>\n";
						print "	<td colspan=\"2\" style=\"border-top:1px solid #999999\">Averages</td>\n";
						foreach ($totals as $total) {
							$average = $total / $result->num_rows;
							print "	<td style=\"border-top:1px solid #999999;text-align:right;\">".number_format($average,'3','.','')."</td>\n";
						}
						$avtemp = $tempTotal / $result->num_rows;
						print "	<td style=\"border-top:1px solid #999999;text-align:right;\">".number_format($avtemp,'1','.','')."&deg;</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						$resultMessage .= "No Readings Found<br>";
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
print "</pre>"; */

// print $totals for testing
/* print "<pre>";
print_r($totals);
print "</pre>"; */

print "</td>\n";
include('footer.php');

function getReading($recData,$typeid,$start,$length,$divisor,$unit)
{
	if ($recData) {
		$recData = unserialize($recData);
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

			if ($typeid == 6 OR $typeid == 7) {
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
	} else {
		$read = 0;
	}
	return $read;
}
