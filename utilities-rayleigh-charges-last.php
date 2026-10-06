<?php
ini_set("memory_limit","300M");
$page = "Utilities";
$subpage = "Previous Charges";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Previous Charges</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['commit']) {
				$commit = $_POST['commit'];
				$file   = $_POST['filename'];
			} else {
				$file = "20200427T095037Z.json";
			}

			if ($file) {

				$lastmonth = date("Y-m",strtotime("-1 month"));

				if ($_REQUEST['siteid'] AND $_REQUEST['siteid'] != "All") {
					$siteid = $_REQUEST['siteid'];
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
					if ($comm)   { $delsearch[] = "commodity = '$comm'"; }
					if ($siteid) { $delsearch[] = "siteid = '$siteid'"; }
					if ($delsearch) {
						$delsearch = "AND " . implode(" AND ", $delsearch);
					}
					list($dy,$dm) = explode("-",date("Y-m"));
					$sql = "DELETE FROM UtlUsedNew WHERE year = '$dy' AND month = '$dm' $delsearch";
					$result = $dbcnx->query($sql);
				}

				$data = file_get_contents("../../utilities/$file");
				$data = json_decode($data, true);
				$to = $data['to'] / 1000;
				$to = date("Y-m-d H:i:s",$to);

				list($year,$month,$day) = explode("-",date("Y-m-d"));
				$now = time();

				print "<p align=\"center\">File: $file<br>Readings To: $to<br />Previous is $lastmonth</p>\n";

				print "<form name=\"addform\" method=\"post\" action=\"utilities-rayleigh-charges-last.php\">\n";
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
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Now</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Prev</b></td>\n";
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

							$sql = "SELECT `siteid`, `sitename`, `unit`, `commodity`, `title`
									FROM `UtlCollectors`
									WHERE `collector` = '$collector'
									AND `counter` = '$sensor'
									$filters
									$search
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$tsiteid   = $row['siteid'];
								$sitename  = $row['sitename'];
								$title     = $row['title'];
								$unit      = $row['unit'];
                                $commodity = $row['commodity'];

								// get custref
								$unitsrch = str_replace("A","",$unit);
								$pfxunit = $unitPrefixes[$sitename] . " " . $unitsrch;
								$unitsrch = str_replace("A","",$unit);
                                $sql = "SELECT b.custid
                                        FROM bookings AS b
                                        LEFT JOIN units AS u ON b.unitid = u.ID
                                        WHERE u.title = '$pfxunit'
                                ";
                                $custresult = $dbcnx->query($sql);
                                if (($custresult) AND ($custresult->num_rows)) {
                                    $custrow = $custresult->fetch_assoc();
									$custref = $custrow['custid'];

									// get previous reading
									$lastmonth = date("Y-m",strtotime("-1 month"));
									list($py,$pm) = explode("-",$lastmonth);
									$sql = "SELECT meter, offset
											FROM UtlUsedNew
											WHERE custref   = '$custref'
											  AND collector = '$collector'
											  AND `counter` = '$sensor'
											  AND commodity = '$commodity'
											  AND `year`    = '$py'
											  AND `month`   = '$pm'
									";
									if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
										$prevrow = $prevresult->fetch_assoc();
										$previous = $prevrow['meter'];
										$offset   = $prevrow['offset'];
									} else {
										$previous = 0.000;
										$offset   = 0.000;
									}

									// get rate
									// try customer rate first
									$sql = "SELECT rate FROM UtlCommodities
									WHERE sitename='$sitename' AND commodity = '$commodity' AND custref = '$custref'
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
										'$tsiteid',
										'$sitename',
										'$unit',
										'$custref',
										'$collector',
										'$title',
										'$sensor',
										'{$subs[$commodity]}',
										'$commodity',
										'$offset',
										'',
										'$reading',
										'$previous',
										'$used',
										'$rate',
										'$charge',
										'$now',
										''
									)";
									$result = $dbcnx->query($sql);
									if ($result) {
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
								print " <td align=\"right\">".number_format($reading,3,'.','')."</td>\n";
								print " <td align=\"right\">$previous</td>\n";
                                print " <td align=\"right\">".number_format($used,3,'.','')."</td>\n";
                                print " <td align=\"right\">$rate</td>\n";
                                print " <td align=\"right\">".number_format($charge,2)."</td>\n";
								print "</tr>\n";
							} else {
								if (!$filters) {
									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td>&nbsp;</td>\n";
									print " <td>&nbsp;</td>\n";
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
				print " <td style=\"border-top:1px solid #666\" colspan=\"12\" align=\"right\"><b>".number_format($total,2)."</b></td>\n";
				print "</tr>\n";
				print "</table>\n";

				/* if (!$commit) {
					print "<p align=\"center\"><b>Commit These Charges</b><br>This will transfer the charges to the Itemised Rayleigh tab where they can be edited.<br><b>WARNING: It will overwrite any changes already made.</b></p>\n";
					print "<form name=\"commitform\" method=\"post\" action=\"utilities-rayleigh-charges.php\">\n";
					print "<input type=\"hidden\" name=\"commit\" value=\"1\">\n";
					print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\">\n";
					print "<input type=\"hidden\" name=\"filename\" value=\"$file\">\n";
					print "<input type=\"hidden\" name=\"comm\" value=\"$comm\">\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Commit\" /></p>\n";
					print "</form>\n";
				} elseif ($commit) {
					print "<p align=\"center\"><b>Charging Result</b><br>Committed: $commited<br>Failed: $failed</p>\n";
				} */

			} else {
				$resultMessage .= "No File Founnd!<br>\n";
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
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
