<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
ini_set("memory_limit","400M");
$page = "Utilities";
$subpage = "Rayleigh";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Current Charges</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['commit']) {
				$commit = $_POST['commit'];
				$file   = $_POST['filename'];
			} else {
				// get most recent file uploaded
				$path = "../../../dephna_u"; 
				$latest_ctime = 0; $file = '';    
				$d = dir($path);
				while (false !== ($entry = $d->read())) {
					$filepath = "{$path}/{$entry}";
					// could do also other checks than just checking whether the entry is a file
					if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
						$latest_ctime = filectime($filepath);
						$file = $entry;
					}
				}
			}

			if ($file) {

				if ($_REQUEST['siteid'] AND $_REQUEST['siteid'] != "All") {
					$siteid = $_REQUEST['siteid'];
					$sitefilter = $siteid;
					$filters[] = "siteid = '$siteid'";
				}
				if ($_REQUEST['comm'] AND $_REQUEST['comm'] != "All") {
					$comm = $_REQUEST['comm'];
					$filters[] = "commodity = '$comm'";
				}
				if ($filters) {
					$filters = "AND " . implode(" AND ", $filters);
				}

				if ($commit) {
					// Delete existing committed
					$delsearch = array();
					$delsearch[] = "collector NOT LIKE 'MAN-%'";
					if ($comm)   { $delsearch[] = "commodity = '$comm'"; }
					if ($siteid) { $delsearch[] = "siteid = '$siteid'"; }
					if ($delsearch) {
						$delsearch = "AND " . implode(" AND ", $delsearch);
					}
					list($dy,$dm) = explode("-",date("Y-m"));
					$sql = "DELETE FROM UtlUsedNew WHERE year = '$dy' AND month = '$dm' $delsearch";
					$result = $dbcnx->query($sql);
				}

				if ($data = file_get_contents("../../../dephna_u/$file")) {
					if ($data = json_decode($data, true)) {

						$to = $data['to'] / 1000;
						$to = date("Y-m-d H:i:s",$to);
						list($year,$month,$day) = explode("-",date("Y-m-d"));
						$thismonth = "$year-$month";
						list($prevyear,$prevmonth) = explode("-",date("Y-m",strtotime("-1 month")));
						if ($month == "03") {
							$prevmonth = "02"; // feb 28/29 fix
						}
						$now = time();

						print "<p align=\"center\">File: $file<br>Readings To: $to</p>\n";

						print "<form name=\"addform\" method=\"post\" action=\"utilities-rayleigh-charges.php\">\n";
						print "<table align=\"center\" class=\"smalltext\" cellspacing=\"2\" cellpadding=\"0\">\n";
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
						print "	<td><a href=\"utilities-rayleigh-charges.php\">All</a></td?\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
						print "<p>&nbsp;</p>\n";

						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Meter</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Sen</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #666\"><b>Sub</b></td>\n";
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
						
						$alldps = array();
						$subs = array(
							"Electricity" => "0",
							"Hot" => "1",
							"Cold" => "2",
							"Gas" => "3"
						);

						foreach ($data['data'] AS $collector => $sensors) {
							ksort($sensors, SORT_NATURAL);
							foreach ($sensors AS $sensor => $values) {
								list($collector,$junk) = explode("@",$collector);
								$thisdata = end($values); 
								$timestamp = $thisdata[0] / 1000;
								$time = date("H:i:s",$timestamp);
								$reading = $thisdata[1];
								list($sensor,$type) = explode(".",$sensor);
								$sensor = str_replace("e","",$sensor);
								$commodity = "";
								if (strpos($type, "c") !== FALSE AND $type != "c4") {
									$sub = str_replace("c","",$type);
									$search = "AND `sub` = '$sub'";
								} else {
									$search = "";
									$sub = "0";
								}

								if (($type == "kwh") OR ($search)) {

									$sql = "SELECT `ID`, `siteid`, `sitename`, `unit`, `commodity`, `title`, `custref`
											FROM `UtlCollectors`
											WHERE `collector` = '$collector'
											AND `counter` = '$sensor'
											$filters
											$search
									";
									if ($result = $dbcnx->query($sql) AND $result->num_rows) {
										$row = $result->fetch_assoc();
										$mid       = $row['ID'];
										$siteid    = $row['siteid'];
										$sitename  = $row['sitename'];
										$title     = $row['title'];
										$unit      = $row['unit'];
										$commodity = $row['commodity'];
										$custref   = $row['custref']; // might be hard coded in UtlCollectors

										if (strlen($title) > 8) { $title = substr($title, 0, 8); }
										
										$udatefrom = "";
										$monthfrom = "";

										// get previous udatefrom (and custref if we haven't got it from UtiCollectors)
										// $unitPrefixes are in the settings file
										$unitsrch = str_replace("A","",$unit);
										$unitsrch = str_replace("B","",$unit);
										$pfxunit = $unitPrefixes[$sitename] . " " . $unitsrch;
										$sql = "SELECT b.custid, b.datefrom
												FROM bookings AS b
												LEFT JOIN units AS u ON b.unitid = u.ID
												WHERE u.title = '$pfxunit'
										";
										$custresult = $dbcnx->query($sql);
										if (($custresult) AND ($custresult->num_rows)) {
											$custrow = $custresult->fetch_assoc();
											if (!$custref) {
												$custref = $custrow['custid'];
											}
											$datefrom = $custrow['datefrom'];
											$monthfrom = date("Y-m",$datefrom);
										}
										
										if ($custref) {

											$previous = 0;

											if ($monthfrom == $thismonth) {
												// New customer. Try to get meter reading from this months inventory
												// a client may have just moved into the unit
												$sql = "SELECT meter
														FROM inventory_meters
														WHERE custref = '$custref'
														AND `inout` = 'in'
														AND mid = '$mid'
												";
												if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
													$prevrow = $prevresult->fetch_assoc();
													$previous = $prevrow['meter'];
													$udatefrom = date("Y-m-d",$datefrom);
												}
											}

											// AND `collector` = '$collector'
											// AND `counter` = '$sensor'

											if ($previous == 0) {
												// try to get previous reading from last month
												$sql = "SELECT `meter`, `year`, `month`, `day`
														FROM `UtlUsedNew`
														WHERE `custref` = '$custref'
														AND `siteid` = '$siteid'
														AND `unit` = '$unit'
														AND `commodity` = '$commodity'
														AND `year` = '$prevyear'
														AND `month` = '$prevmonth'
												";
												if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
													$prevrow = $prevresult->fetch_assoc();
													$previous = $prevrow['meter'];
													$udatefrom = "{$prevrow['year']}-{$prevrow['month']}-{$prevrow['day']}";
												} else {
													$previous = 0.000;
												}
											}

											// get rate
											// try customer rate first
											$sql = "SELECT rate
													FROM UtlCommodities
													WHERE sitename='$sitename'
														AND commodity = '$commodity'
														AND custref = '$custref'
											";
											$rateresult = $dbcnx->query($sql);
											if (($rateresult) && ($rateresult->num_rows)) {
												$raterow = $rateresult->fetch_assoc();
												$rate = $raterow['rate'];
											} else {
												// get default rate for site
												$sql = "SELECT rate FROM UtlCommodities
													WHERE sitename='$sitename' AND commodity = '$commodity' AND custref = 'Default'
												";
												$rateresult = $dbcnx->query($sql);
												if (($rateresult) && ($rateresult->num_rows)) {
													$raterow = $rateresult->fetch_assoc();
													$rate = $raterow['rate'];
												}
											}

											// Raj 25/08/2020 Lolas manual override Acton Lane Electricity in Units 108 - 113
											if ($custref == "20352" AND $siteid == "16" AND $commodity == "Electricity" AND $unit > "107" AND $unit < "114") {
												$rate = 0.26;
											}

											if (!$rate) {
												$rate = 0;
											}

										} else {
											$custref = "-NF";
											$previous = 0;
											$rate = 0;
										}

										$used   = $reading - $previous;
										$charge = $used * $rate;
										$total += $charge;

										if (($commit) AND ($custref)) {
											
											// commit the charge
											$sql = "INSERT INTO UtlUsedNew VALUES (
												null,
												'$year',
												'$month',
												'$day',
												'$siteid',
												'$sitename',
												'$unit',
												'$custref',
												'$collector',
												'$title',
												'$sensor',
												'{$subs[$commodity]}',
												'$commodity',
												'$udatefrom',
												'',
												'$reading',
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
										print " <td>$title</td>\n";
										print " <td>$sensor</td>\n";
										print " <td>$sub</td>\n";
										print " <td><a href=\"utilities-rayleigh-charges.php?siteid=$siteid\">$sitename</a></td>\n";
										print " <td>$unit</td>\n";
										print " <td>$commodity</td>\n";
										print " <td align=\"right\">$udatefrom</td>\n";
										print " <td align=\"right\">$previous</td>\n";
										print " <td align=\"right\">".number_format($reading,3,'.','')."</td>\n";
										print " <td align=\"right\">".number_format($used,3,'.','')."</td>\n";
										print " <td align=\"right\">$rate</td>\n";
										print " <td align=\"right\">".number_format($charge,2)."</td>\n";
										print "</tr>\n";
									} else {
										if (!$filters) {
											print "<tr class=\"r" . ($i++ % 2) . "\">\n";
											print " <td colspan=\"2\">$collector</td>\n";
											print " <td>$sensor</td>\n";
											print " <td>$sub</td>\n";
											print " <td>NOT FOUND</td>\n";
											print " <td>&nbsp;</td>\n";
											print " <td>&nbsp;</td>\n";
											print " <td></td>\n";
											print " <td align=\"right\">$reading</td>\n";
											print " <td>&nbsp;</td>\n";
											print " <td>&nbsp;</td>\n";
											print " <td>&nbsp;</td>\n";
											print "</tr>\n";
										}
									}
								}
							}
						}
						
						print "<tr>\n";
						print " <td style=\"border-top:1px solid #666\" colspan=\"13\" align=\"right\"><b>".number_format($total,2)."</b></td>\n";
						print "</tr>\n";
						print "</table>\n";

						if (!$commit) {
							print "<p align=\"center\"><b>Commit These Charges</b><br>This will transfer the charges to the history tab where they can be edited.<br><b>WARNING: It will overwrite any changes already applied this month.</b></p>\n";
							print "<form name=\"commitform\" method=\"post\" action=\"utilities-rayleigh-charges.php\">\n";
							print "<input type=\"hidden\" name=\"commit\" value=\"1\">\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$sitefilter\">\n";
							print "<input type=\"hidden\" name=\"filename\" value=\"$file\">\n";
							print "<input type=\"hidden\" name=\"comm\" value=\"$comm\">\n";
							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Commit\" /></p>\n";
							print "</form>\n";
						} elseif ($commit) {
							print "<p align=\"center\"><b>Charging Result</b><br>Committed: $commited<br>Failed: $failed</p>\n";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Commited','Rayleigh Charges','$page','$now')");
						}

					} else {
						$resultMessage .= "Json Decode FAILED<br />";
					}

				} else {
					$resultMessage .= "Could NOT Load File<br />";
				}

			} else {
				$resultMessage .= "No File Found!<br />\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"utilities-collectors-addedit.php\">Add New Collector</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Collector</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	/* print "<pre>";
	print_r($data);
	print "</pre>"; */
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
