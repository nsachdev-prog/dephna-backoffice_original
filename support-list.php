<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$print = $_REQUEST['print'];
$page = "Support";
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Support</title>
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
	print "<style>\n";
	print "	#newalert { width: 100%; height: 20px; padding-top:1px; border-top: 1px solid #fff; border-left: 1px solid #fff; background-color: #666666; color: #fff; font-size: 12px; text-align: center; line-height:20px; }\n";
	print "</style>\n";
	print "<td class=\"main\" valign=\"top\">\n";
	print "<div id=\"newalert\"></div>\n";
}
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";

	?>
	<script>
	// run script imediately than every 20 seconds to check for new tickets
	$(document).ready(function(){
	function getnewalerts() {
		$('#newalert').html('Checking...');
		$.ajax({
			type:'GET',
			url: 'support-newalert.php',
			success: function (data){
				var json = JSON.parse(data);
				if (json.newsupport > 0 || json.newaccounts > 0 || json.newcamilla > 0 || json.newflats > 0) {
					$('#newalert').html('New Tickets&nbsp;&nbsp;&nbsp;&nbsp;Support ' + json.newsupport + '&nbsp;&nbsp;&nbsp;&nbsp;Accounts ' + json.newaccounts + '&nbsp;&nbsp;&nbsp;&nbsp;Flats ' + json.newflats + '&nbsp;&nbsp;&nbsp;&nbsp;Camilla ' + json.newcamilla);
					$('#newalert').show();
				} else {
					$('#newalert').hide();
				}
			},
			error: function (xhr, ajaxOptions, thrownError) {
				alert(xhr.status);
				alert(thrownError);
			}
		});
	}
	getnewalerts();
	setInterval(getnewalerts, 20000);
	});
	</script>
	<?php
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
			if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
			if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; }
			if ($_REQUEST['al'] != "All") { $al = $_REQUEST['al']; }
			$search[] = "t.status = 'Open'";
			if ($se) { $search[] = "(u.compname LIKE '%$se%' OR u.username LIKE '%$se%')"; }
			if ($dp) { $search[] = "t.custref = '$dp'"; }
			if ($al) { $search[] = "t.assignedto = '$al'"; }
			if ($ty) { $search[] = "t.type = '$ty'"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}
			$qString ="se=$se&amp;dp=$dp&amp;al=$al&amp;ty=$ty";

			$subpage = $ty;

			if (!$print) {
				include('support-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
			}

			print "<p align=\"center\" class=\"larger\">Open $ty Tickets</p>\n";

			if (!$print) {
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

			$sql = "SELECT t.*, u.compname, s.username AS assigned,
					(SELECT edatetime
							FROM support_emails
							WHERE tid = t.ID
							ORDER BY edatetime DESC
							LIMIT 0,1) AS edatetime
					FROM support_tickets AS t
					LEFT JOIN users AS u ON u.custref = t.custref
					LEFT JOIN staff AS s ON s.ID = t.assignedto
					$search
					ORDER BY t.flagged DESC, t.pending, t.replied, edatetime DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				if (!$print) {
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"4px\">\n";
					print "<tr>\n";
					print "<td style=\"width:130px;text-align:center;background-color:#e25e5e\">New</td>\n";
					print "<td style=\"width:130px;text-align:center;background-color:#d1793e\">Client Responded</td>\n";
					print "<td style=\"width:130px;text-align:center;background-color:#67afe2\">Dephna Answered</td>\n";
					print "<td style=\"width:130px;text-align:center;background-color:#ccca68\">Flagged</td>\n";
					print "<td style=\"width:130px;text-align:center;background-color:#74c09d\">Pending</td>\n";
					print "</tr>\n";
					print "</table><br>\n";
					print "<p align=\"center\">";
					print "<a href=\"support-new.php?ty=$ty\">Start New Ticket</a> | ";
					print "<a href=\"support-firealarm-close.php\">Close All Fire Alarms</a> | ";
					print "<a href=\"support-list.php?print=1\" target=\"_blank\">Print</a>";
					print "</p>\n";
				}

				$pendingCount = 0;
				$answeredCount = 0;
				$count = 0;

				print "<table class=\"sub-tbl\" width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";

				// print section heading
				print "<tr>\n";
				print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Inbox</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"60px\"><b>Asgn To</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Call</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Email</b></td>\n";
				if (!$print) {
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Meeting</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
					$colspan = 9;
				} else {
					$colspan = 7;
				}
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
					$meeting    = $row['meeting'];
					$pending    = $row['pending'];
					$replied    = $row['replied'];
					$from       = $row['aemails'];

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

					if (!$compname) { $compname = $from; }
					if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr","Mrs","Miss"),array("",""),$assigned);
						$assigned = trim($assigned);
					}
					
					if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
					if (strlen($subject)  > 30) { $subject  = substr($subject, 0, 30); }

					// get last call back
					$sql = "SELECT `crdate`, `crby`
							FROM `support_notes`
							WHERE `tid` = '$tid'
							  AND `details` LIKE '%Call Back...%'
							ORDER BY `crdate` DESC
							LIMIT 0,1
					";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$crdate = $lrow['crdate'];
						$cbby   = $lrow['crby'];
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
					$sql = "SELECT edatetime, `crdate`, `body`, `headers`
							FROM `support_emails`
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
						print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Answered</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"60px\"><b>Asgn To</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Call</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Email</b></td>\n";
						if (!$print) {
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Meeting</b></td>\n";
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
						print "	<td colspan=\"2\" class=\"larger\" style=\"border-bottom:1px solid #999999\">Pending</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"60px\"><b>Asgn To</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Call</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Email</b></td>\n";
						if (!$print) {
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Meeting</b></td>\n";
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
					print "	<td class=\"sup-hdr{$styleviewed}\" align=\"center\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$ticketno</a></td>\n";
					print "	<td class=\"sup-hdr tooltip\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\"><span class=\"tooltiptext\">$body</span><span class=\"subject\">$subject</span><br>$edatetime - $compname</a></td>\n";
					print "	<td class=\"sup-hdr\" align=\"right\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$days</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$assigned</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$fvby<br>$fvdate</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$cbby<br>$lastcb</a></td>\n";
					print "	<td class=\"sup-hdr\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">$crby<br>$last</a></td>\n";
					if (!$print) {
						print "	<td class=\"sup-hdr\"><a href=\"support-meeting.php?tid=$tid&amp;custref=$custref&amp;$qString\">$meeting</a></td>\n";
						if ($accessRights[$page] > 2) {
							print "	<td class=\"sup-hdr\"><a href=\"support-delete.php?tid=$tid&amp;$qString\">Del</a></td>\n";
						} else {
							print "	<td class=\"sup-hdr\"><span class=\"noAccess\">Delete</span></td>\n";
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

				if ($ty == "Camilla") {
					print "<p align=\"center\">";
					print "<a href=\"support-close-all.php?ty=$ty\">Close All Open Camilla Tickets</a>";
					print "</p>\n";
				}

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