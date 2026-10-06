<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$bkid   = $_REQUEST['bkid'];
$custid = $_REQUEST['custid'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">Bookings - Contract End</p>\n";
			
			if ($bkid) {
				// get the variables we need out of the tables	
				// get booking details
				$sql = "SELECT custid, siteid, unitid, dateto FROM bookings WHERE ID = '$bkid'";
				if ($bkresult = $dbcnx->query($sql)) {
					if ($bkresult->num_rows) {
						$bkrow = $bkresult->fetch_assoc();
						$custref = $bkrow['custid'];
						$siteid  = $bkrow['siteid'];
						$unitid  = $bkrow['unitid'];
						$conend  = $bkrow['dateto'];
						if ($conend > 0) { $conend = date("Y-m-d",$conend); } else { $conend = ""; }
						
						// get site name
						$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
						if ($siteresult = $dbcnx->query($sql) AND $siteresult->num_rows) {
							$siterow  = $siteresult->fetch_assoc();
							$sitename = $siterow['title'];
						}
						
						// get unit name, unittype
						$sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
						if ($unitresult = $dbcnx->query($sql) AND $unitresult->num_rows) {
							$unitrow  = $unitresult->fetch_assoc();
							$unitname = $unitrow['title'];
							$unittype = $unitrow['unittype'];
						}
				
						// get customer details
						$sql = "SELECT username, compname, email, aemails, addr1, addr2, addr3, addr4, telephone FROM users WHERE custref = '$custref'";
						if ($custresult = $dbcnx->query($sql)) {
							if ($custresult->num_rows) {
								$custrow = $custresult->fetch_assoc();
								$compname  = $custrow['compname'];
								$custname  = $custrow['username'];
								$email     = $custrow['email'];
								$aemails   = $custrow['aemails'];
								$addr1     = $custrow['addr1'];
								$addr2     = $custrow['addr2'];
								$addr3     = $custrow['addr3'];
								$addr4     = $custrow['addr4'];
								$telephone = $custrow['telephone'];
								if (!$aemails) { $aemails = $email; }
							} else {
								$resultMessage = "<b>Could not get customer details from users</b>"; $e++;
							}
						} else {
							$resultMessage = "<b>Could not get anything from users database</b><br>" . $dbcnx->error; $e++;
						}
						
					} else {
						$resultMessage = "<b>Could not get custid from bookings database</b>"; $e++;
					}
				} else {
					$resultMessage = "<b>Could not get anything from bookings database</b><br>" . $dbcnx->error; $e++;
				}
				
				
				print "<p align=\"center\" class=\"heading\">$sitename - $unitname - $compname</p>\n";
				
				if ($_POST['posted']) {
				
					// error check
					$cedate = $_POST['cedate'];
					if (!$cedate) {
						$period = $_POST['period'];
						if ($period == "Select") { $erperiod = "Please select a period"; $e++; }
						$nrdate = $_POST['nrdate'];
						if (!$nrdate) { $ernrdate = "Please enter notice received"; $e++; }
					}
					
					$mailit = $_POST['mailit'];
					if ($mailit) {
						$subject  = strip_tags($_POST['subject']);
						$body     = strip_tags($_POST['body']);
						if (!$subject) { $ersubject = "No Subject"; $e++; }
						if (!$body)    { $erbody    = "No Message"; $e++; }
					}
					
					$depositrequest = $_POST['depositrequest'];
					$inventory      = $_POST['inventory'];
					
					if (!$e) {
						// if no errors post to database
						$now = time();
						$periods = array("1w" => "1 Week","1m" => "1 Month","3m" => "3 Months","6m" => "6 Months");
						if (!$cedate) {
							$cedate = $nrdate;
							$unixcedate = strtotime("$cedate +{$periods[$period]}");
							$cedate = date("Y-m-d",$unixcedate);
						} else {
							$unixcedate = strtotime("$cedate");
						}
						// reversre $cedate
						list($y,$m,$d) = explode("-",$cedate);
						$cedate  = "$d-$m-$y";
						$txndate = "$y-$m-$d";
						$duedate = date("Y-m-d",strtotime("+14 days",$unixcedate));
						$body = str_replace("[[enddate]]",$cedate,$body);
						
						$sql = "UPDATE bookings SET dateto = '$unixcedate' WHERE ID = '$bkid'";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage = "Record successfuly updated<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $unitname Contract End Date','$page','$now')");
							
							if ($mailit) {
								// if mail to customer selected
								require "../../PHPMailer/src/SMTP.php";
								require "../../PHPMailer/src/PHPMailer.php";
								require "../../PHPMailer/src/Exception.php";

								$from = "accounts@dephna.com";
								$fromname = "Dephna Accounts";
								
								$message  = file_get_contents('email-header.html') . "\r\n";
								$message .= nl2br($body);
								$message .= file_get_contents('email-footer.html') . "\r\n";

								$mail = new PHPMailer(true);

								try {
									
									include('../../cron/SMTP.php');

									$mail->CharSet = 'UTF-8';
									$mail->setFrom($from, $fromname);
									$mail->addReplyTo($from, $fromname);
									$emails = explode(",",$aemails);
									foreach ($emails as $e) {
										$mail->addAddress($e);
									}
									$mail->addBCC('accounts@dephna.com');
									$mail->Subject = $subject;
									$mail->isHTML(true);
									$mail->Body    = $message;
									$mail->send();
									$resultMessage .= "Email sent to $aemails<br>";

								} catch (Exception $e) {
									$resultMessage .= "<b>Could not send email to customer</b><br>";
								}
								
							} else {
								$resultMessage .= "Email customer not selected";
							}
						} else {
							$resultMessage = "<b>Could not update bookings database</b><br>" . $dbcnx->error;
						}
						
						if ($depositrequest == "on") {
							$sql = "INSERT INTO deposit_requests VALUES (
								null,
								'$custref',
								'0',
								'',
								'',
								'',
								'$duedate',
								'0',
								'0',
								'0',
								'',
								'',
								'$now',
								'{$_SESSION['username']}'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Reposit request activated<br>";
							} else {
								$resultMessage .= "<b>Could NOT activate deposit request</b><br>";
							}
						}

						if ($inventory == "on") {
							if ($unittype != "Car Parking") {
								// check if there is already a current checkout for this client at this site
								$sql = "SELECT `ID` FROM `inventory`
										WHERE `custref` = '$custref'
											AND `siteid`  = '$siteid'
											AND `inout`   = 'out'
											AND `txndate` = '$txndate'
											AND `sent`    = '0'
								";
								if ($iresult = $dbcnx->query($sql) AND $iresult->num_rows) {
									$irow = $iresult->fetch_assoc();
									$iid  = $irow['ID'];
									$resultMessage .= "Existing Checkout Found<br>";
								} else {
									// create new checkout
									$now = time();
									$sql = "INSERT INTO inventory VALUES (
										null,
										'$custref',
										'$aemails',
										'$siteid',
										'$txndate',
										'out',
										'',
										'',
										'',
										'',
										'0',
										'',
										'0',
										'0',
										'',
										'',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($dbcnx->query($sql)) {
										$iid = $dbcnx->insert_id;
										$resultMessage .= "New Checkout Created<br>";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$custref IID$iid','$page','$now')");
										$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$iid','$now','{$_SESSION['user']}','{$_SESSION['username']}','Contract End Started in Bookings')");
									} else {
										$resultMessage .= "<b>Could NOT Create New Checkout</b><br>" . $dbcnx->error . "<br>";
									}
								}

								if ($iid) {
									// add the unit
									$sql = "INSERT INTO inventory_units VALUES (
										null,
										'$iid',
										'$custref',
										'$siteid',
										'$unitid',
										'$unitname',
										'$unittype',
										'',
										'',
										'',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Unit $unitname Added To Checkout<br>";
										$uid = $dbcnx->insert_id;
										list($pf,$unit) = explode(" ",$unitname);
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref IID$iid $unitname','$page','$now')");

										// get manual meters for this unit
										$sql = "SELECT `ID`, `commodity` FROM `UtlCollectors` WHERE `siteid` = '$siteid' AND `unit` = '$unit'";
										if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

											while ($row = $result->fetch_assoc()) {

												$umid      = $row['ID'];
												$commodity = $row['commodity'];

												// get previous reading
												$sql = "SELECT `meter`, `year`, `month`, `day`
															FROM `UtlUsedNew`
															WHERE `siteid` = '$siteid'
															AND `unit` = '$unit'
															AND `commodity` = '$commodity'
														ORDER BY `year` DESC, `month` DESC, `day` DESC
															LIMIT 0,1
												";
												if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
													$urow = $uresult->fetch_assoc();
													$previous = $urow['meter'];
													$py       = $urow['year'];
													$pm       = $urow['month'];
													$pd       = $urow['day'];
													$prevdate = "$py-$pm-$pd";
												} else {
													$previous = 0;
													$prevdate = "";
												}

												$sql = "INSERT INTO inventory_meters VALUES (
													null,
													'$iid',
													'$uid',
													'out',
													'$custref',
													'$siteid',
													'$unitid',
													'$unit',
													'$unitname',
													'$umid',
													'$commodity',
													'$prevdate',
													'$previous',
													'$previous',
													'',
													'',
													'$now',
													'{$_SESSION['username']}'
												)";
												if ($dbcnx->query($sql)) {
													$resultMessage .= "$unitname Manual $commodity Added<br />";
												} else {
													$resultMessage .= "<b>Could NOT Add $unitname Manual $commodity to Checkout</b><br>";
												}
											}
										} else {
											$resultMessage .= "No Manual Meters Found for $unitname<br />";
										}

										// get meters on gateways
										$sql = "SELECT `ID`, `commodity` FROM `UtlMeters` WHERE `siteid` = '$siteid' AND `unitname` = '$unit'";
										if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
											while ($row = $result->fetch_assoc()) {

												$umid      = $row['ID'];
												$commodity = $row['commodity'];

												// get previous reading
												$sql = "SELECT `meter`, `year`, `month`, `day`
															FROM `UtlUsedNew`
															WHERE `siteid` = '$siteid'
															AND `unit` = '$unit'
															AND `commodity` = '$commodity'
														ORDER BY `year` DESC, `month` DESC, `day` DESC
														LIMIT 0,1
												";
												if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
													$urow = $uresult->fetch_assoc();
													$previous = $urow['meter'];
													$py       = $urow['year'];
													$pm       = $urow['month'];
													$pd       = $urow['day'];
													$prevdate = "$py-$pm-$pd";
												} else {
													$previous = 0;
													$prevdate = "";
												}

												$sql = "INSERT INTO inventory_meters VALUES (
													null,
													'$iid',
													'$uid',
													'out',
													'$custref',
													'$siteid',
													'$unitid',
													'$unit',
													'$unitname',
													'$umid',
													'$commodity',
													'$prevdate',
													'$previous',
													'$previous',
													'',
													'',
													'$now',
													'{$_SESSION['username']}'
												)";
												if ($dbcnx->query($sql)) {
													$resultMessage .= "$unitname $commodity Added to Checkout<br>";
												} else {
													$resultMessage .= "<b>Could NOT Add $unitname $commodity to Checkout</b><br>";
												}
											}
										} else {
											$resultMessage .= "No Gateway Meters Found for $unitname<br>";
										}
										
									} else {
										$resultMessage .= "<b>Could NOT Add Unit $unitname to Checkout</b><br>";
									}
								} else {
									$resultMessage .= "<b>Checkout NOT FOUND or NOT Created</b><br>";
								}
							} else {
								$resultMessage .= "Car Parking Not Added To Checkout<br>";
							}
						} else {
							$resultMessage .= "Inventory Not Selected<br>";
						}
					}
				} else {
					// get email template
					$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'ContractEndUnits'";
					if ($tresult = $dbcnx->query($sql)) {
						if ($tresult->num_rows) {
							$trow = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];
							// replace fields in template with data
							$search  = array("[[custname]]","[[staffname]]","[[unitname]]");
							$replace = array($custname,$_SESSION['username'],$unitname);
							$body    = str_replace($search,$replace,$body);
						} else {
							$resultMessage = "<b>Could not get email template ContractEndUnits.</b>\n";
						}
					} else {
						$resultMessage = "<b>Could not get anything from templates.</b>\n";
					}
					$mailit = "on";
					$inventory = "on";
					
					// get deposit request status
					$sql = "SELECT ID FROM `deposit_requests` WHERE `custref` = '$custref' AND `refunded` = '0'";
					if ($dresult = $dbcnx->query($sql)) {
						if ($dresult->num_rows) {
							$depositrequest = "active";
						}
					}
				}
				
				if ((!$_POST['posted']) OR ($e)) {
					// print form
					print "<form name=\"invform\" method=\"post\" action=\"bookings-end.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
					print "<input type=\"hidden\" name=\"custid\" value=\"$custid\" />\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					
					if ($conend) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Current End Date:</b></td>\n";
						print "	<td>$conend</td>\n";
						print "</tr>\n";
					}
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Notice Received:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"nrdate\" value=\"$nrdate\" class=\"element\" />";
					if ($ernrdate) { print "&nbsp;<span class=\"redtext\">$ernrdate</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Notice Period:</b></td>\n";
					print "	<td><select name=\"period\" class=\"element\">\n";      
					print "		<option value=\"Select\">Select</option>\n";
					print "		<option value=\"1w\""; if ($period == "1w") { print " SELECTED"; } print ">1 Week</option>\n";
					print "		<option value=\"1m\""; if ($period == "1m") { print " SELECTED"; } print ">1 Month</option>\n";
					print "		<option value=\"3m\""; if ($period == "3m") { print " SELECTED"; } print ">3 Months</option>\n";
					print "		<option value=\"6m\""; if ($period == "6m") { print " SELECTED"; } print ">6 Months</option>\n";
					print "	</select>";
					if ($erperiod) { print "&nbsp;<span class=\"redtext\">$erperiod</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Override End Date:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker2\" name=\"cedate\" value=\"$cedate\" class=\"element\" />";
					if ($ercedate) { print "&nbsp;<span class=\"redtext\">$ercedate</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Email Customer:</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Send To Inventory Check Out</b>:</td>\n";
					print "	<td><input type=\"checkbox\" name=\"inventory\""; if ($inventory == "on") { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Subject:</b></td>\n";
					print "	<td><input type=\"text\" name=\"subject\" value=\"$subject\" class=\"element\" />";
					if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\">Do not change [[enddate]] below.<br>This will be replaced with your selection above.</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Message:</b>"; if ($erbody) { print "&nbsp;<span class=\"redtext\">$erbody</span>"; } print "<br>";
					print "<textarea name=\"body\" wrap=\"virtual\" rows=\"15\" class=\"element_box_large\">$body</textarea></td>\n";
					print "</tr>\n";
					
					// display deposit history for this Customer
					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\"><b>Deposits History</b></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\">\n";

					$sql = "SELECT 	l.details2, l.linetotal,
									h.txtype, h.invnumber, h.year, h.month, h.day
								FROM inv_lines AS l
								LEFT JOIN inv_headers AS h ON l.hid = h.ID
								WHERE h.custref='$custref' AND l.unittype = 'Deposit'
								ORDER BY h.year, h.month, h.day
					";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td class=\"colHead\">Date</td>\n";
						print "	<td class=\"colHead\">T</td>\n";
						print "	<td class=\"colHead\">Inv/Crd Num</td>\n";
						print "	<td class=\"colHead\">Details</td>\n";
						print "	<td class=\"colHeadEnd\" align=\"right\">Amount</td>\n";
						print "</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$year   = $row['year'];
							$month  = $row['month'];
							$day    = $row['day'];
							$txtype = $row['txtype'];
							$invnum = $row['invnumber'];
							$amount = $row['linetotal'];
							$details = $row['details2'];
							$tamount += $amount;
							print "<tr>\n";
							print "	<td class=\"colGreen\">$day/$month/$year</td>\n";
							print "	<td class=\"colGreen\">$txtype</td>\n";
							print "	<td class=\"colGreen\">$invnum</td>\n";
							print "	<td class=\"colGreen\">$details</td>\n";
							print "	<td class=\"colEndGreen\" align=\"right\">".number_format($amount,2)."</td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "	<td class=\"colGreen\" colspan=\"4\" align=\"right\">Deposits Still Held</td>\n";
						print "	<td class=\"colEndGreen\" align=\"right\">".number_format($tamount,2)."</td>\n";
						print "</tr>\n";
						print "</table>\n";
						
					} else {
						print "<p align=\"center\">No Deposit History</p>\n";
					}
					print "</td>";
					print "</tr>\n";
					
					if ($depositrequest != "active") {
						print "<input type=\"hidden\" name=\"depositrequest\" value=\"on\" />\n";
					}
					
					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"Submit\" style=\"width:100px;\" /></td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "</form>\n";
					
					// jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";
				}

			} else {
				$resultMessage = "<b>Page called without bkid</b>";
			}			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
	if ($custid) {
		print "<p align=\"center\"><a href=\"sl-customer-view.php?id=$custid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><a href=\"bookings-units-view.php?siteid=$siteid\">Return</a></p>\n";
	}
	print "<p align=\"center\">&nbsp;</p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include ('footer.php');
?>
