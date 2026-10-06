<?php
$page = "Fire_Alarms";
$subpage = "Points";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Point";
			} else {
				$heading = "New Point";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {

				$site       = strip_tags($_POST['site']);
				$title      = strip_tags($_POST['title']);
				$details    = strip_tags($_POST['details']);
				$notes      = strip_tags($_POST['notes']);

				if (!$site) {
					$ersite = "Please select a site"; $e++;
				} else {
					list($siteid,$sitename) = explode("-",$site);
				}
				
				if (!$title) { $ertitle = "Please enter a location code"; $e++; }
				if (!$details) { $erdetails = "Please enter location description"; $e++; }
				if ($notes) { $notes = trim($notes); }

				if (!$e) {
					
					$title   = addslashes($title);
					$details = addslashes($details);
					$notes   = addslashes($notes);
					$notes = str_replace('\n',"<br>",$notes);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE fam_points SET
							siteid='$siteid',
							sitename='$sitename',
							title='$title',
							details='$details',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fire Alarm Point Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Point: $title','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO fam_points VALUES (
							null,
							'$siteid',
							'$sitename',
							'$title',
							'$details',
							'$notes',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fire Alarm Point Successfully Added</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Point: $title','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM fam_points WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$siteid     = $row['siteid'];
					$sitename   = $row['sitename'];
					$title      = $row['title'];
					$details    = $row['details'];
					$notes      = $row['notes'];
					$notes = str_replace("<br>","\n",$notes);
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Point Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"fam-points-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
		  			print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					while ($row = $sitesresult->fetch_assoc()) {
						$thissiteid   = $row['ID'];
						$thissitename = $row['title'];
						print "		<option value=\"$thissiteid-$thissitename\"";
						if ($thissiteid == $siteid) {
							print " SELECTED";
						}
						print ">$thissitename</option>\n";
					}
					print "	</select>";
					if ($ersite) { print "&nbsp;<span class=\"redtext\">$ersite</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location Code:</b></td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$title\" />\n";
					if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location Details:</b></td>\n";
					print "	<td><input name=\"details\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$details\" />\n";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fam-points-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
