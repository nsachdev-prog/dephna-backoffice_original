<?php
$page = "Fitouts";
$subpage = "Checklist";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

        print "<p align = \"center\" class=\"heading\">Checklist</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Item";
			} else {
				$heading = "New Item";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				
				$commodity = strip_tags(trim($_POST['commodity']));
				$details   = strip_tags(trim($_POST['details']));
				
				if (!$commodity)   { $ercommodity   = "Select a commodity"; $e++; }
				if (!$details) { $erdetails = "Enter details"; $e++; }

				if (!$e) {
					$commodity = addslashes($commodity);
					$details   = addslashes($details);
					$details   = str_replace('\n',"<br>",$details);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE fitouts_checklist SET
							commodity='$commodity',
							details='$details',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Item Updated<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Item: $commodity','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\">Could NOT Update Record</span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						# get next pos
						$sql = "SELECT pos FROM fitouts_checklist WHERE commodity = '$commodity' ORDER BY pos DESC LIMIT 0,1";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$pos = $row['pos'];
							$pos++;
						} else {
							$pos = 1;
						}
						$sql = "INSERT INTO fitouts_checklist VALUES (
							null,
							'$commodity',
							'$details',
							'$pos',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Item Added<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Item: $commodity','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\">Could NOT add record to database</span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  			$sql = "SELECT * FROM fitouts_checklist WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$commodity = $row['commodity'];
					$details   = $row['details'];
					$details = str_replace("<br>","\n",$details);
				} else {
					$resultMessage = "<span class=\"redtext\">Item Not Found!</span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"fitouts-checklist-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					// select commodity, Electricty, Water, Gas
                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Commodity:</b></td>\n";
                    print "	<td><select name=\"commodity\" class=\"element_box\">\n";
                    print "	<option value=\"Electricity\""; if ($commodity == "Electricity") { print " selected"; } print ">Electricity</option>\n";
                    print "	<option value=\"Water\""; if ($commodity == "Water") { print " selected"; } print ">Water</option>\n";
                    print "	<option value=\"Gas\""; if ($commodity == "Gas") { print " selected"; } print ">Gas</option>\n";
                    print "	</select>";
                    if ($ercommodity) { print "<br><span class=\"redtext\"> $ercommodity</span>"; }
                    print "</td>\n";
                    print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Details:</b></td>\n";
					print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"7\" class=\"element_box\">$details</textarea>";
					if ($erdetails) { print "<br><span class=\"redtext\"> $erdetails</span>"; }
					print "</td>\n";
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
		print "<p align=\"center\"><a href=\"fitouts-checklist-list.php\">Return</a></p>\n";
	
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}	
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
