<?php
$page = "Bank";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
            
            print "<p align=\"center\" class=\"heading\">BANK</p>\n";
		    include('bank-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Ledgers</p>\n";

            
            $sql = "SELECT ID, title
                    FROM costcentres
                    WHERE ccgroup = '3'
                    ORDER BY title
            ";
            
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
                print "	    <td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\"><b>Last Transaction</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amount</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\">&nbsp;</td>\n";
                print "	</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                while ($row = $result->fetch_assoc()) {
                    $ccid   = $row['ID'];
                    $title  = $row['title'];

                    // get last paid
                    $sql = "SELECT amount, year, month, day, txtype
                            FROM bank
                            WHERE costcentre = '$ccid'
                            ORDER BY year DESC, month DESC, day DESC
                            LIMIT 0,1
                    ";
                    if ($aresult = $dbcnx->query($sql) AND $aresult->num_rows) {
                        $arow   = $aresult->fetch_assoc();
                        $year   = $arow['year'];
                        $month  = $arow['month'];
                        $day    = $arow['day'];
                        $amount = $arow['amount'];
                        $txtype = $arow['txtype'];
                        $date   = "$day-$month-$year";
                    } else {
                        $year   = "&nbsp;";
                        $month  = "&nbsp;";
                        $day    = "&nbsp;";
                        $amount = "&nbsp;";
                        $txtype = "&nbsp;";
                        $date   = "&nbsp;";
                    }

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td><a href=\"bank-ledger.php?ccid=$ccid\">$title</a></td>\n";
                    print "<td>$date</td>\n";
                    print "<td align=\"right\">".number_format($amount,2)."</td>\n";
                    print "<td>$txtype</td>\n";
                    print "</tr>\n";

                }

                print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"4\">&nbsp;</td>\n";
                print "</tr>\n";

                print "</table>\n";
            } else {
                $resultMessage = "<b>Nothing Found!</b>";
            }

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
