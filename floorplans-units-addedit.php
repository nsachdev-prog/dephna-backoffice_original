<?php
$page = "Floorplans";
$subpage = "Edit";
$siteid = $_REQUEST['siteid'];
$unitid = $_REQUEST['unitid'];
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\"class=\"heading\">FLOORPLANS</p>\n";

			if ($siteid AND $unitid) {

				// get site name
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

                // get unit name
                $sql = "SELECT title FROM units WHERE ID = '$unitid'";
                $result = $dbcnx->query($sql);
                if (($result) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $unitname = $row['title'];
                } else {
                    $resultMessage = "Unit Not Found<br />" . $dbcnx->error;
                }

                print "<p align = \"center\" class=\"larger\">$sitename - $unitname</p>\n";

				if ($_POST['posted']) {
					$fpwidth     = $_POST['fpwidth'];
                    $fpdepth     = $_POST['fpdepth'];
                    $fpheight    = $_POST['fpheight'];
                    $fpdoorwidth = $_POST['fpdoorwidth'];
                    $fpdoorheight = $_POST['fpdoorheight'];
					$fpdoorleft = $_POST['fpdoorleft'];
					$fpdoorright = $_POST['fpdoorright'];
                    $fpcanopy    = $_POST['fpcanopy'];
                    $fpcanopywidth = $_POST['fpcanopywidth'];
					$fpcanopydepth = $_POST['fpcanopydepth'];
					$fpcanopyheight = $_POST['fpcanopyheight'];
					$fpleftofcanopy = $_POST['fpleftofcanopy'];
					$fprightofcanopy = $_POST['fprightofcanopy'];
                    $fpdrainleft = $_POST['fpdrainleft'];
					$fpdrainright = $_POST['fpdrainright'];
                    $fpacodrains = $_POST['fpacodrains'];
                    $fpacodrainswidth = $_POST['fpacodrainswidth'];
                    $fpacofromdoor = $_POST['fpacofromdoor'];
					$fpevap = $_POST['fpevap'];
					$fpevappos = $_POST['fpevappos'];
					$flspecs	 = $_POST['flspecs'];
                    $fpnotes     = $_POST['fpnotes'];

					if (!$fpwidth) { $erfpwidth = "Width is required"; $e++; }
					elseif (!is_numeric($fpwidth)) { $erfpwidth .= "Width must be numeric"; $e++; }
					if (!$fpdepth) { $erfpdepth = "Depth is required"; $e++; }
					elseif (!is_numeric($fpdepth)) { $erfpdepth = "Depth must be numeric"; $e++; }
					if (!$fpheight) { $erfpheight = "Height is required"; $e++; }
					elseif (!is_numeric($fpheight)) { $erfpheight = "Height must be numeric"; $e++; }
					if (!$fpdoorwidth) { $erfpdoorwidth = "Door Width is required"; $e++; }
					elseif (!is_numeric($fpdoorwidth)) { $erfpdoorwidth = "Door Width must be numeric"; $e++; }
					if (!$fpdoorheight) { $erfpdoorheight = "Door Height is required"; $e++; }
					elseif (!is_numeric($fpdoorheight)) { $erfpdoorheight = "Door Height must be numeric"; $e++; }
					if (!$fpdoorleft) { $erfpdoorleft = "To Left of Door is required"; $e++; }
					elseif (!is_numeric($fpdoorleft)) { $erfpdoorleft = "To Left of Door must be numeric"; $e++; }
					if (!$fpdoorright) { $erfpdoorright = "To Right of Door is required"; $e++; }
					elseif (!is_numeric($fpdoorright)) { $erfpdoorright = "To Right of Door must be numeric"; $e++; }
					if ($fpcanopy == "on") {
						if (!$fpcanopywidth) { $erfpcanopywidth = "Canopy Width is required"; $e++; }
						elseif (!is_numeric($fpcanopywidth)) { $erfpcanopywidth = "Canopy Width must be numeric"; $e++; }
						if (!$fpcanopydepth) { $erfpcanopydepth = "Canopy Depth is required"; $e++; }
						elseif (!is_numeric($fpcanopydepth)) { $erfpcanopydepth = "Canopy Depth must be numeric"; $e++; }
						if (!$fpcanopyheight) { $erfpcanopyheight = "Canopy Height is required"; $e++; }
						elseif (!is_numeric($fpcanopyheight)) { $erfpcanopyheight = "Canopy Height must be numeric"; $e++; }
						if (!$fpleftofcanopy) { $erfpleftofcanopy = "To Left of Canopy is required"; $e++; }
						elseif (!is_numeric($fpleftofcanopy)) { $erfpleftofcanopy = "To Left of Canopy must be numeric"; $e++; }
						if (!$fprightofcanopy) { $erfprightofcanopy = "To Right of Canopy is required"; $e++; }
						elseif (!is_numeric($fprightofcanopy)) { $erfprightofcanopy = "To Right of Canopy must be numeric"; $e++; }
					}
                    if ($fpacodrains == "on") {
                        if (!$fpacodrainswidth) { $erfpacodrainswidth = "ACO Drains Width is required"; $e++; }
                        elseif (!is_numeric($fpacodrainswidth)) { $erfpacodrainswidth = "ACO Drains Width must be numeric"; $e++; }
                        if (!$fpacofromdoor) { $erfpacofromdoor = "ACO Drains From Door is required"; $e++; }
                        elseif (!is_numeric($fpacofromdoor)) { $erfpacofromdoor = "ACO Drains From Door must be numeric"; $e++; }
                    }
					if ($fpevap == 'on') {
						if (!$fpevappos) { $erfpevappos = "Evaporator Position is required"; $e++; }
					}

					if (!$e) {
						$flspecs = trim($flspecs);
						$fpnotes = trim($fpnotes);
						$flspecs = $dbcnx->real_escape_string($flspecs);
						$fpnotes = $dbcnx->real_escape_string($fpnotes);
                        $sql = "UPDATE units SET
                            fpwidth = '$fpwidth',
                            fpdepth = '$fpdepth',
                            fpheight = '$fpheight',
                            fpdoorwidth = '$fpdoorwidth',
                            fpdoorheight = '$fpdoorheight',
                            fpdoorleft = '$fpdoorleft',
                            fpdoorright = '$fpdoorright',
                            fpcanopy = '$fpcanopy',
                            fpcanopywidth = '$fpcanopywidth',
							fpcanopydepth = '$fpcanopydepth',
							fpcanopyheight = '$fpcanopyheight',
                            fpleftofcanopy = '$fpleftofcanopy',
                            fprightofcanopy = '$fprightofcanopy',
                            fpdrainleft = '$fpdrainleft',
                            fpdrainright = '$fpdrainright',
                            fpacodrains = '$fpacodrains',
                            fpacodrainswidth = '$fpacodrainswidth',
                            fpacofromdoor = '$fpacofromdoor',
							fpevap = '$fpevap',
							fpevappos = '$fpevappos',
							flspecs = '$flspecs',
                            fpnotes = '$fpnotes'
                            WHERE ID='$unitid'
                        ";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Floorplan Updated<br>";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $unitname','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\">Could NOT update floorplan</span><br>" . $dbcnx->error;
                        }
					}

				} elseif ($_POST['copy']) {
					$copyfrom = $_POST['copyfrom'];
					$sql = "SELECT * FROM units WHERE ID = '$copyfrom'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$fpwidth     = $row['fpwidth'];
						$fpdepth     = $row['fpdepth'];
						$fpheight    = $row['fpheight'];
						$fpdoorwidth = $row['fpdoorwidth'];
						$fpdoorheight = $row['fpdoorheight'];
						$fpdoorleft  = $row['fpdoorleft'];
						$fpdoorright = $row['fpdoorright'];
						$fpcanopy    = $row['fpcanopy'];
						$fpcanopywidth = $row['fpcanopywidth'];
						$fpcanopydepth = $row['fpcanopydepth'];
						$fpcanopyheight = $row['fpcanopyheight'];
						$fpleftofcanopy = $row['fpleftofcanopy'];
						$fprightofcanopy = $row['fprightofcanopy'];
						$fpdrainleft = $row['fpdrainleft'];
						$fpdrainright = $row['fpdrainright'];
						$fpacodrains = $row['fpacodrains'];
						$fpacodrainswidth = $row['fpacodrainswidth'];
						$fpacofromdoor = $row['fpacofromdoor'];
						$fpevap = $row['fpevap'];
						$fpevappos = $row['fpevappos'];
						$flspecs	 = $row['flspecs'];
						$fpnotes     = $row['fpnotes'];
						$flspecs = stripslashes($flspecs);
						$fpnotes = stripslashes($fpnotes);
					} else {
						$resultMessage .= "<span class=\"redtext\">Unit Not Found</span><br>" . $dbcnx->error;
					}

				} else {
					// get floorplan details
					$sql = "SELECT * FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
                        $row = $result->fetch_assoc();
						$fpwidth     = $row['fpwidth'];
						$fpdepth     = $row['fpdepth'];
						$fpheight    = $row['fpheight'];
						$fpdoorwidth = $row['fpdoorwidth'];
						$fpdoorheight = $row['fpdoorheight'];
						$fpdoorleft  = $row['fpdoorleft'];
						$fpdoorright = $row['fpdoorright'];
						$fpcanopy    = $row['fpcanopy'];
						$fpcanopywidth = $row['fpcanopywidth'];
						$fpcanopydepth = $row['fpcanopydepth'];
						$fpcanopyheight = $row['fpcanopyheight'];
						$fpleftofcanopy = $row['fpleftofcanopy'];
						$fprightofcanopy = $row['fprightofcanopy'];
						$fpdrainleft = $row['fpdrainleft'];
						$fpdrainright = $row['fpdrainright'];
                        $fpacodrains = $row['fpacodrains'];
                        $fpacodrainswidth = $row['fpacodrainswidth'];
                        $fpacofromdoor = $row['fpacofromdoor'];
						$fpevap = $row['fpevap'];
						$fpevappos = $row['fpevappos'];
						$flspecs	 = $row['flspecs'];
						$fpnotes     = $row['fpnotes'];
						$flspecs = stripslashes($flspecs);
						$fpnotes = stripslashes($fpnotes);
					} else {
						$resultMessage .= "<span class=\"redtext\">Unit Not Found</span><br>" . $dbcnx->error;
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						// copy floorplan details from another unit
						print "<form name=\"copyform\" method=\"post\" action=\"floorplans-units-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"copy\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "<td align=\"right\">Copy From:</td>\n";
						print "<td><select name=\"copyfrom\">\n";
						$sql = "SELECT ID, title FROM units WHERE siteid = '$siteid' AND ID != '$unitid' ORDER BY title";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							while ($row = $result->fetch_assoc()) {
								$copyid = $row['ID'];
								$copyname = $row['title'];
								print "<option value=\"$copyid\"" . ($copyfrom == $copyid ? " selected" : "") . ">$copyname</option>\n";
							}
						}
						print "</select></td>\n";
						print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Copy From Selected Unit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";

						print "<p align=\"center\">&nbsp;</p>\n";

						// edit floorplan details

						print "<form name=\"addform\" method=\"post\" action=\"floorplans-units-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "<td align=\"right\">Unit Width:</td>\n";
						print "<td><input type=\"text\" name=\"fpwidth\" class=\"element_tiny\" value=\"$fpwidth\">"; if ($erfpwidth) { print "<br><span class=\"redtext\">$erfpwidth</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td align=\"right\">Unit Depth:</td>\n";
						print "<td><input type=\"text\" name=\"fpdepth\" class=\"element_tiny\" value=\"$fpdepth\">"; if ($erfpdepth) { print "<br><span class=\"redtext\">$erfpdepth</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td align=\"right\">Unit Height:</td>\n";
						print "<td><input type=\"text\" name=\"fpheight\" class=\"element_tiny\" value=\"$fpheight\">"; if ($erfpheight) { print "<br><span class=\"redtext\">$erfpheight</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td align=\"right\">Door Width:</td>\n";
						print "<td><input type=\"text\" name=\"fpdoorwidth\" class=\"element_tiny\" value=\"$fpdoorwidth\">"; if ($erfpdoorwidth) { print "<br><span class=\"redtext\">$erfpdoorwidth</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td align=\"right\">Door Height:</td>\n";
						print "<td><input type=\"text\" name=\"fpdoorheight\" class=\"element_tiny\" value=\"$fpdoorheight\">"; if ($erfpdoorheight) { print "<br><span class=\"redtext\">$erfpdoorheight</span>"; } print "</td>\n";
						print "</tr>\n";
						// To left of door
						print "<tr>\n";
						print "<td align=\"right\">To Left of Door:</td>\n";
						print "<td><input type=\"text\" name=\"fpdoorleft\" class=\"element_tiny\" value=\"$fpdoorleft\">"; if ($erfpdoorleft) { print "<br><span class=\"redtext\">$erfpdoorleft</span>"; } print "</td>\n";
						print "</tr>\n";
						// To right of door
						print "<tr>\n";
						print "<td align=\"right\">To Right of Door:</td>\n";
						print "<td><input type=\"text\" name=\"fpdoorright\" class=\"element_tiny\" value=\"$fpdoorright\">"; if ($erfpdoorright) { print "<br><span class=\"redtext\">$erfpdoorright</span>"; } print "</td>\n";
						print "</tr>\n";
						// canopy checkbox with javascript to show/hide canopy dimensions
						print "<td align=\"right\">Canopy:</td>\n";
						print "<td><input type=\"checkbox\" name=\"fpcanopy\" id=\"fpcanopy\""; if ($fpcanopy) { print " checked"; } print " onclick=\"showCanopy()\"></td>\n";
						print "</tr>\n";
						print "<tr class=\"canopywidth\""; if (!$fpcanopy) { print " style=\"display:none\""; } print ">\n";
						print "<td align=\"right\">Canopy Width:</td>\n";
						print "<td><input type=\"text\" name=\"fpcanopywidth\" class=\"element_tiny\" value=\"$fpcanopywidth\">"; if ($erfpcanopywidth) { print "<br><span class=\"redtext\">$erfpcanopywidth</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr class=\"canopywidth\""; if (!$fpcanopy) { print " style=\"display:none\""; } print ">\n";
						print "<td align=\"right\">Canopy Depth:</td>\n";
						print "<td><input type=\"text\" name=\"fpcanopydepth\" class=\"element_tiny\" value=\"$fpcanopydepth\">"; if ($erfpcanopydepth) { print "<br><span class=\"redtext\">$erfpcanopydepth</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr class=\"canopywidth\""; if (!$fpcanopy) { print " style=\"display:none\""; } print ">\n";
						print "<td align=\"right\">Canopy Height:</td>\n";
						print "<td><input type=\"text\" name=\"fpcanopyheight\" class=\"element_tiny\" value=\"$fpcanopyheight\">"; if ($erfpcanopyheight) { print "<br><span class=\"redtext\">$erfpcanopyheight</span>"; } print "</td>\n";
						print "</tr>\n";
						// left and right of canopy
						print "<tr class=\"canopywidth\""; if (!$fpcanopy) { print " style=\"display:none\""; } print ">\n";
						print "<td align=\"right\">To Left of Canopy:</td>\n";
						print "<td><input type=\"text\" name=\"fpleftofcanopy\" class=\"element_tiny\" value=\"$fpleftofcanopy\">"; if ($erfpleftofcanopy) { print "<br><span class=\"redtext\">$erfpleftofcanopy</span>"; } print "</td>\n";
						print "</tr>\n";
						print "<tr class=\"canopywidth\""; if (!$fpcanopy) { print " style=\"display:none\""; } print ">\n";
						print "<td align=\"right\">To Right of Canopy:</td>\n";
						print "<td><input type=\"text\" name=\"fprightofcanopy\" class=\"element_tiny\" value=\"$fprightofcanopy\">"; if ($erfprightofcanopy) { print "<br><span class=\"redtext\">$erfprightofcanopy</span>"; } print "</td>\n";
						print "</tr>\n";
						// drains left and right with checkboxes to show/hide
						print "<tr>\n";
						print "<td align=\"right\">Drain Left:</td>\n";
						print "<td><input type=\"checkbox\" name=\"fpdrainleft\""; if ($fpdrainleft == 'on') { print " checked"; } print "></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td align=\"right\">Drain Right:</td>\n";
						print "<td><input type=\"checkbox\" name=\"fpdrainright\""; if ($fpdrainright == 'on') { print " checked"; } print "></td>\n";
						print "</tr>\n";
                        // aco drains with checkboxe to show/hide group
                        print "<tr>\n";
                        print "<td align=\"right\">ACO Drains:</td>\n";
                        print "<td><input type=\"checkbox\" name=\"fpacodrains\" id=\"fpacodrains\""; if ($fpacodrains == 'on') { print " checked"; } print " onclick=\"showACODrains()\"></td>\n";
                        print "</tr>\n";
                        print "<tr class=\"acodrains\""; if (!$fpacodrains) { print " style=\"display:none\""; } print ">\n";
                        print "<td align=\"right\">ACO Drains Width:</td>\n";
                        print "<td><input type=\"text\" name=\"fpacodrainswidth\" class=\"element_tiny\" value=\"$fpacodrainswidth\">"; if ($erfpacodrainswidth) { print "<br><span class=\"redtext\">$erfpacodrainswidth</span>"; } print "</td>\n";
                        print "</tr>\n";
                        print "<tr class=\"acodrains\""; if (!$fpacodrains) { print " style=\"display:none\""; } print ">\n";
                        print "<td align=\"right\">ACO Drains From Door:</td>\n";
                        print "<td><input type=\"text\" name=\"fpacofromdoor\" class=\"element_tiny\" value=\"$fpacofromdoor\">"; if ($erfpacofromdoor) { print "<br><span class=\"redtext\">$erfpacofromdoor</span>"; } print "</td>\n";
                        print "</tr>\n";
						// evaporator checkbox
						print "<tr>\n";
						print "<td align=\"right\">Evaporator:</td>\n";
						print "<td><input type=\"checkbox\" name=\"fpevap\" id=\"fpevap\""; if ($fpevap == 'on') { print " checked"; } print " onclick=\"showEvap()\"></td>\n";
						print "</tr>\n";
						print "<tr class=\"evappos\""; if (!$fpevap) { print " style=\"display:none\""; } print ">\n";
						print "<td align=\"right\">Evaporator Position:</td>\n";
						print "<td><select name=\"fpevappos\">\n";
						print "<option value=\"1\"" . ($fpevappos == 1 ? " selected" : "") . ">To Right of Door</option>\n";
						print "<option value=\"2\"" . ($fpevappos == 2 ? " selected" : "") . ">On Back Wall</option>\n";
						print "</select></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td colspan=\"2\">Floorplan Specs:<br><textarea name=\"flspecs\" class=\"element_box\" rows=\"5\" cols=\"30\">$flspecs</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td colspan=\"2\">Floorplan Notes:<br><textarea name=\"fpnotes\" class=\"element_box\" rows=\"5\" cols=\"30\">$fpnotes</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

						// javascript to show/hide canopy dimensions
						print "<script type=\"text/javascript\">\n";
						print "function showCanopy() {\n";
						print "	if (document.getElementById('fpcanopy').checked) {\n";
						print "		$('.canopywidth').show();\n";
						print "	} else {\n";
						print "		$('.canopywidth').hide();\n";
						print "	}\n";
						print "}\n";
						print "</script>\n";

                        // javascript to show/hide aco drains group
                        print "<script type=\"text/javascript\">\n";
                        print "function showACODrains() {\n";
                        print "	if (document.getElementById('fpacodrains').checked) {\n";
                        print "		$('.acodrains').show();\n";
                        print "	} else {\n";
                        print "		$('.acodrains').hide();\n";
                        print "	}\n";
                        print "}\n";
                        print "</script>\n";

						// javascript to show/hide evaporator position
						print "<script type=\"text/javascript\">\n";
						print "function showEvap() {\n";
						print "	if (document.getElementById('fpevap').checked) {\n";
						print "		$('.evappos').show();\n";
						print "	} else {\n";
						print "		$('.evappos').hide();\n";
						print "	}\n";
						print "}\n";
						print "</script>\n";

					}
				}

			} else {
				$resultMessage .= "<span class=\"redtext\">Page called without site/unit id</span><br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\">Could Not Connect To Database!</span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"floorplans-units-list.php?siteid=$siteid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
