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
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
        if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

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

					$sql = "SELECT h.invnumber, h.year, h.month, h.day, h.invtotal, h.outstanding, h.crdate, h.crby,
									e.type AS slextype, e.details
							FROM inv_headers AS h
							LEFT JOIN sl_extra AS e ON e.hid = h.ID
							WHERE h.ID = '$hid'
					";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['invnumber'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['invtotal'];
						$outstanding = $row['outstanding'];
						$lastupd  = $row['crdate'];
						$updby    = $row['crby'];
						$slextype = $row['slextype'];
						$details  = $row['details'];
						$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
                            
                        $txndate = "$d/$m/$y";
						$txntotal = number_format($txntotal,2);
						$outstanding = number_format($outstanding,2);

						$slexTypes = array(
							"RC" => "Returned Cheque",
							"BD" => "Bounced DD",
							"DR" => "Deposit Repayment"
						);

						if ($slextype) {
							$heading = $slexTypes[$slextype];
						} else {
							$heading = "Payment";
						}

						print "<p align =\"center\"><b>$heading</b></p>\n";

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Ref</b></td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Date</b></td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Amount</b></td>\n";
						print "<td>$txntotal</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Notes</b></td>\n";
						print "<td>$details</td>\n";
						print "</tr>\n";

                        print "</table>\n";

                        print "<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";

						print "<p align=\"center\">";
						print "<a href=\"sl-payment-addedit.php?id=$id&hid=$hid&$qString\">Edit</a> | ";
						print "<a href=\"sl-payment-cancel.php?id=$id&hid=$hid&$qString\">Cancel</a></p>\n";
                        
					} else {
						$resultMessage = "Payment not found!";
					}

				} else {
                    $resultMessage = "Customer Not Found<br>";
                }

			} else {
				$resultMessage = "Page called without customer or header ID!<br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&$qString\">Return</a>\n";
}
include('footer.php');

?>
