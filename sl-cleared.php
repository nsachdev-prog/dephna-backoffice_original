<?php
$page = "Sales_Ledger";
$subpage = "Pending";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";
	
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $id     = $_REQUEST['id'];
            $posted = $_POST['posted'];

            if ($id) {

                $sql = "SELECT h.txtype, h.year, h.month, h.day, h.custref, h.invtotal, h.invnumber, u.compname
                        FROM inv_headers AS h
                        LEFT JOIN users AS u ON h.custref = u.custref
                        WHERE h.ID = '$id'
                ";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {

                    $row = $result->fetch_assoc();
                    $year     = $row['year'];
                    $month    = $row['month'];
                    $day      = $row['day'];
                    $custref  = $row['custref'];
                    $amount   = $row['invtotal'];
                    $compname = $row['compname'];
                    $txnnum   = $row['invnumber'];
                    $txtype   = $row['txtype'];

                    if ($txtype == "r") { $headtext = "Receipt"; $newtxtype = "R"; }
                    elseif ($txtype == "p") { $headtext = "Payment"; $newtxtype = "P"; }

                    if ($posted) {

                        $txndate = $_POST['txndate'];
                        list($day,$month,$year) = explode("-",$txndate);
                        $now = time();
    
                        $sql = "UPDATE inv_headers SET
                            txtype='$newtxtype',
                            year='$year',
                            month='$month',
                            day='$day'
                            WHERE ID='$id'
                        ";
                        if ($result = $dbcnx->query($sql)) {
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cleared','Receipt $txnnum DP$custref $amount','$page','$now')");
                            print "<script type=\"text/javascript\">\n";
                            print "	window.location=\"sl-pending.php\"\n";
                            print "</script>\n";
                        } else {
                            $resultMessage = "Could not update transaction<br>" . $dbcnx->error;
                        }
                    }

                    include('sl-submenu.php');
                    print "<p align=\"center\">$menustr</p>\n";
                    print "<p align=\"center\" class=\"larger\">$headtext Cleared Bank</p>\n";

                    if (!$txndate) {
                        $txndate = date("d-m-Y");
                    }

                    print "<form name=\"addform\" method=\"post\" action=\"sl-cleared.php\">\n";
                    print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                    print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                    print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        
                    print "<tr>\n";
                    print "	<td><b>Date</b></td>\n";
                    print "	<td>$day-$month-$year</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td><b>Custref</b></td>\n";
                    print "	<td>DP$custref</td>\n";
                    print "</tr>\n";
                    
                    print "<tr>\n";
                    print "	<td><b>Customer</b></td>\n";
                    print "	<td>$compname</td>\n";
                    print "</tr>\n";
                    
                    print "<tr>\n";
                    print "	<td><b>$headtext</b></td>\n";
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

                    print "	<p align=\"center\"><a href=\"sl-pending.php\">Return</a></p>\n";

                    # print jquery date picker
                    print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
                    print "	});\n";
                    print "</script>\n";

                } else {
                    print "<p align=\"center\">Transaction Not Found!</p>\n";
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
