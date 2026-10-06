<?php
$page = "Telecoms";
$subpage = "Calls";
$subsubpage = "By Customer";
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

        $custref = $_REQUEST['custref'];

        if ($custref) {

            $search[] = "custref = '$custref'";

            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                // get filters
                $date = $_REQUEST['date'];
                if (!$date) { $date = date("m-Y"); } 
                list($m,$y) = explode("-",$date);
                $search[] = "callyear='$y'";
                $search[] = "callmonth='$m'";
                $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
                $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
                $thismonth = date("F Y",strtotime("$y-$m-01"));
                if ($search) { $search = implode(" AND ", $search);}
                
                // print filter form
                print "<form name=\"addform\" method=\"post\" action=\"telecoms-calls-bycust-detail.php?custref=$custref\">\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td style=\"font-size:1.6em\"><a href=\"telecoms-calls-bycust-detail.php?custref=$custref&amp;date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
                print "<td style=\"font-size:1.1em\"><b>Month</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
                print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                print "<td style=\"font-size:1.6em\"><a href=\"telecoms-calls-bycust-detail.php?custref=$custref&amp;date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";

                // get customer
                $sql = "SELECT compname FROM users WHERE custref = '$custref'";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $compname = $row['compname'];
                }

                print "<p align=\"center\" class=\"heading\">Calls - $thismonth<br>DP$custref - $compname</p>\n";

                // submenu
                include('telecoms-callsmenu.php');
                print "<p align=\"center\">$menustr</p>\n";

                // get available calls
                $sql = "SELECT *
                        FROM TelCallData
                        WHERE $search
                        ORDER BY callyear, callmonth, callday, calltime
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                    print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                    print "	<tr>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Date &amp; Time</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Extension</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Dialled</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Call Band</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\"><b>Duration</b></td>\n";
                    print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Cost</b></td>\n";
                    print "	</tr>\n";

                    # row background colours
                    print "<style>\n";
                    print ".r0 { background-color:#fff }\n";
                    print ".r1 { background-color:#ddd }\n";
                    print "</style>\n";

                    while ($row = $result->fetch_assoc()) {
                        $callyear     = $row['callyear'];
                        $callmonth    = $row['callmonth'];
                        $callday      = $row['callday'];
                        $calltime     = $row['calltime'];
                        $numberdialed = $row['numberdialed'];
                        $callduration = $row['callduration'];
                        $bandname     = $row['bandname'];
                        $cost         = $row['totalcharge'];
                        $extension    = $row['extention'];

                        $totcost += $cost;

                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	    <td>$callday-$callmonth-$callyear $calltime</td>\n";
                        print "	    <td>$extension</td>\n";
                        print "	    <td>$numberdialed</td>\n";
                        print "	    <td>$bandname</td>\n";
                        print "	    <td>$callduration</td>\n";
                        print "	    <td align=\"right\">$cost</td>\n";
                        print "	  </tr>\n";

                    }

                    $calls = $result->num_rows;

                    print "		<tr>\n";
                    print "		  	<td style=\"border-top:1px solid #666\"><b>$calls</b></td>\n";
                    print "		  	<td colspan=\"5\" style=\"border-top:1px solid #666\" align=\"right\"><b>".number_format($totcost,3)."</b></td>\n";
                    
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
        } else {
            $resultMessage = "<b>Page Called Without Custref</b><br>";
        }
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
        }
        print "<p align=\"center\"><a href=\"telecoms-calls-bycust.php?date=$date\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
