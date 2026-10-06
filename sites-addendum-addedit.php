<?php
$page = "Sites";
$subpage = "Addendum";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align = \"center\" class=\"heading\">SITES H&amp;S ADDENDUM</p>\n";
	
	if ($accessRights[$page]) {
		
		include('sites-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Addendum";
			} else {
				$heading = "New Addendum";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				
				$adate    = strip_tags($_POST['adate']);
				$siteid   = strip_tags($_POST['siteid']);
				$atitle   = strip_tags($_POST['atitle']);
				$details  = strip_tags($_POST['details']);

				if (!$adate)   { $eradate = "Please enter a date"; $e++; }
				if (!$atitle)  { $eratitle = "Please enter a title"; $e++; }
				if (!$details) { $erdetails = "Please enter the details"; $e++; }

				if (!$e) {
					
					list($d,$m,$y) = explode("-",$adate);
					$adate = "$y-$m-$d";
					
					$atitle   = addslashes($atitle);
					$details = addslashes($details);
					$details = str_replace('\n',"<br>",$details);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE sites_addendum SET
							adate='$adate',
							siteid='$siteid',
							atitle='$atitle',
							details='$details',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Addendum Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Adden: $atitle','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO sites_addendum VALUES (
							null,
							'$adate',
							'$siteid',
							'$atitle',
							'$details',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Addendum Successfully Added</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Adden: $atitle','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM sites_addendum WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$adate      = $row['adate'];
					$siteid     = $row['siteid'];
					$atitle     = $row['atitle'];
					$details    = $row['details'];
					$details = str_replace("<br>","\n",$details);
					list($y,$m,$d) = explode("-",$adate);
					$adate = "$d-$m-$y";
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Addendum Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					
					if (!$adate) { $adate = date("d-m-Y"); }
						
					print "<form name=\"addform\" method=\"post\" action=\"sites-addendum-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Date:</td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"adate\" value=\"$adate\" class=\"element\" />\n";
					if ($eradate) { print "&nbsp;<span class=\"redtext\">$eradate</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
		  		print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td><select name=\"siteid\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					while ($row = $sitesresult->fetch_assoc()) {
						$thissiteid   = $row['ID'];
						$thissitename = $row['title'];
						print "		<option value=\"$thissiteid\""; if ($thissiteid == $siteid) { print " SELECTED"; } print ">$thissitename</option>\n";
					}
					print "	</select>";
					if ($ersiteid) { print "&nbsp;<span class=\"redtext\">$ersiteid</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Title:</td>\n";
					print "	<td><input type=\"text\" name=\"atitle\" value=\"$atitle\" class=\"element\" />\n";
					if ($eratitle) { print "&nbsp;<span class=\"redtext\">$eratitle</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\">Details:</td>";
					print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					# jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
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
		print "<p align=\"center\"><a href=\"sites-addendum-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
