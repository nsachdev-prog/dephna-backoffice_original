<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Payment Plans";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
	include('ad-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az";

			$ppid = $_REQUEST['ppid'];
			$id   = $_REQUEST['id'];

			$do     = $_REQUEST['do'];
			$nid 	= $_REQUEST['nid'];
			$action = $_REQUEST['action'];

			print "<p align=\"center\" class=\"larger\">Payment Plan &amp; Notes</p>\n";

			if ($id) {

				// get customer
				$sql = "SELECT custref, compname, terminationCheck, terminationWarning, terminationNotice FROM users WHERE ID = '$id'";
				if ($cresult = $dbcnx->query($sql) AND $cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$custref  = $crow['custref'];
					$compname = $crow['compname'];
					$terminationCheck   = $crow['terminationCheck'];
					$terminationWarning = $crow['terminationWarning'];
					$terminationNotice  = $crow['terminationNotice'];

					print "<p align=\"center\" class=\"larger\">$compname</p>\n";

					if ($do) {

						if ($action == "set") {
							$ymd = strip_tags($_POST['ymd']);
							if (!validateDate($ymd)) {
								$resultMessage .= "Date invalid<br>"; $e++;
							} else {
								$sql = "UPDATE ad_notes SET remdate = '$ymd' WHERE ID = '$nid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$rdb .= $sql.";\n";
								} else {
									$resultMessage .= "Could NOT Set Reminder Date<br>"; $e++;
								}
								$action = "";
							}
						}

						elseif ($action == "clear") {
							$sql = "UPDATE ad_notes SET remdate = '' WHERE ID = '$nid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= $sql.";\n";
							} else {
								$resultMessage .= "Could NOT Clear Reminder Date<br>";
							}
							$action = "";
						}

						elseif ($action == "delete") {
							$sql = "DELETE FROM ad_notes WHERE ID = '$nid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= $sql.";\n";
							} else {
								$resultMessage .= "Could NOT Delete Note<br>";
							}
							$action = "";
						}
					
						elseif ($action == "addedit") {
							$details  = strip_tags($_POST['details']);
							$ymd      = strip_tags($_POST['ymd']);
							$whatsapp = $_POST['whatsapp'];
							if (!$details) {
								$resultMessage .= "No note posted<br>"; $e++;
							}
							if ($ymd) {
								if (!validateDate($ymd)) {
									$resultMessage .= "Date invalid<br>"; $e++;
								}
							}

							if (!$e) {

								if ($whatsapp == "on") {
									// send to wharsapp
									// build whatsapp message
									$wanote = "Client:$compname\n$details";

									if ($ymd) {
										$wanote .= "\nReminder:$ymd";
									}

									// send to whatsapp
									$postData = array(
										'group_admin' => $WA_GROUP_ADMIN,
										'group_name' => $WA_GROUP_NAME,
										'message' => $wanote
									);
									$headers = array(
										'Content-Type: application/json',
										'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
										'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
									);
									$url = $WA_URL . $WA_INSTANCE_ID;
									$ch = curl_init($url);
									curl_setopt($ch, CURLOPT_POST, 1);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
									$response = curl_exec($ch);
									curl_close($ch);
									$resultMessage .= "WhatsApp Responded...<br>" . $response;
								}

								$details = addslashes($details);
								if ($nid) {
									$sql = "UPDATE ad_notes SET details = '$details', remdate = '$ymd' WHERE ID = '$nid'";
									$result = $dbcnx->query($sql);
									if ($result) {
										$rdb .= $sql.";\n";
									}
								} else {
									$now = time();
									$mydate = date("Y")."-".date("m")."-".date("d");
									$sql = "INSERT INTO ad_notes VALUES (
										null,
										'$mydate',
										'$custref',
										'$details',
										'note',
										'0',
										'$ymd',
										'{$_SESSION['user']}',
										'$now',
										'{$_SESSION['username']}'
									)";
									$result = $dbcnx->query($sql);
									if ($result) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
									}
								}
								$action = "";
							}
							$nid = 0;
						}

						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}
					}

					elseif ($action == "delete") {
						// get the note
						$sql = "SELECT details FROM ad_notes WHERE ID='$nid'";
						$result  = $dbcnx->query($sql);
						$row     = $result->fetch_assoc();
						$details = $row['details'];
						print "<p align=\"center\" class=\"larger\"><b>Delete Note</b></p>\n";
						print "<table width=\"350px\" align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td>$details</td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "<p align=\"center\" class=\"larger\">ARE YOU SURE?</p>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;nid=$nid&amp;action=delete&amp;do=1&amp;ppid=$ppid\">Yes Delete</a> | <a href=\"pplans-view.php?id=$id\">No Don't Delete</a></p>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;ppid=$ppid\">Return</a></p>\n";
					}
					
					elseif ($action == "clear") {
						// get the note
						$sql = "SELECT details, remdate FROM ad_notes WHERE ID='$nid'";
						$result  = $dbcnx->query($sql);
						$row     = $result->fetch_assoc();
						$details = $row['details'];
						$remdate = $row['remdate'];
						print "<p align=\"center\" class=\"larger\"><b>Clear Reminder</b></p>\n";
						print "<table width=\"350px\" align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td>$details</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"center\">Reminder $remdate</td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "<p align=\"center\" class=\"larger\">ARE YOU SURE?</p>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;nid=$nid&amp;action=clear&amp;do=1&amp;ppid=$ppid\">Yes Clear</a> | <a href=\"pplans-view.php?id=$id\">No Don't Clear</a></p>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;ppid=$ppid\">Return</a></p>\n";
					}

					elseif ($action == "set") {
						// get the note
						$sql = "SELECT details, remdate FROM ad_notes WHERE ID='$nid'";
						$result  = $dbcnx->query($sql);
						$row     = $result->fetch_assoc();
						$details = $row['details'];
						$ymd     = $row['remdate'];
						print "<p align=\"center\" class=\"larger\">Set Reminder</p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"pplans-view.php\">\n";
						print "<input type=\"hidden\" name=\"action\" value=\"set\">\n";
						print "<input type=\"hidden\" name=\"do\" value=\"1\">\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
						print "<input type=\"hidden\" name=\"nid\" value=\"$nid\">\n";
						print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\">\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td width=\"250\">$details</td>";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Reminder Date</b>";
						print "	<input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element\"></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;ppid=$ppid\">Return</a></p>\n";
					}

					elseif ($action == "addedit") {

						if ($nid) {
							$sql = "SELECT details FROM ad_notes WHERE ID='$nid'";
							$result  = $dbcnx->query($sql);
							$row     = $result->fetch_assoc();
							$details = $row['details'];
							$ymd     = $row['remdate'];
							$heading = "Edit Note";
						} else {
							$heading = "Add Note";
						}
						
						print "<p align=\"center\" class=\"larger\">$heading</p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"pplans-view.php\">\n";
						print "<input type=\"hidden\" name=\"action\" value=\"addedit\">\n";
						print "<input type=\"hidden\" name=\"do\" value=\"1\">\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
						print "<input type=\"hidden\" name=\"nid\" value=\"$nid\">\n";
						print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\">\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"3\" class=\"element_box\">$details</textarea></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Reminder Date</b> ";
						print " <input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element_small\"></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Send WhatsApp</b> ";
						print " <input type=\"checkbox\" name=\"whatsapp\""; if ($whatsapp == "on") { print " CHECKED"; } print "></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;ppid=$ppid\">Return</a></p>\n";

					}

					if (!$action) {

						if ($ppid) {
							// get plan

							$sql = "SELECT *
									FROM pplans_headers
									WHERE ID = '$ppid'
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$txndate  = $row['txndate'];
								$aemails  = $row['aemails'];
								$notes    = $row['notes'];
								$lastupd  = $row['lastupd'];
								$updby    = $row['updby'];
								$status   = $row['status'];
								$defaults = $row['defaults'];
								$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
								$dispemails = str_replace(",","<br>",$aemails);
							
								$states = array(
									0 => "Pending",
									1 => "Active",
									2 => "Complete"
								);
					
								print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

								list($y,$m,$d) = explode("-",$txndate);
								print "<tr>\n";
								print "	<td align=\"right\"><b>Agreement Date</b></td>\n";
								print "	<td>$d-$m-$y</td>\n";
								print "</tr>\n";

								print "<tr>\n";
								print "	<td align=\"right\"><b>Status</b></td>\n";
								print "	<td valign=\"top\">{$states[$status]}</td>\n";
								print "</tr>\n";

								print "<tr>\n";
								print "	<td align=\"right\"><b>Defaults</b></td>\n";
								print "	<td valign=\"top\">$defaults</td>\n";
								print "</tr>\n";

								print "<tr>\n";
								print "	<td align=\"right\" valign=\"top\"><b>Email Address(es)</b></td>\n";
								print "	<td>$dispemails</td>\n";
								print "</tr>\n";

								// get outstanding
								$sql = "SELECT SUM(outstanding) AS outstanding FROM inv_headers WHERE custref = '$custref'";
								if (($result = $dbcnx->query($sql) AND ($result->num_rows))) {
									$row = $result->fetch_assoc();
									$outstanding = $row['outstanding'];
								} else {
									$outstanding = 0.00;
								}

								print "<tr>\n";
								print "	<td align=\"right\"><b>Outstanding</b></td>\n";
								print "	<td>" . number_format($outstanding,2) . "</td>\n";
								print "</tr>\n";

								print "<tr>\n";
								print "	<td align=\"right\" valign=\"top\"><b>Internal Notes</b></td>";
								print "	<td>$notes</td>\n";
								print "</tr>\n";

								print "</table>\n";

								print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Details Last Updated on $lastupd by $updby</p>\n";

								print "<p align=\"center\">";
								print "<a href=\"pplans-addedit.php?ppid=$ppid\">Update</a> | ";
								print "<a href=\"pplans-email.php?ppid=$ppid\">Email</a> | ";
								if ($status < 2) {
									if ($accessRights[$page] > 1) {
										print "<a href=\"pplans-complete.php?ppid=$ppid&amp;$qString\">Complete</a> | ";
									} else {
										print "	<span class=\"noAccess\">Complete</span> | ";
									}
								} else {
									print "	<span class=\"noAccess\">Complete</span> | ";
								}
								if ($accessRights[$page] > 2) {
									print "<a href=\"pplans-delete.php?ppid=$ppid&amp;$qString\">Delete</a> | ";
								} else {
									print "<span class=\"noAccess\">Delete</span> | ";
								}
								print "<a href=\"ad-list.php?$qString\">Debtors</a> | ";
								print "<a href=\"pplans-list.php?$qString\">Plans</a>";
								print "</p>\n";

								print "<br><p align=\"center\" class=\"larger\">Plan</p>\n";
								// get current plan
								$sql = "SELECT * FROM pplans_lines WHERE ppid = '$ppid' ORDER BY duedate";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
									print "<tr>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
									print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Due</b></td>\n";
									print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
									print "</tr>\n";
									$totdr = 0;
									$totcr = 0;
									while ($row = $result->fetch_assoc()) {
										$lid     = $row['ID'];
										$duedate = $row['duedate'];
										$amount  = $row['amount'];
										$txtype  = $row['txtype'];
										list($y,$m,$d) = explode("-",$duedate);
										print "<tr class=\"r" . ($i++ % 2) . "\">\n";
										print "	<td>$d-$m-$y</td>\n";
										if ($txtype == "DR") {
											print "	<td align=\"right\">$amount</td>\n";
											print "	<td align=\"right\">&nbsp;</td>\n";
											$totdr += $amount;
										} else {
											print "	<td align=\"right\">&nbsp;</td>\n";
											print "	<td align=\"right\">$amount</td>\n";
											$totcr += $amount;
										}
										print "</tr>\n";
									}
									print "<tr>\n";
									print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
									print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>". number_format($totdr,2) ."</b></td>\n";
									print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>". number_format($totcr,2) ."</b></td>\n";
									print "</tr>\n";
									print "</table>\n";
								} else {
									print "<p align=\"center\">No payments added yet (click update).</p>\n";
								}
								
								print "<br><p align=\"center\" class=\"larger\">Plan History</p>\n";
								$sql = "SELECT * FROM pplans_notes WHERE ppid = '$ppid' ORDER BY crdate DESC";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
									print "<tr>\n";
									print "	<td style=\"border-bottom:1px solid #999999\" width=\"100\"><b>Date</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\" width=\"100\"><b>By</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Email</b></td>\n";
									print "</tr>\n";
									while ($row = $result->fetch_assoc()) {
										$nid    = $row['ID'];
										$crdate = $row['crdate'];
										$crby   = $row['crby'];
										$details = $row['details'];
										$ebody  = $row['emailbody'];
										$crdate = date("d-m-Y H:i",$crdate);
										print "<tr class=\"r" . ($i++ % 2) . "\">\n";
										print "	<td valign=\"top\">$crdate</td>\n";
										print "	<td valign=\"top\">$crby</td>\n";
										print "	<td valign=\"top\">$details</td>\n";
										if ($ebody) {
											print "<td  valign=\"top\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('pplans-emailbody.php?nid=$nid','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">View</a></td>\n";
										} else {
											print "	<td valign=\"top\">&nbsp;</td>\n";
										}
										print "</tr>\n";

									}
									print "<tr>\n";
									print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
									print "</tr>\n";
									print "</table>\n";
								}

							} else {
								$resultMessage .= "<span class=\"redtext\">Payment Plan Record Not Found!</span><br>" . $dbcnx->error . "<br>";
							}

						} else {
							print "<p align=\"center\"><b>No Current Payment Plan</b></p>\n";
							if ($accessRights[$page] > 1) {
								print "<p align=\"center\"><a href=\"pplans-addedit.php?custref=$custref\">Add New Payment Plan</a></p>\n";
							} else {
								print "<p align=\"center\"><span class=\"noAccess\">Add New Payment Plan</span></p>\n";
							}
						}
						
						// terminations
						print "<br><p align=\"center\" class=\"larger\">Termination</p>\n";
						if (!$terminationCheck) {
							print "<p align=\"center\"><a href=\"ad-termination-email.php?ppid=$ppid&amp;custref=$custref&amp;action=Check\" class=\"button\">Checked With Office</a></p>\n";
						} elseif (!$terminationWarning) {
							print "<p align=\"center\">Termination Check Done $terminationCheck</p>\n";
							print "<p align=\"center\"><a href=\"ad-termination-email.php?ppid=$ppid&amp;custref=$custref&amp;action=Warning\" class=\"button\">Send Termination Warning</a></p>\n";
						} elseif (!$terminationNotice) {
							print "<p align=\"center\">Termination Warning Sent $terminationWarning</p>\n";
							print "<p align=\"center\"><a href=\"ad-termination-email.php?ppid=$ppid&amp;custref=$custref&amp;action=Cancel\" class=\"button\">Cancel Termination Warning</a></p>\n";
							print "<p align=\"center\"><a href=\"ad-termination-email.php?ppid=$ppid&amp;custref=$custref&amp;action=Notice\" class=\"button\">Send Termination Notice</a></p>\n";
						}
						if ($terminationNotice) {
							print "<p align=\"center\">Termination Warning Sent $terminationWarning</p>\n";
							print "<p align=\"center\">Termination Notice Sent $terminationNotice</p>\n";
							print "<p align=\"center\"><a href=\"ad-termination-email.php?ppid=$ppid&amp;custref=$custref&amp;action=Cancel\" class=\"button\">Cancel Termination Process</a></p>\n";
						}
						
						// get notes
						print "<br><p align=\"center\" class=\"larger\">Customer Notes</p>\n";
						print "<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;action=addedit&amp;ppid=$ppid\">Add New Note</a></p>\n";
						$sql = "SELECT * FROM ad_notes WHERE custref='$custref' ORDER BY crdate DESC";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							print "<tr>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"110px\"><b>Date/Time</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"300px\"><b>Note</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Reminder</b></td>\n";
							/*print "	<td width=\"40px\">&nbsp;</td>\n";
							print "	<td width=\"40px\">&nbsp;</td>\n";*/
							print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";
							while ($row = $result->fetch_assoc()) {
								$fdate = date("d-m-Y H:i",$row['crdate']);
								$thisid  = $row['ID'];
								$details = $row['details'];
								$remdate = $row['remdate'];
								$crby    = $row['crby'];
								
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td valign=\"top\">$fdate</td>\n";
								print "	<td valign=\"top\">$details</td>\n";
								print "	<td valign=\"top\">$crby</td>\n";
								if ($remdate) {
									print "	<td valign=\"top\">$remdate</td>\n";
								} else {
									print "	<td valign=\"top\">&nbsp;</td>\n";
								}
								/*if ($nrow['crby'] == $_SESSION['username']) {
									print "	<td align=\"center\" valign=\"top\"><a href=\"pplans-view.php?id=$id&amp;nid=$thisid&amp;edit=1\">Edit</a></td>\n";
									print "	<td align=\"center\" valign=\"top\"><a href=\"pplans-view.php?id=$id&amp;nid=$thisid&amp;delete=1\">Delete</a></td>\n";
								} else {
									print "	<td>&nbsp;</td>\n";
									print "	<td>&nbsp;</td>\n";
								}*/
								if (!$remdate) {
									print "	<td valign=\"top\"><a href=\"pplans-view.php?id=$id&amp;nid=$thisid&amp;action=set&amp;ppid=$ppid\">Set</a></td>\n";
									print "	<td>&nbsp;</td>\n";
								} else {
									print "	<td valign=\"top\"><a href=\"pplans-view.php?id=$id&amp;nid=$thisid&amp;action=set&amp;ppid=$ppid\">Reset</a></td>\n";
									print "	<td valign=\"top\"><a href=\"pplans-view.php?id=$id&amp;nid=$thisid&amp;action=clear&amp;ppid=$ppid\">Clear</a></td>\n";
								}
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";
						} else {
							print "<p align=\"center\"><b>No notes for this client yet.</b></p>\n";
						}
						print "	<p align=\"center\"><a href=\"pplans-view.php?id=$id&amp;action=addedit&amp;ppid=$ppid\">Add New Note</a></p>\n";

					}

					// space for datepicker
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";

					// print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";

				} else {
					$resultMessage .= "<span class=\"redtext\">Customer Record Not Found!</span><br>" . $dbcnx->error . "<br>";
				}

			} else {
				$resultMessage .= "Page Called Without Customer id<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
