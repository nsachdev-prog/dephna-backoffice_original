<?php
$page = "Coldroom_Monitors";
$subpage = "Meter History";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">COLD ROOM MONITORS</p>\n";
	include('coldrooms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid  = $_GET['siteid'];
			$qString = "siteid=$siteid";

			if ($siteid) {

				// get sitename
				$sql = "SELECT `title` FROM `sites` WHERE `ID` = '$siteid'";
				if (($result = $dbcnx->query($sql)) && $result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
				}

				$date = $_REQUEST['date'];
				if (!$date) { $date = date("Y-m-d"); }

				$datename = date("l, jS F Y",strtotime($date));
				print "<p align=\"center\" class=\"larger\">Power Consumption for $datename</p>\n";

				$prev = date("Y-m-d",strtotime("$date -1 Day"));
				$next = date("Y-m-d",strtotime("$date +1 Day"));

				$dates[] = $date;
				$dates[] = $prev;

				print "<form id=\"rform\" method=\"post\" action=\"coldrooms-meters-daily.php?$qString\">\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<tr>\n";
				print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-meters-daily.php?$qString&amp;date=$prev\"><b>&lt;&lt;</b></a></td>\n";
				print "<td><b>Month</b></td>";
				print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>";
				print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-meters-daily.php?$qString\"><b>&bull;</b></a></td>\n";
				print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-meters-daily.php?$qString&amp;date=$next\"><b>&gt;&gt;</b></a></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "</form>\n";

				$sql = "SELECT m.ID, m.siteid, m.typeid, m.unitname, m.commodity, m.unitid
						FROM UtlMeters AS m
						LEFT JOIN units AS u ON m.unitid = u.ID
						WHERE m.siteid = '$siteid' AND u.unittype = 'Cold Room'
						ORDER BY m.unitname
				";
				if (($result = $dbcnx->query($sql)) && $result->num_rows) {
					print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Yesterday</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Today</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Used</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Setpint</b></td>\n";
					print "</tr>\n";
					while ($row = $result->fetch_assoc()) {
						$mid       = $row['ID'];
						$typeid    = $row['typeid'];
						$unitname  = $row['unitname'];
						$unitid    = $row['unitid'];
						$commodity = $row['commodity'];

						$sql = "SELECT * FROM `UtlRegisters`
								WHERE `typeid` = '$typeid'
								AND `title` = 'Meter Reading'
						";
						if (($rresult = $dbcnx->query($sql)) AND $rresult->num_rows) {
							$rrow = $rresult->fetch_assoc();
							$unit	 = $rrow['unit'];
							$start   = $rrow['start'];
							$length  = $rrow['length'];
							$divisor = $rrow['divisor']; 
							$title   = "Meter Reading";

							$reads = array();
							foreach ($dates AS $date) {
								$search = "`xdatetime` = '$date' AND `mid` = '$mid'";
								$sql = "SELECT * FROM `UtlReadings`
										WHERE $search
										ORDER BY `xdatetime` DESC
										LIMIT 1
								";
								if (($uresult = $dbcnx->query($sql)) && $uresult->num_rows) {
									$urow = $uresult->fetch_assoc();
									$xdatetime = $urow['xdatetime'];
									$recData   = $urow['recData'];
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
								} else {
									$read = 0;
								}
								$reads[] = $read;
							}

							// get current pstpoint
							$sql = "SELECT setpoint FROM xweb_history1 WHERE siteid = '$siteid' AND unitid = '$unitid' ORDER BY xdatetime DESC LIMIT 1";
							if (($sresult = $dbcnx->query($sql)) && $sresult->num_rows) {
								$srow = $sresult->fetch_assoc();
								$setpoint = $srow['setpoint'];
							} else {
								$setpoint = 0;
							}

							$used = $reads[0] - $reads[1];
							$used = number_format($used, 2, '.', '');
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$unitname</td>\n";
							print "	<td align=\"right\">$reads[1]</td>\n";
							print "	<td align=\"right\">$reads[0]</td>\n";
							print "	<td align=\"right\">$used $unit</td>\n";
							print "	<td align=\"right\">$setpoint&deg;</td>\n";
							print "</tr>\n";

						} else {
							$resultMessage .= "Registers not found<br>" . $dbcnx->error;
						}

					}

					print "<tr>\n";
                	print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";

					// jquery date picker script
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";

				} else {
					$resultMessage .= "Meter not found<br>" . $dbcnx->error;
				}

			} else {
				$resultMessage .= "No Site ID<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
