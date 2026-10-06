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
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
        
		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT username FROM staff WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$staffname = $row['username'];

					// get filtering
                    $y = $_REQUEST['y'];
                    if (!$y) { $y = date("Y"); } 
                    $prevy = date("Y",strtotime("$y-06-06 -1 Year"));
                    $nexty = date("Y",strtotime("$y-06-06 +1 Year"));

					include('bank-payroll-submenu.php');
                    print "<p align=\"center\">$menustr</p>\n";
					print "<p class=\"larger\" align=\"center\">Payroll - $staffname - $y</p>\n";
					
					// print filter form
                    print "<form name=\"addform\" method=\"post\" action=\"bank-payroll-ledger.php?id=$id\">\n";
                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                    print "<tr>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-payroll-ledger.php?y=$prevy&amp;id=$id\"><b>&lt;&lt;</b></a></td>\n";
                    print "<td style=\"font-size:1.2em\"><b>Year</b></td>\n";
                    print "<td><input type=\"text\" name=\"y\" value=\"$y\" class=\"element_tiny\" /></td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "<td style=\"font-size:3.6em\"><a href=\"bank-payroll-ledger.php?id=$id\"><b>&bull;</b></a></td>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"bank-payroll-ledger.php?y=$nexty&amp;id=$id\"><b>&gt;&gt;</b></a></td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                    print "</form>\n";

					// get results
					$sql = "SELECT h.year, h.month, h.day, l.amount, l.ccid, c.title AS cctitle
							FROM bank_payroll_lines AS l
							LEFT JOIN bank_payroll_headers AS h ON h.ID = l.hid
							LEFT JOIN costcentres AS c ON c.ID = l.ccid
							WHERE l.sid = '$id' AND l.ccid = '66' AND h.year = '$y'
							ORDER BY h.year DESC, h.month DESC, h.day DESC
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
						print "</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						while ($row = $result->fetch_assoc()) {
							$year      = $row['year'];
							$month     = $row['month'];
							$day       = $row['day'];
							$amount    = $row['amount'];
							$cctitle   = $row['cctitle'];

							$total += $amount;

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$day-$month-$year</td>\n";
							print "	<td>$cctitle</td>\n";
							print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
							print "</tr>\n";
						}
						$numrows = $result->num_rows;
                		print "<tr>\n";
						print "<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
						print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                		print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($total,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\"><b>No Payments Found!<br></b>".$dbcnx->error."</p>\n";
					}

				} else {
					$resultMessage = "Could not find customer<br />" . $dbcnx->error;
				}
				$dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
		} else {
			$resultMessage = "Page called without staff id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bank-payroll-ledgers-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
