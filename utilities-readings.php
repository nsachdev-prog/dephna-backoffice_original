<?php
$page = "Utilities";
$subpage = "Readings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Latest Readings</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['commit']) {
				$commit = $_POST['commit'];
			}
			
			if ($_REQUEST['siteid'] AND $_REQUEST['siteid'] != "All") {
				$siteid = $_REQUEST['siteid'];
				$filters[] = "m.siteid = '$siteid'";
			}

			if ($_REQUEST['comm'] AND $_REQUEST['comm'] != "All") {
				$comm = $_REQUEST['comm'];
				$filters[] = "m.commodity = '$comm'";
			}

			if ($filters) {
				$filters = "WHERE " . implode(" AND ", $filters);
			}

			list($year,$month,$day) = explode("-",date("Y-m-d"));
			$thismonth = "$year-$month";
			list($prevyear,$prevmonth) = explode("-",date("Y-m",strtotime("-1 month")));
			if ($month == "03") {
				$prevmonth = "02"; // feb 28/29 fix
			}
			$now = time();
			$subs = array(
				"Electricity" => "0",
				"Hot" => "1",
				"Cold" => "2",
				"Gas" => "3"
			);
			$read = 0;
			$today = date("Y-m-d");
            $todaytxt = date("D jS M Y");

			$failed = 0;

            print "<p align=\"center\" class=\"larger\">$todaytxt</p>\n";
			
			if ($commit) {
				// Delete existing committed
				if ($comm)   { $delsearch[] = "`commodity` = '$comm'"; }
				if ($siteid) { $delsearch[] = "`siteid` = '$siteid'"; }
				if ($delsearch) {
					$delsearch = "AND " . implode(" AND ", $delsearch);
				}
				$sql = "DELETE FROM `UtlUsedNew`
						WHERE `year`  = '$year'
						  AND `month` = '$month'
						  AND `counter` > '0'
						$delsearch
				";
				$result = $dbcnx->query($sql);
			}

			print "<form name=\"addform\" method=\"post\" action=\"utilities-readings.php\">\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";

			$sql = "SELECT ID, title FROM sites ORDER BY title";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				print "		<td align=\"right\">Site</td>\n";
				print "		<td><select name=\"siteid\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($row = $result->fetch_assoc()) {
					$thisid    = $row['ID'];
					$thissite  = $row['title'];
					print "		<option value=\"$thisid\""; if ($thisid == $siteid) { print " SELECTED"; } print ">$thissite</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			$sql = "SELECT commodity FROM UtlCommodities GROUP BY commodity ORDER BY commodity";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				print "		<td align=\"right\">Comm</td>\n";
				print "		<td><select name=\"comm\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($row = $result->fetch_assoc()) {
					$thiscomm = $row['commodity'];
					print "		<option value=\"$thiscomm\""; if ($thiscomm == $comm) { print " SELECTED"; } print ">$thiscomm</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"utilities-readings.php\">All</a></td?\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
			print "<p>&nbsp;</p>\n";

			$sql = "SELECT m.*, s.title AS sitename
					FROM `UtlMeters` AS m
					LEFT JOIN sites AS s ON s.ID = m.siteid
					$filters
					ORDER BY sitename, m.unitname
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Comm</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Predate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Pre</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Now</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Used</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Rate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Charge</b></td>\n";
				print "</tr>\n";

				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$mid       = $row['ID'];
					$tsiteid   = $row['siteid'];
					$tsitename = $row['sitename'];
					$gatewayid = $row['gatewayid'];
					$deviceid  = $row['deviceid'];
					$typeid    = $row['typeid'];
					$unitid    = $row['unitid'];
					$unitname  = $row['unitname'];
					$commodity = $row['commodity'];
					$custref   = $row['custref']; // possibly hard coded into UtlMeters
					$read   = 0;
					
					$udatefrom = "";
					$monthfrom = "";
	
					// get previous udatefrom and custref if we haven't got it from UtlMeters
					// $unitPrefixes are in the settings file
					$unitsrch = str_replace("A","",$unitname); // unit may have two meters
					$pfxunit = $unitPrefixes[$tsitename] . " " . $unitsrch; // unit names in bookings are prefixed
					$sql = "SELECT b.custid, datefrom
							FROM bookings AS b
							LEFT JOIN units AS u ON b.unitid = u.ID
							WHERE u.title = '$pfxunit'
					";
					if (($custresult = $dbcnx->query($sql)) AND ($custresult->num_rows)) {
						$custrow  = $custresult->fetch_assoc();
						if (!$custref) {
							$custref  = $custrow['custid'];
						}
						$datefrom = $custrow['datefrom'];
						$monthfrom = date("Y-m",$datefrom);
					}
					
					if ($custref) {

						$sql = "SELECT `start`, `length`, `divisor` FROM `UtlRegisters` WHERE `typeid` = '$typeid' AND `title` = 'Meter Reading'";
						if (($rresult = $dbcnx->query($sql)) AND ($rresult->num_rows)) {
							$rrow = $rresult->fetch_assoc();
							$start   = $rrow['start'];
							$length  = $rrow['length'];
							$divisor = $rrow['divisor'];

							$sql = "SELECT recData
									FROM UtlReadings
									WHERE xdatetime = '$today'
									  AND siteid    = '$tsiteid'
									  AND unitname  = '$unitname'
									  AND commodity = '$commodity'
							";
							if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
								$urow = $uresult->fetch_assoc();
								$recData = $urow['recData'];
								$recData = unserialize($recData);
							
								$read = "";
								for ($n=0; $n<$length; $n++) {
									$reg = $start + $n;
									$thisread = dechex($recData[$reg]);
									$thisread = str_pad($thisread, 2, "0", STR_PAD_LEFT);
									$read .= $thisread;
								}

								if ($typeid == 8) {
									if ($length == 6) {
										$bit1 = substr($read, 8, 4);
										$bit2 = substr($read, 4, 4);
										$bit3 = substr($read, 0, 4);
										$read = $bit1.$bit2; // ignore byte3. don't know why this works!
									} else {
										$msb = substr($read, 4, 4);
										$lsb = substr($read, 0, 4);
										$read = $msb.$lsb;
									}
								}

								if ($typeid == 6 OR $typeid == 7) { // D35 or QSE3 Meter)
									$read = unpack('G', hex2bin($read));
									$read = $read[1];
								} else {
									$read = hexdec($read);
								}
								if ($read) {
									if ($divisor) {
										$read = $read / $divisor;
									}
									$read = number_format($read,'3','.','');
								} else {
									$resultMessage .= "No Reading Available for $pfxunit $commodity<br />";
								}
							} else {
								$resultMessage .= "Could NOT get reading for $pfxunit $commodity<br />";
							}
							
						} else {
							$resultMessage .= "Could NOT get reading register and length for $pfxunit<br />";
						}

						$previous  = 0;

						if ($monthfrom == $thismonth) {
							// New customer. Try to get meter reading from this months inventory
							// a client may have just moved into the unit
							$sql = "SELECT meter
									FROM inventory_meters
									WHERE custref = '$custref'
									AND `inout` = 'in'
									AND unitid = '$unitid'
									AND commodity = '$commodity'
							";
							if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
								$prevrow = $prevresult->fetch_assoc();
								$previous = $prevrow['meter'];
								$udatefrom = date("Y-m-d",$datefrom);
							}
						}

						if ($previous == 0) {
							// try to get previous reading from last month
							$sql = "SELECT `meter`, `year`, `month`, `day`
								FROM `UtlUsedNew`
								WHERE `custref` = '$custref'
								AND `siteid`    = '$tsiteid'
								AND `unit`      = '$unitname'
								AND `commodity` = '$commodity'
								AND `year`      = '$prevyear'
								AND `month`     = '$prevmonth'
							";
							if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
								$prevrow = $prevresult->fetch_assoc();
								$previous = $prevrow['meter'];
								$udatefrom = "{$prevrow['year']}-{$prevrow['month']}-{$prevrow['day']}";
							}
						}

						// get rate
						// try customer rate first
						$sql = "SELECT rate FROM UtlCommodities
						WHERE sitename = '$tsitename' AND commodity = '$commodity' AND custref = '$custref'";
						$rateresult = $dbcnx->query($sql);
						if (($rateresult) && ($rateresult->num_rows)) {
							$raterow = $rateresult->fetch_assoc();
							$rate = $raterow['rate'];
						} else {
							// get default rate for site
							$sql = "SELECT rate FROM UtlCommodities
								WHERE sitename = '$tsitename' AND commodity = '$commodity' AND custref = 'Default'
							";
							$rateresult = $dbcnx->query($sql);
							if (($rateresult) && ($rateresult->num_rows)) {
								$raterow = $rateresult->fetch_assoc();
								$rate = $raterow['rate'];
							}
						}
						if (!$rate) {
							$rate = 0;
						}

					} else {
						$custref = "-NF";
						$previous = 0;
						$rate = 0;
					}

					$used   = $read - $previous;
					$charge = $used * $rate;
					$total += $charge;

					if (($commit) AND ($custref)) {
						
						// commit the charge
						$sql = "INSERT INTO UtlUsedNew VALUES (
							null,
							'$year',
							'$month',
							'$day',
							'$tsiteid',
							'$tsitename',
							'$unitname',
							'$custref',
							'',
							'',
							'$mid',
							'{$subs[$commodity]}',
							'$commodity',
							'$udatefrom',
							'0',
							'$read',
							'$previous',
							'$used',
							'$rate',
							'$charge',
							'$now',
							''
						)";
						if ($dbcnx->query($sql)) {
							$commited++;
						} else {
							$failed++;
						}
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print " <td>DP$custref</td>\n";
					print " <td><a href=\"utilities-manual.php?siteid=$tsiteid\">$tsitename</a></td>\n";
					print " <td>$unitname</td>\n";
					print " <td>$commodity</td>\n";
					print " <td align=\"right\">$udatefrom</td>\n";
					print " <td align=\"right\">$previous</td>\n";
					print " <td align=\"right\">".number_format($read,3,'.','')."</td>\n";
					print " <td align=\"right\">".number_format($used,3,'.','')."</td>\n";
					print " <td align=\"right\">$rate</td>\n";
					print " <td align=\"right\">".number_format($charge,2)."</td>\n";
					print "</tr>\n";
				}
				
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"10\" align=\"right\"><b>".number_format($total,2)."</b></td>\n";
				print "</tr>\n";
				print "</table>\n";

				if (!$commit) {
					print "<p align=\"center\"><b>Commit These Charges</b><br>This will transfer the charges to the imported tab where they can be edited.<br><b>WARNING: It will overwrite any changes already imported this month.</b></p>\n";
					print "<form name=\"commitform\" method=\"post\" action=\"utilities-readings.php\">\n";
					print "<input type=\"hidden\" name=\"commit\" value=\"1\">\n";
					print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\">\n";
					print "<input type=\"hidden\" name=\"comm\" value=\"$comm\">\n";

					print "<span class=\"notclicked\">\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Commit\" /></p>\n";
					print "</span>\n";

					print "<span class=\"uploading\">\n";
					print "<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "</span>\n";

					print "</form>\n";

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";

				} elseif ($commit) {
					print "<p align=\"center\"><b>Charging Result</b><br>Committed: $commited<br>Failed: $failed</p>\n";
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Commited','Meter Charges','$page','$now')");
				}

			} else {
				$resultMessage .= "No Meters Found!<br>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<blockquote><p>$resultMessage</p></blockquote>\n";
}
print "</td>\n";
include('footer.php');
?>
