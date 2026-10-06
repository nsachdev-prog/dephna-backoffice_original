<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bank";
$subpage = "Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
        // get filtering
            $search = array();
            $search[]  = "BINARY p.txtype = 'P'";
            if ($_REQUEST['cc'] AND $_REQUEST['cc'] != "All") { $cc = $_REQUEST['cc']; } else { $cc = ""; }
            if ($_REQUEST['site'] AND $_REQUEST['site'] != "All") {
                $site = $_REQUEST['site'];
                $search[] = "s.site='$site'";
            }
            if ($_REQUEST['util'] AND $_REQUEST['util'] != "All") {
                $util = $_REQUEST['util'];
                $search[] = "s.utilitytype='$util'";
            }
            if ($cc) { $search[] = "s.costcentre='$cc'";
                $year = $_REQUEST['year'];
                if (!$year) { $year = date("Y"); }
                $search[] = "p.year='$year'";
                $prevyear = $year - 1;
                $nextyear = $year + 1;
                $thismonth = "Year $year";
            } else {
                $date = $_REQUEST['date'];
                if (!$date) { $date = date("m-Y"); } 
                list($m,$y) = explode("-",$date);
                $search[] = "p.year='$y'";
                $search[] = "p.month='$m'";
                $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
                $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
                $thismonth = date("F Y",strtotime("$y-$m-01"));
            }
            
            if ($search) { $search = implode(" AND ", $search);}
            $qString ="cc=$cc&site=$site&util=$util";

            print "<p align=\"center\" class=\"heading\">BANK</p>\n";
            if (!$print) {
                include('bank-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
                // print filter form
                print "<form name=\"addform\" method=\"post\" action=\"bank-payments.php\">\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                // costcenters
                print "<td align=\"right\"><b>Cost Centre</b></td>\n";
                print "<td><select name=\"cc\" class=\"element_small\">\n";
                print "<option value=\"All\">All</option>\n";
                $ccsql = "SELECT ID, title FROM costcentres WHERE ccgroup = '1' ORDER BY title";
                $ccresult = $dbcnx->query($ccsql);
                if ($ccresult->num_rows) {
                    while ($ccrow = $ccresult->fetch_assoc()) {
                        $ccid    = $ccrow['ID'];
                        $cctitle = $ccrow['title'];
                        print "<option value=\"$ccid\""; if ($cc == $ccid) { print " SELECTED"; } print ">$cctitle</option>\n";
                    }
                }
                print "</select>";
                print "</td>\n";
                // site filter dropdown
                print "<td align=\"right\"><b>Site</b></td>\n";
                print "<td><select name=\"site\" class=\"element_small\">\n";
                print "<option value=\"All\">All</option>\n";
                $sitesql = "SELECT ID, title AS sitename FROM sites ORDER BY sitename";;
                $siteresult = $dbcnx->query($sitesql);
                if ($siteresult->num_rows) {
                    while ($siterow = $siteresult->fetch_assoc()) {
                        $siteid    = $siterow['ID'];
                        $sitename  = $siterow['sitename'];
                        print "<option value=\"$siteid\""; if ($_REQUEST['site'] == $siteid) { print " SELECTED"; } print ">$sitename</option>\n";
                    }
                }
                print "</select>";
                print "</td>\n";
                // utilitytype filter dropdown
                print "<td align=\"right\"><b>Type</b></td>\n";
                print "<td><select name=\"util\" class=\"element_small\">\n";
                print "<option value=\"All\">All</option>\n";
                $utilitytypes = array("N/A", "Electricity", "Gas", "Water", "Sewerage", "Telecoms");
                foreach ($utilitytypes as $utype) {
                    print "<option value=\"$utype\""; if ($util == $utype) { print " SELECTED"; } print ">$utype</option>\n";
                }
                print "</select>";
                print "</td>\n";
                print "<td><a href=\"bank-payments.php?date=$date\"><b>All</b></a></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                if ($cc) {
                    // get available years
                    $yearsql = "SELECT DISTINCT year FROM purchases ORDER BY year DESC";
                    $yearresult = $dbcnx->query($yearsql);
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-payments.php?$qString&year=$prevyear\"><b>&lt;&lt;</b></a></td>\n";
                    print "<td style=\"font-size:1.2em\"><b>Year</b></td>\n";
                    print "<td><select name=\"year\" class=\"element_small\">\n";
                    while ($yearrow = $yearresult->fetch_assoc()) {
                        $y = $yearrow['year'];
                        print "<option value=\"$y\""; if ($year == $y) { print " SELECTED"; } print ">$y</option>\n";
                    }
                    print "</select></td>\n";
                    print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                    print "<td style=\"font-size:3.6em\"><a href=\"bank-payments.php?$qString&year="; print date("Y"); print "\"><b>&bull;</b></a></td>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-payments.php?$qString&year=$nextyear\"><b>&gt;&gt;</b></a></td>\n";
                } else {
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-payments.php?$qString&date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
                    print "<td style=\"font-size:1.2em\"><b>Month</b></td>\n";
                    print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
                    print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                    print "<td style=\"font-size:3.6em\"><a href=\"bank-payments.php\"><b>&bull;</b></a></td>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-payments.php?$qString&date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
                }
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";
            }
            print "<p align=\"center\" class=\"heading\">PL Payments - $thismonth</p>\n";

            $sql = "SELECT
                        p.year,
                        p.month,
                        p.day,
                        p.total AS amount,
                        p.dephnaref,
                        c.title AS cctitle,
                        s.suppname AS suppname,
                        s.ID AS suppid
					FROM purchases AS p
					LEFT JOIN suppliers AS s ON p.suppid = s.ID  
                    LEFT JOIN costcentres AS c ON s.costcentre = c.ID
                    WHERE $search
					ORDER BY year, month, day
            ";

			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					
                print "<table width=\"95%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>To</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Cost Centre</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                print "</tr>\n";

                while ($row = $result->fetch_assoc()) {
                    $thisdate = $row['day']."-".$row['month']."-".$row['year'];
                    $dephnaref = $row['dephnaref'];
                    $amount   = $row['amount'];
                    $cctitle  = $row['cctitle'];
                    $suppid   = $row['suppid'];
                    $suppname = $row['suppname'];
                    $totalAmount += $amount;
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    print "<td><a href=\"pl-ledger.php?id=$suppid&from=bankdb\">$suppname</a></td>\n";
                    print "<td>$dephnaref</td>\n";
                    print "<td>$cctitle</td>\n";
                    print "<td align=\"right\">".number_format($amount,2)."</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalAmount,2)."</b></td>\n";
                print "</tr>\n";
                
                print "</table>\n";                

			} else {
				$resultMessage = "<b>No Transactions Found!</b><br>" . $dbcnx->error;
            }
            
            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
            print "	});\n";
            print "</script>\n";
            
			$dbcnx->close();

            // download CSV link
            $csvlink = "bank-payments-dnl-csv.php?$qString&year=$year&date=$date";
            print "<p align=\"center\"><a href=\"$csvlink\"><b>Download as CSV</b></a></p>\n";

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
