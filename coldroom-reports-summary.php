<?php
$page = "Coldroom_Reports";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$date = $_REQUEST['date'];
            if (!$date) { $date = date("Y-m"); } 
            list($y,$m) = explode("-",$date);
            $search = "r.sdate Like '$date%'";
            $prevmon = date("Y-m",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("Y-m",strtotime("$y-$m-01 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));

			print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";

            // print filter form
            print "<form name=\"addform\" method=\"post\" action=\"coldroom-reports-summary.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"coldroom-reports-summary.php?date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.2em\"><b>Month</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:3.6em\"><a href=\"coldroom-reports-summary.php\"><b>&bull;</b></a></td>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"coldroom-reports-summary.php?date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";
            print "<p align=\"center\" class=\"larger\">Summary - $thismonth</p>\n";

			$sql = "SELECT r.*, s.title
                    FROM cr_reports_headers AS r
                    LEFT JOIN sites AS s ON s.ID = r.siteid
                    WHERE $search
                    ORDER BY r.sdate DESC
            ";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Engineer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";
				print "</tr>\n";

				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print ".r0:hover { background-color: #e8ff80; }\n";
				print ".r1:hover { background-color: #e8ff80; }\n";
				print ".notviewed { font-weight: bold; }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
                    $id       = $row['ID'];
                    $sdate    = $row['sdate'];
                    $type     = $row['type'];
                    $siteid   = $row['siteid'];
					$unitid   = $row['unitid'];
                    $sitename = $row['title'];
					$unitname = $row['unitname'];
                    $engname  = $row['engname'];
					list($y,$m,$d) = explode("-",$sdate);

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$d-$m-$y</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$type</td>\n";
					print "	<td>$engname</td>\n";
					print "	<td align=\"center\"><a href=\"coldroom-reports-view.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id&amp;date=$date\">View</a></td>\n";
					print "	<td align=\"center\"><a href=\"coldroom-reports-pdf.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\" target=\"_blank\">Pdf</a></td>\n";
					print "	<td align=\"center\"><a href=\"coldroom-reports-email.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id&amp;date=$date\">Email</a></td>\n";
				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\">". $result->num_rows ."</td>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"8\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";
			} else {
				print "<p align=\"center\">None Found!</p>\n";
			}

            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm'});\n";
            print "	});\n";
            print "</script>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
