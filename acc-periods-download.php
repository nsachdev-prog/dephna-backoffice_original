<?php
$page = "Accounts";
$subpage = "Download";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 1) {

        $id = $_REQUEST['id'];

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";

            // get period dates and manual figures
            $sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $row = $result->fetch_assoc();
                $period_start  = $row['period_start'];
                $period_end    = $row['period_end'];

                $txt_st = date("jS F Y",strtotime("$period_start"));
                $txt_en = date("jS F Y",strtotime("$period_end"));

                $wages = 'on';
                $dividends = 'on';
                $paye = 'on';
                $vat = 'on';
                $intercompany = 'on';
                $pledgers = 'on';
                $bankcharges = 'on';
                $personalincome = 'on';
                $personaltax = 'on';
                $donations = 'on';
                $deposits = 'on';
                $costcentres = 'on';

                print "<p align=\"center\"><span class=\"larger\">Download Accounts</span><br><b>For The Period</b><br>$txt_st to $txt_en</p>\n";

                print "<form name=\"addeditform\" method=\"post\" action=\"acc-periods-download-do.php\">\n";
                print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                
                print "<tr>\n";
                print "	<td>Wages</td>\n";
                print "	<td><input type=\"checkbox\" name=\"wages\""; if ($wages) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Dividends</td>\n";
                print "	<td><input type=\"checkbox\" name=\"dividends\""; if ($dividends) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Paye &amp; NI</td>\n";
                print "	<td><input type=\"checkbox\" name=\"paye\""; if ($paye) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Vat Payments &amp; Receipts</td>\n";
                print "	<td><input type=\"checkbox\" name=\"vat\""; if ($vat) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Intercompany Transfer NRS and Dephna Capital</td>\n";
                print "	<td><input type=\"checkbox\" name=\"intercompany\""; if ($intercompany) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Supplier Ledgers</td>\n";
                print "	<td><input type=\"checkbox\" name=\"pledgers\""; if ($pledgers) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Bank Charges &amp; Interest</td>\n";
                print "	<td><input type=\"checkbox\" name=\"bankcharges\""; if ($bankcharges) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Personal Income</td>\n";
                //print "	<td><input type=\"checkbox\" name=\"personalincome\""; if ($personalincome) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Personal Tax Paid</td>\n";
                print "	<td><input type=\"checkbox\" name=\"personaltax\""; if ($personaltax) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Donations Made</td>\n";
                print "	<td><input type=\"checkbox\" name=\"donations\""; if ($donations) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Deposit Ledger</td>\n";
                //print "	<td><input type=\"checkbox\" name=\"deposits\""; if ($deposits) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "	<td>Cost Centres</td>\n";
                print "	<td><input type=\"checkbox\" name=\"costcentres\""; if ($costcentres) { print " CHECKED"; } print " /></td>\n";
                print "</tr>\n";

                print "</table>\n";
                
                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                print "</form>\n";

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
    print "<p align=\"center\"><a href=\"acc-periods-list.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
