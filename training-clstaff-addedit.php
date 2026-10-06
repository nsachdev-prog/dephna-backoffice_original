<?php
$page = "Training";
$subpage = "Key Fobs";
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
			
			# get filters
			if ($_REQUEST['cl']) { $cl = $_REQUEST['cl']; }
			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
			$qString = "cl=$cl&lo=$lo";
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Key Fob";
			} else {
				$heading = "New Key Fob";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				
				$sid    = strip_tags($_POST['sid']);
				$cid    = strip_tags($_POST['cid']);
				$name   = strip_tags($_POST['name']);
				$fobnum = strip_tags($_POST['fobnum']);
				$phone  = strip_tags($_POST['phone']);
				$photo  = strip_tags($_POST['photo']);
				$notes  = strip_tags($_POST['notes']);
				
				if ($sid == "--")   { $ersid   = "Select a Site &amp; Client"; $e++; }
				if (!$name)   { $ername   = "Enter a Name"; $e++; }
				if (!$fobnum) { $erfobnum = "Enter a Fob Number"; $e++; }
				if (!$phone)  { $erphone  = "Enter a Phone Number"; $e++; }

				if (!$e) {
					
					$notes = trim($notes);
					$notes = addslashes($notes);
					$notes = str_replace('\n',"<br>",$notes);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE training_clstaff SET
							sid='$sid',
							cid='$cid',
							name='$name',
							fobnum='$fobnum',
							phone='$phone',
							photo='$photo',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fob Number Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Fob: $fobnum','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO training_clstaff VALUES (
							null,
							'$sid',
							'$cid',
							'$name',
							'$fobnum',
							'$phone',
							'$photo',
							'$notes',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$id = $dbcnx->insert_id;
							$resultMessage .= "<b>Fob Number Successfully Added</b><br>";
							$another = "cl=$cid&lo=$sid";
							
							# See if there is a already a active session started for this client
							$sql = "SELECT i.ID, i.tsid
											FROM training_sessions_included AS i
											RIGHT JOIN training_sessions AS s ON i.tsid = s.ID
											WHERE s.cid = '$cid' AND !s.completed
											LIMIT 0,1
							";
							$iresult = $dbcnx->query($sql);
							if ($iresult->num_rows) {
								$irow = $iresult->fetch_assoc();
								$tsid = $irow['tsid'];
								
								# add new key fob
								$sql = "INSERT INTO training_sessions_included VALUES (null, '$tsid', '$id', '')";
								if ($nresult = $dbcnx->query($sql)) {
									$resultMessage .= "<b>Fob Added to Existing Training Session</b><br>";
								} else {
									$resultMessage .= "<b>Fob Could NOT be Added to Existing Training Session</b><br>";
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
								if ($tresult = $dbcnx->query($sql)) {
									$tsid = $dbcnx->insert_id;
									$resultMessage .= "<b>Active Training Session Started</b><br>";
									
									# add new key fob
									$sql = "INSERT INTO training_sessions_included VALUES (null, '$tsid', '$id', '')";
									if ($nresult = $dbcnx->query($sql)) {
										$resultMessage .= "<b>Fob Added to New Training Session</b><br>";
									} else {
										$resultMessage .= "<b>Fob Could NOT be Added to New Training Session</b><br>";
									}
										
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT start new training session</b></span><br>" . $dbcnx->error . "<br>";
								}
							}
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Fob: $fobnum','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  			$sql = "SELECT * FROM training_clstaff WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sid  = $row['sid'];
					$cid  = $row['cid'];
					$name = $row['name'];
					$fobnum = $row['fobnum'];
					$phone = $row['phone'];
					$photo = $row['photo'];
					$notes    = $row['notes'];
					$notes = str_replace("<br>","\n",$notes);
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fob Number Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$sid = $lo;
				$cid = $cl;
			}

			if (!$resultMessage) {
				if ((!$_POST['posted']) OR ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"training-clstaff-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					$sql = "SELECT ID, title, ipaddress FROM sites WHERE training = 'on' ORDER BY title";
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
						$sql = "SELECT ID, compname FROM users WHERE location = '{$site['ipaddress']}' ORDER BY compname";
						$uresult = $dbcnx->query($sql);
						if ($uresult->num_rows) {
							while ($urow = $uresult->fetch_assoc()) {
								$thisuserid   = $urow['ID'];
								$thiscompname = $urow['compname'];
								print "		<option value=\"$thisuserid\" class=\"{$site['siteid']}\"";
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
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Name:</b></td>\n";
					print "	<td><input type=\"text\" name=\"name\" value=\"$name\" class=\"element\" />\n";
					if ($ername) { print "&nbsp;<span class=\"redtext\">$ername</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Fob Number:</b></td>\n";
					print "	<td><input type=\"text\" name=\"fobnum\" value=\"$fobnum\" class=\"element\" />\n";
					if ($erfobnum) { print "&nbsp;<span class=\"redtext\">$erfobnum</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Phone Number:</b></td>\n";
					print "	<td><input type=\"text\" name=\"phone\" value=\"$phone\" class=\"element\" />\n";
					if ($erphone) { print "&nbsp;<span class=\"redtext\">$erphone</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Photo Filed?</b>:</td>\n";
					print "	<td><input type=\"checkbox\" name=\"photo\""; if ($photo) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>\n";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"7\" class=\"element_box\">$notes</textarea>";
					if ($ernotes) { print "&nbsp;<span class=\"redtext\"> $ernotes</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					print "<script src=\"jquery.chained.min.js\"></script>\n";
					print "<script type=\"text/javascript\" charset=\"utf-8\">\n";
					print "$(function() {\n";
				  print "  $(\"#client\").chained(\"#site\");\n";
				  print "});\n";
  				print "</script>\n";
					
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($another) {
			print "<p align=\"center\"><a href=\"training-clstaff-addedit.php?$another\">Add Another</a></p>\n";
		}
		print "<p align=\"center\"><a href=\"training-clstaff-list.php?$qString\">Return</a></p>\n";
	
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}	
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
