#!/usr/bin/php

<?php
include('settings.php');

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$start_date = $_GET['date'];
    $end_date   = $_GET['date'];

	$weatherData = file_get_contents("https://historical-forecast-api.open-meteo.com/v1/forecast?latitude=51.5085&longitude=-0.1257&hourly=temperature_2m&timezone=Europe%2FLondon&start_date=$start_date&end_date=$end_date");
    $weatherData = json_decode($weatherData, true);
	$weatherData = serialize($weatherData);

    if ($result = $dbcnx->query("SELECT * FROM weather_data WHERE date = '$start_date'")) {
        if ($result->num_rows) {
            $sql = "UPDATE weather_data SET data = '$weatherData' WHERE date = '$start_date'";
        } else {
            $sql = "INSERT INTO weather_data (date, data) VALUES ('$start_date', '$weatherData')";
        }
        if ($dbcnx->query($sql)) {
            $resultMessage = "Weather Data saved";
        } else {
            $resultMessage = "Weather Data not saved<br>" . $dbcnx->error;
        }
    }
	
	$dbcnx->close();
} else {
	$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
}
if ($resultMessage) {
    print "<p>$resultMessage</p>";
}
?>
