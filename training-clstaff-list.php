<?php
$page = "Training";
$subpage = "Key Fobs";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">TRAINING</p>\n";
		include('training-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		print "<p align = \"center\" class=\"heading\">Key Fobs</p>\n";
		
		# get filters
		if (($_REQUEST['lo']) AND ($_REQUEST['lo'] != "All")) {
			$lo = $_REQUEST['lo'];
			$search[] = "t.sid = '$lo'";
		}
		if ($_REQUEST['cl']) {
			$cl = $_REQUEST['cl'];
			$search[] = "t.cid = '$cl'";
		}
		if ($search) { $search = "WHERE ". implode(" AND ",$search); }
		$qString = "lo=$lo&cl=$cl";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<form name=\"filterform\" method=\"post\" action=\"training-clstaff-list.php\">\n";
			print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
			print "<tr>\n";
			
			$sql = "SELECT ID, title, ipaddress FROM sites WHERE training = 'on' ORDER BY title";
			$sitesresult = $dbcnx->query($sql);
			if ($sitesresult->num_rows) {
				print "	<td align=\"right\"><b>Site:</b></td>\n";
				print "	<td><select name=\"lo\" id=\"site\" class=\"element_medium\">\n";
				print "	<option>All</option>\n";
				while ($row = $sitesresult->fetch_assoc()) {
					$thissiteid    = $row['ID'];
					$thissitename  = $row['title'];
					$thisipaddress = $row['ipaddress'];
					$siteids[] = array ('siteid' => $thissiteid, 'ipaddress' => $thisipaddress);
					print "		<option value=\"$thissiteid\" class=\"$thissiteid\"";
					if ($thissiteid == $lo) {
						print " SELECTED";
					}
					print ">$thissitename</option>\n";
				}
				print "	</select>";
				if ($ersid) { print "&nbsp;<span class=\"redtext\">$ersid</span>"; }
				print "</td>\n";
			}

			print "	<td align=\"right\"><b>Client:</b></td>\n";
			print "	<td><select name=\"cl\" id=\"client\" class=\"element_medium\">\n";
			print "		<option>All</option>\n";
			foreach ($siteids AS $site) {
				$sql = "SELECT ID, compname FROM users WHERE location = '{$site['ipaddress']}' ORDER BY compname";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					while ($urow = $uresult->fetch_assoc()) {
						$thisuserid   = $urow['ID'];
						$thiscompname = $urow['compname'];
						print "		<option value=\"$thisuserid\" class=\"{$site['siteid']}\"";
						if ($thisuserid == $cl) {
							print " SELECTED";
						}
						print ">$thiscompname</option>\n";
					}
				}
			}
			print "	</select>";
			if ($ersid) { print "&nbsp;<span class=\"redtext\">$ersid</span>"; }
			print "</td>\n";
			
			print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
			
			print "<p style=\"margin:2% 5%;\"><strong>Guide</strong><br /><i>When new company personnel are added a new training session will be generated. Go to Sessions and choose the corresponding Pending Session and select the trainee(s) who will be attending to start training.</i></p>\n";
			
			# get some records
			$sql = "SELECT t.ID, t.sid, t.cid, t.name, t.fobnum, s.title AS sitename, u.compname AS compname
					FROM training_clstaff AS t
					LEFT JOIN sites AS s ON t.sid = s.ID
					LEFT JOIN users AS u ON t.cid = u.ID
					$search
					ORDER BY sitename, compname, t.name";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Key Fob</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Trained</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$name     = $row['name'];
					$fobnum   = $row['fobnum'];
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 17) . "..."; }
					
					# last trained
					$sql = "SELECT t.completed
							FROM training_sessions AS t
							LEFT JOIN training_sessions_included AS i ON t.ID = i.tsid
							WHERE t.completed AND i.clsid = '$id'
							ORDER BY t.completed DESC
							LIMIT 0,1
					";
					$iresult = $dbcnx->query($sql);
					if ($iresult->num_rows) {
						$irow = $iresult->fetch_assoc();
						$completed = $irow['completed'];
						list($y,$m,$d) = explode("-",$completed);
						$completed = "$d-$m-$y";
					} else {
						$completed = "&nbsp;";
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$name</td>\n";
					print "	<td>$fobnum</td>\n";
					print "	<td>$completed</td>\n";
					print "<td align=\"center\"><a href=\"training-clstaff-view.php?id=$id&$qString\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "<td align=\"center\"><a href=\"training-clstaff-addedit.php?id=$id&$qString\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "<td align=\"center\"><a href=\"training-clstaff-delete.php?id=$id&$qString\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\"><b>No Key Fobs Found For This Client</b></p>\n";
			}
			
			print "<script src=\"jscripts/jquery.chained.min.js\"></script>\n";
			print "<script type=\"text/javascript\" charset=\"utf-8\">\n";
			print "$(function() {\n";
		  	print "  $(\"#client\").chained(\"#site\");\n";
		  	print "});\n";
			print "</script>\n";
			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"training-clstaff-addedit.php?$qString\">Create New Key Fob</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
