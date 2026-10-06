<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$page = "Sales_Ledger";
$subpage = "Pending";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['cleared']) {
				$cleared = $_POST['cleared'];
				$txndate = $_POST['txndate'];
				list($day,$month,$year) = explode("-",$txndate);
				if (!validateDate("$year-$month-$day")) {
					$errorMessage .= "<b>Invalid Date</b><br>";
				} else {

					$now = time();
					foreach ($cleared AS $pmtid => $junk) {

						// get payment/receipt
						$sql = "SELECT h.txtype, u.username, u.compname, h.custref, u.tim, u.aemails, u.email, h.ddmonth
								FROM inv_headers AS h
								LEFT JOIN users AS u ON u.custref = h.custref
								WHERE h.ID = '$pmtid'
						";
						if (($presult = $dbcnx->query($sql)) AND ($presult->num_rows)) {
							$prow = $presult->fetch_assoc();
							$compname   = $prow['compname'];
							$custname   = $prow['username'];
							$custref    = $prow['custref'];
							$txtype     = $prow['txtype'];
							$restricted = $prow['tim'];
							$aemails    = $prow['aemails'];
							$email      = $prow['email'];
							$ddmonth    = $prow['ddmonth'];
							$txtype     = strtoupper($txtype);
							$type       = $types[$txtype];
							if (!$aemails) { $aemails = $email; }

							$resultMessage .= "<br><b>Processing DP$custref - $compname</b><br>";

							$sql = "UPDATE inv_headers SET 
								txtype='$txtype',
								year='$year',
								month='$month',
								day='$day',
								tally_guid='' 
								WHERE ID='$pmtid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Transaction Cleared in CAM<br />";
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";

								// is the customer restricted and not Direct Debit (DDs might be returned)
								if ($restricted == "Yes" AND $ddmonth != "dd") {
									// is the account balance now clear
									$sql = "SELECT SUM(outstanding) AS outstanding FROM inv_headers WHERE custref = '$custref'";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$outstanding = $row['outstanding'];
										// see if there is a payment plan in place
										$today = date("Y-m-d");
										$planoutstanding = 0.00;
										$pplan = FALSE;
										$resultMessage .= "Current Outstanding Balance: ".number_format($outstanding,2)."<br />";
										// get payment plan outstanding
										// get total debits and credits on plan to date
										$sql = "SELECT
													SUM(CASE WHEN BINARY l.txtype = 'DR' THEN l.amount END) as debits,
													SUM(CASE WHEN BINARY l.txtype = 'CR' THEN l.amount END) as credits
												FROM pplans_lines AS l
												LEFT JOIN pplans_headers AS h ON h.ID = l.ppid
												WHERE l.duedate <= '$today'
												AND h.status = '1'
												AND h.custref = '$custref'
										";
										if ($ppresult = $dbcnx->query($sql) AND $ppresult->num_rows) {
											$pplan = TRUE;
											// get debits and credits
											$pprow = $ppresult->fetch_assoc();
											$debits  = $pprow['debits'];
											$credits = $pprow['credits'];
											$planoutstanding = $debits - $credits;
											$resultMessage .= "Payment Plan Outstanding: ".number_format($planoutstanding,2)."<br />";
										} else {
											$resultMessage .= "No Payment Plan Found<br />";
											$planoutstanding = 0.00;
										}
										if ($outstanding <= 0.00 OR ($pplan AND $planoutstanding <= 0.00)) {
											// clear restriction
											// update paxton
											// get paxton sites
											$sql = "SELECT p.siteid, s.title AS sitename
													FROM PaxAuth AS p
													LEFT JOIN sites AS s ON s.ID = p.siteid
											";
											if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
												while ($srow = $sresult->fetch_assoc()) {
													$siteid   = $srow['siteid'];
													$sitename = $srow['sitename'];
													// check if user has bookings at this site
													$sql = "SELECT ID FROM `bookings` WHERE `custid` = '$custref' AND `siteid` = '$siteid'";
													if (($bresult = $dbcnx->query($sql)) AND ($bresult->num_rows)) {
														// get paxton access token
														$access_token = "";
														include('PaxGetAccessToken.php');
														if ($access_token) {
															// get all users with this custref at this site
															$headers = array(
																'Authorization: bearer '.$access_token,
																'Accept: application/json'
															);
															$postdata = http_build_query(
																array(
																	'query' => "SELECT userID, firstName, surname FROM UsersEx WHERE field4_50 = '$custref'"
																)
															);
															$ch = curl_init();
															curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
															curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
															curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
															curl_setopt($ch, CURLINFO_HEADER_OUT, true);
															curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        									curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
															$uresult = curl_exec($ch);
															$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
															if ($uresult !== FALSE) {
																if ($uresult = json_decode($uresult, TRUE)) {
																	foreach($uresult AS $uid => $puser) {
																		$userid    = $puser['userID'];
																		$firstName = $puser['firstName'];
																		$surname   = $puser['surname'];
																		$headers = array(
																			'Authorization: bearer '.$access_token,
																			'Accept: application/json'
																		);
																		$postdata = http_build_query(
																			array(
																				'id'         => $userid,
																				'expiryDate' => ''
																			)
																		);
																		$ch = curl_init();
																		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
																		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
																		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
																		curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
																		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
																		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
																		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
																		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
																		$u2result = curl_exec($ch);
																		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
																		if ($u2result !== FALSE) {
																			if ($httpcode == 200) {
																				$resultMessage .= "$sitename - $firstName $surname - Unrestricted<br />";
																			} else {
																				$resultMessage .= "Error2: $sitename - $firstName $surname Code $httpcode<br />";
																			}
																		} else {
																			$resultMessage .= "Error1: $sitename - $firstName $surname Code $httpcode<br />";
																		}
																	}
																} else {
																	$resultMessage .= "No users found $sitename Code $httpcode<br />";
																}
															} else {
																$resultMessage .= "No response from paxton $sitename Code $httpcode<br />";
															}

														} else {
															$resultMessage .= "Could not get paxton access token for $sitename<br />";
														}
													} else {
														$resultMessage .= "No bookings found for $custref at $sitename<br />";
													}
												}
											} else {
												$resultMessage .= "Could not get paxton sites<br />";
											}

											// do they have internet?
											$sql = "SELECT ID FROM `bookings_an` WHERE `custref` = '$custref' AND `ancid` = '5'";
											if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

												$resultMessage .= "Customer has internet. Trying to Unrestrict...<br />";

												// get ive apikey
												$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
												if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
													$irow = $iresult->fetch_assoc();
													$iceapi_access_point = $irow['api_access_point'];
													$iceaccess_token     = $irow['access_token'];

													$postdata = http_build_query(
														array(
															'apikey' => $iceaccess_token,
															'action' => 'listall'
														)
													);

													// get customers vlan from all customer list!!
													$ch = curl_init(); 
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
													curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
													curl_setopt($ch, CURLINFO_HEADER_OUT, true);
													curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
													curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
													$result = curl_exec($ch);
													$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

													if ($httpcode == 200) {
														if ($result = json_decode($result, TRUE)) {
															$vlans = array();
															foreach ($result AS $id => $item) {
																if ($item['Data']['ID'] == $custref) {
																	$vlans[] = $item['VLAN'];
																	$vlan++;
																}
															}
														} else {
															$resultMessage .= "Json Decode FAILED getting vlan<br />";
														}
													} elseif ($httpcode == 400) {
														$resultMessage .= "Error 400 Getting VLAN<br />";
													} elseif ($httpcode == 401) {
														$resultMessage .= "Unauthorised Getting VLAN<br />";
													} else {
														$resultMessage .= "Unknown error code $httpcode Getting VLAN<br />";
													}

													if ($vlan) {

														foreach($vlans AS $vlan) {

															$postdata = http_build_query(
																array(
																	'apikey' => $iceaccess_token,
																	'action' => 'servicestatus',
																	'vlan'   => $vlan,
																	'status' => 'Active'
																)
															);
									
															$ch = curl_init(); 
															curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
															curl_setopt($ch, CURLOPT_URL, $iceapi_access_point."/?".$postdata);
															curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
															curl_setopt($ch, CURLINFO_HEADER_OUT, true);
															curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
															curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
															$result = curl_exec($ch);
															$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
									
															if ($httpcode == 200) {
																$resultMessage .= "Internet Unrestricted VLAN $vlan<br />";
															} elseif ($httpcode == 400) {
																	$resultMessage .= "Internet Unrestrict FAILED code 400<br />";
															} elseif ($httpcode == 401) {
																$resultMessage .= "Internet Unrestrict FAILED Unauthorised<br />";
															} else {
																$resultMessage .= "Internet Unrestrict FAILED code $httpcode<br />";
															}

														}

													} else {
														$resultMessage .= "VLAN NOT FOUND for DP$custref<br />";
													}

												} else {
													$resultMessage .= "Could NOT get ice access token<br />";
												}

											} else {
												$resultMessage .= "Customer does not have internet<br />";
											}

											$sql = "UPDATE users SET tim = 'No' WHERE custref = '$custref'";
											if ($result = $dbcnx->query($sql)) {
												$rdb .= $sql.";\n";
												$resultMessage .= "Customer Ledger Unrestricted<br />";
												$mailout = "DebtorRelease";
												$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'DebtorRelease'";
												if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
													$row  = $result->fetch_assoc();
													$subject = $row['subject'];
													$body    = $row['html'];
													$search  = array("[[custname]]","[[staffname]]");
													$replace = array($custname,"Accounts Team");
													$body    = str_replace($search,$replace,$body);

													// send email to client
													$from = "accounts@dephna.com";
													$fromname = "Dephna Accounts";

													require "../../PHPMailer/src/SMTP.php";
													require "../../PHPMailer/src/PHPMailer.php";
													require "../../PHPMailer/src/Exception.php";

													$mail = new PHPMailer(true);
													try {
														
														include_once('../../cron/SMTP.php');

														$message  = file_get_contents('email-header.html') . "\r\n";
														$message .= nl2br($body) . "\r\n";
														$message .= file_get_contents('email-footer.html') . "\r\n";

														$mail->setFrom($from, $fromname);
														$emails = explode(",",$aemails);
														foreach ($emails AS $email) {
															$mail->addAddress($email);
														}
														$mail->Subject = $subject;
														$mail->isHTML(true);
														$mail->Body = $message;
														$mail->send();

														$resultMessage .= "Notification Email Sent to $aemails<br />";

													} catch (Exception $e) {
														$resultMessage .= "Email could not be sent. Mailer Error: {$mail->ErrorInfo}<br />";
													}

												} else {
													$resultMessage .= "Could NOT Get Mailout Template<br />";
												}

												// add a note
												$mydate = date("Y")."-".date("m")."-".date("d");
												$sql = "INSERT INTO ad_notes VALUES (
													null,
													'$mydate',
													'$custref',
													'Customer Auto Unrestricted',
													'note',
													'',
													'',
													'',
													'$now',
													'System'
												)";
												if ($result = $dbcnx->query($sql)) {
													$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
												}

											} else {
												$resultMessage .= "Could NOT Unrestrict Customer Ledger<br />";
											}
										} else {
											$resultMessage .= "Balance Still Outstanding<br />";
										}
									} else {
										$resultMessage .= "Could NOT Get Outstanding Balance<br />";
									}
								} else {
									$resultMessage .= "Customer Not Restricted or Direct Debit<br />";
								}

							} else {
								$resultMessage .= "Could NOT Update Header<br />";
							}
							
						} else {
							$resultMessage .= "Could not get customer for $pmtid<br />" . $dbcnx->error;
						} 
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
				}
			}

			include('sl-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Pending Receipts &amp; Payments</p>\n";

			if ((!$resultMessage) OR ($e)) {
				// build array of sites (database holds ip address instead of sitename).
				$sql = "SELECT title, ipaddress FROM sites";
				$sitesresult = $dbcnx->query($sql);
				if ($sitesresult->num_rows) {
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$title     = $sitesrow['title'];
						$ipaddress = $sitesrow['ipaddress'];
						$locations[$title] = $ipaddress;
					}
				}

				if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } // location
				if ($lo) { $search = "AND u.location = '$locations[$lo]'"; }
				if ($_REQUEST['dd'] != "All") { $dd = $_REQUEST['dd']; } // dd filter
				if ($dd) { $search .= " AND h.ddmonth = '$dd'"; }

				print "<form name=\"addform\" method=\"post\" action=\"sl-pending.php\">\n";
				print "<table class=\"filterBlock\" align=\"center\">\n";
				print "	<tr>\n";
				print "		<td align=\"right\"><b>Site</b></td>\n";
				print "		<td><select name=\"lo\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				foreach ($locations AS $key => $value) {
					print "		<option value=\"$key\""; if ($lo == $key) { print " SELECTED"; } print ">$key</option>\n";
				}
				print "		</select>";
				print "		</td>\n";

				print "		<td align=\"right\"><b>Type</b></td>\n";
				print "		<td><select name=\"dd\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				print "		<option value=\"dd\""; if ($dd == "dd") { print " SELECTED"; } print ">DD</option>\n";
				print "		</select>";
				print "		</td>\n";

				print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

				print "		<td><a href=\"sl-pending.php\"><b>All</b></a></td>\n";

				print "	</tr>\n";
				print "</table>\n";
				print "</form>\n";

				$sql = "SELECT h.ID, h.txtype, h.year, h.month, h.day, h.custref, h.invtotal, h.invnumber, h.ddmonth,
								u.compname, u.ID AS custid, u.location
						FROM inv_headers AS h
						LEFT JOIN users AS u ON h.custref = u.custref
						LEFT JOIN sl_extra AS e ON h.ID = e.hid
						WHERE (BINARY txtype = 'r' OR BINARY txtype = 'p') $search
						ORDER BY h.year, h.month, h.day, u.compname
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {

					if ($errorMessage) { print "<p align=\"center\">$errorMessage</p>\n"; }

					print "<form name=\"form1\" id=\"form1\" method=\"post\" action=\"sl-pending.php\">\n";

					print "<fieldset style=\"border:none\">\n";

					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
					print "</tr>\n";
				
					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";

					$txtypes = array(
						"I"=>"Invoice",
						"C"=>"Credit Note",
						"P"=>"Payment",
						"p"=>"Payment (p)",
						"R"=>"Receipt",
						"r"=>"Receipt (p)",
						"X"=>"Unknown"
					);

					if (!$txndate) { $txndate = date("d-m-Y"); }

					while ($row = $result->fetch_assoc()) {

						$id       = $row['ID'];
						$year     = $row['year'];
						$month    = $row['month'];
						$day      = $row['day'];
						$custref  = $row['custref'];
						$amount   = $row['invtotal'];
						$compname = $row['compname'];
						$txnnum   = $row['invnumber'];
						$txtype   = $row['txtype'];
						$location = $row['location'];
						$ddmonth  = $row['ddmonth'];
						if ($ddmonth != "dd") { $ddmonth = ""; }

						$totAmount += $amount;

						if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
						if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
						
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$day-$month-$year</td>\n";
						print "	<td>DP$custref</td>\n";
						print "	<td>$compname</td>\n";
						print "	<td>$location</td>\n";
						print "	<td>{$txtypes[$txtype]}</td>\n";
						print "	<td>$txnnum $ddmonth</td>\n";
						print "	<td align=\"right\">$amount</td>\n";
						print "	<td align=\"right\"><input type=\"checkbox\" name=\"cleared[$id]\" value=\"$txtype\"";
								if ($cleared[$id]) { print " CHECKED"; }
						print "></td>\n";
						print "</tr>\n";
					
					}
				
					print "</fieldset>\n";
				
					print "<tr>\n";
					print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totAmount,2)."</b></td>\n";
					print "	<td align=\"right\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
				
					print "<tr class=\"notclicked\">\n";
					print "	<td colspan=\"6\" align=\"right\"><b>Batch Cleared Date</b> <input type=\"text\" id=\"datepicker\" name=\"txndate\" class=\"element_small\" value=\"$txndate\"></td>\n";
					print "	<td colspan=\"2\" align=\"right\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\"></td>\n";
					print "</tr>\n";

					print "<tr class=\"uploading\">\n";
					print "	<td colspan=\"6\">&nbsp;</td>\n";
					print "	<td colspan=\"2\" align=\"right\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

					// select all toggle
					print "<script type=\"text/javascript\">\n";
					print "$(function () {\n";
					print "    $('.checkall').on('click', function () {\n";
					print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
					print "    });\n";
					print "});\n";
					print "</script>\n";

					// print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
					print "</script>\n";

					// ajax loader
					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";

				} else {
					print "<p align=\"center\">No Pending Receipts or Payments Found!</p>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p style=\"margin-left:60px\">$resultMessage</p>\n";
	print "<p align=\"center\"><a href=\"sl-pending.php\">Return</a></p>\n";
}
print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
