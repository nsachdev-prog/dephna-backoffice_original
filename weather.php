<?php


$weatherData = file_get_contents('https://api.open-meteo.com/v1/forecast?latitude=51.5085&longitude=-0.1257&hourly=temperature_2m&timezone=Europe%2FLondon&start_date=2024-07-01&end_date=2024-07-01');
$weatherData = json_decode($weatherData, true);
/* $weatherData = $weatherData['daily'];
$weatherMax = max($weatherData['temperature_2m_max']);
$weatherMin = min($weatherData['temperature_2m_min']);
$weatherMean = array_sum($weatherData['temperature_2m_max'])/count($weatherData['temperature_2m_max']);

print "Max: " . $weatherMax . "<br>";
print "Min: " . $weatherMin . "<br>";
print "Mean: " . $weatherMean . "<br>"; */

print "<pre>";
print_r($weatherData);
print "</pre>";


?>
