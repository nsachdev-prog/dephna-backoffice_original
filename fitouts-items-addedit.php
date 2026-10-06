<?php
$page = "Fitouts";
$subpage = "Items";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update";
			} else {
				$heading = "New";
			}
			print "<p align=\"center\" class=\"larger\">$heading Item</p>\n";

			// Types: Electricity, Gas, Water, Canopy, Partition, Other
			$types = array('Electricity','Gas','Water','Canopy','Partition','Other');
			
			if ($_POST['posted']) {

                $title   = strip_tags($_POST['title']);
                $details = strip_tags($_POST['details']);
				$type    = strip_tags($_POST['type']);
                $power   = strip_tags($_POST['power']);
				$powerg  = strip_tags($_POST['powerg']);
                $amount  = strip_tags($_POST['amount']);
				$notes   = strip_tags($_POST['notes']);
				$upgrade = strip_tags($_POST['upgrade']);

				if (!$title)   { $ertitle   = "Please enter a title"; $e++; }
                if (!$details) { $erdetails = "Please enter description"; $e++; }
                if (!$power AND !$powerg)   { $erpower   = "Please enter power consumption"; $e++; }
                elseif ($power AND !is_numeric($power)) { $erpower = "Not Numeric"; $e++; }
				elseif ($powerg AND !is_numeric($powerg)) { $erpowerg = "Not Numeric"; $e++; }
                if (!$amount)  { $eramount = "Please enter the cost"; $e++; }
                elseif (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
				
				if (!$e) {
					
					$title   = addslashes($title);
                    $details = addslashes($details);
                    $details = str_replace('\n',"<br>",$details);
					$notes   = addslashes($notes);
					$notes   = str_replace('\n',"<br>",$notes);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE fitouts_items SET
							title    = '$title',
							details  = '$details',
							`type`     = '$type',
							power    = '$power',
							powerg   = '$powerg',
							upgrade  = '$upgrade',
							amount   = '$amount',
							notes    = '$notes',
							lastupd  = '$now',
							updby    = '{$_SESSION['username']}'
							WHERE ID = '$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fitout Item Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO fitouts_items VALUES (
							null,
							'$title',
							'$details',
							'$type',
							'$power',
							'$powerg',
							'$upgrade',
							'$amount',
							'$notes',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fitout Item Successfully Added</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM fitouts_items WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title   = $row['title'];
					$details = $row['details'];
					$type    = $row['type'];
					$power   = $row['power'];
					$powerg  = $row['powerg'];
					$upgrade = $row['upgrade'];
					$amount  = $row['amount'];
					$notes   = $row['notes'];
                    $notes   = str_replace("<br>","\n",$notes);
                    $details = str_replace("<br>","\n",$details);
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fitout Item Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"fitouts-items-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Item Name:</b></td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$title\" />\n";
					if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Description:</b></td>";
					print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea>\n";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
					print "</td>\n";
                    print "</tr>\n";

					// type select 
					print "<tr>\n";
					print "	<td align=\"right\"><b>Type:</b></td>\n";
					print "	<td><select name=\"type\" class=\"element\">\n";
					foreach ($types as $thistype) {
						print "		<option value=\"$thistype\""; if ($thistype == $type) { print " SELECTED"; } print ">$thistype</option>\n";
					}
					print "	</select></td>\n";
					print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>Power Electricity:</b></td>\n";
					print "	<td><input name=\"power\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$power\" /> Amps\n";
					if ($erpower) { print "&nbsp;<span class=\"redtext\">$erpower</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Power Gas:</b></td>\n";
					print "	<td><input name=\"powerg\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$powerg\" /> Kw\n";
					if ($erpowerg) { print "&nbsp;<span class=\"redtext\">$erpowerg</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Power Upgrade?:</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"upgrade\""; if ($upgrade) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";
                    
                    print "<tr>\n";
					print "	<td align=\"right\"><b>Cost:</b></td>\n";
					print "	<td><input name=\"amount\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$amount\" />\n";
					if ($eramount) { print "&nbsp;<span class=\"redtext\">$eramount</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-items-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
