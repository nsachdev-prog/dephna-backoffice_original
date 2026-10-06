<?php
$page = "Reporting";
$subpage = "Ancillaries By Customer";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">$subpage</p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			// get filtering if any and build search array
			if ($_REQUEST['ye'] != "All") { $ye = $_REQUEST['ye']; }
			if (!$ye) { $ye = date("Y"); }
			if ($_REQUEST['cu'] != "All") { $cu = $_REQUEST['cu']; }

			if ($ye) { $search[] = "h.year='$ye'"; }
			if ($cu) { $search[] = "h.custref='$cu'"; }

			if ($search) {
				// make MySql search string
				$search = implode(" AND ", $search);
			}

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-ancillaries.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			// get available years
			$sql = "SELECT year FROM inv_headers GROUP BY year ORDER BY year DESC";
			$yresult = $dbcnx->query($sql);
			if (($yresult) && ($yresult->num_rows)) {
				print "		<td align=\"right\"><b>Year</b></td>\n";
				print "		<td><select name=\"ye\" class=\"element_date\">\n";
				while ($yrow = $yresult->fetch_assoc()) {
					$year = $yrow['year'];
					print "		<option value=\"$year\""; if ($ye == $year) { print " SELECTED"; } print ">$year</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "		<td align=\"right\"><b>Customer</b></td>\n";
			print "		<td><select name=\"cu\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT custref, compname FROM users ORDER BY compname";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
                    $thiscustref  = $row['custref'];
                    $thiscompname = $row['compname'];
                    print "		<option value=\"$thiscustref\"";
                    if ($cu == $thiscustref) {
                        print " SELECTED";
                        $custselected = $thiscompname;
                    } else {
                        $custselected = "All Customers";
                    }
                    print ">$thiscompname</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
            print "</form>\n";

            // get list of ancillaries
            $sql = "SELECT title FROM ancillaries ORDER BY title";
            if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                while ($row = $result->fetch_assoc()) {
                    $ancillaries[] = $row['title'];
                }
            } else {
                $resultMessage = "No Anvillaries Found";
            }
            
            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

            foreach ($months AS $key => $month) { // month loop start
                print "<p align=\"center\"><b>$month - $ye - $custselected</b></p>\n";
                print "<table width=\"\" align=\"center\">\n";
                print "<tr>\n";
                print "	<td>&nbsp;</td>\n";
                print "	<td align=\"right\"><b>Net</b></td>\n";
                print "	<td align=\"right\"><b>Vat</b></td>\n";
                print "	<td align=\"right\"><b>Total</b></td>\n";
                print "</tr>\n";

                foreach ($ancillaries AS $ancillary) { // ancillary loop start

                    $sql = "SELECT SUM(l.linetotal) AS linetotal, SUM(l.vattotal) AS vattotal, SUM(l.linenet) AS linenet FROM inv_lines AS l
                        JOIN inv_headers AS h ON l.hid = h.ID
                        WHERE $search AND h.month='$key' AND l.office = '$ancillary'
                    ";

                    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                        
                        while ($row = $result->fetch_assoc()) {
                            $antotal    = $row['linetotal'];
                            $anvattotal = $row['vattotal'];
                            $annettotal = $row['linenet'];
                            print "<tr>\n";
                            print "	<td>$ancillary</td>\n";
                            print "	<td align=\"right\">" . number_format($annettotal,0) . "</td>\n";
                            print "	<td align=\"right\">" . number_format($anvattotal,0) . "</td>\n";
                            print "	<td align=\"right\">" . number_format($antotal,0) . "</td>\n";
                            print "</tr>\n";
                            
                        }
                    } else {
                        print "<tr>\n";
                        print "	<td colspan=\"4\">Nothing Found for $ancillary</td>\n";
                        print "</tr>\n";
                    }

                } // ancillary loop end
                print "</table>\n";
 
            } // month loop end
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
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
