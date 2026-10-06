<?php
$page = "Services";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SERVICES</p>\n";
	if ($accessRights[$page]) {
		
		$sid = $_REQUEST['id'];
		$rid = $_REQUEST['rid'];
		$lo = $_REQUEST['lo'];
		$y = $_REQUEST['y'];
		$m = $_REQUEST['m'];
		$qString ="lo=$lo&amp;y=$y&amp;m=$m";
		
		if ($sid) {
		
			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT * FROM services WHERE ID = '$sid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$suppref       = $row['suppref'];
					$servname      = $row['servname'];
					$servtype	   = $row['servtype'];
					$sitename      = $row['sitename'];
					
					if ($rid) {
						$action = "Edit Receipt";
					} else {
						$action = "Receive";
					}
					
					print "<p align=\"center\" class=\"larger\">$action</p>\n";
					print "<p align=\"center\" class=\"larger\">$sitename - $servname</p>\n";
					print "<p align=\"center\" class=\"larger\">$servtype</p>\n";

				} else {
					$resultMessage = "<b>Service Not Found</b><br>" . $dbcnx->error;
				}
			
				if ($_POST['posted']) {
					foreach ($_POST as $key => $value) {
						$_POST[$key] = strip_tags($_POST[$key]);
					}
					$received  = $_POST['received'];
					$qty       = $_POST['qty'];
					$fixedrate = $_POST['fixedrate'];
					$eachrate  = $_POST['eachrate'];
					$notes     = $_POST['notes'];
					
					if (!$qty) {
						$erqty = "Please give a qty"; $e++;
					} else {
						if (!is_numeric($qty)) { $erqty = "Qty must be numeric"; $e++; }
					}
					
					if ($fixedrate) {
						if (!is_numeric($fixedrate)) { $erfixedrate = "Price must be numeric"; $e++; }
					} elseif ($eachrate) {
						if (!is_numeric($eachrate)) { $ereachrate = "Price must be numeric"; $e++; }
					} else {
						$erfixedrate = "Enter fixed or unit price"; $e++;
					}

					if (!$e) {
						$notes = addslashes($notes);
						if ($fixedrate) {
							$nettotal = $fixedrate;
						} else {
							$nettotal = $qty * $eachrate;
						}
						$now = time();
					
						if ($rid) {
							$sql = "UPDATE services_done SET
								received='$received',
								qty='$qty',
								fixedrate='$fixedrate',
								eachrate='$eachrate',
								nettotal='$nettotal',
								notes='$notes',
								lastupd='$now',
								updby='{$_SESSION['username']}'
								WHERE ID='$rid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>Receipt Successfully Updated</b>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $servname','$page','$now')");
							} else {
								$resultMessage = "<b>Could NOT Update Record</b><br>" . $dbcnx->error;
							}
						} else {
							$sql = "INSERT INTO services_done VALUES (
								null,
								'$sid',
								'$suppref',
								'$received',
								'$qty',
								'$fixedrate',
								'$eachrate',
								'$nettotal',
								'$notes',
								'$now',
								'{$_SESSION['username']}'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>Receipt Successfully Added</b>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename $servname','$page','$now')");
							} else {
								$resultMessage = "<b>Could NOT Add New Record To Database</b><br>" . $dbcnx->error;
							}
						}
					}
				}

				elseif ($rid) {

	  				$sql = "SELECT * FROM services_done WHERE ID = '$rid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$received  = $row['received'];
						$qty       = $row['qty'];
						$fixedrate = $row['fixedrate'];
						$eachrate  = $row['eachrate'];
						$notes     = $row['notes'];
						$lastupd   = $row['lastupd'];
						$updby     = $row['updby'];
						$lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
						if ($fixedrate == 0.00) { $fixedrate = ""; }
						if ($eachrate == 0.00) { $eachrate = ""; }
					} else {
						$resultMessage = "<b>Receipt Not Found</b><br>" . $dbcnx->error;
					}
					
				} else {
					$sql = "SELECT qty, fixedrate, eachrate FROM services WHERE ID = '$sid'";
					$servresult = $dbcnx->query($sql);
					if ($servresult->num_rows) {
						$servrow = $servresult->fetch_assoc();
						$qty        = $servrow['qty'];
						$fixedrate  = $servrow['fixedrate'];
						$eachrate   = $servrow['eachrate'];
						if ($fixedrate == 0.00) { $fixedrate = ""; }
						if ($eachrate == 0.00) { $eachrate = ""; }
					} else {
						$resultMessage = "<b>Service Not Found</b><br>" . $dbcnx->error;
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
					
						print "<form name=\"addform\" method=\"post\" action=\"services-receive.php?$qString\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$sid\" />\n";
						print "<input type=\"hidden\" name=\"rid\" value=\"$rid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
						print "<tr>\n";
						print "	<td align=\"right\"><b>Date/Time Received</b></td>\n";
						print "	<td><input type=\"text\" name=\"received\" id=\"datetime\" value=\"{$row['received']}\" /></td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Qty</b></td>\n";
						print "	<td><input name=\"qty\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$qty\" />";
						if ($erqty) { print "&nbsp;<span class=\"redtext\">$erqty</span>"; }
						print "</td>\n";
						print "</tr>\n";
	
						if ($fixedrate) {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Fixed Price</b></td>\n";
							print "	<td><input name=\"fixedrate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$fixedrate\" />";
							if ($erfixedrate) { print "&nbsp;<span class=\"redtext\">$erfixedrate</span>"; }
							print "</td>\n";
							print "</tr>\n";
						} elseif ($eachrate) {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Price Each</b></td>\n";
							print "	<td><input name=\"eachrate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$eachrate\" />";
							if ($ereachrate) { print "&nbsp;<span class=\"redtext\">$ereachrate</span>"; }
							print "</td>\n";
							print "</tr>\n";
						}
	
						print "<tr>\n";
						print "	<td colspan=\"2\"><b>Notes</b><br />";
						print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
						print "</tr>\n";
	
						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
	
						print "</table>\n";
						print "</form>\n";
						
						if ($rid) {
							print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
						}
						
						// print jquery date/time picker
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$('#datetime').datetimepicker({ dateFormat: 'yy/mm/dd' });\n";
						print "	});\n";
						print "</script>\n";
					
					}
				}		
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without Service ID</b>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"services-view.php?id=$sid&amp;$qString\">View Service</a> | <a href=\"services-list.php?$qString\">Sevices List</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
