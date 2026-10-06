<?php
$page = "Telecoms";
$subpage = "Calls";
$subsubpage = "By Site";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Calls</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            // get filters
			$date = $_REQUEST['date'];
            if (!$date) { $date = date("m-Y"); } 
            list($m,$y) = explode("-",$date);
            $search[] = "t.callyear='$y'";
            $search[] = "t.callmonth='$m'";
            $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));
            if ($search) { $search = implode(" AND ", $search);}
            
            // print filter form
            print "<form name=\"addform\" method=\"post\" action=\"telecoms-calls-bysite.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"telecoms-calls-bysite.php?date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.1em\"><b>Month</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"telecoms-calls-bysite.php?date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";

            print "<p align=\"center\" class=\"heading\">Calls - $thismonth</p>\n";

            // submenu
            include('telecoms-callsmenu.php');
	        print "<p align=\"center\">$menustr</p>\n";

            // get available calls
            $sql = "SELECT t.sitename, SUM(t.totalcharge) AS totcost, COUNT(*)
                    FROM TelCallData AS t
                    WHERE $search
                    GROUP BY t.sitename
                    ORDER BY t.sitename
            ";
            if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "	<tr>\n";
                print "		<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
                print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Calls</b></td>\n";
                print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Total</b></td>\n";
                print "	</tr>\n";

                # row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                while ($row = $result->fetch_assoc()) {
                    $totcost   = $row['totcost'];
                    $sitename  = $row['sitename'];
                    $calls     = $row['COUNT(*)'];
                    $tottot += $totcost;
                    $totcalls += $calls;
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	    <td>$sitename</td>\n";
                    print "	    <td align=\"right\">$calls</td>\n";
                    print "	    <td align=\"right\">$totcost</td>\n";
                    print "	  </tr>\n";

                }

                print "		<tr>\n";
                print "		  	<td colspan=\"2\" style=\"border-top:1px solid #666\" align=\"right\"><b>$totcalls</b></td>\n";
                print "		  	<td style=\"border-top:1px solid #666\" align=\"right\"><b>".number_format($tottot,3)."</b></td>\n";
                
                print "		</tr>\n";

                print "</table>\n";

                print "<p>&nbsp;</p>\n";

                // jquery date picker script
                print "<script type=\"text/javascript\">\n";
                print "	$( document ).ready(function() {\n";
                print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
                print "	});\n";
                print "</script>\n";
                
            } else {
                print "<p align=\"center\"><b>No calls found</b></p>";
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
