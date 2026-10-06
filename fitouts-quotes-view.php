<?php
$page = "Fitouts";
$subpage = "Details";
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
                    $sitename  = $row['sitename'];
                    $year      = $row['year'];
                    $month     = $row['month'];
                    $day       = $row['day'];
                    $dcamount  = $row['dcamount'];
                    $dcpercent = $row['dcpercent'];
                    $ufpercent = $row['ufpercent'];
                    $compname  = $row['compname'];
					$notes     = $row['notes'];
					
					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

                    print "<table align=\"center\" width=\"60%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\" width=\"130\"><b>Site:</b></td>";
                    print "	<td>$sitename</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Discount Amount:</b></td>\n";
                    print "	<td>$dcamount</td>\n";
                    print "</tr>\n";
                
                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Discount Percent:</b></td>";
                    print "	<td>$dcpercent%</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Up Front Percent:</b></td>";
                    print "	<td>$ufpercent%</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
                    print "	<td>$notes</td>\n";
                    print "</tr>\n";

					print "</table>\n";
					
					print "<p align=\"center\">";
					print "<a href=\"fitouts-quotes-addedit.php?qid=$qid\">Edit</a> | ";
					print "<a href=\"fitouts-quotes-delete.php?qid=$qid\">Delete</a>";
                    print "</p>\n";
	
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
