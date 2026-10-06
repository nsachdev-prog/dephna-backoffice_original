#!/usr/bin/php

<?php
$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$start_date = date("Y-m-d");
    $end_date   = date("Y-m-d");

	$weatherData = file_get_contents("https://api.open-meteo.com/v1/forecast?latitude=51.5085&longitude=-0.1257&hourly=temperature_2m&timezone=Europe%2FLondon&forecast_days=1");
    $weatherData = json_decode($weatherData, true);
	$weatherData = serialize($weatherData);

    if ($result = $dbcnx->query("SELECT * FROM weather_data WHERE date = '$start_date'")) {
        if ($result->num_rows) {
            $sql = "UPDATE weather_data SET data = '$weatherData' WHERE date = '$start_date'";
        } else {
            $sql = "INSERT INTO weather_data (date, data) VALUES ('$start_date', '$weatherData')";
        }
        $dbcnx->query($sql);
    }
	
	$dbcnx->close();
} else {
	$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
}
?>
