<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$print = $_REQUEST['print'];
$page = "Leads";
$subpage = "List";
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Leads</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<style>
		table { font-size:1.4em }
	</style>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$tids = $_POST['tids'];
				if (count($tids) == 0) {
					$resultMessage .= "<span class=\"redtext\">No Leads Selected.</span><br />"; $e++;
				}

				if (!$e) {
					foreach ($tids AS $tid) {
						$failed = 0;
						$now = time();
						// get ticket details
						$sql = "SELECT `ticketno`, `subject` FROM `leads_tickets` WHERE `ID` = '$tid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$ticketno = $row['ticketno'];
							$subject  = $row['subject'];
							if (strlen($subject) > 15) { $subject = substr($subject, 0, 15); }
							$resultMessage .= "Deleting $ticketno $subject<br>\n";
						
							// get email attachments
							$sql = "SELECT ID, attfilename FROM leads_attachments WHERE tid = '$tid'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								while ($row = $result->fetch_assoc()) {
									$aid         = $row['ID'];
									$attname     = $row['attname'];
									$attfilename = $row['attfilename'];
									$filename = "../../dis2_db/lds/$attfilename";
									if (is_file($filename)) {
										if (!unlink($filename)) {
											$resultMessage .= "<span class=\"redtext\">Attachment $attname Could NOT Be Deleted.</span><br>\n";
											$failed++;
										} else {
											$resultMessage .= "Attachment $attname Deleted.<br>\n";
										}
									} else {
										$resultMessage .= "<span class=\"redtext\">Attachment $attfilename NOT FOUND.</span><br>\n";
									}
									$sql = "DELETE FROM leads_attachments WHERE ID = '$aid'";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Attachment Record Deleted.<br>\n";
									} else {
										$resultMessage .= "<span class=\"redtext\">Attachment Record($attname) Could NOT Be Deleted.</span><br>\n";
										$failed++;
									}
								}
							}
							if (!$failed) {
								$sql = "DELETE FROM leads_emails WHERE tid = '$tid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Emails Deleted<br>\n";
								} else {
									$resultMessage .= "<span class=\"redtext\">Emails Could NOT Be Deleted.</span><br>\n";
								}

								$sql = "DELETE FROM leads_notes WHERE tid = '$tid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Notes Deleted<br>\n";
								} else {
									$resultMessage .= "<span class=\"redtext\">Notes Could NOT Be Deleted.</span><br>\n";
								}

								$sql = "DELETE FROM leads_tickets WHERE ID = '$tid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Lead Deleted<br>\n";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Tkt:$ticketno $subject','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Lead Could NOT Be Deleted.</span><br>\n";
								}
							}
						} else {
							$resultMessage .= "<span class=\"redtext\">Lead($tid) NOT FOUND.</span><br>\n";
						}
					}
					$resultMessage .= "Deleted " . count($tids) . " records<br />";
				}

			}

			// get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			// if ($_REQUEST['rp']) { $rp = $_REQUEST['rp']; } else { $rp = "0"; }
			if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; } else { $ty = "Leads"; }
			$subpage = $ty;
			if ($ty == "Leads") {
				$search[] = "(t.type = '$ty' OR t.type = 'Offices')";
			} else {
				$search[] = "t.type = '$ty'";
			}
			if ($_REQUEST['al'] != "All") { $al = $_REQUEST['al']; }
			if ($se) { $search[] = "(t.compname LIKE '%$se%' OR t.fullname LIKE '%$se%')"; }
			$search[] = "t.status = 'Open'";
			if ($al) { $search[] = "t.assignedto = '$al'"; }
			// if ($rp) { $search[] = "t.replied = '$rp'"; } else { $search[] = "t.replied = '0'"; }
			// if ($rp == "1") { $subpage = "Answered"; } else { $subpage = "Leads"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}
			$qString ="se=$se&amp;ty=$ty&amp;al=$al";

			if ($ty == "Offices") {
				$heading = "OFFICE LEADS";
			} else {
				$heading = "LEADS";
			}

			print "<p align=\"center\" class=\"heading\">$heading</p>\n";

			if (!$print) {
				include('leads-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
			}

			if (!$print) {
				// filter form
				print "<form name=\"addform\" method=\"post\" action=\"leads-list.php?ty=$ty\">\n";
				print "<table class=\"filterBlock\" align=\"center\">\n";
				print "	<tr>\n";

				print "		<td align=\"right\"><b>Name/Company</b></td>\n";
				print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

				print "     <td align=\"right\"><b>Alloc To</b></td>\n";
				print "     <td><select name=\"al\" class=\"element_small\">\n";
				print "		<option>All</option>\n";
				$sql = "SELECT ID, username FROM staff WHERE onsupport = 'on' ORDER BY username";
				$staffresult = $dbcnx->query($sql);
				while ($staffrow = $staffresult->fetch_assoc()) {
					$thisid   = $staffrow['ID'];
					$thisname = $staffrow['username'];
					print "		<option value=\"$thisid\""; if ($thisid == $al) { print " SELECTED"; } print ">$thisname</option>\n";
				}
				print "	</select>";
				print "</td>\n";

				print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

				print "		<td><a href=\"leads-list.php?ty=$ty\"><b>Reset</b></a></td>\n";

				print "	</tr>\n";
				print "</table>\n";
				print "</form>\n";
			}

			print "<br>\n";

			?>
			<style>
				.sup-hdr {
					color: #333;
					border-bottom: 1px solid #fff;
				}
				.sup-hdr a { display: block; }
				.sup-hdr a:link { color: #333; }
				.sup-hdr a:hover { color: #333; }
				.sup-hdr a:visited { color: #333; }
				.subject {
					font-size: 1.2em;
					font-weight: bold;
				}
				.newticket {
					background-color: #e25e5e;
				}
				.clientresponded {
					background-color: #d1793e;
				}
				.dephnareplied {
					background-color: #67afe2;
				}
				.flagged {
					background-color: #ccca68;
				}
				.flaggedNotRep {
					background-color: #ccca68;
					color: #b22e2e;
					font-weight: bold;
					text-decoration-line: underline;
					text-decoration-thickness: 3px;
				}
				.pendingrepl {
					background-color: #82b09b;
				}
				.pending {
					background-color: #84d0ad;
				}
				.tooltip {
					position: relative;
					display: inline block;
					width: 260px;
				}
				.tooltip .tooltiptext {
					visibility: hidden;
					width: 300px;
					background-color: #e8ff80;
					color: #000;
					border: 1px solid black;
					border-radius: 6px;
					padding: 5px;
					position: absolute;
					z-index: 1;
					bottom: 125%;
					left: 50%;
					margin-left: -60px;
					opacity: 0;
					transition: opacity 0.3s;
				}
				.tooltip .tooltiptext::after {
					content: "";
					position: absolute;
					top: 100%;
					left: 50%;
					margin-left: -5px;
					border-width: 2px;
					border-style: solid;
					border-color: #555 transparent transparent transparent;
				}
				.tooltip:hover .tooltiptext {
					visibility: visible;
					opacity: 1;
				}
				.dotClient { color:#e25e5e; }
				.dotDephna { color:#e25e5e; }
			</style>
			<?php

			$sql = "SELECT t.*, s.username AS assigned,
					(SELECT edatetime
							FROM leads_emails
							WHERE tid = t.ID
							ORDER BY edatetime DESC
							LIMIT 0,1) AS edatetime
					FROM leads_tickets AS t
					LEFT JOIN staff AS s ON s.ID = t.assignedto
					$search
					ORDER BY t.flagged DESC, t.pending, t.replied, edatetime DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				$tids = array();

				if (!$print) {
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"4px\">\n";
					print "<tr>\n";
					print "<td style=\"width:120px;text-align:center;background-color:#e25e5e\">New Lead</td>\n";
					print "<td style=\"width:120px;text-align:center;background-color:#d1793e\">Lead Responded</td>\n";
					print "<td style=\"width:120px;text-align:center;background-color:#67afe2\">Dephna Answered</td>\n";
					print "<td style=\"width:120px;text-align:center;background-color:#ccca68\">Flagged</td>\n";
					print "<td style=\"width:120px;text-align:center;background-color:#82b09b\">Pending Answered</td>\n";
					print "<td style=\"width:120px;text-align:center;background-color:#84d0ad\">Pending Unanswered</td>\n";
					print "</tr>\n";
					print "</table><br>\n";
					print "<p align=\"center\">";
					print "<a href=\"leads-new.php\">Add New Lead</a> | ";
					print "<a href=\"leads-list.php?print=1&amp;$qString\" target=\"_blank\">Print</a>";
					print "</p>\n";
				}

				$pendingCount = 0;
				$answeredCount = 0;
				$count = 0;

				print "<form name=\"delform\" method=\"post\" action=\"leads-list.php?$qString\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";

				// print section heading
				print "<tr>\n";
				print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Inbox</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Asgnd To</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Call</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Email</b></td>\n";
				if (!$print) {
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Meeting</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
					$colspan = 9;
				} else {
					$colspan = 7;
				}
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$tid        = $row['ID'];
					$ticketno   = $row['ticketno'];
					$fullname   = $row['fullname'];
					$compname   = $row['compname'];
					$subject    = $row['subject'];
					$assigned   = $row['assigned'];
					$flagged    = $row['flagged'];
					$fvdate     = $row['fvdate'];
					$fvby       = $row['fvby'];
					$section    = $row['section'];
					$pending    = $row['pending'];
					$replied    = $row['replied'];
					$from       = $row['aemails'];
					$meeting    = $row['meeting'];

					if ($meeting) {
						$meeting = date('l<\b\r>d-m H:i',strtotime($meeting));
					} else {
						$meeting = "Set";
					}

					list($junk,$from) = explode("@",$from);
					list($from,$junk) = explode(".",$from);

					if ($fvby) {
						$fvdate = date("d/m H:i",$fvdate);
						$fvby = str_replace(array("Mr ","Mrs "),array("",""),$fvby);
						list($fvby,$junk) = explode(" ",$fvby);
						$fvby = trim($fvby);
					} else {
						$fvby   = "&nbsp;";
						$fvdate = "&nbsp;";
					}

					list($ind,$section) = explode("-",$section);

					if (!$compname) { $compname = $from; }
					if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
						// get date from notes
						$sql = "SELECT crdate FROM leads_notes WHERE tid = '$tid' AND details LIKE '%Allocated%' ORDER BY crdate DESC LIMIT 0,1";
						if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
							$lrow = $lresult->fetch_assoc();
							$asdate = $lrow['crdate'];
							$asdate = date("d/m H:i",$asdate);
							$assigned .= "<br>" . $asdate;
						}
					}
					
					if (strlen($fullname) > 30) { $fullname = substr($fullname, 0, 30); }
					if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
					if (strlen($subject)  > 30) { $subject  = substr($subject, 0, 30); }

					// get last call back
					$sql = "SELECT `crdate`, `crby`, `details`
							FROM `leads_notes`
							WHERE `tid` = '$tid'
							 AND `details` LIKE '%Call Back...%'
							ORDER BY `crdate` DESC
							LIMIT 0,1
					";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$crdate = $lrow['crdate'];
						$cbby   = $lrow['crby'];
						$details = $lrow['details'];
						if (strpos($details, "No Answer") !== FALSE) {
							$cbstyle = " style=\"color:#FF0000\"";
						} else {
							$cbstyle = "";
						}
						$lastcb = date("d/m H:i",$crdate);
						$cbby   = str_replace(array("Mr ","Mrs "),array("",""),$cbby);
						list($cbby,$junk) = explode(" ",$cbby);
						$cbby   = trim($cbby);
					} else {
						$lastcb = "&nbsp;";
						$cbby   = "&nbsp;";
					}

					// get last answer
					$sql = "SELECT `crdate`, `crby`
							FROM `leads_emails`
							WHERE `tid` = '$tid'
							AND `origin` = 'Dephna'
							ORDER BY `crdate` DESC
							LIMIT 0,1
					";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$crdate = $lrow['crdate'];
						$crby   = $lrow['crby'];
						$last = date("d/m H:i",$crdate);
						$crby = str_replace(array("Mr ","Mrs "),array("",""),$crby);
						list($crby,$junk) = explode(" ",$crby);
						$crby = trim($crby);
						$new = 0;
					} else {
						$new = 1;
						$last = "&nbsp;";
						$crby = "&nbsp;";
					}

					// get last email text for tool tip and days
					$sql = "SELECT edatetime, `crdate`, `body`, `headers`, origin
							FROM `leads_emails`
							WHERE `tid` = '$tid'
							ORDER BY `crdate` DESC
							LIMIT 0,1
					";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$crdate    = $lrow['crdate'];
						$edatetime = $lrow['edatetime'];
						$body      = $lrow['body'];
						$headers   = $lrow['headers'];
						if ($headers == "Phone") {
							$new = 0;
						}
						$origin = $lrow['origin'];
						$body = strip_tags($body);
						$body = str_replace('\r','',$body);
						$body = str_replace('\n'," ",$body);
						if (strlen($body) > 300) { $body = substr($body, 0, 300); }
						list($date,$time) = explode(" ",$edatetime);
						list($y,$m,$d) = explode("-",$date);
						$edatetime = "$d/$m $time";
					} else {
						$body = "Not Found";
					}

					// get unit types
                    $utdisp = "";
					if ($row['unittypes']) {
						$unittypes = explode("|",$row['unittypes']);
						foreach ($unittypes AS $unittype) {
							$typeimage = strtolower($unittype).".png";
							$typeimage = str_replace(" ","",$typeimage);
							$utdisp .= "<img src=\"images/$typeimage\" width=\"11px\" height=\"11px\" alt=\"\" />";
						}
                        $utdisp .= "&nbsp;";
					}

					if ($flagged AND $replied) {
						$styleviewed = " flagged";
					} elseif ($flagged) {
						$styleviewed = " flaggedNotRep";
					} elseif ($pending AND $replied) {
						$styleviewed = " pendingrepl";
					} elseif ($pending) {
						$styleviewed = " pending";
					} elseif ($replied) {
						$styleviewed = " dephnareplied";
					} elseif ($new) {
						$styleviewed = " newticket";
					} else {
						$styleviewed = " clientresponded";
					}

					if ($replied AND !$pending AND !$answeredCount AND !$flagged) {
						print "<tr>\n";
						print "	<td colspan=\"$colspan\" style=\"border-top:1px solid #999999\">";
						if ($count) { print $count; $count = 0; }
						print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Answered</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Asgnd To</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Call</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Email</b></td>\n";
						if (!$print) {
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Meeting</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
						}
						print "</tr>\n";
						$answeredCount++;
					}
					elseif ($pending AND !$pendingCount AND !$flagged) {
						print "<tr>\n";
						print "	<td colspan=\"$colspan\" style=\"border-top:1px solid #999999\">";
						if ($count) { print $count; $count = 0; }
						print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Pending</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Asgnd To</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Call</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Email</b></td>\n";
						if (!$print) {
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Meeting</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
						}
						print "</tr>\n";
						$pendingCount++;
					}
					elseif ($pending AND !$flagged) {
						$pendingCount++;
					}
					$count++;

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td class=\"sup-hdr{$styleviewed}\" align=\"center\">$ticketno</td>\n";
					print "	<td class=\"sup-hdr tooltip\"><a href=\"leads-view.php?tid=$tid&amp;$qString\"><span class=\"tooltiptext\">$body</span><span class=\"subject\">$subject</span><br>{$utdisp}$edatetime - $compname</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"leads-view.php?tid=$tid&amp;$qString\">$assigned</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"leads-view.php?tid=$tid&amp;$qString\">$fvby<br>$fvdate</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"leads-view.php?tid=$tid&amp;$qString\"><span{$cbstyle}>$cbby<br>$lastcb</span></a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"leads-view.php?tid=$tid&amp;$qString\">$crby<br>$last</a></td>\n";
					if (!$print) {
						print "	<td class=\"sup-hdr\"><a href=\"leads-meeting.php?tid=$tid&amp;$qString\">$meeting</a></td>\n";
						if ($accessRights[$page] > 2) {
							print "	<td class=\"sup-hdr\"><a href=\"leads-delete.php?tid=$tid&amp;$qString\">Del</a></td>\n";
							print "	<td class=\"sup-hdr\"><input type=\"checkbox\" name=\"tids[]\" value=\"$tid\""; if (in_array($tid,$tids)) { print " CHECKED"; } print " /></td>\n";
						} else {
							print "	<td class=\"sup-hdr\"><span class=\"noAccess\">Del</span></td>\n";
							print "	<td class=\"sup-hdr\"><span class=\"noAccess\">&nbsp;</span></td>\n";
						}
					}
					print "</tr>\n";
				}

				print "</tr>\n";
				print "<tr>\n";
				print "	<td colspan=\"$colspan\" style=\"border-top:1px solid #999999\">";
				if ($pendingCount) { print $pendingCount; }
				elseif ($count) { print $count; }
				print "</td>\n";
				print "</tr>\n";
				print "</table>\n";

				print "<span class=\"notclicked\">\n";
				print "	<p style=\"width:98%;text-align:right\">Multiple Delete <input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>";
				print "</span>\n";

				print "<span class=\"uploading\">\n";
				print "	<p style=\"width:98%;text-align:right\"><img src=\"images/uploading.gif\" alt=\"\" /></p>";
				print "</span>\n";

				print "<p style=\"width:98%;text-align:right\"><span class=\"redtext\">Warning, multiple delete does not ask for confimation.</span></p>";

				print "</form>\n";

				print "<script>\n";
				print "  $('.uploading').hide();\n";
				print "  	$('form').submit(function () {\n";
				print " 	$('.uploading').show();\n";
				print "  	$('.notclicked').hide();\n";
				print "  });\n";
				print "</script>\n";

			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
if ($print) {
	?>
	</body>
</html>
<?php
} else {
	print "</td>\n";
	include('footer.php');
}
?>