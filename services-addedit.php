<?php
$page = "Services";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$lo = $_REQUEST['lo'];
			$qString ="lo=$lo";

			print "<p align = \"center\" class=\"heading\">SERVICES</p>\n";
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Service";
			} else {
				$heading = "New Service";
			}
			print "<p align = \"center\" class=\"larger\">$heading</p>\n";
			
			if ($_POST['posted']) {
				$suppref        = $_POST['suppref'];
				$contractno		= $_POST['contractno'];
				$contractdate	= $_POST['contractdate'];
				$servname       = $_POST['servname'];
				$sitename       = $_POST['sitename'];
				$servtype       = $_POST['servtype'];
				$frequency      = $_POST['frequency'];
				$dayofweek      = $_POST['dayofweek'];
				$dayofmonth     = $_POST['dayofmonth'];
				$qty            = $_POST['qty'];
				$fixedrate      = $_POST['fixedrate'];
				$eachrate       = $_POST['eachrate'];
				$notes          = $_POST['notes'];
				
				if ($suppref == "Select") { $ersuppref = "Please select supplier"; $e++; }
				if (!$servname) { $erservname = "Please describe the service"; $e++; }
				if ($sitename == "Select") { $ersitename = "Please select a site"; $e++; }
				if ($frequency == "Select") {
					$erfrequency = "Please select frequency"; $e++;
				} else {
					if ($frequency == "Monthly") {
						$fqcy = 3;
						$dayofweek = "";
						if ($dayofmonth == "Select") { $erdayofmonth = "Please select day of month"; $e++; }
					} elseif ($frequency == "Weekly") {
						$fqcy = 2;
						$dayofmonth = "";
						// check if day of week array is empty
						if (empty($dayofweek)) { $erdayofweek = "Please select day of week"; $e++; }
					} elseif ($frequency == "On Demand") {
						$fqcy = 4;
						$dayofmonth = "";
						$dayofweek = "";
					} else {
						$fqcy = 4;
						$dayofweek = "";
						$dayofmonth = "";
					}
				}
				
				if (!$qty) {
					$erqty = "Please give a qty"; $e++;
				} else {
					if (!is_numeric($qty)) { $erqty = "Qty must be numeric"; $e++; }
				}
				
				if ($fixedrate) {
					if (!is_numeric($fixedrate)) { $erfixedrate = "Price must be numeric"; $e++; }
					$eachrate = "0";
				} elseif ($eachrate) {
					$fixedrate = "0";
					if (!is_numeric($eachrate)) { $ereachrate = "Price must be numeric"; $e++; }
				} else {
					$erfixedrate = "Enter fixed or unit price"; $e++;
				}

				if (!$e) {
					$dayofweek		= implode(",",$dayofweek);
					$contractno		= addslashes($contractno);
					$servname		= addslashes($servname);
					$servtype		= addslashes($servtype);
					$sitename		= addslashes($sitename);
					$notes			= addslashes($notes);
					$now = time();
					
					if ($id) {
						$sql = "UPDATE services SET
							suppref='$suppref',
							contractno='$contractno',
							contractdate='$contractdate',
							servname='$servname',
							servtype='$servtype',
							sitename='$sitename',
							fqcy='$fqcy',
							frequency='$frequency',
							dayofweek='$dayofweek',
							dayofmonth='$dayofmonth',
							qty='$qty',
							fixedrate='$fixedrate',
							eachrate='$eachrate',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Service Updated</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $servname','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Update Record</b><br>" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO services VALUES (
							null,
							'$suppref',
							'$contractno',
							'$contractdate',
							'$servname',
							'$servtype',
							'$sitename',
							'$fqcy',
							'$frequency',
							'$dayofweek',
							'$dayofmonth',
							'$qty',
							'$fixedrate',
							'$eachrate',
							'$notes',
							'$now',
							'{$_SESSION['username']}'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Service Added</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename $servname','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Add New Record To Database</b><br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM services WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$suppref       = $row['suppref'];
					$contractno	   = $row['contractno'];
					$contractdate  = $row['contractdate'];
					$servname      = $row['servname'];
					$servtype      = $row['servtype'];
					$sitename      = $row['sitename'];
					$frequency     = $row['frequency'];
					$dayofweek     = $row['dayofweek'];
					$dayofmonth    = $row['dayofmonth'];
					$qty           = $row['qty'];
					$fixedrate     = $row['fixedrate'];
					$eachrate      = $row['eachrate'];
					$notes         = $row['notes'];
					$lastupd       = $row['lastupd'];
					$updby         = $row['updby'];
					$lastupd       = date("D jS M Y \a\\t H:i",$lastupd);
					if ($fixedrate == 0.00) { $fixedrate = ""; }
					if ($eachrate == 0.00) { $eachrate = ""; }
					$dayofweek = explode(",",$dayofweek);
				} else {
					$resultMessage = "<b>Service Not Found</b><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					
					print "<form name=\"addform\" method=\"post\" action=\"services-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Service Name</b></td>\n";
					print "	<td><input name=\"servname\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$servname\" />";
					if ($erservname) { print "<br><span class=\"redtext\">$erservname</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					// service type as servtype text field
					print "<tr>\n";
					print "	<td align=\"right\"><b>Service Type</b></td>\n";
					print "	<td><input name=\"servtype\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$servtype\" />";
					if ($erservtype) { print "<br><span class=\"redtext\">$erservtype</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					// get suppliers
					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier</b></td>\n";
					$sql = "SELECT suppref, suppname FROM suppliers ORDER BY suppname";
					$suppresult = $dbcnx->query($sql);
					if ($suppresult->num_rows) {
						print "	<td><select name=\"suppref\" class=\"element\">\n";
						print "		<option value=\"Select\">Select</option>\n";
						while ($supprow = $suppresult->fetch_assoc()) {
							$thissuppref  = $supprow['suppref'];
							$thissuppname = $supprow['suppname'];
							print "		<option value=\"$thissuppref\"";
							if ($thissuppref == $suppref) {
								print " SELECTED";
							}
							print ">$thissuppname</option>\n";
						}
						print "	</select>";
						if ($ersuppref) { print "<br><span class=\"redtext\">$ersuppref</span>"; }
						print "</td>\n";
					} else {
						print "	<td>Can't get suppliers!</td>\n";
					}
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Suppliers Ref</b></td>\n";
					print "	<td><input name=\"contractno\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$contractno\" />";
					if ($ercontractno) { print "<br><span class=\"redtext\">$ercontractno</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Contract Date</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"contractdate\" value=\"$contractdate\" class=\"element\" />";
					if ($ercontractdate) { print "<br><span class=\"redtext\">$ercontractdate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					// get sites
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site</b></td>\n";
					$sql = "SELECT title FROM sites ORDER BY title";
					$siteresult = $dbcnx->query($sql);
					if ($siteresult->num_rows) {
						print "	<td><select name=\"sitename\" class=\"element\">\n";
						print "		<option value=\"Select\">Select</option>\n";
						while ($siterow = $siteresult->fetch_assoc()) {
							$thistitle  = $siterow['title'];
							print "		<option value=\"$thistitle\"";
							if ($thistitle == $sitename) {
								print " SELECTED";
							}
							print ">$thistitle</option>\n";
						}
						print "	</select>";
						if ($ersitename) { print "<br><span class=\"redtext\">$ersitename</span>"; }
						print "</td>\n";
					} else {
						print "	<td>Can't get sites!</td>\n";
					}
					print "</tr>\n";
					
					print "<tr>\n";
  	 				print "	<td align=\"right\"><b>Frequency</b></td>\n";
  	 				print "	<td>";
  	 				print "<table>\n";
  	 				print "<tr><td>Weekly</td><td><input name=\"frequency\" type=\"radio\" value=\"Weekly\""; if ($frequency == "Weekly") { print " CHECKED"; } print " /></td></tr>";
  	 				print "<tr><td>Monthly</td><td><input name=\"frequency\" type=\"radio\" value=\"Monthly\""; if ($frequency == "Monthly") { print " CHECKED"; } print " /></td></tr>";
  	 				print "<tr><td>On Demand</td><td><input name=\"frequency\" type=\"radio\" value=\"On Demand\""; if ($frequency == "On Demand") { print " CHECKED"; } print " /></td></tr>";
  	 				print "</table>\n";
  	 				print "<td>\n";
  	 				print "</tr>\n";
  	 				
  	 				print "<tbody id=\"slideWeek\">\n";
  	 				// day of week, 1 = Monday, 7 = Sunday checkboxes
					print "<tr>\n";
					print "	<td align=\"right\"><b>Day of Week</b></td>\n";
					print "	<td>";
					$days = array("na","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
					for ($n = 1; $n < 8; $n++) {
						print "<input name=\"dayofweek[]\" type=\"checkbox\" value=\"$n\"";
						if ($dayofweek) {
							if (in_array($n,$dayofweek)) {
								print " CHECKED";
							}
						}
						print " /> $days[$n]<br>";
					}
					if ($erdayofweek) { print "<br><span class=\"redtext\">$erdayofweek</span>"; }
					print "</td>\n";
					print "</tr>\n";
					print "</tbody>\n";
					
					print "<tbody id=\"slideMonth\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Day of Month</b></td>\n";
					print "	<td><select name=\"dayofmonth\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					for ($n = 1; $n < 29; $n++) {
						$o = sprintf("%02s",$n);
						print "		<option value=\"$o\""; if ($dayofmonth == $o) { print " SELECTED"; } print ">$o</option>\n";
					}
					print "	</select>";
					if ($erdayofmonth) { print "<br><span class=\"redtext\">$erdayofmonth</span>"; }
					print "</td>\n";
					print "</tr>\n";
					print "</tbody>\n";
						
					print "<tr>\n";
					print "	<td align=\"right\"><b>Qty</b></td>\n";
					print "	<td><input name=\"qty\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$qty\" />";
					if ($erqty) { print "<br><span class=\"redtext\">$erqty</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Fixed Price</b></td>\n";
					print "	<td><input name=\"fixedrate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$fixedrate\" />";
					if ($erfixedrate) { print "<br><span class=\"redtext\">$erfixedrate</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><b>OR</b></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Price Each</b></td>\n";
					print "	<td><input name=\"eachrate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$eachrate\" />";
					if ($ereachrate) { print "<br><span class=\"redtext\">$ereachrate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Notes</b><br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					if ($id) {
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					}
					
					// print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";
					
					print "<script type=\"text/javascript\">\n";
					print " $( document ).ready(function() {\n";
					if ($frequency == "Daily") {
						print "    $( \"#slideWeek\" ).hide('fast');\n";
						print "    $( \"#slideMonth\" ).hide('fast');\n";
					} elseif ($frequency == "Weekly") {
						print "    $( \"#slideWeek\" ).show('fast');\n";
						print "    $( \"#slideMonth\" ).hide('fast');\n";
					} elseif ($frequency == "Monthly") {
						print "    $( \"#slideWeek\" ).hide('fast');\n";
						print "    $( \"#slideMonth\" ).show('fast');\n";
					}  elseif ($frequency == "On Demand") {
						print "    $( \"#slideWeek\" ).hide('fast');\n";
						print "    $( \"#slideMonth\" ).hide('fast');\n";
					}
					print "    $('input[name=\"frequency\"]').click(function(){\n";
					print "        if($(this).attr(\"value\")==\"Daily\") {\n";
					print "            $( \"#slideWeek\" ).hide('fast');\n";
					print "            $( \"#slideMonth\" ).hide('fast');\n";
					print "        }\n";
					print "        if($(this).attr(\"value\")==\"Weekly\") {\n";
					print "            $( \"#slideWeek\" ).show('fast');\n";
					print "            $( \"#slideMonth\" ).hide('fast');\n";
					print "        }\n";
					print "        if($(this).attr(\"value\")==\"Monthly\") {\n";
					print "            $( \"#slideWeek\" ).hide('fast');\n";
					print "            $( \"#slideMonth\" ).show('fast');\n";
					print "        }\n";
					print "        if($(this).attr(\"value\")==\"On Demand\") {\n";
					print "            $( \"#slideWeek\" ).hide('fast');\n";
					print "            $( \"#slideMonth\" ).hide('fast');\n";
					print "        }\n";
					print "    });\n";
					print " });\n";
					print "</script>\n";
					
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($id) {
			print "<p align=\"center\"><a href=\"services-view.php?id=$id&amp;$qString\">View Service</a> | <a href=\"services-list.php?$qString\">Return</a></p>\n";
		} else {
			print "<p align=\"center\"><a href=\"services-list.php?$qString\">Return</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
