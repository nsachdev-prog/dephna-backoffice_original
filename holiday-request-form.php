<?php
$page = "Holidays";
date_default_timezone_set('UTC');
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">HOLIDAY REQUESTS</p>\n";
	print "<p align=\"center\">Holiday Request Form</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				$sid       = $_POST['sid'];
				$fday      = $_POST['fday'];
				$fmonth    = $_POST['fmonth'];
				$fyear     = $_POST['fyear'];
				$tday      = $_POST['tday'];
				$tmonth    = $_POST['tmonth'];
				$tyear     = $_POST['tyear'];
				$pnotes    = strip_tags($_POST['pnotes']);

				if (($fday) && ($fmonth) && ($fyear)) {
					if (!checkdate($fmonth,$fday,$fyear)) {
						$erfdate = "Invalid Date"; $e++;
					}
				} else {
					$erfdate = "Enter Date"; $e++;
				}

				if (($tday) && ($tmonth) && ($tyear)) {
					if (!checkdate($tmonth,$tday,$tyear)) {
						$ertdate = "Invalid Date"; $e++;
					}
				} else {
					$ertdate = "Enter Date"; $e++;
				}

				if (!$e) {

					# get staff data
                    $sql = "SELECT `username`, `email` FROM `staff` WHERE `ID`='$sid'";
                    $sresult = $dbcnx->query($sql);
					if ($sresult->num_rows) {
						$srow = $sresult->fetch_assoc();
						$username  = $srow['username'];
						$useremail = $srow['email'];
					} else {
						$resultMessage .= "<b>Could not get staff data</b><br>" . $dbcnx->error; $e++;
					}

					# get contract data
					$sql = "SELECT * FROM `pr_contracts` WHERE `sid`='$sid'";
					$cresult = $dbcnx->query($sql);
					if ($cresult->num_rows) {
						$crow = $cresult->fetch_assoc();
						$cid          = $crow['ID'];
						$workingdays  = $crow['workingdays'];
						$workinghours = $crow['workinghours'];
						$holsone      = $crow['holsone'];
						$stdholidays  = $crow['stdholidays'];
						$constartdate = $crow['constartdate'];
						$csyear       = date("Y",$constartdate);
						$currentYear  = $fyear;
						if ($csyear == $currentYear) { $totalhols = $holsone; } else { $totalhols = $stdholidays; }
						$workingdays = explode("-",$workingdays);
					} else {
						$resultMessage .= "<b>Could not get contract data</b><br>" . $dbcnx->error; $e++;
					}

					if (!$e) {
						# make array of requested days
						$requested = array();
						$start  = strtotime($fyear."-".sprintf("%02s",$fmonth)."-".sprintf("%02s",$fday));
						$end    = strtotime($tyear."-".sprintf("%02s",$tmonth)."-".sprintf("%02s",$tday));
						$days   = ceil(abs($end-$start)/86400)+1;
						$lastday = date("t",$start);
						# print "$start<br>$end<br>$days<br>$lastday<br>";
						$y = $fyear; $m = $fmonth; $d = $fday;
						for ($n = 0; $n < $days; $n++) {
							if ($d > $lastday) {
								$d = 1; $m++;
								if ($m > 12) {
									$m = 1; $y++;
								}
							}
							$revdate = $y."-".sprintf("%02s",$m)."-".sprintf("%02s",$d);
							$fordate = sprintf("%02s",$d)."-".sprintf("%02s",$m)."-".$y;
							$unixdate = strtotime($revdate);
							$thisdayofweek = date("w",$unixdate);
							if ($workingdays[$thisdayofweek] == "y") {
								$requested[$n]['y'] = "$y";
								$requested[$n]['m'] = "$m";
								$requested[$n]['d'] = "$d";
								$requested[$n]['unixdate']   = $unixdate;
								$requested[$n]['revdate']    = $revdate;
								$requested[$n]['fordate']    = $fordate;
								$requested[$n]['datestring'] = date("D jS M Y",strtotime("$y-$m-$d"));
								$daysrequested++;
							}
							$d++;
						}

						# print "<pre>\n";
						# print_r($requested);
						# print "<pre>\n";

						# find out how many days total allowed
						if ($cid) {
							# find out how many days are already in the calendar
							$sql = "SELECT * FROM pr_calendar WHERE cid='$cid' AND year='$currentYear' AND (type='hp' OR type='wphp')";
							$calresult = $dbcnx->query($sql);
							$daystaken = 0;
							$hourstaken = 0;
							if ($calresult->num_rows) {
								while ($calrow = $calresult->fetch_assoc()) {
									$thistype    = $calrow['type'];
									$thesethours = $calrow['hours'];
									if ($thistype == "hp") { $daystaken++; }
									if ($thistype == "wphp") { $hourstaken += $thesethours; }
								}
								while ($hourstaken >= $workinghours) { $daystaken++; $hourstaken -= $workinghours; }
							}

							# find out how many days are pending approval
							$dayspending = 0;
							$sql = "SELECT * FROM `hol_requests` WHERE `sid`='$sid' AND `rejected`!='on'";
							$rresult = $dbcnx->query($sql);
							if ($rresult->num_rows) {
								while ($rrow = $rresult->fetch_assoc()) {
									$rfday    = $rrow['fday'];
									$rfmonth  = $rrow['fmonth'];
									$rfyear   = $rrow['fyear'];
									$rtday    = $rrow['tday'];
									$rtmonth  = $rrow['tmonth'];
									$rtyear   = $rrow['tyear'];

									$pdstart = strtotime($rfyear."-".sprintf("%02s",$rfmonth)."-".sprintf("%02s",$rfday));
									$pdend   = strtotime($rtyear."-".sprintf("%02s",$rtmonth)."-".sprintf("%02s",$rtday));
									$dayspending += ceil(abs($pdend-$pdstart)/86400)+1;

								}
							}

							if ($daysrequested > ($totalhols-$daystaken-$dayspending)) {
								$resultMessage = "<b>Holiday Entitlement Exceeded</b><br>
								You have requested $daysrequested Day".plural($daysrequested)."<br>
								Your total entitlement for this year is $totalhols Day".plural($totalhols)."<br>
								You have already booked or taken $daystaken Day".plural($daystaken)." $hourstaken Hour".plural($hourstaken)."<br>
								And have $dayspending Day".plural($dayspending)." pending approval";
								$e++;
							}

							if (!$e) {
								$pnotes = addslashes($pnotes);
								$now = time();
								$sql = "INSERT INTO hol_requests VALUES (
								null,
								'$sid',
								'$fyear',
								'$fmonth',
								'$fday',
								'$tyear',
								'$tmonth',
								'$tday',
								'$pnotes',
								'',
								'$now',
								''
								)";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage = "<b>Record successfully added</b>";
									$success++;
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Added','Holiday Request','$page','$now')");
								} else {
									$resultMessage = "<b>Could not add new record to database</b><br>" . $dbcnx->error;
								}
							}

							if ($success) {
								if (!$_SESSION['testmode']) {
									$to   = "\"$username\" <$useremail>";
									$from = "holidays@dephna.com";
									$headers = "From: $from\r\nReply-To: $from\r\nX-Mailer: PHP/" . phpversion();
									$subject = "Holiday Request - {$_SESSION['username']}";
									$message = "Your request to take the following holiday...\r\n";
									foreach ($requested AS $reqday) {
										$message .= $reqday['datestring']."\r\n";
									}
									$message .= "Has been sent for approval\r\n";

									# mail employee
									if (mail($to, $subject, $message, $headers)) {
										$resultMessage .= "<br />An email confirming this request has been sent to<br />$username - $useremail";
									}

									# get possible conflicts to email Raj.

									# from payroll hols calendar

									foreach ($requested AS $reqday) {
										$sql = "SELECT sid FROM `pr_calendar`
											  WHERE `year`='{$reqday['y']}'
												AND `month`='{$reqday['m']}'
												AND `day`='{$reqday['d']}'
												AND	(`type`='hp' OR `type`='hu' OR `type`='wphp')
											";
										$nresult = $dbcnx->query($sql);
										if ($nresult->num_rows) {
											$prconflicts .= "\r\n{$reqday['fordate']} - ";
											while ($nrow = $nresult->fetch_assoc()) {
												$thissid = $nrow['sid'];
												$sql = "SELECT username FROM `staff` WHERE `ID`='$thissid'";
												$qresult = $dbcnx->query($sql);
												if ($qresult->num_rows) {
													$qrow = $qresult->fetch_assoc();
													$thisusername = $qrow['username'];
													$prconflicts .= $thisusername . " ";
												}
											}
										}
									}
									if ($prconflicts) {
										$prconflicts = "In Payroll Calendar..." . $prconflicts."\r\n\r\n";
									}

									# now search pending holidays
									foreach ($requested AS $reqday) {
										$concatday = $reqday['y'].sprintf("%02s",$reqday['m']).sprintf("%02s",$reqday['d']);
										$sql = "SELECT sid FROM `hol_requests`
												WHERE CONCAT(fyear,LPAD(fmonth,2,0),LPAD(fday,2,0))<='$concatday'
												AND CONCAT(tyear,LPAD(tmonth,2,0),LPAD(tday,2,0))>='$concatday'
												AND `sid`!='$sid'
												AND `rejected`!='on'
												";
										$nresult = $dbcnx->query($sql);
										if ($nresult->num_rows) {
											$hrconflicts .= "\r\n{$reqday['fordate']} - ";
											while ($nrow = $nresult->fetch_assoc()) {
												$thissid = $nrow['sid'];
												$sql = "SELECT username FROM `staff` WHERE `ID`='$thissid'";
												$qresult = $dbcnx->query($sql);
												if ($qresult->num_rows) {
													$qrow = $qresult->fetch_assoc();
													$thisusername = $qrow['username'];
													$hrconflicts .= $thisusername . " ";
												}
											}
										}
									}
									if ($hrconflicts) {
										$hrconflicts = "Pending Approval..." . $hrconflicts;
									}

									# mail Raj
									$to = "holidays@dephna.com";
									# $to = "\"Mat Blackmore\" <mail@matthewblackmore.com>";
									# $to = "\"David Sherwood\" <david@davidsherwood.co.uk>";
									$message = "Holiday request from {$_SESSION['username']}\n";
									foreach ($requested AS $reqday) {
										$message .= $reqday['datestring']."\n";
									}
									$message .= "\n\n$pnotes\n\n";
									$message .= "Possible Conflicts:\n";
									$message .= $prconflicts . "\n\n" . $hrconflicts;
									mail($to, $subject, $message, $headers);
								} else {
									$resultMessage .= "<br><b><span class=\"redtext\">Emails not sent in test mode</span></b>";
								}
							}
						} else {
							$resultMessage = "<b>Could not get contract details</b><br />" . $dbcnx->error;
						}
					}
				}

			} elseif ($hrid) {
				$sql = "SELECT * FROM hol_requests WHERE ID = '$hrid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$fyear  = $row['fyear'];
					$fmonth = $row['fmonth'];
					$fday   = $row['fday'];
					$tyear  = $row['tyear'];
					$tmonth = $row['tmonth'];
					$tday   = $row['tday'];
					$notes  = $row['notes'];
					$pnotes = $row['pnotes'];
					$notes  = stripslashes($notes);
					$pnotes = stripslashes($pnotes);
					$notes  = str_replace('\n',"<br />",$notes);
				} else {
					$resultMessage = "<b>Record Not Found</b><br />" . $dbcnx->error;
				}
			} else {
				$fyear  = date("Y");
				$fmonth = date("m");
				$fday   = date("d");
				$tyear  = date("Y");
				$tmonth = date("m");
				$tday   = date("d");
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					print "<form name=\"addform\" method=\"post\" action=\"holiday-request-form.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"hrid\" value=\"$hrid\" />\n";
					print "<table align=\"center\" border=\"0\">\n";

					if ($accessRights[$page] > 2) {

						$sql = "SELECT ID, username FROM `staff` ORDER BY `username`";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Employee:</b></td>\n";
							print "	<td><select name=\"sid\" class=\"element\">\n";
							while ($row = $result->fetch_assoc()) {
								$thissid  = $row['ID'];
								$thisname = $row['username'];
								print "		<option value=\"$thissid\""; if ($thissid == $sid) { print " SELECTED"; } print ">$thisname</option>\n";
							}
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";
						} else {
							$resultMessage = "Could not get employees from database<br />" . $dbcnx->error;
						}
					} else {
						print "<input type=\"hidden\" name=\"sid\" value=\"{$_SESSION['user']}\" />\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Request From:</b></td>\n";
						print "	<td>{$_SESSION['username']}</td>\n";
						print "</tr>\n";
					}

				  	print "<tr>\n";
					print "	<td align=\"right\"><b>From (first day):</b></td>\n";
					print "	<td>\n";
					print "	<select name=\"fday\" class=\"element_date\">\n";
					for ($o=1; $o < 32; $o++) {
						print "		<option ";
						if ($fday == $o) { print "SELECTED "; }
						print "value=\"$o\">$o</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"fmonth\" class=\"element_date\">\n";
					$options = array("null","January","February","March","April","May","June","July","August","September","October","November","December");
					for ($o=1; $o < 13; $o++) {
						print "		<option ";
						if ($fmonth == $o) { print "SELECTED "; }
						print "value=\"$o\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"fyear\" class=\"element_date\">\n";
					$options[0] = date("Y");
					$options[1] = date("Y")+1;
					for ($o=0; $o < 2; $o++) {
						print "		<option ";
						if ($fyear == $options[$o]) { print "SELECTED "; }
						print "value=\"$options[$o]\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					if ($erfdate) { print "&nbsp;<span class=\"redtext\">$erfdate</span>"; }
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>To (last day):</b></td>\n";
					print "	<td>\n";
					print "	<select name=\"tday\" class=\"element_date\">\n";
					for ($o=1; $o < 32; $o++) {
						print "		<option ";
						if ($tday == $o) { print "SELECTED "; }
						print "value=\"$o\">$o</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"tmonth\" class=\"element_date\">\n";
					$options = array("null","January","February","March","April","May","June","July","August","September","October","November","December");
					for ($o=1; $o < 13; $o++) {
						print "		<option ";
						if ($tmonth == $o) { print "SELECTED "; }
						print "value=\"$o\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"tyear\" class=\"element_date\">\n";
					$options[0] = date("Y");
					$options[1] = date("Y")+1;
					for ($o=0; $o < 2; $o++) {
						print "		<option ";
						if ($tyear == $options[$o]) { print "SELECTED "; }
						print "value=\"$options[$o]\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					if ($ertdate) { print "&nbsp;<span class=\"redtext\">$ertdate</span>"; }
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>\n";
					print "	<td><textarea name=\"pnotes\" rows=\"5\" class=\"element_box\" style=\"width:240px;\">$pnotes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Rejection Notes:</b></td>\n";
					print "	<td>$notes</td>\n";
					print "</tr>\n";

				  	print "<tr>\n";
					print "<td colspan=\"2\"><div style=\"float:left;width:400px;padding:8px;border:1px dashed #ccc;margin:10px;font-size:10px;\"><h3>PLEASE NOTE:</h3><ol><li>The system will automatically reject a holiday if it goes over your holiday allowance - this also includes any outstanding holiday still to be approved.</li>
						<li>No two staff members are permitted to have the same day off.</li>
						<li>All holidays over one week require a minimum of 4 weeks notice.</li>
						<li>All holidays which are one week or less require a two week notice.</li>
						<li>Holidays are not approved on a first come first serve basis.</li>
						<li>We do not issue half day holidays - (if you urgently require a half day please email <a href=\"mailto:holidays@dephna.com\">holidays@dephna.com</a> - it is at the discretion of your line manager to approve this).</li></ol>
						</div></td>\n";
					print "</tr>\n";

				  	print "<tr>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

		  			print "</table>\n";

	  	 			print "<table align=\"center\" cellspacing=\"10px\">\n";
					print "<tr>\n";
					print "	<td><input type=\"submit\" value=\"Submit\" style=\"width:130px;\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"holiday-request-view.php\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
function plural($n)
{
	if ($n > 1) { return("s"); } else { return(""); }
}
?>
