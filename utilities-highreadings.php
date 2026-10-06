<?php
// rror_reporting(E_ALL);
// ini_set('display_errors', 1);
include('settings.php');
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
            $sid = $_REQUEST['sid'];
			$date = $_REQUEST['date'];
			if (!$date) { $date = date("Y-m-d"); } 
			$nicedate = date("l, jS F Y", strtotime($date));
			$prevday = date("Y-m-d",strtotime("$date -1 Day"));
			$nextday = date("Y-m-d",strtotime("$date +1 Day"));

			// get weather data for the day
			$sql = "SELECT `data` AS weatherData FROM `weather_data` WHERE `date` = '$date'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$weatherData = unserialize($row['weatherData']);
			} else {
				$resultMessage .= "Weather Data Not Found<br>" . $dbcnx->error . "<br>";
			}

            $sql = "SELECT title FROM sites WHERE ID = '$sid'";
            $result = $dbcnx->query($sql);
            if ($result->num_rows) {
                $row = $result->fetch_assoc();
                $sitename = $row['title'];
            }

            print "<p align=\"center\" class=\"larger\">$sitename</p>\n";

            print "<p align=\"center\" class=\"larger\">High Readings Over 55A</p>\n";

			print "<p align=\"center\" class=\"larger\">$nicedate</p>\n";

            // print filter form
            print "<form name=\"addform\" method=\"post\" action=\"utilities-highreadings.php?sid=$sid\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"utilities-highreadings.php?date=$prevday&amp;sid=$sid\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.2em\"><b>Day</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:3.6em\"><a href=\"utilities-highreadings.php?sid=$sid\"><b>&bull;</b></a></td>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"utilities-highreadings.php?date=$nextday&amp;sid=$sid\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";

			// get readings
			$sql = "SELECT * FROM `UtlHighReadings` WHERE `SiteID` = '$sid' AND `Date` = '$date'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit Name</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>L1</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>L2</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>L3</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Outside Temp</b></td>\n";
                print "</tr>\n";

                while ($row = $result->fetch_assoc()) {
                    $time = $row['Time'];
                    // $time = substr($time, 0, 5);
                    $unitname = $row['UnitName'];
                    $l1 = $row['L1'];
                    $l2 = $row['L2'];
                    $l3 = $row['L3'];

                    $units[$unitname]++;
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$date</td>\n";
                    print "	<td>$time</td>\n";
                    print "	<td>$unitname</td>\n";
                    print "	<td style=\"text-align:right;\">$l1</td>\n";
                    print "	<td style=\"text-align:right;\">$l2</td>\n";
                    print "	<td style=\"text-align:right;\">$l3</td>\n";

                    // weather outside temperature
                    list($hour, $minute) = explode(":", $time);
                    $hour = intval($hour);
                    $temperature = $weatherData['hourly']['temperature_2m'][$hour];
                    if (!$temperature) {
                        $temperature = "NA";
                    }
                    print "	<td style=\"text-align:right;\">$temperature&deg;</td>\n";
                    print "</tr>\n";

                }

                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\" colspan=\"7\">&nbsp;</td>\n";
                print "</tr>\n";

                print "</table>\n";

                if (count ($units) > 1) {
                    // order units by count
                    arsort($units);
                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
                    foreach ($units as $unit => $count) {
                        print "<tr>\n";
                        print "<td>$unit</b></td>\n";
                        print "<td>$count</b></td>\n";
                        print "</tr>\n";
                    }
                    print "</table>\n";
                }

			} else {
				$resultMessage .= "Nothing Found<br>" . $dbcnx->error;
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
print "<p align=\"center\"><a href=\"utilities-testmeters-list.php\">Return</a></p>\n";
// print weather data for testing
/* print "<pre>";
print_r($weatherData);
print "</pre>"; */
print "</td>\n";
include('footer.php');
?>
