<?php
$page = "Fitouts";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		if ($_REQUEST['qid']) { $qid = $_REQUEST['qid']; }
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Quote</p>\n";
			
		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT q.*, u.compname, s.title AS sitename
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					LEFT JOIN sites AS s ON q.siteid = s.ID
					WHERE q.ID = '$qid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
                    $custref   = $row['custref'];
					$sitename  = $row['sitename'];
					$siteid    = $row['siteid'];
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\" class=\"larger\">$sitename Units</p>\n";

					if ($_POST['units-posted']) {
						$units = $_POST['units'];
                        if (count($units)) {
                            // delete current
                            $sql = "DELETE FROM fitouts_quotes_units WHERE qid = '$qid'";
                            $dbcnx->query($sql);
                            // post new
                            foreach ($units as $unit) {
                                list($unitid,$unitname) = explode("--",$unit);
                                $sql = "INSERT INTO fitouts_quotes_units VALUES (
                                    null,
                                    '$qid',
                                    '$unitid',
                                    '$unitname',
                                    '0',
                                    '0',
                                    '0',
                                    '0',
                                    '0',
                                    '',
                                    '0',
                                    ''
                                )";
                                if ($dbcnx->query($sql)) {
                                    $resultMessage .= "Units Added<br>";
                                } else {
                                    $resultMessage .= "Error Adding Units<br>" . $dbcnx->error;
                                }
                            }
                        } else {
                            $resultMessage .= "No Units Selected<br>";
                        }
					}

					print "<p align=\"center\" class=\"larger\">Units Added</p>\n";
					
					$sql = "SELECT f.*, u.unittype
                            FROM fitouts_quotes_units AS f
                            LEFT JOIN units AS u ON f.uid = u.ID
                            WHERE qid = '$qid'
                    ";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<table width=\"200\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Unit</b></td>\n";
                        print "	    <td style=\"border-bottom:1px solid #999\"><b>Unittype</b></td>\n";
						print "	</tr>\n";

                        $currentunits = array();

						while ($row = $result->fetch_assoc()) {

                            $unitid   = $row['uid'];
							$unitname = $row['unitname'];
                            $unittype = $row['unittype'];

                            $currentunits[] = $unitid;

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\">$unitname</td>\n";
                            print "<td valign=\"top\">$unittype</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "<td colspan=\"2\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\">No Units Added Yet</p>\n";
					}


					print "<p align=\"center\" class=\"larger\">Add/Update Units</p>\n";

                    $units = array();
                    $n = 0;

                    // get units for this custref from bookings
                    $sql = "SELECT b.ID, b.unitid, u.title AS unitname, u.unittype
                            FROM bookings AS b
                            LEFT JOIN units AS u ON b.unitid = u.ID
                            WHERE b.custid = '$custref'
                              AND b.siteid = '$siteid'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        while ($row = $result->fetch_assoc()) {
                            $units[$n][unitid] = $row['unitid'];
                            $units[$n][unitname] = $row['unitname'];
                            $units[$n][unittype] = $row['unittype'];
                            $n++;
                        }
                    }

                    // get units for this custref from bk_manager
                    $sql = "SELECT b.ID, u.unitid, u.unitname, u.unittype
                            FROM bk_manager AS b
                            LEFT JOIN bkm_units AS u ON b.ID = u.bkid
                            WHERE b.custref = '$custref'
                              AND b.siteid = '$siteid'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        while ($row = $result->fetch_assoc()) {
                            $units[$n][unitid] = $row['unitid'];
                            $units[$n][unitname] = $row['unitname'];
                            $units[$n][unittype] = $row['unittype'];
                            $n++;
                        }
                    }

                    // array unique
                    $units = array_map("unserialize", array_unique(array_map("serialize", $units)));
                    // sort by unitname
                    usort($units, function($a, $b) {
                        return $a['unitname'] <=> $b['unitname'];
                    });

                    if ($units) {

                        print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-units.php\">\n";
					    print "<input type=\"hidden\" name=\"units-posted\" value=\"1\" />\n";
					    print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";

                        print "<table width=\"200\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
                        print "<td style=\"border-bottom:1px solid #999\"><b>Unit</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\"><b>Unittype</b></td>\n";
                        print "<td style=\"border-bottom:1px solid #999\" width=\"35\">&nbsp;</td>\n";
                        print "</tr>\n";
					
                        foreach ($units as $unit) {
                            $uid      = $unit['unitid'];
                            $unitname = $unit['unitname'];
                            $unittype = $unit['unittype'];

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "	<td>$unitname</td>\n";
                            print " <td>$unittype</td>\n";
                            print "	<td align=\"right\"><input type=\"checkbox\" name=\"units[]\" value=\"$uid--$unitname\""; if (in_array($uid,$currentunits)) { print " checked"; } print " /></td>\n";
                            print "</tr>\n";
                        }

                        print "<tr>\n";
                        print "<td colspan=\"3\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "</tr>\n";

                        print "</table>\n";

                        print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";

                        print "</form>\n";

                        print "<p align=\"center\">";
                        print "<a href=\"fitouts-quotes-list.php\">Back</a> | ";
                        print "<a href=\"fitouts-quotes-items.php?qid=$qid\">Next</a>";
                        print "</p>\n";

                    } else {
                        $resultMessage .= "No Units Found!<br>" . $dbcnx->error;
                    }

				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fitout Quote Not Found!</b></span><br>" . $dbcnx->error;
				}

				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Page Called Without ID</b></span><br>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
