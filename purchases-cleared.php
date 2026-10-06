<?php
$page = "Purchases";
$subpage = "Pending";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $id     = $_REQUEST['id'];
            $posted = $_POST['posted'];

            if ($id) {

                $sql = "SELECT p.txtype, p.year, p.month, p.day, p.total, p.dephnaref, p.suppid, s.suppname
                        FROM purchases AS p
                        LEFT JOIN suppliers AS s ON p.suppid = s.ID
                        WHERE p.ID = '$id'
                ";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {

                    $row = $result->fetch_assoc();
                    $year     = $row['year'];
                    $month    = $row['month'];
                    $day      = $row['day'];
                    $amount   = $row['total'];
                    $compname = $row['suppname'];
                    $txnnum   = $row['dephnaref'];
                    $txtype   = $row['txtype'];

                    if ($posted) {

                        $txndate = $_POST['txndate'];
                        list($day,$month,$year) = explode("-",$txndate);
                        $txtype = strtoupper($txtype);
                        $now = time();
    
                        $sql = "UPDATE purchases SET
                            txtype='$txtype',
                            year='$year',
                            month='$month',
                            day='$day'
                            WHERE ID='$id'
                        ";
                        if ($result = $dbcnx->query($sql)) {
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cleared','Payment $txnnum Supplier $suppid $amount','$page','$now')");
                            print "<script type=\"text/javascript\">\n";
                            print "	window.location=\"purchases-pending.php\"\n";
                            print "</script>\n";
                        } else {
                            $resultMessage = "Could not update transaction<br>" . $dbcnx->error;
                        }
                    }

                    include('purchases-submenu.php');
                    print "<p align=\"center\">$menustr</p>\n";
                    print "<p align=\"center\" class=\"larger\">Payment Cleared Bank</p>\n";

                    if (!$txndate) {
                        $txndate = date("d-m-Y");
                    }

                    print "<form name=\"addform\" method=\"post\" action=\"purchases-cleared.php\">\n";
                    print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                    print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        
                    print "<tr>\n";
                    print "	<td><b>Date</b></td>\n";
                    print "	<td>$day-$month-$year</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td><b>Supplier</b></td>\n";
                    print "	<td>$compname</td>\n";
                    print "</tr>\n";
                    
                    print "<tr>\n";
                    print "	<td><b>Payment</b></td>\n";
                    print "	<td>$amount</td>\n";
                    print "</tr>\n";

                    if ($ertxndate) { $class = "row-error"; } else { $class = "row"; }
                    print "<tr>\n";
                    print "<td><b>Date Cleared</b></td>\n";
                    print "<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element\" />";
                    if ($ertxndate) { print "&nbsp;<span class=\"redtext\">$ertxndate</span>"; }
                    print "</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"submit\" /></p>\n";

                    print "</form>\n";

                    print "	<p align=\"center\"><a href=\"purchases-pending.php\">Return</a></p>\n";

                    # print jquery date picker
                    print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
                    print "	});\n";
                    print "</script>\n";

                } else {
                    print "<p align=\"center\">Payment Not Found!</p>\n";
                }
            } else {
                print "<p align=\"center\">Page called without ID</p>\n";
            }
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
