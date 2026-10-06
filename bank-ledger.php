<?php
$page = "Bank";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align =\"center\" class=\"heading\">BANK</p>\n";
	if ($accessRights[$page]) {

		if ($_REQUEST['ccid']) { $ccid = $_REQUEST['ccid']; }
		
		if ($ccid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT title FROM costcentres WHERE ID = '$ccid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title = $row['title'];

					$y = $_REQUEST['y'];
                    if (!$y) { $y = date("Y"); } 
                    $prevy = date("Y",strtotime("$y-06-06 -1 Year"));
                    $nexty = date("Y",strtotime("$y-06-06 +1 Year"));

					include('bank-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p class=\"larger\" align=\"center\">Ledger - $title - $y</p>\n";

					// print filter form
                    print "<form name=\"addform\" method=\"post\" action=\"bank-ledger.php?ccid=$ccid\">\n";
                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                    print "<tr>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-ledger.php?y=$prevy&amp;ccid=$ccid\"><b>&lt;&lt;</b></a></td>\n";
                    print "<td style=\"font-size:1.2em\"><b>Year</b></td>\n";
                    print "<td><input type=\"text\" name=\"y\" value=\"$y\" class=\"element_tiny\" /></td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "<td style=\"font-size:3.6em\"><a href=\"bank-ledger.php?ccid=$ccid\"><b>&bull;</b></a></td>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-ledger.php?y=$nexty&amp;ccid=$ccid\"><b>&gt;&gt;</b></a></td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                    print "</form>\n";

					// get results
					$sql = "SELECT ID, year, month, day, amount, dephnaref, bankref, details, txtype, pdf
							FROM bank
							WHERE costcentre = '$ccid' AND year = '$y'
							ORDER BY year DESC, month DESC, day DESC
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Bank Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Dr</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Cr</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width \"35\" >&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width \"35\" >&nbsp;</td>\n";
						print "</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						$numdr = 0;
						$numcr = 0;

						while ($row = $result->fetch_assoc()) {
							$bkid      = $row['ID'];
							$year      = $row['year'];
							$month     = $row['month'];
							$day       = $row['day'];
							$amount    = $row['amount'];
							$txtype    = $row['txtype'];
							$dephnaref = $row['dephnaref'];
							$bankref   = $row['bankref'];
							$details   = $row['details'];
							$pdf       = $row['pdf'];
							
							if ($txtype == "CR" or $txtype == "cr") {
								$credit = abs($amount);
								$debit = "&nbsp;";
								$totalCredit += $credit;
								$numcr++;
							} else {
								$debit = abs($amount);
								$credit = "&nbsp;";
								$totalDebit += $debit;
								$numdr++;
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$day-$month-$year</td>\n";
							print "	<td>$dephnaref</td>\n";
							print "	<td>$bankref</td>\n";
							print "	<td>$details</td>\n";
							// convert to number format
							if (!is_numeric($debit)) { $debit = 0.00; }
							if (!is_numeric($credit)) { $credit = 0.00; }
							print " <td align=\"right\">".number_format($debit,2)."</td>\n";
							print " <td align=\"right\">".number_format($credit,2)."</td>\n";
							print "	<td><a href=\"bank-view.php?id=$bkid&amp;fr=ld&amp;y=$y\">View</a></td>\n";
							if ($pdf) {
								print "	<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">Pdf</a></td>\n";
							} else {
								print "<td>&nbsp;</td>\n";
							}
							print "</tr>\n";
						}
						$numrows = $result->num_rows;
                		print "<tr>\n";
						print "<td style=\"border-top:1px solid #999999\"><b>$numrows | $numdr | $numcr</b></td>\n";
						print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                		print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalDebit,2)."</b></td>\n";
                		print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalCredit,2)."</b></td>\n";
                		print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						$balance = $totalCredit - $totalDebit;

						print "<tr>\n";
						print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                		print "<td style=\"border-top:1px solid #999999\"><b>Balance</b></td>\n";
						if ($balance < 0) {
							print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format(ABS($balance),2)."</b></td>\n";
                			print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
							print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						} else {
							print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                			print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($balance,2)."</b></td>\n";
                			print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						}
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\"><b>No Transactions Found!<br></b>".$dbcnx->error."</p>\n";
					}

				} else {
					$resultMessage = "Could not find cost centre<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without cost centre id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bank-ledgers-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
