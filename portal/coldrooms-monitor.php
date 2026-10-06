<?php
	include('settings.php');
	include('session.php');
	$jquery = 1;
	$charts = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
	.redtext { color:red }
</style>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">Cold Rooms</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$custid  = $_SESSION['user'];
		$siteid  = $_REQUEST['siteid'];
		$unitid  = $_REQUEST['unitid'];
		$cmdid   = $_REQUEST['cmdid'];
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			if ($siteid AND $unitid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];

					// get unit
					$sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unittype = $row['unittype'];
						$unitname = $row['title'];
						list($pre,$unitname) = explode(" ",$unitname);

						print "<h3>$sitename - $unitname - Monitor</h3>\n";

						$fr = $_REQUEST['fr'];
						$to = $_REQUEST['to'];
						if (!$fr) { $fr = date("Y-m-d H:i",strtotime("-3 Hours")); }
						if (!$to) { $to = date("Y-m-d H:i"); }
						$prevfr = date("Y-m-d H:i",strtotime("$fr -3 Hours"));
						$prevto = date("Y-m-d H:i",strtotime("$to -3 Hours"));
						$nextfr = date("Y-m-d H:i",strtotime("$fr +3 Hours"));
						$nextto = date("Y-m-d H:i",strtotime("$to +3 Hours"));

						print "<form id=\"rform\" method=\"post\" action=\"coldrooms-monitor.php?siteid=$siteid&amp;unitid=$unitid\">\n";
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-monitor.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$prevfr&amp;to=$prevto\"><b>&lt;&lt;</b></a></td>\n";
						print "<td><b>From</b></td>";
						print "<td><input type=\"text\" id=\"datetimepicker1\" name=\"fr\" value=\"$fr\" class=\"element_small\" /></td>";
						print "<td><b>To</b></td>";
						print "<td><input type=\"text\" id=\"datetimepicker2\" name=\"to\" value=\"$to\" class=\"element_small\" /></td>";
						print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-monitor.php?siteid=$siteid&amp;unitid=$unitid\"><b>&bull;</b></a></td>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-monitor.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$nextfr&amp;to=$nextto\"><b>&gt;&gt;</b></a></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";

						print "<div style=\"height: 400px\">\n";
						print "	<canvas id=\"myChart1\"><p>Chart</p></canvas>";
						print "</div>\n";
						print "<div style=\"height: 180px\">\n";
						print "	<canvas id=\"myChart2\"><p>Chart</p></canvas>";
						print "</div>\n";

						print "<p align=\"center\"><a href=\"coldrooms-list.php\">Return</a></p>";

						$sql = "SELECT *
								FROM xweb_history1
								WHERE siteid = '$siteid'
								  AND unitid = '$unitid'
								  AND custref = '$custref'
								  AND xdatetime >= '$fr'
								  AND xdatetime <= '$to'
								ORDER BY xdatetime DESC
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

							$chLablTemp = array();
							$chDataTemp = array();
							$chColrTemp = array();

							$chDataEvap = array();
							$chColrEvap = array();

							$chDataSetp = array();
							$chColrsetp = array();

							$chLablDefr = array();
							$chDataDefr = array();
							$chColrDefr = array();

							$chDataDoor = array();
							$chColrDoor = array();

							$chDataLight = array();
							$chColrLight = array();

							print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							print "<tr>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Eng M</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>No C</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Powr</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>A Off</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Kb L</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\"><b>Door/Heater</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>SetP&deg;</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap&deg;</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Defr</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>CAM Action</b></td>\n";
							print "</tr>\n";

							// row background colours
							print "<style>\n";
							print ".r0 { background-color:#fff }\n";
							print ".r1 { background-color:#ddd }\n";
							print "</style>\n";

							while ($row = $result->fetch_assoc()) {

								$xdatetime   = $row['xdatetime'];
								$deviceid    = $row['deviceid'];
								$siteid      = $row['siteid'];
								$sitename    = $row['sitename'];
								$unitid      = $row['unitid'];
								$unitname    = $row['unitname'];
								$onoff       = $row['onoff']; 
								$setpoint    = $row['setpoint'];
								$temperature = $row['temperature'];
								$evaporator  = $row['evaporator'];
								$doorswitch  = $row['doorswitch'];
								$dooropen    = $row['dooropen'];
								$doorheater  = $row['doorheater'];
								$keyboard    = $row['keyboard'];
								$light       = $row['light'];
								$defrost     = $row['defrost'];
								$xstatus     = $row['xstatus'];
								$engineermode = $row['engineermode'];
								$nocallout   = $row['nocallout'];
								$approvedoff = $row['approvedoff'];

								if (!$compname) { $compname = "Empty"; }

								if ($xstatus == 1) {
									$xstext = "Callout";
								} elseif ($xstatus == 2) {
									$xstext = "Switch Off";
								} elseif ($xstatus == 3) {
									$xstext = "Keyb locked";
								} elseif ($xstatus == 4) {
									$xstext = "Defrost";
								} elseif ($xstatus == 8) {
									$xstext = "Switch On";
								} elseif ($xstatus == 9) {
									$xstext = "Restarted";
								} elseif ($xstatus == 10) {
									$xstext = "Odc=no";
								} else {
									$xstext = "&nbsp;";
								}


								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print " <td>$xdatetime</td>\n";
								if ($engineermode) {
									print " <td><span class=\"redtext\">ON</span></td>\n";
								} else {
									print " <td>OFF</td>";
								}
								if ($nocallout) {
									print " <td><span class=\"redtext\">ON</span></td>\n";
								} else {
									print " <td>OFF</td>";
								}
								if ($onoff) {
									print " <td>ON</td>\n";
								} else {
									print " <td><span class=\"redtext\">OFF</span></td>";
								}
								if ($approvedoff) {
									print " <td><span class=\"redtext\">ON</span></td>\n";
								} else {
									print " <td>OFF</td>";
								}
								if ($keyboard) {
									print " <td>ON</td>\n";
								} else {
									print " <td><span class=\"redtext\">OFF</span></td>";
								}
								if ($doorswitch) {
									print " <td><span class=\"redtext\">OPEN</span></td>\n";
								} else {
									print " <td>Closed</td>";
								}
								if ($doorheater) {
									print " <td>ON</td>\n";
								} else {
									print " <td>OFF</td>";
								}
								if ($light) {
									print " <td>ON</td>\n";
								} else {
									print " <td>OFF</td>";
								}
								print " <td align=\"right\">$setpoint</td>\n";
								print " <td align=\"right\">$temperature</td>\n";
								print " <td align=\"right\">$evaporator</td>\n";
								if ($defrost) {
									print " <td><span class=\"redtext\">ON</span></td>\n";
								} else {
									print " <td>OFF</td>";
								}
								print " <td>$xstext</td>\n";
								print "</tr>\n";
								
								list($xdate,$xtime) = explode(" ",$xdatetime);
								$chLablTemp[] = "''";
								$chLablDefr[] = "'$xtime'";
								$chDataTemp[] = $temperature;
								if (($temperature > $setpoint+2) OR ($temperature < $setpoint-2)) {
									$chColrTemp[] = "'red'";
								} else {
									$chColrTemp[] = "'green'";
								}
								
								$chDataEvap[] = $evaporator;
								$chColrEvap[] = "'brown'";

								$chDataSetp[] = $setpoint;
								$chColrSetp[] = "'black'";

								$chDataDefr[] = $defrost+4;
								$chColrDefr[] = "'yellow'";

								$chDataDoor[] = $doorswitch+2;
								$chColrDoor[] = "'orange'";

								$chDataLight[] = $light;
								$chColrLight[] = "'blue'";
							}

							$chLablTemp = implode(", ",array_reverse($chLablTemp));
							$chDataTemp = implode(", ",array_reverse($chDataTemp));
							$chColrTemp = implode(", ",array_reverse($chColrTemp));

							$chDataEvap = implode(", ",array_reverse($chDataEvap));
							$chColrEvap = implode(", ",array_reverse($chColrEvap));
							
							$chDataSetp = implode(", ",array_reverse($chDataSetp));
							$chColrSetp = implode(", ",array_reverse($chColrSetp));
							
							$chLablDefr = implode(", ",array_reverse($chLablDefr));
							$chDataDefr = implode(", ",array_reverse($chDataDefr));
							$chColrDefr = implode(", ",array_reverse($chColrDefr));
							
							$chDataDoor = implode(", ",array_reverse($chDataDoor));
							$chColrDoor = implode(", ",array_reverse($chColrDoor));
							
							$chDataLight = implode(", ",array_reverse($chDataLight));
							$chColrLight = implode(", ",array_reverse($chColrLight));
						
							print "<tr>\n";
							print "<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";

							print "</table>\n";   
						} else {
							$resultMessage .= "Nothing Found<br />" . $result->error . "<br />";
						}
						
						if (!$print) {
							print "<script type=\"text/javascript\">\n";
							print "	$( document ).ready(function() {\n";
							print " jQuery.datetimepicker.setLocale('en');\n";
							print "		$( \"#datetimepicker1\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
							print "		$( \"#datetimepicker2\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
							print "	});\n";
							print "</script>\n";
						}


						
					} else {
						$resultMessage .= "Unit Not Found<br />" . $result->error . "<br />Please contact support<br />";
					}

				} else {
					$resultMessage .= "Site Not Found<br />" . $result->error . "<br />Please contact support<br />";
				}

			} else {
				$resultMessage .= "Page called without ids<br />Please contact support<br />";
			}

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"coldrooms-list.php\">Return</a></p>";
	print "<p align=\"center\"><a href=\"index.php\">MAIN MENU</a></p>";

	/* print "<p><b>Raw Data From XWeb</b></p>";
	print "<pre>";
	print_r($xresult);
	print "</pre>"; */

?>
			</div>
	</div>

<script>
var ctx1 = document.getElementById('myChart1');
var ctx2 = document.getElementById('myChart2');
var myChart1 = new Chart(ctx1, {
    type: 'line',
    data: {
        labels: [<?php print $chLablTemp ?>],
        datasets: [
			{
				label: 'Temperature',
				data: [<?php print $chDataTemp ?>],
				backgroundColor: [<?php print $chColrTemp ?>],
				fill: false,
				borderColor: 'green',
				borderWidth: '1',
				radius: '2',
			},
			{
				label: 'Evaporator',
				data: [<?php print $chDataEvap ?>],
				backgroundColor: [<?php print $chColrEvap ?>],
				fill: false,
				borderColor: 'brown',
				borderWidth: '1',
				radius: '1',
			},
			{
				label: 'Set Point',
				data: [<?php print $chDataSetp ?>],
				backgroundColor: [<?php print $chColrSetp ?>],
				fill: false,
				borderColor: 'black',
				borderWidth: '1',
				radius: '0',
			},	
        ]
    },
    options: {
		responsive: true,
		maintainAspectRatio: false,
        layout: {
            padding: {
                left: 20,
                right: 20,
                top: 0,
                bottom: 0
            },
        },
        scales: {
			xAxes: [{
				gridLines: {
					drawTicks: false
				},
                ticks: {
                    stepSize: 1,
					display: false
                }
            }],
            yAxes: [{
                ticks: {
                    stepSize: 1
                },
				afterFit: function(scaleInstance) {
    				scaleInstance.width = 34;
    			}
            }]
        }
    }
});
var myChart2 = new Chart(ctx2, {
    type: 'line',
    data: {
		labels: [<?php print $chLablDefr ?>],
        datasets: [
			{
				label: 'Defrost',
				data: [<?php print $chDataDefr ?>],
				backgroundColor: [<?php print $chColrDefr ?>],
				fill: false,
				borderColor: 'yellow',
				borderWidth: '3',
				radius: '0',
				steppedLine: 'true',
			},
			{
				label: 'Door',
				data: [<?php print $chDataDoor ?>],
				backgroundColor: [<?php print $chColrDoor ?>],
				fill: false,
				borderColor: 'orange',
				borderWidth: '3',
				radius: '0',
				steppedLine: 'true',
			},
			{
				label: 'Light',
				data: [<?php print $chDataLight ?>],
				backgroundColor: [<?php print $chColrLight ?>],
				fill: false,
				borderColor: 'blue',
				borderWidth: '3',
				radius: '0',
				steppedLine: 'true',
			},
        ]
    },
    options: {
		responsive: true,
		maintainAspectRatio: false,
		legend: {
			position: 'bottom',
		},
        layout: {
            padding: {
                left: 20,
                right: 20,
                top: 0,
                bottom: 20
            },
        },
        scales: {
            yAxes: [{
				type: 'category',
				labels: ['Off','On','Closed', 'Open', 'Off', 'On'],
                ticks: {
					reverse: true,
					min: 0,
					max: 3
                },
				afterFit: function(scaleInstance) {
    				scaleInstance.width = 34;
    			}
            }]
        }
    }
});

</script>

<?php			
	include('elements/footer.php');
?>
