<?php
$page = "Filing";
$subpage = "Filing";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">FILING</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Item";
			} else {
				$heading = "New Item";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";

			if ($accessRights[$page] > 2) {
				$sitename = $_REQUEST['sitename'];
			} else {
				# get the users site
				$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$row = $uresult->fetch_assoc();
					$sitename = $row['site'];
				}
			}
			
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$dephnaref  = $_POST['dephnaref'];
				$datetime   = $_POST['datetime'];
				$details    = $_POST['details'];

				if (!$datetime) { $erdatetime = "Please select a transaction date"; $e++; }
				if (!$details) { $erdetails = "Please enter some details"; $e++; }

				if (!$e) {

					$details = addslashes($details);
					$now = time();
					
					if ($id) {
						$sql = "UPDATE filing SET
							details='$details',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage = "<b>Item Successfully Updated</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename DR$dephnaref','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Update Record</b><br>" . $dbcnx->error;
						}
					} else {
						
						list($date,$time) = explode(" ",$datetime);
						list($day,$month,$year) = explode("/",$date);

						$sql = "INSERT INTO filing VALUES (
							null,
							'$dephnaref',
							'$year',
							'$month',
							'$day',
							'$time',
							'$sitename',
							'$details',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage = "<b>Item Successfully Added</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename DR$dephnaref','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Add New Record To Database</b><br>" . $dbcnx->error;
						}
						
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM filing WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$dephnaref   = $row['dephnaref'];
					$year        = $row['year'];
					$month       = $row['month'];
					$day         = $row['day'];
					$time        = $row['time'];
					$sitename    = $row['sitename'];
					$details     = $row['details'];
					$lastupd     = $row['lastupd'];
					$updby       = $row['updby'];
					$lastupd     = date("D jS M Y \a\\t H:i",$lastupd);
					$datetime    = "$day/$month/$year $time";
				} else {
					$resultMessage = "<b>Supplier Not Found</b><br>" . $dbcnx->error;
				}
			}

			if ((!$resultMessage) OR ($e)) {
				if ($sitename) {
					if (!$datetime) {
						$datetime = date("d/m/Y H:i");
					}

					if (!$id) {
						# NEW > Auto generate next dephnaref
						$sql = "SELECT `dephnaref` FROM `filing` WHERE `sitename`='$sitename' ORDER BY `dephnaref` DESC LIMIT 0, 1";
						if ($srresult = $dbcnx->query($sql)) {
							if ($srresult->num_rows) {
								$srrow = $srresult->fetch_assoc();
								$dephnaref = $srrow['dephnaref'];
								$dephnaref++;	
							} else {
								$dephnaref = 1001;
							}
						}
					}
						
					print "<form name=\"addform\" method=\"post\" action=\"filing-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"dephnaref\" value=\"$dephnaref\" />\n";
					print "<input type=\"hidden\" name=\"sitename\" value=\"$sitename\" />\n";
					print "<input type=\"hidden\" name=\"datetime\" value=\"$datetime\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Item Number:</b></td>\n";
					print "	<td>DR$dephnaref</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Item Date:</b></td>\n";
					print "	<td>$datetime</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Details:</b><br />";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span><br />"; }
					print "<textarea name=\"details\" wrap=\"virtual\" rows=\"20\" class=\"element_box_large\">$details</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					if ($id) {
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					}
				} else {
					# get sitename
					print "<p align=\"center\"><b>Select Site</b></p>\n";
					print "<form name=\"form1\" method=\"post\" action=\"filing-addedit.php\">\n";
					print "<table class=\"filterBlock\" align=\"center\">\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Site</b></td>\n";
					print "		<td><select name=\"sitename\" class=\"element\">\n";
					$sql = "SELECT title FROM sites ORDER BY title";
					$sresult = $dbcnx->query($sql);
					if ($sresult->num_rows) {
						while ($srow = $sresult->fetch_assoc()) {
							print "		<option value=\"{$srow['title']}\""; if ($sitename == $srow['title']) { print " SELECTED"; } print ">{$srow['title']}</option>\n";
						}
					}
					print "		</select>";
					print "		</td>\n";
					print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "	</tr>\n";
					print "</table>\n";
					print "</form>\n";
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"filing-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
