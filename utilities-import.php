<?php
$page = "Utilities";
$subpage = "Import";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES IMPORT</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$posted = $_POST['posted'];
			if ($posted) {
				$site    = $_POST['site'];
				$ftrcust = $_POST['ftrcust'];
				$iday    = $_POST['iday'];
				$imonth  = $_POST['imonth'];
				$iyear   = $_POST['iyear'];
				if ($ftrcust == "All") { $ftrcust = ""; }
				if (($iday) || ($imonth) || ($iyear)) {
					if (!checkdate($imonth,$iday,$iyear)) { $erdate = "Invalid Date"; $e++; }
				}

				if (!$e) {
					$added = 0;
					$total = 0;
					$sites = array();
					if ($site != "All") {
						if ($site) {
							$sites[] = $site;
						}
					} else {
						$sql = "SELECT title FROM sites ORDER BY title";
						$sitesresult = $dbcnx->query($sql);
						if (($sitesresult) && ($sitesresult->num_rows)) {
							while ($sitesrow = $sitesresult->fetch_assoc()) {
								$sites[] = $sitesrow['title'];
							}
						}
					}

					if ($sites) {
						foreach($sites AS $site) {

							# delete existing records for this month/year/site in case we are reposting.
							if ($ftrcust) {
								$search = "`custref`='$ftrcust' AND `year`='$iyear' AND `month`='$imonth' AND `site`='$site'";
							} else {
								$search = "`year`='$iyear' AND `month`='$imonth' AND `site`='$site'";
							}
							$delresult = $dbcnx->query("DELETE FROM `UtlUsed` WHERE $search");
							if ($delresult) {
								$delrows = $dbcnx->affected_rows;
								if ($delrows > 0) {
									$resultMessage .= "<tr><td colspan=\"11\">$delrows existing records for ";
									if ($ftrcust) { $resultMessage .= "DP$ftrcust "; }
									$resultMessage .= "$imonth/$iyear $site deleted</td></tr>\n";
								}
							}

							$sitetotal = 0;

							$resultMessage .= "<tr><td colspan=\"11\">&nbsp;</td></tr>\n";
							$resultMessage .= "<tr><td colspan=\"6\"><strong>$site</strong></td><td colspan=\"5\" align=\"right\"><strong>Charges to $iday/$imonth/$iyear</strong></td></tr>\n";
							$resultMessage .= "<tr><td><i>Result</i></td><td><i>Coll</i></td><td><i>Count</i></td><td><i>Unit</i></td><td><i>Custref</i></td><td><i>Commodity</i></td><td align=\"right\"><i>Reading</i></td><td align=\"right\"><i>Previous</i></td><td align=\"right\"><i>Used</i></td><td align=\"right\"><i>Rate</i></td><td align=\"right\"><i>Charge</i></td></tr>\n";

							# get collector list
							$sql = "SELECT collector, counter FROM UtlCollectors WHERE sitename = '$site' GROUP BY collector ORDER BY collector";
							$collresult = $dbcnx->query($sql);
							if (($collresult) && ($collresult->num_rows)) {
								while ($collrow = $collresult->fetch_assoc()) {
									$collector = $collrow['collector'];
									$counterck = $collrow['counter'];

									$filename = $iyear . "-" . $imonth . "-" . $iday . "_" . $collector . ".csv";
									$thisfile = "../../utilities/$site/$collector/$filename";

									if (is_file($thisfile)) {

										$records = file($thisfile);

										# get final reading for each counter
										$lastline = count($records)-1;
										$Counters = array();
										if ($counterck == 0) {
											list($ndate,$nname,$ntype,$ncom,$KTA,$KTV,$Power_factor,$Sector_pow_factor,$Frequency,$Insertion,$Time_counterhh,
												$Time_countermm,$Active_pow_3ph,$React_pow_ph1,$React_pow_ph2,$React_pow_ph3,$React_pow_3ph,$Appar_pow_3ph,
												$Average_power,$Max_power,$Power_ph1,$Power_ph2,$Power_ph3,$Counters[0],$React_en_3ph,$Voltage_ph1,
												$Voltage_ph2,$Voltage_ph3,$l1_l2voltage,$l2_l3voltage,$l3_l1voltage,$Current_ph1,$Current_ph2,$Current_ph3,$Neutral_current
											) = explode(";",$records[$lastline]);
										} else {
											list($ndate,$nname,$ntype,$ncom,$Counters[1],$Type1,$Counters[2],$Type2,$Counters[3],$Type3,$Counters[4],$Type4,$Counters[5],
												$Type5,$Counters[6],$Type6,$Counters[7],$Type7,$Counters[8],$Type8,$Counters[9],$Type9,$Counters[10],$Type10,$Counters[11],
												$Type11,$Counters[12],$Type12
											) = explode(";",$records[$lastline]);
										}

										foreach ($Counters AS $key => $value) {

											$unit		= "";
											$custref	= "";
											$commodity	= "";
											$ermessage	= "";
											$offset		= 0;
											$used		= 0;
											$charge		= 0;
											$rate		= 0;
											$meter		= 0;
											$previous	= 0;
											$runer		= 0;

											$value = str_replace(",",".",$value);

											# get unit commodity and offset
											$sql = "SELECT unit, commodity, offset FROM UtlCollectors
													WHERE sitename='$site' AND collector = '$collector' AND counter = '$key'
											";
											$result = $dbcnx->query($sql);
											if (($result) && ($result->num_rows)) {
												$row = $result->fetch_assoc();
												$unit      = $row['unit'];
												$commodity = $row['commodity'];
												$offset    = $row['offset'];
												$unit = $unitPrefixes[$site] . " " . $unit;
												$meter = $value+$offset;

												# get customer
												$sql = "SELECT custid FROM bookings
														LEFT JOIN units on bookings.unitid = units.ID
														WHERE units.title = '$unit'
												";
												$custresult = $dbcnx->query($sql);
												if (($custresult) && ($custresult->num_rows)) {
													$custrow = $custresult->fetch_assoc();
													$custref = $custrow['custid'];
												} else {
													$custref = "20199";
												}

												if ((!$ftrcust) || ($custref == $ftrcust)) {

													# get commodity charge rate
													$sql = "SELECT rate FROM UtlCommodities
															WHERE sitename='$site' AND commodity = '$commodity' AND custref = '$custref'
													";
													$rateresult = $dbcnx->query($sql);
													if (($rateresult) && ($rateresult->num_rows)) {
														$raterow = $rateresult->fetch_assoc();
														$rate = $raterow['rate'];
													} else {
														$sql = "SELECT rate FROM UtlCommodities
															WHERE sitename='$site' AND commodity = '$commodity' AND custref = 'Default'
														";
														$rateresult = $dbcnx->query($sql);
														if (($rateresult) && ($rateresult->num_rows)) {
															$raterow = $rateresult->fetch_assoc();
															$rate = $raterow['rate'];
														}
													}
													if (!$rate) {
														$runer++;
														if (!$ermessage) { $ermessage = "Rate Not Found"; }
													}

													# get previous reading for this counter from database
													$prevresult = $dbcnx->query("
														SELECT meter
														FROM UtlUsed
														WHERE site = '$site'
														  AND collector = '$collector'
														  AND counter = '$key'
														ORDER BY year DESC, month DESC, day DESC
														LIMIT 0,1
													");
													if (($prevresult) && ($prevresult->num_rows)) {
														$prevrow = $prevresult->fetch_assoc();
														$previous = $prevrow['meter'];
													} else {
														$previous = 0;
													}

													# calculate charge
													$used   = $meter - $previous;
													$charge = $used * $rate;

													if (!$runer) {
														$sql = "INSERT INTO UtlUsed VALUES (
															'',
															'$iyear',
															'$imonth',
															'$iday',
															'$site',
															'$unit',
															'$custref',
															'$collector',
															'$key',
															'$commodity',
															'$offset',
															'$value',
															'$meter',
															'$previous',
															'$used',
															'$rate',
															'$charge',
															'$now',
															''
														)";
														$postresult = $dbcnx->query($sql);
														if ($postresult) {
															$ermessage = "Okay";
															$added++;
															$sitetotal += $charge;
														} else {
															$ermessage = "Failed To Post";
															$charge = 0;
														}
													}
												} else {
													$ermessage = "Line skipped";
												}
												if ($custref) { $custref = "DP" . $custref; }
											} else {
												$runer++; $ermessage = "Collector Not Found";
											}
											$charge = number_format($charge,2);
											$used   = number_format($used,3);
											$previous = number_format($previous,3);
											$meter = number_format($meter,3);
											$resultMessage .= "<tr><td>$ermessage</td><td>$collector</td><td>$key</td><td>$unit</td><td>$custref</td><td>$commodity</td><td align=\"right\">$meter</td><td align=\"right\">$previous</td><td align=\"right\">$used</td><td align=\"right\">$rate</td><td align=\"right\">$charge</td></tr>\n";

										} # end of counters loop

									} else {
										$resultMessage .= "<tr><td colspan=\"11\">File utilities/$site/$collector/$filename not found</td></tr>\n";
										$runer++;
									}

								} # end of collector loop

								if ($added) {
									$resultMessage .= "<tr><td colspan=\"11\">&nbsp;</td></tr>\n";
									$resultMessage .= "<tr><td colspan=\"10\" align=\"right\"><b>Total Posted</b></td><td align=\"right\"><b>&pound;$sitetotal</b></td></tr>\n";
									$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Imported','$site $iday/$imonth/$iyear','$page','$now')");
								}
							} else {
								$resultMessage .= "<tr><td colspan=\"11\">No Collectors Found</td></tr>\n";
							}
						}
					} else {
						$resultMessage .= "<tr><td colspan=\"11\">No Sites Found</td></tr>\n";
					}

				}

			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					if ((!$iday) && (!$imonth) && (!$iyear)) {
						$yesterday = strtotime("-1 day");
						$iday   = date("d", $yesterday);
						$imonth = date("m", $yesterday);
						$iyear  = date("Y", $yesterday);
					}

					print "<p align=\"center\">Use this to manually import utilities.<br />Csv files must have been transfered from site to server.<br />Any existing data for each collector will be overwritten.</p>\n";
					# print the form
					print "<form name=\"addform\" method=\"post\" action=\"utilities-import.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

					$sql = "SELECT title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					print "		<option value=\"All\">All</option>\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$sitename = $sitesrow['title'];
						print "		<option value=\"$sitename\"";
						if ($sitename == $site) {
							print " SELECTED";
						}
						print ">$sitename</option>\n";
					}
					print "	</select>";
					if ($ersite) { print "&nbsp;<span class=\"redtext\">$ersite</span>"; }
					print "</td>\n";
					print "</tr>\n";

					$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
					$usersresult = $dbcnx->query($sql);
					print "<tr>\n";
					print "	<td align=\"right\">Customer</td>\n";
					print "	<td><select name=\"ftrcust\" class=\"element\">\n";
					print "		<option value=\"All\">All</option>\n";
					while ($usersrow = $usersresult->fetch_assoc()) {
						$compname = $usersrow['compname'];
						$thiscustref = $usersrow['custref'];
						print "		<option value=\"$thiscustref\""; if ($thiscustref == $ftrcust) { print " SELECTED"; } print ">$compname</option>\n";
					}
					print "	</select>";
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Import Date:</td>\n";
					print "	<td>\n";
					print "	<select name=\"iday\" class=\"element_date\">\n";
					for ($o=1; $o <= 31; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($iday == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"imonth\" class=\"element_date\">\n";
					$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
					for ($o=1; $o <= 12; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($imonth == $check) { print "SELECTED "; }
						$op = $o-1;
						print "value=\"$check\">$options[$op]</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"iyear\" class=\"element_date\">\n";
					$options[0] = date("Y")-1;
					$options[1] = date("Y");
					for ($o=0; $o < 2; $o++) {
						print "		<option ";
						if ($iyear == $options[$o]) { print "SELECTED "; }
						print "value=\"$options[$o]\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					if ($erdate) { print "&nbsp;<span class=\"redtext\">$erdate</span>"; }
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<table align=\"center\" cellspacing=\"4\">$resultMessage</table>\n";
			print "<p align=\"center\"><a href=\"utilities-import.php\">Return</a></p>\n";
		}

	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
