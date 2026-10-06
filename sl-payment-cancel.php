<?php
$page = "Sales_Ledger";
$subpage = "Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
		if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
		if ($_REQUEST['cancel'])  { $cancel  = $_REQUEST['cancel']; }
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
		if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref      = $row['custref'];
					$compname     = $row['compname'];

					print "<p align =\"center\" class=\"larger\">$compname</p>\n";
					print "<p align =\"center\"><b>Cancel Payment</b></p>\n";

					$sql = "SELECT * FROM inv_headers WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['invnumber'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['invtotal'];
						$outstanding = $row['outstanding'];
						$txndate = "$d-$m-$y";
						$txntotal = number_format($txntotal,2);
						$outstanding = number_format($outstanding,2);

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td>Payment</td>\n";
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

						if ($cancel) {

							// cancel payment
							$sql = "UPDATE inv_headers SET 
										invtotal='0.00',
										outstanding='0.00' 
									WHERE ID='$hid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
								$resultMessage .= "Payment Cancelled<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cancelled','$compname Payment $txnnum','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\">Could NOT cancel payment.</span><br>" . $dbcnx->error;
							}

							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}

						} elseif ($txntotal != $outstanding) {
							$resultMessage .= "<b>Payment Allocataed.</b><br>Please undo allocation first.<br>";

						} else {
							print "<p class=\"centered\"><b>Are You Sure?</b></p>";
							print "<p class=\"centered\">";
							print "	<a href=\"sl-payment-cancel.php?id=$id&amp;hid=$hid&amp;cancel=1&amp;$qString\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"sl-payment-details.php?id=$id&amp;hid=$hid&amp;$qString\">No DON'T Cancel</a>";
							print "</p>\n";
						}

					} else {
						$resultMessage = "Payment Not Found<br />" . $dbcnx->error;
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
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&$qString\">Return</a></p>\n";
}
include('footer.php');

?>
