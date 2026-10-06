<?php
$page = "Training";
$subpage = "Sessions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Training Session";
			} else {
				$heading = "New Training Session";
			}
			print "<p align=\"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				
				$sid    = strip_tags($_POST['sid']);
				$client = strip_tags($_POST['cid']);
				
				if ($sid == "--")   { $ersid   = "Select a Site &amp; Client"; $e++; }
				if ($client) {
					list($cid,$custref) = explode("-",$client);
				}

				if (!$e) {
					
					$now = time();
								
					if ($id) {
						$sql = "UPDATE training_sessions SET
							sid='$sid',
							cid='$cid',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Session Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Session DP$custref','$page','$now')");
							$continue = 1;
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$started = date("Y-m-d-H:i");
						$sql = "INSERT INTO training_sessions VALUES (
							null,
							'$sid',
							'$cid',
							'$started',
							'',
							'',
							'',
							'',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$id = $dbcnx->insert_id;
							$resultMessage .= "<b>Session Successfully Added</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Session DP$custref','$page','$now')");
							$continue = 1;
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
				if ($resultMessage) {
					print "<p align=\"center\">$resultMessage</p>\n";
				}
			}

			elseif ($id) {
  			$sql = "SELECT * FROM training_sessions WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sid  = $row['sid'];
					$cid  = $row['cid'];
					$continue = 1;
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Training Session Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$sid = $lo;
				$cid = $cl;
			}
		
			print "<form name=\"addform\" method=\"post\" action=\"training-sessions-addedit.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
			
			$sql = "SELECT ID, title, ipaddress FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);
			if ($sitesresult->num_rows) {
	  		print "<tr>\n";
				print "	<td align=\"right\"><b>Site:</b></td>\n";
				print "	<td><select name=\"sid\" id=\"site\" class=\"element\">\n";
				print "		<option>--</option>\n";
				while ($row = $sitesresult->fetch_assoc()) {
					$thissiteid    = $row['ID'];
					$thissitename  = $row['title'];
					$thisipaddress = $row['ipaddress'];
					$siteids[] = array ('siteid' => $thissiteid, 'ipaddress' => $thisipaddress);
					print "		<option value=\"$thissiteid\" class=\"$thissiteid\"";
					if ($thissiteid == $sid) {
						print " SELECTED";
					}
					print ">$thissitename</option>\n";
				}
				print "	</select>";
				if ($ersid) { print "&nbsp;<span class=\"redtext\">$ersid</span>"; }
				print "</td>\n";
				print "</tr>\n";
			}
			
			print "<tr>\n";
			print "	<td align=\"right\"><b>Client:</b></td>\n";
			print "	<td><select name=\"cid\" id=\"client\" class=\"element\">\n";
			print "		<option>--</option>\n";
			foreach ($siteids AS $site) {
				$sql = "SELECT ID, compname, custref FROM users WHERE location = '{$site['ipaddress']}' ORDER BY compname";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					while ($urow = $uresult->fetch_assoc()) {
						$thisuserid   = $urow['ID'];
						$thiscompname = $urow['compname'];
						$thiscustref  = $urow['custref'];
						print "		<option value=\"$thisuserid-$thiscustref\" class=\"{$site['siteid']}\"";
						if ($thisuserid == $cid) {
							print " SELECTED";
						}
						print ">$thiscompname</option>\n";
					}
				}
			}
			print "	</select>";
			if ($ersid) { print "&nbsp;<span class=\"redtext\">$ersid</span>"; }
			print "</td>\n";
			print "</tr>\n";

			if (!$id) {
				print "<tr>\n";
				print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
				print "</tr>\n";
			}

			print "</table>\n";
			print "</form>\n";
			
			print "<script src=\"jquery.chained.min.js\"></script>\n";
			print "<script type=\"text/javascript\" charset=\"utf-8\">\n";
			print "$(function() {\n";
		  print "  $(\"#client\").chained(\"#site\");\n";
		  print "});\n";
			print "</script>\n";
			
			if ($_POST['include']) {
				# delete existing
				$sql = "DELETE FROM training_sessions_included WHERE tsid = '$id'";
				$dresult = $dbcnx->query($sql);
				# add new
				$incs = $_POST['inc'];
				if ($incs) {
					foreach ($incs AS $inc => $value) {
						if ($value == "on") {
							$sql = "INSERT INTO training_sessions_included VALUES (null, '$id', '$inc', '')";
							$nresult = $dbcnx->query($sql);
						}
					}
				}
				print "<p align=\"center\"><b>Trainee List Updated</b></p>\n";
			}
			
			if ($continue) {
				print "<p align=\"center\" class=\"heading\">Trainees</p>\n";
				
				# get all fob holders at this company
				$sql = "SELECT ID, name FROM training_clstaff WHERE cid='$cid'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					
					# get existing included in this session
					$included = array();
					$sql = "SELECT ID, clsid FROM training_sessions_included WHERE tsid='$id'";
					$iresult = $dbcnx->query($sql);
					if ($iresult->num_rows) {
						while ($irow = $iresult->fetch_assoc()) {
							$included[] = $irow['clsid']; # array of client staff already included
						}
					}
					
					print "<form name=\"incform\" method=\"post\" action=\"training-sessions-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"include\" value=\"1\">\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td><b>Name</b></td>\n";
					print "	<td><b>Last Trained</b></td>\n";
					print "	<td><b>Include</b></td>\n";
					print "</tr>\n";
					
					while ($crow  = $cresult->fetch_assoc()) {
						$clsid = $crow['ID'];
						$name  = $crow['name'];
						
						# get last training session if any
						$sql = "SELECT t.completed
										FROM training_sessions t
										LEFT JOIN training_sessions_included i ON t.ID = i.tsid
										WHERE i.clsid='$clsid' AND t.completed AND t.confirmed
										ORDER BY t.completed DESC
										LIMIT 0,1
						";
						$compresult = $dbcnx->query($sql);
						if ($compresult->num_rows) {
							$comprow = $compresult->fetch_assoc();
							$lasttrained = $comprow['completed'];
							list($y,$m,$d,$t) = explode("-",$lasttrained);
							$lasttrained = "$d-$m-$y";
						} else {
							$lasttrained = "Not Trained";
						}
						
						print "<tr>\n";
						print "	<td>$name</td>\n";
						print "	<td>$lasttrained</td>\n";
						print "	<td><input type=\"checkbox\" name=\"inc[$clsid]\"";
						if (in_array($clsid, $included)) { print " CHECKED"; }
						print "></td>\n";
						print "</tr>\n";
						
					}
					
					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
				
			}	
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		
		print "<p align=\"center\"><a href=\"training-sessions-list.php\">Return</a></p>\n";
	
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}	
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
