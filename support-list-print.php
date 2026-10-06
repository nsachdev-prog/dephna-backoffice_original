<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "List";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
			if ($_REQUEST['st']) { $st = $_REQUEST['st']; } else { $st = "Open"; }
			$subpage = $st;
			if ($_REQUEST['al'] != "All") { $al = $_REQUEST['al']; }
			if ($se) { $search[] = "(u.compname LIKE '%$se%' OR u.username LIKE '%$se%')"; }
			if ($dp) { $search[] = "t.custref = '$dp'"; }
			if ($st) { $search[] = "t.status = '$st'"; }
			if ($al) { $search[] = "t.assignedto = '$al'"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}
			if ($st == "Closed") {
				$limit = "LIMIT $offset, $rowsPerPage";
			} else {
				$limit = "";
			}
			$qString ="se=$se&amp;dp=$dp&amp;st=$st&amp;al=$al";

			include('support-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			if ($st == "Open") {
				print "<p align=\"center\" class=\"larger\">Open Tickets</p>\n";
			} elseif ($st == "Closed") {
				print "<p align=\"center\" class=\"larger\">Closed Tickets</p>\n";
			}

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"support-list.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Company/Name</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>DP</b></td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";

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

			print "		<td><a href=\"support-list.php\"><b>Reset</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			print "<br>\n";

			?>
			<style>
				.sup-hdr {
					color: #333;
					font-size: 12px;
					border-bottom: 1px solid #fff;
				}
				.sup-hdr a { display: block; }
				.sup-hdr a:link { color: #333; }
				.sup-hdr a:hover { color: #333; }
				.sup-hdr a:visited { color: #333; }
				.subject {
					font-size: 14px;
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
				.pending {
					background-color: #74c09d;
				}
				.tooltip {
					position: relative;
					display: block;
				}
				.tooltip .tooltiptext {
					visibility: hidden;
					width: 400px;
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
					border-width: 5px;
					border-style: solid;
					border-color: #555 transparent transparent transparent;
				}
				.tooltip:hover .tooltiptext {
					visibility: visible;
					opacity: 1;
				}
			</style>
			<?php

			$sql = "SELECT t.*, u.compname, u.location, s.username AS assigned
					FROM support_tickets AS t
					LEFT JOIN users AS u ON u.custref = t.custref
					LEFT JOIN staff AS s ON s.ID = t.assignedto
					$search
					ORDER BY flagged DESC, pending, replied, tdatetime DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"4px\">\n";
				print "<tr>\n";
				print "<td style=\"width:130px;text-align:center;background-color:#e25e5e\">New</td>\n";
				print "<td style=\"width:130px;text-align:center;background-color:#d1793e\">Client Responded</td>\n";
				print "<td style=\"width:130px;text-align:center;background-color:#67afe2\">Dephna Answered</td>\n";
				print "<td style=\"width:130px;text-align:center;background-color:#ccca68\">Flagged</td>\n";
				print "<td style=\"width:130px;text-align:center;background-color:#74c09d\">Pending</td>\n";
				print "</tr>\n";
				print "</table><br>\n";

				print "<p align=\"center\"><a href=\"support-new.php\">Start New Ticket</a></p>\n";

				$pendingCount = 0;
				$answeredCount = 0;
				$count = 0;

				print "<table class=\"sub-tbl\" width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";

				// print section heading
				print "<tr>\n";
				print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Inbox</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Assigned To</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>First Viewed</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Response</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$tid        = $row['ID'];
					$tdatetime  = $row['tdatetime'];
					$ticketno   = $row['ticketno'];
					$custref    = $row['custref'];
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

					list($junk,$from) = explode("@",$from);
					list($from,$junk) = explode(".",$from);

					if ($fvby) {
						$fvdate = date("d/m H:i",$fvdate);
						$fvby = str_replace(array("Mr","Mrs"),array("",""),$fvby);
						$fvby = trim($fvby);
					} else {
						$fvby   = "&nbsp;";
						$fvdate = "&nbsp;";
					}

					list($ind,$section) = explode("-",$section);

					if (!$compname) { $compname = $from; }
					if (!$assigned) {
						$assigned = "Unassigned";
					} else {
						$assigned = str_replace(array("Mr","Mrs","Miss"),array("",""),$assigned);
						$assigned = trim($assigned);
					}
					
					if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
					if (strlen($subject)  > 30) { $subject  = substr($subject, 0, 30); }

					$new = 0;
					// get last answer
					$sql = "SELECT `crdate`, `crby`
							FROM `support_emails`
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
						$crby = str_replace(array("Mr","Mrs"),array("",""),$crby);
						$crby = trim($crby);
					} else {
						$new = 1;
						$last = "&nbsp;";
						$crby = "&nbsp;";
					}

					// get last email text for tool tip and days
					$sql = "SELECT edatetime, `crdate`, `body`
							FROM `support_emails`
							WHERE `tid` = '$tid'
							ORDER BY `crdate` DESC
							LIMIT 0,1
					";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$crdate = $lrow['crdate'];
						$edatetime = $lrow['edatetime'];
						$body = $lrow['body'];
						$body = strip_tags($body);
						$body = str_replace('\r','',$body);
						$body = str_replace('\n'," ",$body);
						if (strlen($body) > 300) { $body = substr($body, 0, 300); }
						$now  = time();
						$diff = $now - $crdate;
						$days = floor($diff/(60*60*24));
						list($date,$time) = explode(" ",$edatetime);
						list($y,$m,$d) = explode("-",$date);
						$edatetime = "$d/$m $time";
					} else {
						$body = "Not Found";
					}

					if ($flagged) {
						$styleviewed = " flagged";
					} elseif ($pending) {
						$styleviewed = " pending";
					} elseif ($new) {
						$styleviewed = " newticket";
					} elseif ($replied AND !$pending) {
						$styleviewed = " dephnareplied";
					} else {
						$styleviewed = " clientresponded";
					}

					if ($st == "Open") {
						if ($replied AND !$pending AND !$answeredCount AND !$flagged) {
							print "<tr>\n";
							print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">";
							if ($count) { print $count; $count = 0; }
							print "</td>\n";
							print "</tr>\n";
							print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Answered</td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Assigned To</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>First Viewed</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Response</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
							print "</tr>\n";
							$answeredCount++;
						}
						elseif ($pending AND !$pendingCount AND !$flagged) {
							print "<tr>\n";
							print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">";
							if ($count) { print $count; $count = 0; }
							print "</td>\n";
							print "</tr>\n";
							print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Pending</td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Assigned To</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>First Viewed</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Response</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
							print "</tr>\n";
							$pendingCount++;
						}
						elseif ($pending AND !$flagged) {
							$pendingCount++;
						}
						$count++;
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td class=\"sup-hdr{$styleviewed}\" align=\"center\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$ticketno</a></td>\n";
					print "	<td class=\"sup-hdr tooltip\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\"><span class=\"tooltiptext\">$body</span><span class=\"subject\">$subject</span><br>$edatetime - $compname</a></td>\n";
					print "	<td class=\"sup-hdr\" align=\"right\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$days</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$assigned</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$fvby<br>$fvdate</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$crby<br>$last</a></td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td class=\"sup-hdr\"><a href=\"support-delete.php?tid=$tid&amp;$qString\">Delete</a></td>\n";
					} else {
						print "	<td class=\"sup-hdr\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}

				print "</tr>\n";
				print "<tr>\n";
				print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">";
				if ($pendingCount) { print $pendingCount; }
				elseif ($count) { print $count; }
				print "</td>\n";
				print "</tr>\n";
				print "</table>\n";

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
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
