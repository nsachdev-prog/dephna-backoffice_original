<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";
$page = "Holidays";
$failed = 0;
$added  = 0;
date_default_timezone_set('UTC');
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">HOLIDAY REQUESTS</p>\n";
	print "<p align=\"center\"><b>Process Holiday Request</b></p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$hrid = $_REQUEST['hrid'];
			if ($hrid) {

                # get the request
				$sql = "SELECT * FROM hol_requests WHERE ID = '$hrid'";
				$rresult = $dbcnx->query($sql);
				if ($rresult->num_rows) {
					$rrow   = $rresult->fetch_assoc();
					$sid    = $rrow['sid'];
					$fyear  = $rrow['fyear'];
					$fmonth = $rrow['fmonth'];
					$fday   = $rrow['fday'];
					$tyear  = $rrow['tyear'];
					$tmonth = $rrow['tmonth'];
					$tday   = $rrow['tday'];

                    # get staff data
                    $sql = "SELECT `username`, `email` FROM `staff` WHERE `ID`='$sid'";
                    $sresult = $dbcnx->query($sql);
					if ($sresult->num_rows) {
						$srow = $sresult->fetch_assoc();
						$username  = $srow['username'];
						$useremail = $srow['email'];
					}

					# get contract data
					$sql = "SELECT `ID`, workingdays FROM `pr_contracts` WHERE `sid`='$sid'";
					$cresult = $dbcnx->query($sql);
					if ($cresult->num_rows) {
						$crow = $cresult->fetch_assoc();
						$cid = $crow['ID'];
						$workingdays = $crow['workingdays'];
						$workingdays = explode("-",$workingdays);
					}

					# make array of requested days
					$requested = array();
					$start  = strtotime($fyear."-".sprintf("%02s",$fmonth)."-".sprintf("%02s",$fday));
					$end    = strtotime($tyear."-".sprintf("%02s",$tmonth)."-".sprintf("%02s",$tday));
					$days   = ceil(abs($end-$start)/86400)+1;
					$lastday = date("t",$start);
					$y  = $fyear; $m = $fmonth; $d   = $fday;
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
						}
						$d++;
					}
				}

				if ($_POST['posted']) {

					foreach($_POST AS $key => $value) {
						// sanitize
						$_POST[$key] = addslashes($value);
					}

					$message = file_get_contents('email-header.html') . "\r\n";

					if ($_POST['reject']) {
						$notes = strip_tags($_POST['notes']);
						$rawnotes = $notes;
						$notes = addslashes($notes);
						$now = time();

						$sql = "UPDATE hol_requests SET notes='$notes', rejected='on' WHERE ID='$hrid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "Record successfully updated<br>";
							$success++;
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Rejected Holiday Request $hrid','$page','$now')");
						} else {
							$resultMessage .= "Could not update record<br>" . $dbcnx->error . "<br>";
						}

						$subject = "Holiday Request Rejected";
						$message .= "Your request to take the following holiday...<br />\r\n";
						foreach ($requested AS $reqday) {
							$message .= $reqday['datestring']."<br />\r\n";
						}
						$message .= "HAS BEEN REJECTED with the following notes...<br />\r\n";
						$message .= $rawnotes."<br />\r\n";

					} elseif ($_POST['approve']) {
						$type = $_POST['type'];
						$pnotes = strip_tags($_POST['pnotes']);
						$pnotes = addslashes($pnotes);

						if ($cid) {
							$now = time();
		    				foreach ($requested AS $reqday) {
								$sql = "INSERT INTO pr_calendar VALUES (
								null,
								'{$reqday['y']}',
								'{$reqday['m']}',
								'{$reqday['d']}',
								'$sid',
								'$cid',
								'$type',
								'0',
								'',
								'$pnotes'
								)";
								$result = $dbcnx->query($sql);
								if ($result) {
									$added++;
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$username {$reqday['fordate']} $type','$page','$now')");
								} else {
									$failed++;
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Failed','$username {$reqday['fordate']} $type','$page','$now')");
								}
							}
							$resultMessage .= "$added Days Added to Payroll Calendar, $failed Failed.<br />";
							$sql = "DELETE FROM hol_requests WHERE ID='$hrid'";
							$result = $dbcnx->query($sql);
							if (!$result) {
								$resultMessage .= "Could NOT delete request record from database.<br />";
							}

							# email employee
							$subject = "Holiday Request - $username";
							$message .= "Your request to take the following holiday...<br />\r\n";
							foreach ($requested AS $reqday) {
								$message .= $reqday['datestring']."<br />\r\n";
							}
							$message .= "has been approved.<br />\r\n";

						} else {
							$resultMessage .= "Could NOT get contract id<br />";
						}
					}
					if ($useremail) {
						$from = "holidays@dephna.com";
						$message .= "\r\n" . file_get_contents('email-footer.html');
						$mail = new PHPMailer(true);
						try {
							include('../../cron/SMTP.php');
							$mail->setFrom($from, 'Dephna Group');
							$mail->addAddress("$useremail", "$username");
							$mail->addReplyTo($from, 'Dephna Group');
							$mail->isHTML(true);
							$mail->Subject = $subject;
							$mail->Body    = $message;
							$mail->send();

							$resultMessage .= "Message sent to $username<br />";

						} catch (Exception $e) {
							$resultMessage .= "<b>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</b><br />";
						}
					} else {
						$resultMessage .= "<b>No email address found for $username</b><br />";
					}

				} elseif ($hrid) {
					$sql = "SELECT notes, pnotes FROM hol_requests WHERE ID = '$hrid'";
					$hresult = $dbcnx->query($sql);
					if ($hresult->num_rows) {
						$hrow = $hresult->fetch_assoc();
						$notes  = $hrow['notes'];
						$notes  = stripslashes($notes);
						$pnotes = $hrow['pnotes'];
						$pnotes = stripslashes($pnotes);
					} else {
						$resultMessage .= "<b>Record Not Found</b><br />" . $dbcnx->error . "<br />";
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						print "<form name=\"addform\" method=\"post\" action=\"holiday-request-process.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"approve\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"hrid\" value=\"$hrid\" />\n";
						print "<table align=\"center\" cellspacing=\"6px\">\n";

						print "<tr>\n";
						print "	<td align=\"right\" width=\"110px\"><b>Request From:</b></td>\n";
						print "	<td>$username</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>From (first Day):</b></td>\n";
						print "	<td>".sprintf("%02s",$fday)."/".sprintf("%02s",$fmonth)."/$fyear</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>To (last Day):</b></td>\n";
						print "	<td>".sprintf("%02s",$tday)."/".sprintf("%02s",$tmonth)."/$tyear</td>\n";
						print "</tr>\n";

					  	print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Possible Conflicts:</b></td>\n";
						print "<td>\n";
						// get possible conflicts
						foreach ($requested AS $reqday) {
							// from payroll hols calendar
							$sql = "SELECT sid FROM `pr_calendar`
								  WHERE `year`='{$reqday['y']}'
									AND `month`='{$reqday['m']}'
									AND `day`='{$reqday['d']}'
									AND	(`type`='hp' OR `type`='hu' OR `type`='wphp')
								";
							$nresult = $dbcnx->query($sql);
							if ($nresult->num_rows) {
								$prconflicts .= "<br>{$reqday['fordate']} - ";
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
							$prconflicts = "<b>In Payroll Calendar...</b>" . $prconflicts."<br><br>";
						}

						// now search pending holidays
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
								$hrconflicts .= "<br>{$reqday['fordate']} - ";
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
							$hrconflicts = "<b>Pending Approval...</b>" . $hrconflicts."<br><br>";
						}

						// search for staff that don't normally work on requested days
						foreach ($requested AS $reqday) {
							// get day of week
							$unixdate = $reqday['unixdate'];
							$thisdayofweek = date("w",$unixdate);
							$thissid = 0;
							if ($thisdayofweek == 0) {
								$search = "n-_-_-_-_-_-_";
							} elseif ($thisdayofweek == 1) {
								$search = "_-n-_-_-_-_-_";
							} elseif ($thisdayofweek == 2) {
								$search = "_-_-n-_-_-_-_";
							} elseif ($thisdayofweek == 3) {
								$search = "_-_-_-n-_-_-_";
							} elseif ($thisdayofweek == 4) {
								$search = "_-_-_-_-n-_-_";
							} elseif ($thisdayofweek == 5) {
								$search = "_-_-_-_-_-n-_";
							} elseif ($thisdayofweek == 6) {
								$search = "_-_-_-_-_-_-n";
							}
							// check if anyone is nomally off on this day
							$sql = "SELECT `sid` FROM `pr_contracts` WHERE `sid` != '$sid' AND `workingdays` LIKE '$search'";
							if ($n1result = $dbcnx->query($sql) AND $n1result->num_rows) {
								while ($n1row = $n1result->fetch_assoc()) {
									$thissid = $n1row['sid'];
									// check if this person is doing overtime on this day
									$sql = "SELECT `ID`
											 FROM `pr_calendar`
											WHERE `sid` = '$thissid'
											  AND `year` = '{$reqday['y']}'
											  AND `month` = '{$reqday['m']}'
											  AND `day` = '{$reqday['d']}'
											  AND `type` = 'ot'
									";
									if ($c1result = $dbcnx->query($sql) AND $c1result->num_rows) {
										// okay, this person is doing overtime, so no conflict
									} else {
										if (!$wdconflicts) { $wdconflicts .= "<br>{$reqday['fordate']} - "; }
										// get username
										$sql = "SELECT username FROM `staff` WHERE `ID` = '$thissid'";
										if ($q1result = $dbcnx->query($sql) AND $q1result->num_rows) {
											$q1row = $q1result->fetch_assoc();
											$thisusername = $q1row['username'];
											$wdconflicts .= $thisusername . " ";
										}
									}
								}
							}
						}
						if ($wdconflicts) {
							$wdconflicts = "<b>Not normally working...</b>" . $wdconflicts;
						}

						if ($prconflicts) { print $prconflicts; }
						if ($hrconflicts) { print $hrconflicts; }
						if ($wdconflicts) { print $wdconflicts; }
						print "<br>";
						print "</td>\n";
						print "</tr>\n";

						print "<td align=\"right\" valign=\"top\"><b>Select Type:</b></td>\n";
						print "<td>\n";
						print "		<select name=\"type\" id=\"select\" class=\"element_medium\" style=\"width:130px;\">\n";
						print "			<option value=\"hp\">Holiday Paid</option>\n";
						print "			<option value=\"hu\">Holiday Unpaid</option>\n";
						print "		</select>\n";
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Payroll Notes:</b></td>\n";
						print "	<td><textarea name=\"pnotes\" rows=\"5\" class=\"element_box\" style=\"width:240px;\">$pnotes</textarea></td>\n";
						print "</tr>\n";

						print "</table>\n";

						print "<table align=\"center\" cellspacing=\"10px\">\n";
						print "<tr>\n";
						print "	<td><input type=\"submit\" value=\"Approve\" style=\"width:130px;\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

						print "<form name=\"addform\" method=\"post\" action=\"holiday-request-process.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"reject\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"hrid\" value=\"$hrid\" />\n";
						print "<table align=\"center\" cellspacing=\"6px\">\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><b>Rejection Notes:</b> "; if ($ernotes) { print "&nbsp;&nbsp;<span class=\"redtext\">$erNotes</span>"; } print "<br />";
						print "<textarea name=\"notes\" wrap=\"virtual\" cols=\"100\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
						print "</tr>\n";

			  			print "</table>\n";

		  	 			print "<table align=\"center\" cellspacing=\"10px\">\n";
						print "<tr>\n";
						print "	<td><input type=\"submit\" value=\"Reject\" style=\"width:130px;\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

						// print $requested array for debugging
						// print "<pre>";
						// print_r($requested);
						// print "</pre>";
						
					}
				}
			} else {
				$resultMessage .= "Page called without hrid<br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database!<br>" . $dbcnx->connect_error . "<br>";
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

