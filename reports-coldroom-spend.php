<?php
$page = "Reporting";
$subpage = "Coldroom Spend";
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
			$date = $_REQUEST['date'];
            if (!$date) { $date = date("m-Y"); } 
            list($m,$y) = explode("-",$date);
            $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));
            if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
            if (!$lo) { $lo = "190 Acton Lane"; }
            $search[] = "l.year = '$y'";
            $search[] = "l.month = '$m'";
            $search[] = "l.commodity = 'Electricity'";
            $search[] = "l.sitename = '$lo'";
            $search[] = "(u.status = 'Freezer' OR u.status = 'Chiller')";
			$search = "WHERE " . implode(" AND ", $search);

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"reports-coldroom-spend.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"reports-coldroom-spend.php?date=$prevmon&amp;lo=$lo\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.1em\"><b>Month</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
            print "<td align=\"right\"><b>Site</b></td>\n";
			print "<td><select name=\"lo\" class=\"element_small\">\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$lresult = $dbcnx->query($sql);
			if (($lresult) && ($lresult->num_rows)) {
				while ($lrow = $lresult->fetch_assoc()) {
					$ltitle  = $lrow['title'];
					print "		<option value=\"$ltitle\""; if ($lo == $ltitle) { print " SELECTED"; } print ">$ltitle</option>\n";
				}
			}
			print "</select>";
            print "</td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"reports-coldroom-spend.php?date=$nextmon&amp;lo=$lo\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";

            print "<p align=\"center\" class=\"heading\">$lo - $thismonth</p>\n";

            $sql = "SELECT
                        MAX(CASE WHEN l.charge > '0' AND u.status = 'Freezer' AND u.siteid = l.siteid THEN l.charge ELSE NULL END) AS maxfreezer,
                        MIN(CASE WHEN l.charge > '0' AND u.status = 'Freezer' AND u.siteid = l.siteid THEN l.charge ELSE NULL END) AS minfreezer,
                        AVG(CASE WHEN l.charge > '0' AND u.status = 'Freezer' AND u.siteid = l.siteid THEN l.charge ELSE NULL END) AS avgfreezer,
                        MAX(CASE WHEN l.charge > '0' AND u.status = 'Chiller' AND u.siteid = l.siteid THEN l.charge ELSE NULL END) AS maxchiller,
                        MIN(CASE WHEN l.charge > '0' AND u.status = 'Chiller' AND u.siteid = l.siteid THEN l.charge ELSE NULL END) AS minchiller,
                        AVG(CASE WHEN l.charge > '0' AND u.status = 'Chiller' AND u.siteid = l.siteid THEN l.charge ELSE NULL END) AS avgchiller
                    FROM UtlUsedNew AS l
                    LEFT JOIN units AS u ON SUBSTRING_INDEX(u.title, ' ', -1) = l.unit
                    $search
            ";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                // print test results
                print "<table width=\"300\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td><b>Cold Room</b></td>\n";
                print "<td align=\"right\"><b>Max</b></td>\n";
                print "<td align=\"right\"><b>Min</b></td>\n";
                print "<td align=\"right\"><b>Avg</b></td>\n";
                print "</tr>\n";

                while ($row = $result->fetch_assoc()) {
                    $maxfreezer = $row['maxfreezer'];
                    $minfreezer = $row['minfreezer'];
                    $avgfreezer = $row['avgfreezer'];
                    $maxchiller = $row['maxchiller'];
                    $minchiller = $row['minchiller'];
                    $avgchiller = $row['avgchiller'];
                    print "<tr>\n";
                    print "<td>Freezer</td>\n";
                    print "<td align=\"right\">" . number_format($maxfreezer,2) . "</td>\n";
                    print "<td align=\"right\">" . number_format($minfreezer,2) . "</td>\n";
                    print "<td align=\"right\">" . number_format($avgfreezer,2) . "</td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "<td>Chiller</td>\n";
                    print "<td align=\"right\">" . number_format($maxchiller,2) . "</td>\n";
                    print "<td align=\"right\">" . number_format($minchiller,2) . "</td>\n";
                    print "<td align=\"right\">" . number_format($avgchiller,2) . "</td>\n";
                    print "</tr>\n";
                }

                print "</table>\n";
                
                // jquery date picker script
                print "<script type=\"text/javascript\">\n";
                print "	$( document ).ready(function() {\n";
                print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
                print "	});\n";
                print "</script>\n";

			} else {
				$resultMessage = "Nothing Found<br />" . $dbcnx->error;
			}
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
