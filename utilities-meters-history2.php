<?php
$page = "Utilities";
$subpage = "Meters";
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

			$siteid = $_GET['siteid'];
			$unitid = $_GET['unitid'];
			$commodity = "Electricity";
			$qString = "siteid=$siteid&unitid=$unitid&comm=$commodity";

			if ($siteid AND $unitid) {

				$sql = "SELECT m.ID, m.typeid, m.unitname, s.title AS sitename
						FROM UtlMeters AS m
						LEFT JOIN sites AS s ON m.siteid = s.ID
						WHERE `siteid` = '$siteid' AND `unitid` = '$unitid' AND `commodity` = '$commodity'
				";
				if (($result = $dbcnx->query($sql)) && $result->num_rows) {
					$row = $result->fetch_assoc();
					$mid       = $row['ID'];
					$typeid    = $row['typeid'];
					$unitname  = $row['unitname'];
					$sitename  = $row['sitename'];

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname<br />$commodity</p>\n";
					print "<p align=\"center\" class=\"larger\">History</p>\n";

					$month = $_REQUEST['month'];
					if (!$month) { $month = date("Y-m"); }
					$search = array();
					$search[] = "mid = '$mid'";
					$search[] = "siteid = '$siteid'";
					$search[] = "unitid = '$unitid'";
					$search[] = "commodity = '$commodity'";
					$search[] = "xdatetime LIKE '$month%'";

					if ($search) {
						$search = implode(" AND ",$search);
					}

					$prev = date("Y-m",strtotime("$month -1 Month"));
					$next = date("Y-m",strtotime("$month +1 Month"));

					print "<form id=\"rform\" method=\"post\" action=\"utilities-meters-history2.php?$qString\">\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td style=\"font-size:1.8em\"><a href=\"utilities-meters-history2.php?$qString&amp;month=$prev\"><b>&lt;&lt;</b></a></td>\n";
					print "<td><b>Month</b></td>";
					print "<td><input type=\"text\" id=\"datepicker\" name=\"month\" value=\"$month\" class=\"element_small\" /></td>";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "<td style=\"font-size:3.6em\"><a href=\"utilities-meters-history2.php?$qString\"><b>&bull;</b></a></td>\n";
					print "<td style=\"font-size:1.8em\"><a href=\"utilities-meters-history2.php?$qString&amp;month=$next\"><b>&gt;&gt;</b></a></td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					$sql = "SELECT * FROM `UtlRegisters`
							WHERE `typeid` = '$typeid'
							AND `title` = 'Meter Reading'
					";
					$result = $dbcnx->query($sql);
					if ($result && $result->num_rows) {
						$row = $result->fetch_assoc();
						$unit	 = $row['unit'];
						$start   = $row['start'];
						$length  = $row['length'];
						$divisor = $row['divisor']; 
						$title   = "Meter Reading";

						// get readings
						$sql = "SELECT * FROM `UtlReadings`
								WHERE $search
								ORDER BY `xdatetime` DESC
						";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {

							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							print "<tr>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Reading</b></td>\n";
							print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Used</b></td>\n";
							print "</tr>\n";

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";

							while ($row = $result->fetch_assoc()) {
								$xdatetime = $row['xdatetime'];
								$recData   = $row['recData'];
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

								if ($last !== null) {
									$used = $last - $read;
									print "	<td align=\"right\">".number_format($used,'3','.','')."</td>\n";
									print "</tr>\n";
									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								}

								print "	<td>$xdatetime</td>\n";
								print "	<td align=\"right\">".number_format($read,'3','.','')."</td>\n";
								$last = $read;

							}

							print "<tr>\n";
							print "	<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";

							print "</table>\n";

						} else {
							$resultMessage .= "Nothing Found<br>";
						}

					} else {
						$resultMessage .= "Registers not found<br>" . $dbcnx->error;
					}

					// jquery date picker script
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm'});\n";
					print "	});\n";
					print "</script>\n";

				} else {
					$resultMessage .= "Meter not found<br>" . $dbcnx->error;
				}

			} else {
				$resultMessage .= "No Meter ID<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	print "<p align=\"center\"><a href=\"utilities-meters-list.php?siteid=$rsiteid&amp;pa=$lpa&amp;comm=$comm\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
