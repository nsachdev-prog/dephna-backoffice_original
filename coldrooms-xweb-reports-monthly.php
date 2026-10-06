<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
$charts = 1;
$print = $_REQUEST['print'];
if ($print) {
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Consumption Report</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
	</head>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM REPORTS</p>\n";
    print "<p align=\"center\" class=\"larger\">Monthly Consumtion / Outside Temperature</p>\n";
		
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];
			$year = $_REQUEST['year'];
            if (!$year) { $year = date("Y"); }

            // get the site name
            $sql = "SELECT `title` FROM `sites` WHERE `ID` = '$siteid'";
            if (($siteresult = $dbcnx->query($sql)) && $siteresult->num_rows) {
                $siterow = $siteresult->fetch_assoc();
                $sitename = $siterow['title'];
            }

            // get the unit name
            $sql = "SELECT `title` FROM `units` WHERE `ID` = '$unitid'";
            if (($unitresult = $dbcnx->query($sql)) && $unitresult->num_rows) {
                $unitrow = $unitresult->fetch_assoc();
                $unitname = $unitrow['title'];
                list($prefix, $unitname) = explode(" ", $unitname);
            }

            print "<p align=\"center\" class=\"larger\">$sitename - $unitname - $year</p>\n";

            $prev = $year - 1;
            $next = $year + 1; 

            if (!$print) {

                print "<form id=\"rform\" method=\"post\" action=\"coldrooms-xweb-reports-monthly.php?siteid=$siteid&unitid=$unitid\">\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-reports-monthly.php?siteid=$siteid&unitid=$unitid&year=$prev\"><b>&lt;&lt;</b></a></td>\n";
                // year selector. 5 years back including current year
                print "<td><select name=\"year\" onchange=\"document.getElementById('rform').submit();\">\n";
                $tyear = date("Y");
                for ($i = $tyear - 5; $i <= $tyear; $i++) {
                    print "<option value=\"$i\"";
                    if ($i == $year) { print " selected"; }
                    print ">$i</option>\n";
                }
                print "</select></td>\n";
                print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-xweb-reports-monthly.php?siteid=$siteid&unitid=$unitid\"><b>&bull;</b></a></td>\n";
                print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-reports-monthly.php?siteid=$siteid&unitid=$unitid&year=$next\"><b>&gt;&gt;</b></a></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";
            }

            // get consumtion frr each month from UtlUsedNew
            $sql = "SELECT `month`, `used` AS consumption FROM `UtlUsedNew` WHERE `siteid` = '$siteid' AND `unit` = '$unitname' AND `year` = '$year' ORDER BY `month`";
            if (($result = $dbcnx->query($sql)) && $result->num_rows) {
                $consumption = array();
                $weatherData = array();
                while ($row = $result->fetch_assoc()) {
                    $month = $row['month'];
                    $consumption[$month] = $row['consumption'];
                    $sql = "SELECT `date`, `data` FROM `weather_data` WHERE `date` LIKE '$year-$month%' ORDER BY `date`";
                    if (($weatherResult = $dbcnx->query($sql)) && $weatherResult->num_rows) {
                        $weatherData[$month] = 0;
                        while ($weatherRow = $weatherResult->fetch_assoc()) {
                            $data = unserialize($weatherRow['data']);
                            // add to the temperature data
                            $weatherData[$month] += $data['hourly']['temperature_2m'][15];
                        }
                        // average the temperature data for the month and round to 1 decimal place
                        $weatherData[$month] = round($weatherData[$month] / $weatherResult->num_rows, 1);
                    } else {
                        $weatherData[$month] = 0;
                    }
                }
            }
            
            // make graph using Chart.js with consumption scale on the left and temperature scale on the right. Make temperature scale follow it's own scale
            print "<canvas id=\"myChart\" width=\"800\" height=\"400\"></canvas>\n";
            print "<script>\n";
            print "var ctx = document.getElementById('myChart').getContext('2d');\n";
            print "var myChart = new Chart(ctx, {\n";
            print "    type: 'bar',\n";
            print "    data: {\n";
            print "        labels: [";
            foreach ($consumption as $month => $value) {
                print "\"$month\",";
            }
            print "],\n";
            print "        datasets: [{\n";
            print "            label: 'Consumption',\n";
            print "            yAxisID: 'A',\n";
            print "            data: [";
            foreach ($consumption as $month => $value) {
                print "$value,";
            }
            print "],\n";
            print "            backgroundColor: [\n";
            foreach ($consumption as $month => $value) {
                print "'rgba(255, 99, 132, 0.2)',";
            }
            print "],\n";
            print "            borderColor: [\n";
            foreach ($consumption as $month => $value) {
                print "'rgba(255, 99, 132, 1)',";
            }
            print "],\n";
            print "            borderWidth: 1\n";
            print "        }, {\n";
            print "            label: 'Temperature',\n";
            print "            yAxisID: 'B',\n";
            print "            data: [";
            foreach ($weatherData as $month => $value) {
                print "$value,";
            }
            print "],\n";
            print "            backgroundColor: [\n";
            foreach ($weatherData as $month => $value) {
                print "'rgba(54, 162, 235, 0.2)',";
            }
            print "],\n";
            print "            borderColor: [\n";
            foreach ($weatherData as $month => $value) {
                print "'rgba(54, 162, 235, 1)',";
            }
            print "],\n";
            print "            borderWidth: 1\n";
            print "        }]\n";
            print "    },\n";
            print "    options: {\n";
            print "        scales: {\n";
            print "            yAxes: [{\n";
            print "                id: 'A',\n";
            print "                type: 'linear',\n";
            print "                position: 'left',\n";
            print "                scaleLabel: {\n";
            print "                    display: true,\n";
            print "                    labelString: 'Consumption kWh'\n";
            print "                },\n";
            print "                ticks: {\n";
            print "                    beginAtZero: true\n";
            print "                }\n";
            print "            }, {\n";
            print "                id: 'B',\n";
            print "                type: 'linear',\n";
            print "                position: 'right',\n";
            print "                scaleLabel: {\n";
            print "                    display: true,\n";
            print "                    labelString: 'Average Temperature C'\n";
            print "                },\n";
            print "                ticks: {\n";
            print "                    beginAtZero: true\n";
            print "                }\n";
            print "            }]\n";
            print "        }\n";
            print "    }\n";
            print "});\n";
            print "</script>\n";

            // print weather data array for testing
            /* $sql = "SELECT `date`, `data` FROM `weather_data` WHERE `date` LIKE '2024-06-25'";
            if (($weatherResult = $dbcnx->query($sql)) && $weatherResult->num_rows) {
                $weatherRow = $weatherResult->fetch_assoc();
                $weatherData = unserialize($weatherRow['data']);
            }
            print "<pre>";
            print_r($weatherData);
            print "</pre>"; */

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\"$resultMessage</p>";
	}
    if (!$print) {
        print "<p align=\"center\">";
        print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">Return To Unit</a> | ";
        print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Return To Site</a> | ";
        print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
        print "</p>\n";

        print "<p align=\"center\">";
        print "<a href=\"coldrooms-xweb-reports-monthly.php?siteid=$siteid&unitid=$unitid&year=$year&print=1\" target=\"_blank\">Print</a>";
        print "</p>\n";
    }
} else {
	include ('loggedout.php');
}
if (!$print) {
	print "</td>\n";
}
if ($print) { ?>
	</body>
</html>
<?php
} else {
	include('footer.php');
}

