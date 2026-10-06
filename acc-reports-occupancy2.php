<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Accounts";
$subpage = "Occupancy 2";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

    // print headings
    print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";
    print "<p align=\"center\" class=\"larger\">Occupancy Length</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $totdays = 0;
            $totset = 0;

            $sql = "SELECT compname, custref FROM users ORDER BY compname";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                print "<table width=\"90%\" align=\"center\" cellpadding=\"4\" cellspacing=\"0\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Customer Name</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>First Invoice</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Last Invoice</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Period</b></td>\n";
                print "</tr>\n";
                while ($row = $result->fetch_assoc()) {
                    $compname = $row['compname'];
                    $custref  = $row['custref'];
                    if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
                    // select first and last invoice dates from inv_headers for each user
                    $sql2 = "SELECT MIN(CONCAT(year,'-',month,'-',day)) AS firstdate, MAX(CONCAT(year,'-',month,'-',day)) AS lastdate FROM inv_headers WHERE custref = '$custref'";
                    if ($result2 = $dbcnx->query($sql2) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $firstdate = $row2['firstdate'];
                        $lastdate  = $row2['lastdate'];
                        if ($firstdate) {
                            // calculate days between first and last invoice dates
                            $totdays += (strtotime($lastdate) - strtotime($firstdate)) / (60 * 60 * 24);
                            $totset++;
                            // calculate years months and days between first and last invoice dates
                            $period = date_diff(date_create($firstdate), date_create($lastdate));
                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "<td>$custref</td>\n";
                            print "<td>$compname</td>\n";
                            print "<td>$firstdate</td>\n";
                            print "<td>$lastdate</td>\n";
                            print "<td>$period->y years, $period->m months, $period->d days</td>\n";
                            print "</tr>\n";
                        }
                    }
                }

                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";

                print "</table>\n";
                $result->free();

                // calculate average period
                $average = floor($totdays / $totset);

                print "<p align=\"center\">Average days between first and last invoice dates: $average Days</p>\n";
                // approximate years month and days
                print "<p align=\"center\">Approximately " . floor($average / 365) . " years, " . floor(($average % 365) / 30) . " months, " . ($average % 30) . " days</p>\n";
                
                
            } else {
                print "<p align=\"center\"><b>No customers found</b></p>\n";
            }

            print "<p align=\"center\">&nbsp;</p>\n";

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
    print "<p align=\"center\"><a href=\"acc-periods-list.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
