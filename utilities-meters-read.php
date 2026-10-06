<?php
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
set_time_limit(6);
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id      = $_REQUEST['id'];  // meter id
			$uid     = $_REQUEST['uid']; // imported id from utilities imported
			$rsiteid = $_REQUEST['siteid'];
			$pa 	 = $_REQUEST['pa'];
			$comm	 = $_REQUEST['comm'];
			$gid	 = $_REQUEST['gid'];

			if ($uid) {
				// from imported
				// get return vars
				$comm    = $_REQUEST['comm'];
				$site    = $_REQUEST['site'];
				$fmonth  = $_REQUEST['fmonth'];
				$fyear   = $_REQUEST['fyear'];
				$custref = $_REQUEST['custref'];
				$type    = $_REQUEST['type'];
				$qString = "comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;custref=$custref&amp;type=$type";
			}

			$sql = "SELECT * FROM UtlMeters WHERE ID = '$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$gatewayid = $row['gatewayid'];
				$deviceid  = $row['deviceid'];
				$typeid    = $row['typeid'];
				$unitid    = $row['unitid'];
				$unitname  = $row['unitname'];
				$commodity = $row['commodity'];

				$sql = "SELECT * FROM UtlGateways WHERE ID = '$gatewayid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$siteid    = $row['siteid'];
					$gtitle    = $row['title'];
					$ipaddress = $row['ipaddress'];
					$port      = $row['port'];

					$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$sitename = $row['title'];
					}

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname<br />$commodity</p>\n";
					print "<p align=\"center\" class=\"larger\">Live Meter Readings</p>\n";

					$sql = "SELECT * FROM UtlTypes WHERE ID = '$typeid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ttitle	= $row['title'];
						$first  = $row['start'];
						$qty    = $row['qty'];

						require_once '../../Phpmodbus/Phpmodbus/ModbusMaster.php';
						require_once '../../Phpmodbus/Phpmodbus/ModbusMasterTcp.php';

						$modbus = new ModbusMaster($ipaddress, $port, 'TCP');

						try {

							// FC  3: read multiple registers
 							// FC  4: read multiple input registers

							// no response class to catch timeout.
							print "<p align=\"center\" class=\"no-response\"><b>No Response From Gateway</b></p>\n";

							if ($typeid == 8 OR $typeid == 6 OR $typeid == 11 OR $typeid == 13) {
								$recData = $modbus->readMultipleInputRegisters($deviceid, $first, $qty);
							} else {
								$recData = $modbus->readMultipleRegisters($deviceid, $first, $qty);
							}

							// remove no response class
							print "<script>document.querySelector('.no-response').style.display = 'none';</script>\n";

							$sql = "SELECT * FROM `UtlRegisters` WHERE `typeid` = '$typeid' ORDER BY `start`";
							$result = $dbcnx->query($sql);
							if (($result) && ($result->num_rows)) {
								print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
								print "<tr>\n";
								print "	<td style=\"border-bottom:1px solid #999999\"><b>Description</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Value</b></td>\n";
								print "</tr>\n";

								// row background colours
								print "<style>\n";
								print ".r0 { background-color:#fff }\n";
								print ".r1 { background-color:#ddd }\n";
								print ".g { background-color:#88dd88 }\n";
								print "</style>\n";

								while ($row = $result->fetch_assoc()) {
									$title	 = $row['title'];
									$unit	 = $row['unit'];
									$start   = $row['start'];
									$length  = $row['length'];
									$divisor = $row['divisor']; 

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

									if ($title == "Meter Reading") {
										$reading = $read; // for posting if requested by imported page
										$title = "<b>".$title."</b>";
										$unit = "<b>".$unit."</b>";
										$read = "<b>".$read."</b>";
										print "<tr class=\"g\">\n";
									} else {
										print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									}
									print "	<td>$title</td>\n";
									print "	<td>$unit</td>\n";
									print "	<td align=\"right\">$read</td>\n";
									print "</tr>\n";

								}

								if ($uid) {
									// confirm reading post to readings
									print "<p align=\"center\"><a href=\"utilities-used-update.php?uid=$uid&amp;reading=$reading&amp;$qString\">Use This Reading</a></p>\n";
								}

								print "<tr>\n";
								print "	<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";

								print "</table>\n";

								/* print "<p>Data:<br />\n";
								print "<blockquote>\n";
								print "<pre>\n";
								foreach ($recData AS $key => $value) {
									$hex = dechex($value);
									$hex = str_pad($hex, 2, "0", STR_PAD_LEFT);
									print "[$key] => $hex ($value)<br />";
								}
								print "</pre>\n";
								print "</blockquote>\n";
								print "</p>\n"; */

							}

						}

						catch (Exception $e) {
							// Print error information if any
							print $modbus;
							print $e;
						}

						print "<p align=\"center\"><a href=\"utilities-meters-history.php?mid=$id&amp;siteid=$rsiteid&amp;uid=$uid&amp;$qString&amp;lpa=$pa\">Meter History</a></p>\n";

					} else {
						$resultMessage = "Meter Type not found<br />" . $dbcnx->error;
					}

				} else {
					$resultMessage = "Gateway not found<br />" . $dbcnx->error;
				}

			} else {
				$resultMessage = "Meter not found<br />" . $dbcnx->error;
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($uid) {
		// confirm reading post to readings
		print "<p align=\"center\"><a href=\"utilities-itemised-rayleigh.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><a href=\"utilities-meters-list.php?siteid=$rsiteid&amp;pa=$pa&amp;comm=$comm&amp;gid=$gid\">Return</a></p>\n";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
