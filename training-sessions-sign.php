<?php
$page = "Training";
$subpage = "Sessions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if (in_array( $_SESSION['user'], array(1,3,6))) {

	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_REQUEST['id'];
			if ($id) {
				print "<p align = \"center\" class=\"heading\">Sign Off Training Session</p>\n";
				
				$sql = "SELECT t.sid, t.cid, t.started, t.notes, s.title AS sitename, u.compname AS compname
						FROM training_sessions AS t
						LEFT JOIN sites AS s ON t.sid = s.ID
						LEFT JOIN users AS u ON t.cid = u.ID
						WHERE t.ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sid      = $row['sid'];
					$cid      = $row['cid'];
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$started  = $row['started'];
					$completed = $row['completed'];
					$notes    = $row['notes'];
					list($y,$m,$d,$t) = explode("-",$started);
					$started = "$d-$m-$y $t";
					if ($completed) {
						list($y,$m,$d,$t) = explode("-",$completed);
						$completed = "$d-$m-$y $t";
					}
					$notes    = $row['notes'];
					$notes = str_replace("<br>","\n",$notes);
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Client:</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Started:</b></td>\n";
					print "	<td>$started</td>\n";
					print "</tr>\n";
					
					print "</table>\n";
			
					if ($_POST['posted']) {

						$notes  = strip_tags($_POST['notes']);
						$url    = strip_tags($_POST['url']);
						if (!$url) { $erurl = "Enter the Dropbox URL"; $e++; }

						if (!$e) {
					
							$notes = trim($notes);
							$notes = addslashes($notes);
							$notes = str_replace('\n',"<br>",$notes);
							$url   = trim($url);
							$confirmed = date("Y-m-d-H:i");
							$now = time();
						
							# update trainees
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

							$sql = "UPDATE training_sessions SET
								confirmed='$confirmed',
								notes='$notes',
								url='$url',
								lastupd='$now',
								updby='{$_SESSION['username']}'
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "<b>Training Session Signed</b><br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Signed','$sitename $compname','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Records</b></span><br>" . $dbcnx->error . "<br>";
							}	
						}
					}
					
					if ((!$_POST['posted']) OR ($e)) {
						
						print "<form name=\"addform\" method=\"post\" action=\"training-sessions-sign.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
			
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Comments:</b></td>\n";
						print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"7\" class=\"element_box\">$notes</textarea>";
						if ($ernotes) { print "&nbsp;<span class=\"redtext\"> $ernotes</span>"; }
						print "</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Dropbox URL:</b></td>\n";
						print "	<td><input type=\"text\" name=\"url\" value=\"$url\" class=\"element\" />\n";
						if ($erurl) { print "&nbsp;<span class=\"redtext\">$erurl</span>"; }
						print "</td>\n";
						print "</tr>\n";
			
						print "</table>\n";
					
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

							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
							
							while ($crow  = $cresult->fetch_assoc()) {
								$clsid = $crow['ID'];
								$name  = $crow['name'];
								
								print "<tr>\n";
								print "	<td align=\"right\">$name</td>\n";
								print "	<td><input type=\"checkbox\" name=\"inc[$clsid]\"";
								if (in_array($clsid, $included)) { print " CHECKED"; }
								print "></td>\n";
								print "</tr>\n";
								
							}
							
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><p><b>Are You Sure?</b></p></td>\n";
							print "</tr>\n";
							
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Sign Off\" /></td>\n";
							print "</tr>\n";
		
							print "</table>\n";
							print "</form>\n";
								
						}
					}
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Training Session Not Found!</b></span><br>" . $dbcnx->error;
				}	
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>\n";
			}	
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
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
