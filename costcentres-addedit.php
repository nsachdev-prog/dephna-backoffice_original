<?php
$page = "Suppliers";
$subpage = "Cost Centres";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">COST CENTRES</p>\n";
		include('suppliers-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Cost Centre";
			} else {
				$heading = "New Cost Centre";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$title   = $_POST['title'];
				$ccgroup = $_POST['ccgroup'];
				$tbgroup = $_POST['tbgroup'];
				$notes   = $_POST['notes'];
	
				if (!$title) { $ertitle = "Please give a name"; $e++; }

				if (!$e) {
					$title = htmlentities($title, ENT_QUOTES);
					$notes = htmlentities($notes, ENT_QUOTES);
					
					$now = time();
					
					if ($id) {
						if ($ccgroup <> 3) {
							$tbgroup == $ccgroup;
						}
						$sql = "UPDATE costcentres SET
							title='$title',
							ccgroup='$ccgroup',
							tbgroup='$tbgroup',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Cost Centre Successfully Updated</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','$subpage','$now')");
						} else {
							$resultMessage = "<b>Could NOT Update Record</b><br>" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO costcentres VALUES (
							null,
							'$title',
							'$ccgroup',
							'$tbgroup',
							'$notes',
							'$now',
							'{$_SESSION['username']}'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Cost Centre Successfully Added</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','$subpage','$now')");
						} else {
							$resultMessage = "<b>Could NOT Add New Record To Database</b><br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM costcentres WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title    = $row['title'];
					$ccgroup  = $row['ccgroup'];
					$tbgroup  = $row['tbgroup'];
					$notes    = $row['notes'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
					$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
				} else {
					$resultMessage = "<b>Cost Centre Not Found</b><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"costcentres-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Cost Centre Name</b></td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$title\" />";
					if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Group</b></td>\n";
					print "	<td><select id=\"tyselect\" name=\"ccgroup\" class=\"element\">\n";
					print "		<option value=\"2\""; if ($ccgroup == 2) { print " SELECTED"; } print ">Administrative Expenses</option>\n";
					print "		<option value=\"1\""; if ($ccgroup == 1) { print " SELECTED"; } print ">Cost of Sales</option>\n";
					print "		<option value=\"3\""; if ($ccgroup == 3) { print " SELECTED"; } print ">Statement Only Items</option>\n";
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr id=\"exptype\">\n";
					print "	<td align=\"right\"><b>TB Group</b></td>\n";
					print "	<td><select name=\"tbgroup\" class=\"element\">\n";
					print "		<option value=\"2\""; if ($tbgroup == 2) { print " SELECTED"; } print ">Indirect Expenses</option>\n";
					print "		<option value=\"1\""; if ($tbgroup == 1) { print " SELECTED"; } print ">Direct Expenses</option>\n";
					print "		<option value=\"3\""; if ($tbgroup == 0) { print " SELECTED"; } print ">Not Applicable</option>\n";
					print "	</select>";
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

					print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    if ($ccgroup <> 3) {
                        print "     $(\"#exptype\").hide();\n";
                    }
                    print "     $('#tyselect').on('change', function() {\n";
                    print "         if (this.value == '3') {\n";
                    print "              $('#exptype').fadeIn('fast');\n";
                    print "         } else {\n";
                    print "              $('#exptype').fadeOut('fast');\n";
                    print "         };\n";
                    print "     });\n";
                    print "	});\n";
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
		print "<p align=\"center\"><a href=\"costcentres-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

?>
