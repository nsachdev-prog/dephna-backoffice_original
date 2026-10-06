<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
	
	print "<p align=\"center\" class=\"larger\">No Call Out</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$unitid = $_REQUEST['unitid'];
			$aid    = $_REQUEST['aid'];
			$now = time();

			if ($unitid) {

				$sql = "SELECT u.siteid, s.title AS sitename, u.title AS unitname
						FROM units AS u
						LEFT JOIN sites AS s ON u.siteid = s.ID
						WHERE u.ID = '$unitid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename      = $row['sitename'];
					$unitname      = $row['unitname'];
					$siteid        = $row['siteid'];
				}

				if ($_REQUEST['clear']) {
					
					if (!$aid) {
						$datetime = date("Y-m-d H:i");
						$sql = "INSERT INTO `xweb_actions` VALUES (
							null,
							'$datetime',
							'$siteid',
							'$unitid',
							'$unitname',
							'',
							'',
							'',
							'7',
							'{$_SESSION['username']}',
							'0',
							'',
							'No Callout Reset',
							'',
							'',
							'',
							'',
							'No'
						)";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Call Out Action Created<br />";
							$aid = $dbcnx->insert_id;
						} else {
							$resultMessage .= "Could NOT Create Action<br />" . $dbcnx->error . "<br />";
						}
					} else {
						$resultMessage .= "Could NOT clear No Call Out<br />" . $dbcnx->error . "<br />";
					}

					if ($aid) {
						$sql = "UPDATE units SET nocallout = '0', nocalloutid = '0', nocalloutfrom = '0', nocalloutto = '0' WHERE ID = '$unitid'";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "No Call Out Unit Updated<br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cleared','$unitname No Call Out','$page','$now')");
							$datetime = date("Y-m-d H:i");
							// add to xweb_actions_notes
							$sql = "INSERT INTO xweb_actions_notes VALUES (
								null,
								'$aid',
								'$siteid',
								'$unitid',
								'',
								'$datetime',
								'',
								'No Call Out Cleared',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Action Note Created<br />";
								$cleared  = date("Y-m-d H:i");
								$sql = "UPDATE xweb_actions SET 
									cleared='$cleared',
									clearedby='{$_SESSION['username']}'
									WHERE ID = '$aid'
								";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Action Cleared<br />";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Action Note','Xweb','$now')");
								} else {
									$resultMessage .= "Could NOT clear action $aid<br />" . $dbcnx->error . "<br />";
								}
							} else {
								$resultMessage .= "Could NOT add action note<br />" . $dbcnx->error . "<br />";
							}
						} else {
							$resultMessage .= "Could NOT update No Call Out<br />" . $dbcnx->error . "<br />";
						}
					}
				}

				elseif ($_POST['posted']) {
							
					$nocallouthours = $_POST['nocallouthours'];

					if ($nocallouthours) {
						$nocalloutfrom = date("Y-m-d H:i");
						$nocalloutto   = date("Y-m-d H:i", strtotime("+$nocallouthours hours"));

						if (!$aid) {
							$sql = "INSERT INTO `xweb_actions` VALUES (
								null,
								'$datetime',
								'$siteid',
								'$unitid',
								'$unitname',
								'',
								'',
								'',
								'7',
								'{$_SESSION['username']}',
								'0',
								'',
								'No Callout Set $nocalloutfrom to $nocalloutto',
								'',
								'',
								'',
								'',
								'No'
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Call Out Action Created<br />";
								$aid = $dbcnx->insert_id;
							} else {
								$resultMessage .= "Could NOT Create Action<br />" . $dbcnx->error . "<br />";
							}
						}

						if ($aid) {
							$sql = "UPDATE units SET nocallout = '$nocallouthours', nocalloutid = '$aid', nocalloutfrom = '$nocalloutfrom', nocalloutto = '$nocalloutto' WHERE ID = '$unitid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "No Call Out Unit Updated<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname No Call Out Set $nocallouthours Hours','$page','$now')");
								$datetime = date("Y-m-d H:i");
								// add to xweb_actions_notes
								$sql = "INSERT INTO xweb_actions_notes VALUES (
									null,
									'$aid',
									'$siteid',
									'$unitid',
									'',
									'$datetime',
									'',
									'No Call Out Set $nocalloutfrom to $nocalloutto',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Action Note Created<br />";
									$cleared  = date("Y-m-d H:i");
									$sql = "UPDATE xweb_actions SET 
										cleared='$cleared',
										clearedby='{$_SESSION['username']}'
										WHERE ID = '$aid'
									";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Action Cleared<br />";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Action Note','Xweb','$now')");
									} else {
										$resultMessage .= "Could NOT clear action $aid<br />" . $dbcnx->error . "<br />";
									}
								} else {
									$resultMessage .= "Could NOT add action note<br />" . $dbcnx->error . "<br />";
								}
							} else {
								$resultMessage .= "Could NOT update No Call Out<br />" . $dbcnx->error . "<br />";
							}
						}
					}
				}

				$sql = "SELECT u.nocallout, nocalloutfrom, nocalloutto
						FROM units AS u
						LEFT JOIN sites AS s ON u.siteid = s.ID
						WHERE u.ID = '$unitid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$nocallout     = $row['nocallout'];
					$nocalloutfrom = $row['nocalloutfrom'];
					$nocalloutto   = $row['nocalloutto'];

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

					print "<p align=\"center\"><b>No Call Out: "; if ($nocallout) { print "$nocallout Hours From $nocalloutfrom To $nocalloutto"; } else { print "Not Set"; } print "</b></p>\n";

					if ($nocallout) {
						print "<p align=\"center\"><a href=\"coldroome-xweb-nocallout.php?unitid=$unitid&amp;clear=1\"><b>Clear No Call Out</b></a></p>\n";
					}
	
					print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb-nocallout.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
					print "<input type=\"hidden\" name=\"aid\" value=\"$aid\" />\n";
					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "<td align=\"right\">No Call Out Hours From Now:</td>\n";
					print "<td align=\"left\">";
					print "<select name=\"nocallouthours\" class=\"element_small\">\n";
					print "<option value=\"0\""; if ($nocallout == 0) { print " selected=\"selected\""; } print ">Not Set</option>\n";
					print "<option value=\"1\""; if ($nocallout == 1) { print " selected=\"selected\""; } print ">1 Hour</option>\n";
					print "<option value=\"2\""; if ($nocallout == 2) { print " selected=\"selected\""; } print ">2 Hours</option>\n";
					print "<option value=\"3\""; if ($nocallout == 3) { print " selected=\"selected\""; } print ">3 Hours</option>\n";
					print "<option value=\"4\""; if ($nocallout == 4) { print " selected=\"selected\""; } print ">4 Hours</option>\n";
					print "<option value=\"5\""; if ($nocallout == 6) { print " selected=\"selected\""; } print ">6 Hours</option>\n";
					print "<option value=\"12\""; if ($nocallout == 12) { print " selected=\"selected\""; } print ">12 Hours</option>\n";
					print "<option value=\"24\""; if ($nocallout == 24) { print " selected=\"selected\""; } print ">24 Hours</option>\n";
					print "</select>\n";
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

					print "</form>\n";

				} else {
					$resultMessage .= "Could NOT get action<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without unitid.<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-callouts.php\">Return To Callouts</a> | ";
	print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Return To Unit</a> | ";
	print "<a href=\"coldrooms-xweb.php\">Return To Sites</a>";
	print "</p>";
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
