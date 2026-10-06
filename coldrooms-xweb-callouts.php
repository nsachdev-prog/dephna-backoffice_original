<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Callouts";
$ty = $_REQUEST['ty'];
$ob = $_REQUEST['ob'];
if (!$ty) { $ty = "O"; }
if (!$ob) { $ob = "D"; }
if ($ty == "O") { $subpage = "Ongoing"; $search = "a.cleared = '0'"; }
else { $subpage = "Cleared"; $search = "a.cleared > '0'"; }
if ($ob == "U") {
	$orderby = "ORDER BY sitename, a.unitname, a.xdatetime DESC";
} else {
	$orderby = "ORDER BY a.xdatetime DESC";
}
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM CALLOUTS</p>\n";

	print "<p align=\"center\">";
	print "<span class=\"active\">Engineer Callouts</span> | ";
	print "<a href=\"coldrooms-clientcallouts-list.php\">Client Callouts</a> | ";
	print "<a href=\"coldrooms-warnings-lights.php\">Light Warnings</a> | ";
	print "<a href=\"coldrooms-warnings-doors.php\">Door Warnings</a>";
	print "</p>\n";

	print "<p align=\"center\">";
    print "<a href=\"coldrooms-xweb-callouts.php?ty=O\">"; if ($ty == "O") { print "<span class=\"active\">"; } print "Ongoing</a> | ";
    print "<a href=\"coldrooms-xweb-callouts.php?ty=C\">"; if ($ty == "C") { print "<span class=\"active\">"; } print "Cleared</a>";
    print "</p>";

	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-callouts.php?ty=$ty&amp;ob=D\">"; if ($ob == "D") { print "<span class=\"active\">"; } print "By Date</a> | ";
	print "<a href=\"coldrooms-xweb-callouts.php?ty=$ty&amp;ob=U\">"; if ($ob == "U") { print "<span class=\"active\">"; } print "By Unit</a>";
	print "</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// setup page number
		$rowsPerPage = 20;
		$maxPageNums = 8;
		$pageNum = 1;
		if($_GET['pa']) { $pageNum = $_GET['pa']; }
		$offset = ($pageNum - 1) * $rowsPerPage;

		if ($ty == "C") {
			$limit = "LIMIT $offset, $rowsPerPage";
		} else {
			$limit = "";
		}

		$qString ="ty=$ty&amp;ob=$ob";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include_once('xweb-sensor-ids.php');
			$aids = array();

			if ($_POST['posted']) {

				$title   = strip_tags($_POST['title']);
				$details = "";
				if (!$title) {
					$ertitle = "You must enter something!"; $e++;
				}
				$aids = $_POST['aids'];
				if (count($aids) == 0) {
					$resultMessage .= "Nothing Selected<br />"; $e++;
				}

				if (!$e) {

					$title    = addslashes($title);
					$cleared  = date("Y-m-d H:i");
					$now      = time();
					$datetime = date("Y-m-d H:i");

					$doorswitch = $_POST['doorswitch'];

					foreach ($aids AS $aid) {

						// get details
						$sql = "SELECT * FROM xweb_actions WHERE ID = '$aid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$siteid    = $xrow['siteid'];
							$unitid    = $xrow['unitid'];
							$unitname  = $xrow['unitname'];
							$custref   = $xrow['custref'];
							$deviceid  = $xrow['deviceid'];

							$sql = "INSERT INTO xweb_actions_notes VALUES (
								null,
								'$aid',
								'$siteid',
								'$unitid',
								'$custref',
								'$datetime',
								'$title',
								'$details',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Action Note Created<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$unitname Action Note','Xweb','$now')");
							} else {
								$resultMessage .= "Could NOT add action note<br />" . $dbcnx->error . "<br />";
							}
							
							$sql = "UPDATE xweb_actions SET 
								cleared='$cleared',
								clearedby='{$_SESSION['username']}'
								WHERE ID = '$aid'
							";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Action $aid Cleared<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Action Note','Xweb','$now')");
								
								if ($doorswitch) {
									
									if ($deviceid) {
										// reset open door control to F_C

										include('../public_html/backoffice/XWebGetAccessToken.php');

										if ($access_token) {
											
											$parameters = array();
											$parameters[] = array(
												'section' => 1,
												'label'   => 'Odc',
												'value'   => 'F_C'
											);
								
											$targets = array();
											$targets[] = $deviceid;

											$postdata = http_build_query(
												array(
													'auth'   => $access_token,
													'action' => 'write_parameters'
												)
											);

											$postdata .= "&targets=" . json_encode($targets);
											$postdata .= "&parameters=" . json_encode($parameters);

											$ch = curl_init();
											curl_setopt($ch, CURLOPT_POST, 1);
											curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
											curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
											curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
											$oresult = curl_exec($ch);
											curl_close($ch);

											if ($oresult !== FALSE) {
												$oresult = json_decode($oresult, TRUE);
												if ($oresult['status'] == "ok") {
													$resultMessage .= "Odc Reset to F_C Unit $unitname<br />";
												} else {
													$resultMessage .= "Could NOT reset Odc to F_C Unit $unitname<br />";
												}
											} else {
												$resultMessage .= "No Reply from XWeb trying to reset Odc to F_C Unit $unitname<br />";
											}
										} else {
											$resultMessage .= "Could NOT get Access Token trying to reset Odc to F_C Unit $unitname<br />";
										}
									} else {
										$resultMessage .= "No Device ID trying to reset Odc to F_C Unit $unitname<br />";
									}
								}

							} else {
								$resultMessage .= "Could NOT clear action $aid<br />" . $dbcnx->error . "<br />";
							}

						} else {
							$resultMessage .= "Action $aid not found<br />" . $dbcnx->error . "<br />";
						}
						
					}
				}
			}

			$now  = time();
			
			// Callouts
			print "<p align=\"center\"><span class=\"larger\">$subpage Engineer Callouts</span></p>\n";

			print "<p align=\"center\"><span class=\"larger\">Temperature Failures</span></p>\n";
			
			$sql = "SELECT a.*, s.title AS sitename, st.username AS assigned, u.engineermode
					FROM xweb_actions AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					LEFT JOIN staff AS st ON st.ID = a.assignedto
					LEFT JOIN units AS u ON u.ID = a.unitid
					WHERE a.xstatus = '1'
					  AND (a.details = 'Callout-TF' OR a.details = 'Callout-DF' OR a.details = 'Callout-DO' or a.details = 'NODATA' or a.details = 'Callout-GL')
					  AND $search
					$orderby $limit
			";
			if (($xresult = $dbcnx->query($sql)) AND ($xresult->num_rows)) {

				print "<form name=\"addform1\" method=\"post\" action=\"coldrooms-xweb-callouts.php?ty=$ty\">\n";
				print "<fieldset style=\"border:none\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

				print "<table width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Days</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>EngM</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Asg To</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>First V</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Last V</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>LHT</b></td>\n"; // Last Hit Temperature
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Wtch</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>SetP</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Temp</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Evap</b></td>\n";
				}
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
				}
				print "</tr>\n";

				while ($xrow = $xresult->fetch_assoc()) {

					$aid       = $xrow['ID'];
					$datetime  = $xrow['xdatetime'];
					$siteid    = $xrow['siteid'];
					$sitename  = $xrow['sitename'];
					$unitid    = $xrow['unitid'];
					$unitname  = $xrow['unitname'];
					$custref   = $xrow['custref'];
					$compname  = $xrow['compname'];
					$action    = $xrow['details'];
					$cleared   = $xrow['cleared'];
					$assigned  = $xrow['assigned'];
					$fvdate    = $xrow['fvdate'];
					$fvby      = $xrow['fvby'];
					$watching  = $xrow['watching'];
					$engineermode = $xrow['engineermode'];
					$details   = $xrow['details'];

					list($junk,$type) = explode("-",$details);

					if (!$watching OR $watching == "No") {
						$watching = "&nbsp;";
					}

					if ($engineermode) {
						$engineermode = "Yes";
					} else {
						$engineermode = "&nbsp;";
					}

					if (strlen($compname) > 8) { $compname = substr($compname, 0, 8); }
					if (strlen($sitename) > 8) { $sitename = substr($sitename, 0, 8); }

					if ($fvby) {
						$fvdate = date("d/m H:i",$fvdate);
						$fvby = str_replace(array("Mr ","Mrs "),array("",""),$fvby);
						list($fvby,$junk) = explode(" ",$fvby);
						$fvby = trim($fvby);
					} else {
						$fvby   = "&nbsp;";
						$fvdate = "&nbsp;";
					}

					// get last view from xweb_actions_notes
					$sql = "SELECT lastupd, updby FROM xweb_actions_notes WHERE aid = '$aid' ORDER BY lastupd DESC LIMIT 0,1";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$lastview = date("d/m H:i",$lrow['lastupd']);
						$lastby = str_replace(array("Mr ","Mrs "),array("",""),$lrow['updby']);
						list($lastby,$junk) = explode(" ",$lastby);
						$lastby = trim($lastby);
					} else {
						$lastview = "&nbsp;";
						$lastby   = "&nbsp;";
					}

					if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
						// get date from notes
						$sql = "SELECT lastupd FROM xweb_actions_notes WHERE aid = '$aid' AND details LIKE '%Assigned to%' ORDER BY lastupd DESC LIMIT 0,1";
						if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
							$lrow = $lresult->fetch_assoc();
							$asdate = $lrow['lastupd'];
							$asdate = date("d/m H:i",$asdate);
							$assigned .= "<br>" . $asdate;
						}
					}

					if ($ty == "O") {
						// get current temp
						$sql = "SELECT temperature, setpoint, evaporator
								FROM xweb_history1
								WHERE siteid='$siteid' AND unitid = '$unitid'
								ORDER BY xdatetime DESC
								LIMIT 0,1
						";
						if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
							$trow = $tresult->fetch_assoc();
							$temperature = $trow['temperature'];
							$setpoint    = $trow['setpoint'];
							$evaporator  = $trow['evaporator'];

							// get LHT
							$tempFrom = $setpoint + 1;
							$tempTo   = $setpoint - 1;
							$sql = "SELECT xdatetime FROM xweb_history1
									WHERE siteid='$siteid'
									  AND unitid = '$unitid'
									  AND (temperature > $tempTo AND temperature < $tempFrom)
									ORDER BY xdatetime DESC
									LIMIT 0,1
							";
							if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
								$trow = $tresult->fetch_assoc();
								$lht = $trow['xdatetime'];
								$lht = date("d/m H:i", strtotime($lht));
							} else {
								$lht = "&nbsp;";
							}
						} else {
							$temperature = "&nbsp;";
							$setpoint    = "&nbsp;";
							$evaporator  = "&nbsp;";
						}

					}

					$diff = $now - strtotime($datetime);
					$days = floor($diff/(60*60*24));
					$datetime = date("d/m H:i", strtotime($datetime));

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$datetime</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$type</td>\n";
					if ($ty == "O") {
						print "	<td>$days</td>\n";
						print "	<td>$engineermode</td>\n";
						print "	<td>$assigned</td>\n";
						print "	<td>$fvby<br>$fvdate</td>\n";
						print "	<td>$lastby<br>$lastview</td>\n";
						print "	<td>$lht</td>\n";
						print "	<td>$watching</td>\n";
						print "	<td>$setpoint</td>\n";
						print "	<td>$temperature</td>\n";
						print "	<td>$evaporator</td>\n";
					}
					print "	<td><a href=\"coldrooms-xweb-actions-view.php?aid=$aid&amp;ty=$ty\">View</a></td>\n";
					if ($ty == "O") {
						print "	<td align=\"right\"><input type=\"checkbox\" name=\"aids[]\" value=\"$aid\""; if (in_array($aid,$aids)) { print " CHECKED"; } print " /></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
				if ($ty == "O") {
					if (!$title) {
						$title = "Clear";
					}
					print "<td colspan=\"16\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "<tr>\n";
					print "	<td colspan=\"8\" align=\"right\"><b>Bulk Clear Description</b>: ";
					print "<input name=\"title\" type=\"text\" class=\"element_small\" maxlength=\"100\" value=\"$title\" />\n";
					if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
					print "	</td>\n";
					print "	<td colspan=\"2\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>";

					print "</tr>\n";
				} else {
					print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				}
				print "</tr>\n";
				print "</table>\n";
				print "</fieldset>\n";

				// select all toggle
				print "<script type=\"text/javascript\">\n";
				print "$(function () {\n";
				print "    $('.checkall').on('click', function () {\n";
				print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
				print "    });\n";
				print "});\n";
				print "</script>\n";

				if ($ty == "C") {
					// pagination block
					$self  = $_SERVER['PHP_SELF'];
					$query = "SELECT COUNT(*) AS numrows
						FROM xweb_actions AS a
						WHERE a.xstatus = '1' AND a.details = 'Callout-TF' AND $search
					";
					$result = $dbcnx->query($query);
					$row     = $result->fetch_array(MYSQLI_ASSOC);
					$numrows = $row['numrows'];
					$maxPage = ceil($numrows/$rowsPerPage);
					if ($maxPage > 1) {
						$nav  = '';

						if ($maxPage > $maxPageNums+2) {
							$start = ceil($pageNum-$maxPageNums/2);
							if ($start < 3) {
								$start = 1;
							} else {
								$first = " <a href=\"$self?pa=1&amp;$qString&amp;ac=$ac\">1</a> ... ";
							}
							$end = $start+$maxPageNums;
							if ($end > $maxPage-2) {
								$end = $maxPage;
								$start = $end-$maxPageNums;
							} else {
								$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString&amp;ac=$ac\">$maxPage</a> ";
							}
						} else {
							$start = 1;
							$end = $maxPage;
						}

						for($pa = $start; $pa <= $end; $pa++) {
							if ($pa == $pageNum) {
								$nav .= " <span class=\"orangeText\">$pa</span> ";
							} else {
								$nav .= " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">$pa</a> ";
							}
						}

						if ($pageNum > 1) {
							$pa  = $pageNum - 1;
							$prev  = " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Prev</a> |";
						} else {
							$prev  = '&nbsp;';
						}
						if ($pageNum < $maxPage) {
							$pa = $pageNum + 1;
							$next = "| <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Next</a> ";
						} else {
							$next = '&nbsp;';
						}
						print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
					}
					// pagination end
				}

			} else {
				print "<p align=\"center\">None Found</p>\n";
			}

			print "<p align=\"center\"><span class=\"larger\">Door Switch Failures</span></p>\n";
			
			$sql = "SELECT a.*, s.title AS sitename, st.username AS assigned, u.engineermode
					FROM xweb_actions AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					LEFT JOIN staff AS st ON st.ID = a.assignedto
					LEFT JOIN units AS u ON u.ID = a.unitid
					WHERE a.xstatus = '1' AND a.details = 'Callout-DSF' AND $search
					$orderby $limit
			";
			if (($xresult = $dbcnx->query($sql)) AND ($xresult->num_rows)) {

				print "<form name=\"addform2\" method=\"post\" action=\"coldrooms-xweb-callouts.php?ty=$ty\">\n";
				print "<fieldset style=\"border:none\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"doorswitch\" value=\"1\" />\n";

				print "<table width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Days</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>EngM/b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Asgnd To</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Last V</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Wtch</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>SetP</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Temp</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Evap</b></td>\n";
				}
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
				}
				print "</tr>\n";

				while ($xrow = $xresult->fetch_assoc()) {

					$aid       = $xrow['ID'];
					$datetime  = $xrow['xdatetime'];
					$siteid    = $xrow['siteid'];
					$sitename  = $xrow['sitename'];
					$unitid    = $xrow['unitid'];
					$unitname  = $xrow['unitname'];
					$custref   = $xrow['custref'];
					$compname  = $xrow['compname'];
					$action    = $xrow['details'];
					$cleared   = $xrow['cleared'];
					$lastseen  = $xrow['lastseen'];
					$assigned  = $xrow['assigned'];
					$fvdate    = $xrow['fvdate'];
					$fvby      = $xrow['fvby'];
					$watching  = $xrow['watching'];
					$engineermode = $xrow['engineermode'];

					if (!$watching OR $watching == "No") {
						$watching = "&nbsp;";
					}

					if ($engineermode) {
						$engineermode = "Yes";
					} else {
						$engineermode = "&nbsp;";
					}

					if (strlen($compname) > 8) { $compname = substr($compname, 0, 8); }
					if (strlen($sitename) > 8) { $sitename = substr($sitename, 0, 8); }

					if ($fvby) {
						$fvdate = date("d/m H:i",$fvdate);
						$fvby = str_replace(array("Mr ","Mrs "),array("",""),$fvby);
						list($fvby,$junk) = explode(" ",$fvby);
						$fvby = trim($fvby);
					} else {
						$fvby   = "&nbsp;";
						$fvdate = "&nbsp;";
					}

					// get last view from xweb_actions_notes
					$sql = "SELECT lastupd, updby FROM xweb_actions_notes WHERE aid = '$aid' ORDER BY lastupd DESC LIMIT 0,1";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$lastview = date("d/m H:i",$lrow['lastupd']);
						$lastby = str_replace(array("Mr ","Mrs "),array("",""),$lrow['updby']);
						list($lastby,$junk) = explode(" ",$lastby);
						$lastby = trim($lastby);
					} else {
						$lastview = "&nbsp;";
						$lastby   = "&nbsp;";
					}

					if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
						// get date from notes
						$sql = "SELECT lastupd FROM xweb_actions_notes WHERE aid = '$aid' AND details LIKE '%Assigned to%' ORDER BY lastupd DESC LIMIT 0,1";
						if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
							$lrow = $lresult->fetch_assoc();
							$asdate = $lrow['lastupd'];
							$asdate = date("d/m H:i",$asdate);
							$assigned .= "<br>" . $asdate;
						}
					}

					if ($ty == "O") {
						// get latest temp
						$sql = "SELECT temperature, setpoint, evaporator
								FROM xweb_history1
								WHERE siteid='$siteid' AND unitid = '$unitid'
								ORDER BY xdatetime DESC
								LIMIT 0,1
						";
						if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
							$trow = $tresult->fetch_assoc();
							$temperature = $trow['temperature'];
							$setpoint    = $trow['setpoint'];
							$evaporator   = $trow['evaporator'];
						} else {
							$temperature = "&nbsp;";
							$setpoint    = "&nbsp;";
							$evaporator   = "&nbsp;";
						}

					}

					// get callout status
					$sql = "SELECT ID
							FROM ClientCallouts
							WHERE siteid = '$siteid' AND unitid = $unitid
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ccoid = $row['ID'];
					} else {
						$ccoid = 0;
					}

					$diff = $now - strtotime($datetime);
					$days = floor($diff/(60*60*24));

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$datetime</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print "	<td>$compname</td>\n";
					if ($ty == "O") {
						print "	<td>$days</td>\n";
						print "	<td>$engineermode</td>\n";
						print "	<td>$assigned</td>\n";
						print "	<td>$fvby<br>$fvdate</td>\n";
						print "	<td>$watching</td>\n";
						print "	<td>$setpoint</td>\n";
						print "	<td>$temperature</td>\n";
						print "	<td>$evaporator</td>\n";
					}
					print "	<td><a href=\"coldrooms-xweb-actions-view.php?aid=$aid&amp;ty=$ty\">View</a></td>\n";
					if ($ccoid) {
						print " <td align=\"left\"><a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid\">Active</a></td>\n";
					} else {
						print " <td align=\"left\"><a href=\"coldrooms-clientcallouts-addedit.php?siteid=$siteid&amp;unitid=$unitid\">Callout</a></td>\n";
					}
					if ($ty == "O") {
						print "	<td align=\"right\"><input type=\"checkbox\" name=\"aids[]\" value=\"$aid\""; if (in_array($aid,$aids)) { print " CHECKED"; } print " /></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
				if ($ty == "O") {
					if (!$title) {
						$title = "Clear";
					}
					print "<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "<tr>\n";
					print "	<td colspan=\"8\" align=\"right\"><b>Bulk Clear Description</b>: ";
					print "<input name=\"title\" type=\"text\" class=\"element_small\" maxlength=\"100\" value=\"$title\" />\n";
					if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
					print "	</td>\n";
					print "	<td colspan=\"2\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>";

					print "</tr>\n";
				} else {
					print "	<td colspan=\"9\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				}
				print "</tr>\n";
				print "</table>\n";
				print "</fieldset>\n";

				if ($ty == "C") {
					// pagination block
					$self  = $_SERVER['PHP_SELF'];
					$query = "SELECT COUNT(*) AS numrows
						FROM xweb_actions AS a
						WHERE a.xstatus = '1' AND a.details = 'Callout-DSF' AND $search
					";
					$result = $dbcnx->query($query);
					$row     = $result->fetch_array(MYSQLI_ASSOC);
					$numrows = $row['numrows'];
					$maxPage = ceil($numrows/$rowsPerPage);
					if ($maxPage > 1) {
						$nav  = '';

						if ($maxPage > $maxPageNums+2) {
							$start = ceil($pageNum-$maxPageNums/2);
							if ($start < 3) {
								$start = 1;
							} else {
								$first = " <a href=\"$self?pa=1&amp;$qString&amp;ac=$ac\">1</a> ... ";
							}
							$end = $start+$maxPageNums;
							if ($end > $maxPage-2) {
								$end = $maxPage;
								$start = $end-$maxPageNums;
							} else {
								$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString&amp;ac=$ac\">$maxPage</a> ";
							}
						} else {
							$start = 1;
							$end = $maxPage;
						}

						for($pa = $start; $pa <= $end; $pa++) {
							if ($pa == $pageNum) {
								$nav .= " <span class=\"orangeText\">$pa</span> ";
							} else {
								$nav .= " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">$pa</a> ";
							}
						}

						if ($pageNum > 1) {
							$pa  = $pageNum - 1;
							$prev  = " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Prev</a> |";
						} else {
							$prev  = '&nbsp;';
						}
						if ($pageNum < $maxPage) {
							$pa = $pageNum + 1;
							$next = "| <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Next</a> ";
						} else {
							$next = '&nbsp;';
						}
						print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
					}
					// pagination end
				}

				// select all toggle
				print "<script type=\"text/javascript\">\n";
				print "$(function () {\n";
				print "    $('.checkall').on('click', function () {\n";
				print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
				print "    });\n";
				print "});\n";
				print "</script>\n";

			} else {
				print "<p align=\"center\">None Found</p>\n";
			}


			print "<p align=\"center\"><span class=\"larger\">Power Failures</span></p>\n";
			
			$sql = "SELECT a.*, s.title AS sitename, st.username AS assigned, u.engineermode
					FROM xweb_actions AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					LEFT JOIN staff AS st ON st.ID = a.assignedto
					LEFT JOIN units AS u ON u.ID = a.unitid
					WHERE a.xstatus = '1' AND a.details = 'Callout-PF' AND $search
					$orderby $limit
			";
			if (($xresult = $dbcnx->query($sql)) AND ($xresult->num_rows)) {

				print "<form name=\"addform3\" method=\"post\" action=\"coldrooms-xweb-callouts.php?ty=$ty\">\n";
				print "<fieldset style=\"border:none\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

				print "<table width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Days</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>EngM</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Asgnd To</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>First View</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Last V</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Wtch</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>SetP</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Temp</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Evap</b></td>\n";
				}
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
				}
				print "</tr>\n";

				while ($xrow = $xresult->fetch_assoc()) {

					$aid       = $xrow['ID'];
					$datetime  = $xrow['xdatetime'];
					$siteid    = $xrow['siteid'];
					$sitename  = $xrow['sitename'];
					$unitid    = $xrow['unitid'];
					$unitname  = $xrow['unitname'];
					$custref   = $xrow['custref'];
					$compname  = $xrow['compname'];
					$action    = $xrow['details'];
					$cleared   = $xrow['cleared'];
					$assigned  = $xrow['assigned'];
					$fvdate    = $xrow['fvdate'];
					$fvby      = $xrow['fvby'];
					$watching  = $xrow['watching'];
					$engineermode = $xrow['engineermode'];

					if (!$watching OR $watching == "No") {
						$watching = "&nbsp;";
					}

					if ($engineermode) {
						$engineermode = "Yes";
					} else {
						$engineermode = "&nbsp;";
					}

					if (strlen($compname) > 8) { $compname = substr($compname, 0, 8); }
					if (strlen($sitename) > 8) { $sitename = substr($sitename, 0, 8); }

					if ($fvby) {
						$fvdate = date("d/m H:i",$fvdate);
						$fvby = str_replace(array("Mr ","Mrs "),array("",""),$fvby);
						list($fvby,$junk) = explode(" ",$fvby);
						$fvby = trim($fvby);
					} else {
						$fvby   = "&nbsp;";
						$fvdate = "&nbsp;";
					}

					// get last view from xweb_actions_notes
					$sql = "SELECT lastupd, updby FROM xweb_actions_notes WHERE aid = '$aid' ORDER BY lastupd DESC LIMIT 0,1";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$lastview = date("d/m H:i",$lrow['lastupd']);
						$lastby = str_replace(array("Mr ","Mrs "),array("",""),$lrow['updby']);
						list($lastby,$junk) = explode(" ",$lastby);
						$lastby = trim($lastby);
					} else {
						$lastview = "&nbsp;";
						$lastby   = "&nbsp;";
					}

					if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
						// get date from notes
						$sql = "SELECT lastupd FROM xweb_actions_notes WHERE aid = '$aid' AND details LIKE '%Assigned to%' ORDER BY lastupd DESC LIMIT 0,1";
						if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
							$lrow = $lresult->fetch_assoc();
							$asdate = $lrow['lastupd'];
							$asdate = date("d/m H:i",$asdate);
							$assigned .= "<br>" . $asdate;
						}
					}

					if ($ty == "O") {
						// get latest temp
						$sql = "SELECT temperature, setpoint, evaporator
								FROM xweb_history1
								WHERE siteid='$siteid' AND unitid = '$unitid'
								ORDER BY xdatetime DESC
								LIMIT 0,1
						";
						if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
							$trow = $tresult->fetch_assoc();
							$temperature = $trow['temperature'];
							$setpoint    = $trow['setpoint'];
							$evaporator   = $trow['evaporator'];
						} else {
							$temperature = "&nbsp;";
							$setpoint    = "&nbsp;";
							$evaporator   = "&nbsp;";
						}

					}

					// get callout status
					$sql = "SELECT ID
							FROM ClientCallouts
							WHERE siteid = '$siteid' AND unitid = $unitid
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ccoid = $row['ID'];
					} else {
						$ccoid = 0;
					}

					$diff = $now - strtotime($datetime);
					$days = floor($diff/(60*60*24));

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$datetime</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print "	<td>$compname</td>\n";
					if ($ty == "O") {
						print "	<td>$days</td>\n";
						print "	<td>$engineermode</td>\n";
						print "	<td>$assigned</td>\n";
						print "	<td>$fvby<br>$fvdate</td>\n";
						print "	<td>$lastby<br>$lastview</td>\n";
						print "	<td>$watching</td>\n";
						print "	<td>$setpoint</td>\n";
						print "	<td>$temperature</td>\n";
						print "	<td>$evaporator</td>\n";
					}
					print "	<td><a href=\"coldrooms-xweb-actions-view.php?aid=$aid&amp;ty=$ty\">View</a></td>\n";
					if ($ccoid) {
						print " <td align=\"left\"><a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid\">Active</a></td>\n";
					} else {
						print " <td align=\"left\"><a href=\"coldrooms-clientcallouts-addedit.php?siteid=$siteid&amp;unitid=$unitid\">Callout</a></td>\n";
					}
					if ($ty == "O") {
						print "	<td align=\"right\"><input type=\"checkbox\" name=\"aids[]\" value=\"$aid\""; if (in_array($aid,$aids)) { print " CHECKED"; } print " /></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
				if ($ty == "O") {
					if (!$title) {
						$title = "Clear";
					}
					print "<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "<tr>\n";
					print "	<td colspan=\"8\" align=\"right\"><b>Bulk Clear Description</b>: ";
					print "<input name=\"title\" type=\"text\" class=\"element_small\" maxlength=\"100\" value=\"$title\" />\n";
					if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
					print "	</td>\n";
					print "	<td colspan=\"2\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>";

					print "</tr>\n";
				} else {
					print "	<td colspan=\"9\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				}
				print "</tr>\n";
				print "</table>\n";
				print "</fieldset>\n";

				if ($ty == "C") {
					// pagination block
					$self  = $_SERVER['PHP_SELF'];
					$query = "SELECT COUNT(*) AS numrows
						FROM xweb_actions AS a
						WHERE a.xstatus = '1' AND a.details = 'Callout-PF' AND $search
					";
					$result = $dbcnx->query($query);
					$row     = $result->fetch_array(MYSQLI_ASSOC);
					$numrows = $row['numrows'];
					$maxPage = ceil($numrows/$rowsPerPage);
					if ($maxPage > 1) {
						$nav  = '';

						if ($maxPage > $maxPageNums+2) {
							$start = ceil($pageNum-$maxPageNums/2);
							if ($start < 3) {
								$start = 1;
							} else {
								$first = " <a href=\"$self?pa=1&amp;$qString&amp;ac=$ac\">1</a> ... ";
							}
							$end = $start+$maxPageNums;
							if ($end > $maxPage-2) {
								$end = $maxPage;
								$start = $end-$maxPageNums;
							} else {
								$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString&amp;ac=$ac\">$maxPage</a> ";
							}
						} else {
							$start = 1;
							$end = $maxPage;
						}

						for($pa = $start; $pa <= $end; $pa++) {
							if ($pa == $pageNum) {
								$nav .= " <span class=\"orangeText\">$pa</span> ";
							} else {
								$nav .= " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">$pa</a> ";
							}
						}

						if ($pageNum > 1) {
							$pa  = $pageNum - 1;
							$prev  = " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Prev</a> |";
						} else {
							$prev  = '&nbsp;';
						}
						if ($pageNum < $maxPage) {
							$pa = $pageNum + 1;
							$next = "| <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Next</a> ";
						} else {
							$next = '&nbsp;';
						}
						print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
					}
					// pagination end
				}

				// select all toggle
				print "<script type=\"text/javascript\">\n";
				print "$(function () {\n";
				print "    $('.checkall').on('click', function () {\n";
				print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
				print "    });\n";
				print "});\n";
				print "</script>\n";

			} else {
				print "<p align=\"center\">None Found</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
        }

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
