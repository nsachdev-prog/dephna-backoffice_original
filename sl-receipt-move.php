<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Sales_Ledger";
$subpage = "Receipt";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
        if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

        if ($_REQUEST['id'])   { $id  = $_REQUEST['id']; }  // custid
        if ($_REQUEST['hid'])  { $hid = $_REQUEST['hid']; } // tx header id (editing)
        if ($_REQUEST['posted']) { $posted = $_REQUEST['posted']; } 

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$oldcustref  = $row['custref'];
					$oldcompname = $row['compname'];

                    print "<p align=\"center\" class=\"larger\">Move Unallocated Receipt</p>\n";

                    $sql = "SELECT * FROM inv_headers WHERE ID = '$hid'";
                    $result = $dbcnx->query($sql);
                    if (($result) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $txnnum   = $row['invnumber'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['invtotal'];
                        $txndate = "$d-$m-$y";
                        $txntotal *=-1;

                        print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td>Receipt</td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Date</td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Amount</td>\n";
						print "<td>$txntotal</td>\n";
						print "</tr>\n";

                        print "</table>\n";

                        if ($posted) {

                            $customer = $_POST['customer'];
                            if ($customer != "Select") {
                                list($custref,$compname) = explode("--",$customer);
                            } else {
                                $ercustomer = "Select Customer"; $e++;
                            }

                            if (!$e) {

                                $sql = "UPDATE inv_headers SET custref = '$custref' WHERE ID = '$hid'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                    $resultMessage = "<b>Receipt Moved</b><br />From: DP$oldcustref - $oldcompname<br />To: DP$custref - $compname";
                                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Moved','Receipt $txntotal DP$oldcustref > DP$custref','$page','$now')");
                                } else {
                                    $resultMessage = "<span class=\"redtext\">Could NOT move receipt.</span><br />" . $dbcnx->error;
                                }

                                if ($rdb) {
                                    include_once("rdb-save.php");
                                    include_once("rdb-mail.php");
                                }

                            }

                        }

                        if (!$resultMessage OR $e) {

                            $sql = "SELECT custref, compname FROM users ORDER BY compname";
                            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                                print "<p align=\"center\">From: DP$oldcustref - $oldcompname</p>\n";

                                print "<form name=\"addform\" method=\"post\" action=\"sl-receipt-move.php?$qString\">\n";
                                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                                print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                                print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";

                                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                                print "<tr>\n";
                                print "	<td align=\"right\"><b>To Customer</b>:</td>\n";
                                print "	<td><select name=\"customer\" class=\"element\">\n";
                                print " <option value=\"Select\">Select</option>\n";
                                while ($row = $result->fetch_assoc()) {
                                    $thiscustref  = $row['custref'];
                                    $thiscompname = $row['compname'];
                                  print "	<option value=\"$thiscustref--$thiscompname\""; if ($custref == $thiscustref) { print " SELECTED"; } print ">$thiscompname</option>\n";
                                }
                                print "	</select>";
                                if ($ercustomer) {
                                    print "&nbsp;<span class=\"redtext\">$ercustomer</span>";
                                }
                                print "</td>\n";
                                print "</tr>\n";

                                print "</table>\n";

                                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                                print "</form>\n";
                            }

                        }

                    } else {
                        $resultMessage = "Receipt Not Found<br />" . $dbcnx->error;
                    }

                } else {
                    $resultMessage = "Customer Not Found!";
                }

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without customer / header ID!";
			}
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
    print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&amp;$qString\">Return</a></p>\n";
}
include('footer.php');

?>
