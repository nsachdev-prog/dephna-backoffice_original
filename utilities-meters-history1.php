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

            // setup page number
			$rowsPerPage = 30;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			$mid     = $_REQUEST['mid'];  // meter id
			$uid     = $_REQUEST['uid']; // imported id from utilities imported
			$rsiteid = $_REQUEST['siteid'];
			$lpa     = $_REQUEST['lpa']; // return page meters list
			$comm	 = $_REQUEST['comm'];

			if ($uid) {
				// from imported
				// get return vars
				$comm    = $_REQUEST['comm'];
				$site    = $_REQUEST['site'];
				$fmonth  = $_REQUEST['fmonth'];
				$fyear   = $_REQUEST['fyear'];
				$custref = $_REQUEST['custref'];
				$type    = $_REQUEST['type'];
				$qString = "comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;custref=$custref&amp;type=$type&amp;lpa=$lpa";
			}

			$sql = "SELECT * FROM UtlMeters WHERE ID = '$mid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$gatewayid = $row['gatewayid'];
				$typeid    = $row['typeid'];
				$unitid    = $row['unitid'];
				$unitname  = $row['unitname'];
				$commodity = $row['commodity'];

				$sql = "SELECT * FROM UtlGateways WHERE ID = '$gatewayid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$siteid    = $row['siteid'];

					$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$sitename = $row['title'];
					}

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname<br />$commodity</p>\n";
					print "<p align=\"center\" class=\"larger\">History</p>\n";

                        $sql = "SELECT * FROM `UtlRegisters`
                                WHERE `typeid` = '$typeid'
                                  AND `title` = 'Meter Reading'
								   OR `title` = 'Current I1'
								   OR `title` = 'Current I2'
								   OR `title` = 'Current I3'
                        ";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
                            while ($row = $result->fetch_assoc()) {
								$title = $row['title'];
								$registers[$title]['unit']	  = $row['unit'];
								$registers[$title]['start']   = $row['start'];
								$registers[$title]['length']  = $row['length'];
								$registers[$title]['divisor'] = $row['divisor'];
							}

                            // get readings
                            $sql = "SELECT * FROM `UtlReadings`
                                    WHERE `mid` = '$mid'
                                    ORDER BY `xdatetime` DESC
                                    LIMIT $offset, $rowsPerPage
                            ";
                            $result = $dbcnx->query($sql);
                            if (($result) && ($result->num_rows)) {

                                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                print "<tr>\n";
                                print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Current 1</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Current 2</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Current 3</b></td>\n";
                                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Reading</b></td>\n";
                                print "</tr>\n";

                                // row background colours
                                print "<style>\n";
                                print ".r0 { background-color:#fff }\n";
                                print ".r1 { background-color:#ddd }\n";
                                print "</style>\n";

                                while ($row = $result->fetch_assoc()) {
                                    $xdatetime = $row['xdatetime'];
                                    $recData   = $row['recData'];
                                    $recData = unserialize($recData);

									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print "	<td>$xdatetime</td>\n";

									foreach ($registers AS $title => $reg1) {
										$unit    = $reg1['unit'];
										$start   = $reg1['start'];
										$length  = $reg1['length'];
										$divisor = $reg1['divisor'];

                                        if ($length) {

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
										    print "	<td align=\"right\">".number_format($read,'3','.','')."</td>\n";
                                        } else {
                                            print "	<td align=\"right\">&nbsp;</td>\n";
                                        }
                                    }

									print "</tr>\n";

								}

								print "<tr>\n";
								print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                print "</tr>\n";

								print "</table>\n";

                                // pagination block
                                $self   = $_SERVER['PHP_SELF'];
                                $query  = "SELECT COUNT(*) AS `numrows` FROM `UtlReadings` WHERE `mid` = '$mid'";
                                $result = $dbcnx->query($query);
                                $row = $result->fetch_assoc();
                                $numrows = $row['numrows'];
                                $maxPage = ceil($numrows/$rowsPerPage);
                                if ($maxPage > 1) {
                                    $nav  = '';

                                    if ($maxPage > $maxPageNums+2) {
                                        $start = ceil($pageNum-$maxPageNums/2);
                                        if ($start < 3) {
                                            $start = 1;
                                        } else {
                                            $first = " <a href=\"$self?pa=1&amp;mid=$mid&amp;$qString\">1</a> ... ";
                                        }
                                        $end = $start+$maxPageNums;
                                        if ($end > $maxPage-2) {
                                            $end = $maxPage;
                                            $start = $end-$maxPageNums;
                                        } else {
                                            $last = " ... <a href=\"$self?pa=$maxPage&amp;mid=$mid&amp;$qString\">$maxPage</a> ";
                                        }
                                    } else {
                                        $start = 1;
                                        $end = $maxPage;
                                    }

                                    for($pa = $start; $pa <= $end; $pa++) {
                                        if ($pa == $pageNum) {
                                            $nav .= " <span class=\"orangeText\">$pa</span> ";
                                        } else {
                                            $nav .= " <a href=\"$self?pa=$pa&amp;mid=$mid&amp;$qString\">$pa</a> ";
                                        }
                                    }

                                    if ($pageNum > 1) {
                                        $pa  = $pageNum - 1;
                                        $prev  = " <a href=\"$self?pa=$pa&amp;mid=$mid&amp;$qString\">Prev</a> |";
                                    } else {
                                        $prev  = '&nbsp;';
                                    }
                                    if ($pageNum < $maxPage) {
                                        $pa = $pageNum + 1;
                                        $next = "| <a href=\"$self?pa=$pa&amp;mid=$mid&amp;$qString\">Next</a> ";
                                    } else {
                                        $next = '&nbsp;';
                                    }
                                    print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
                                }
                                // pagination end

							} else {
                                $resultMessage = "Nothing Found<br>";
                            }
 
						} else {
                            $resultMessage = "Registers not found<br>" . $dbcnx->error;
                        }

				} else {
					$resultMessage = "Gateway not found<br>" . $dbcnx->error;
				}

			} else {
				$resultMessage = "Meter not found<br>" . $dbcnx->error;
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
		print "<p align=\"center\"><a href=\"utilities-meters-list.php?siteid=$rsiteid&amp;pa=$lpa&amp;comm=$comm\">Return</a></p>\n";
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
