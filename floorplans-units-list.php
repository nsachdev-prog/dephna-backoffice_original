<?php
$page = "Floorplans";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$siteid = $_REQUEST['siteid'];
		
		print "<p align=\"center\" class=\"heading\">FLOORPLANS</p>\n";

		if ($siteid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];

					print "<p align=\"center\" class=\"larger\">$sitename - Units</p>\n";

                    // retun to sites list
                    print "<p align=\"center\"><a href=\"floorplans-sites-list.php\">Return to Sites</a></p>\n";

                    // get units
                    $sql = "SELECT * FROM units WHERE siteid = '$siteid' ORDER BY title";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        print "<table width=\"96%\" align=\"center\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\">\n";
                        print "<tr>\n";
                        print "<td style=\"color:#eee;background-color:#333;border-right: 2px solid white\">&nbsp;</td>\n";
                        print "<td style=\"color:#eee;background-color:#333;border-right: 2px solid white\">&nbsp;</td>\n";
                        print "<td colspan=\"3\" align=\"center\" style=\"color:#eee;background-color:#333;border-right: 2px solid white\"><b>Unit</b></td>\n";
                        print "<td colspan=\"2\" align=\"center\" style=\"color:#eee;background-color:#333;border-right: 2px solid white\"><b>Door</b></td>\n";
                        print "<td align=\"right\" style=\"color:#eee;background-color:#333;border-right: 2px solid white\">&nbsp;</td>\n";
                        print "<td style=\"color:#eee;background-color:#333;border-right: 2px solid white\">&nbsp;</td>\n";
                        print "<td colspan=\"4\" style=\"color:#eee;background-color:#333;border-right: 2px solid white\"S>&nbsp;</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "<td style=\"border-bottom:1px solid #999\"><b>Unit Name</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\"><b>Unit Type</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Width</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Depth</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Height</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Width</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Height</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Canopy</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"center\"><b>ACO</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Evap</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\"><b>Notes</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
                        print "<td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
                        print "</tr>\n";
                        while($row = $result->fetch_assoc()) {
                            $unitid      = $row['ID'];
                            $unitname    = $row['title'];
                            $unittype    = $row['unittype'];
                            $fpwidth     = $row['fpwidth'];
                            $fpdepth     = $row['fpdepth'];
                            $fpheight    = $row['fpheight'];
                            $fpcanopy    = $row['fpcanopy'];
                            $fpdoorwidth = $row['fpdoorwidth'];
                            $fpdoorheight = $row['fpdoorheight'];
                            $fpcanopywidth = $row['fpcanopywidth'];
                            $fpacodrains = $row['fpacodrains'];
                            $fpevap      = $row['fpevap'];
                            $fpnotes     = $row['fpnotes'];

                            if (strlen($fpnotes) > 15) {
                                $fpnotes = substr($fpnotes,0,15);
                            }

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "<td>$unitname</td>\n";
                            print "<td>$unittype</td>\n";
                            print "<td align=\"right\">$fpwidth</td>\n";
                            print "<td align=\"right\">$fpdepth</td>\n";
                            print "<td align=\"right\">$fpheight</td>\n";
                            print "<td align=\"right\">$fpdoorwidth</td>\n";
                            print "<td align=\"right\">$fpdoorheight</td>\n";
                            if ($fpcanopy == 'on') {
                                print "<td align=\"center\">Yes</td>\n";
                            } else {
                                print "<td>&nbsp;</td>\n";
                            }
                            if ($fpacodrains == 'on') {
                                print "<td align=\"center\">Yes</td>\n";
                            } else {
                                print "<td>&nbsp;</td>\n";
                            }
                            if ($fpevap == 'on') {
                                print "<td align=\"center\">Yes</td>\n";
                            } else {
                                print "<td>&nbsp;</td>\n";
                            }
                            print "<td>$fpnotes</td>\n";
                            print "<td><a href=\"floorplans-units-addedit.php?siteid=$siteid&unitid=$unitid\">Edit</a></td>\n";
                            if ($fpwidth > 0.00 AND $fpdepth > 0.00 AND $fpheight > 0.00) {
                                print "<td><a href=\"floorplans-units-pdf.php?siteid=$siteid&unitid=$unitid\" target=\"_blank\">Draw</a></td>\n";
                            } else {
                                print "<td>&nbsp;</td>\n";
                            }
                            print "</tr>\n";
                            
                        }
                        print "<tr>\n";
                        print "<td colspan=\"13\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "</tr>\n";
                        print "</table>\n";

                    } else {
                        print "<p align=\"center\"><span class=\"redtext\"><b>No Units Found!</b></span></p>\n";
                    }

                } else {
                    $resultMessage = "<span class=\"redtext\"><b>Could Not Find Quote!</b></span>";
                }
                $result->free();
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout siteid";
		}
        print "<p align=\"center\"><a href=\"floorplans-sites-list.php\">Return to Sites</a></p>\n";
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
