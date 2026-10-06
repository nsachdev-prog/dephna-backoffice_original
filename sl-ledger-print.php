<?php
$page = "Sales_Ledger";
include('settings.php');
include('session.php');
?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Sales_Ledger - Bank Rec</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
    <body style="background:#fff">
<?php
if ($_SESSION['user']) {
    print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	if ($accessRights[$page]) {
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }

		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT * FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$username     = $row['username'];
					$custref      = $row['custref'];
					$compname     = $row['compname'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];

					print "<p class=\"larger\" align=\"center\">$compname</p>\n";

					$currentmonth   = date("Ym");
					$onemonthago    = date("Ym",strtotime("-1 Month"));
					$twomonthsago   = date("Ym",strtotime("-2 Months"));
					$threemonthsago = $twomonthsago . "01";

					// get account aging
					$sql = "SELECT
						SUM(outstanding) AS outstanding,
						SUM(CASE WHEN CONCAT(year,month) = '$currentmonth' THEN outstanding END) as ocurrentmonth,
						SUM(CASE WHEN CONCAT(year,month) = '$onemonthago' THEN outstanding END) as oonemonthago,
						SUM(CASE WHEN CONCAT(year,month) = '$twomonthsago' THEN outstanding END) as otwomonthsago,
						SUM(CASE WHEN CONCAT(year,month,day) < '$threemonthsago' THEN outstanding END) as othreemonthsago
						FROM inv_headers
						WHERE custref = '$custref'
					";
					if (($result = $dbcnx->query($sql) AND ($result->num_rows))) {
						$row = $result->fetch_assoc();
						$outstanding     = $row['outstanding'];
						$ocurrentmonth   = $row['ocurrentmonth'];
						$oonemonthago    = $row['oonemonthago'];
						$otwomonthsago   = $row['otwomonthsago'];
						$othreemonthsago = $row['othreemonthsago'];
					}
					if (!$outstanding)     { $outstanding = "0.00"; }
					if (!$ocurrentmonth)   { $ocurrentmonth = "0.00"; }
					if (!$oonemonthago)    { $oonemonthago = "0.00"; }
					if (!$otwomonthsago)   { $otwomonthsago = "0.00"; }
					if (!$othreemonthsago) { $othreemonthsago = "0.00"; }

					print "<table width=\"90%\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";

					print "<td align=\"left\" valign=\"top\" width=\"50%\">";

					print "<table cellspacing=\"0px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td colspan=\"2\"><b>Contact Details</b></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Cust Ref</td>\n";
					print "<td>DP$custref</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Contact Name</td>\n";
					print "	<td>$username</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Telephone 1</td>\n";
					print "	<td>$telephone</td>";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td>Telephone 2</td>\n";
					print "	<td>$telephone2</td>";
					print "</tr>\n";
					print "</table>";

					print "</td>\n";
					print "<td align=\"right\" width=\"50%\">";

					print "<table align=\"right\" cellspacing=\"0px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td colspan=\"2\" align=\"right\"><b>Outstanding</b></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>Total</td>\n";
					print "<td align=\"right\">$outstanding</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>This Month</td>\n";
					print "<td align=\"right\">$ocurrentmonth</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Last Month</td>\n";
					print "<td align=\"right\">$oonemonthago</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Two Months</td>\n";
					print "<td align=\"right\">$otwomonthsago</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Older</td>\n";
					print "<td align=\"right\">$othreemonthsago</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";

					# get results
					$sql = "
						SELECT h.ID, h.txtype, h.invnumber, h.year, h.month, h.day, h.invtotal, h.outstanding,
								e.type AS slextype
						FROM inv_headers AS h
						LEFT JOIN sl_extra AS e ON h.ID = e.hid
						WHERE h.custref = '$custref'
						ORDER BY h.year DESC, h.month DESC, h.day DESC
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
						if ($custref == "21443") {
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">&nbsp;</td>\n";
						}
						print "</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"R"=>"Receipt",
							"X"=>"Unknown"
						);

						$slexTypes = array(
							"RC" => "Returned Cheque",
							"BD" => "Bounced DD",
							"DR" => "Deposit Repayment"
						);

						while ($row = $result->fetch_assoc()) {
							$hid	   = $row['ID'];
							$txtype	   = $row['txtype'];
							$invnumber = $row['invnumber'];
							$year      = $row['year'];
							$month     = $row['month'];
							$day       = $row['day'];
							$invtotal  = $row['invtotal'];
							$outstanding = $row['outstanding'];
							$slextype = $row['slextype'];

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$year-$month-$day</td>\n";
							if ($slextype) {
								print "<td>{$slexTypes[$slextype]}</td>\n";
							} else {
								print "<td>{$types[$txtype]}</td>\n";
							}
							print "	<td>$invnumber</td>\n";
							print "	<td align=\"right\">$invtotal</td>\n";
							print "	<td align=\"right\">$outstanding</td>\n";
							if ($txtype == "I" OR $txtype == "C") {
								print "	<td width=\"40px\" align=\"center\"><a href=\"customer-pdf-invoice.php?inv=$invnumber\" target=\"_blank\">View</a></td>\n";
							} elseif ($txtype == "R" AND $invnumber > 12876) { // we don't have allocations for older receipts
								print "	<td width=\"40px\" align=\"center\"><a href=\"sl-receipt-details.php?id=$id&hid=$hid&$qString\">View</a></td>\n";
							} elseif ($txtype == "P" AND $invnumber > 15496) {
								print "	<td width=\"40px\" align=\"center\"><a href=\"sl-payment-details.php?id=$id&hid=$hid&$qString\">View</a></td>\n";
							} else {
								print "	<td width=\"40px\">&nbsp;</td>\n";
							}
							if ($custref == "21443") {
								if (($txtype == "R") AND ($outstanding < 0)) {
									print "	<td width=\"50px\"><i>Transfer</i></td>\n";
								} else {
									print "	<td width=\"50px\">&nbsp;</td>\n";
								}
								$colspan = 7;
							} else {
								$colspan = 6;
							}
							print "</tr>\n";
						}
						print "<tr>\n";
						print "	<td colspan=\"$colspan\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\"><b>No Invoices Founnd.<br></b>".$dbcnx->error."</p>\n";
					}

					# list outstanding deposit payments
					$thismonth = date("Ym");
					$sql = "
						SELECT * FROM deposits
						WHERE custref = '$custref' AND CONCAT(year,month)>='$thismonth'
						ORDER BY year DESC, month DESC
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows))	{
						print "<p align=\"center\"><b>Outstanding Deposit Payments</b></p>\n";
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td class=\"colHead\">Due</td>\n";
						print "	<td class=\"colHeadEnd\" align=\"right\">Amount</td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$id	       = $row['ID'];
							$month     = $row['month'];
							$year      = $row['year'];
							$amount    = $row['amount'];

							print "<tr>\n";
							print "	<td class=\"col\">$year/$month</td>\n";
							print "	<td class=\"colEnd\" align=\"right\">$amount</td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
					}

				} else {
					$resultMessage = "Could not find customer<br />" . $dbcnx->error;
				}
				$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		} else {
			$resultMessage = "Page called without user id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
}
?>
    </body>
</html>
