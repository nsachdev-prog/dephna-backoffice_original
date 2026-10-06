<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Debtors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\"><b>Debtors Snapshot</b></p>\n";

            $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
            list($y, $m, $d) = explode('-', $sevenDaysAgo);
            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

            // Current Debtors
            $sql = "SELECT SUM(outstanding) AS outstanding
					FROM inv_headers
                    WHERE outstanding <> 0
			";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $outstanding = $row['outstanding'];
            } else {
                $outstanding = 0;
            }

            // Over 30 Days
            $sql = "SELECT SUM(outstanding) AS outstanding
                    FROM inv_headers
                    WHERE outstanding <> 0
                    AND CONCAT(year, '-', month, '-', day) < '$thirtyDaysAgo'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $over30Days = $row['outstanding'];
            } else {
                $over30Days = 0;
            }

            // Managed Debtors
            $sql = "SELECT SUM(h.outstanding) AS outstanding
                    FROM inv_headers AS h
                    JOIN users AS c ON h.custref = c.custref
                    WHERE h.outstanding <> 0 AND c.ad_managed = 'Yes'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $managed = $row['outstanding'];
            } else {
                $managed = 0;
            }

            // Restricted Debtors
            $sql = "SELECT SUM(h.outstanding) AS outstanding
                    FROM inv_headers AS h
                    JOIN users AS c ON h.custref = c.custref
                    WHERE h.outstanding <> 0 AND c.tim = 'Yes'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $restricted = $row['outstanding'];
            } else {
                $restricted = 0;
            }

            // TermWarning Debtors
            $sql = "SELECT COUNT(*) AS termWarning
                    FROM users
                    WHERE terminationWarning > '$sevenDaysAgo'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $termWarning = $row['termWarning'];
            } else {
                $termWarning = 0;
            }

            // TermNotice Debtors
            $sql = "SELECT COUNT(*) AS termNotice
                    FROM users
                    WHERE terminationNotice > '$sevenDaysAgo'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $termNotice = $row['termNotice'];
            } else {
                $termNotice = 0;
            }


            print "<table width=\"90%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\">&nbsp;</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Over 30 Days</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Restricted</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Managed</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>TermWarning</b></td>\n";
            print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>TermNotice</b></td>\n";
            print "</tr>\n";
            
            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td><b>Current</b></td>\n";
            print "	<td align=\"right\">" . number_format($outstanding, 2) . "</td>\n";
            print "	<td align=\"right\">" . number_format($over30Days, 2) . "</td>\n";
            print "	<td align=\"right\">" . number_format($restricted, 2) . "</td>\n";
            print "	<td align=\"right\">" . number_format($managed, 2) . "</td>\n";
            print "	<td align=\"right\">$termWarning</td>\n";
            print "	<td align=\"right\">$termNotice</td>\n";
            print "</tr>\n";

            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td><b>18-03-2024</b></td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "</tr>\n";

            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td><b>11-03-2024</b></td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "</tr>\n";

            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td><b>04-03-2024</b></td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "</tr>\n";

            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
            print "	<td><b>26-02-2024</b></td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">99,999.99</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "	<td align=\"right\">1</td>\n";
            print "</tr>\n";

            print "</table>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
