<?php
$page = "Training";
$subpage = "Declaration";
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
			
			print "<p align = \"center\" class=\"heading\">Update Declaration</p>\n";
			
			if ($_POST['posted']) {
				
				$details = strip_tags($_POST['details']);
				
				if (!$details) { $erdetails = "Enter a Declaration"; $e++; }

				if (!$e) {
					
					$details = trim($details);
					$details = addslashes($details);
					$details = str_replace('\n',"<br>",$details);
					$now = time();
								
					$sql = "UPDATE training_declaration SET
						details='$details',
						lastupd='$now',
						updby='{$_SESSION['username']}'
						WHERE ID='1'
					";
					if ($result = $dbcnx->query($sql)) {
						$resultMessage .= "<b>Declaration Successfully Updated</b><br>";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Declaration','$page','$now')");
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
					}

				}
			}

			else {
  			$sql = "SELECT * FROM training_declaration WHERE ID = '1'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$details    = $row['details'];
					$details = str_replace("<br>","\n",$details);
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Declaration Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"training-declaration-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Details:</b></td>\n";
					print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"10\" class=\"element_box\">$details</textarea>";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\"> $erdetails</span>"; }
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
		print "<p align=\"center\"><a href=\"training-declaration-view.php\">Return</a></p>\n";
	
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}	
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
