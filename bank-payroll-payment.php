<?php
$page = "Bank";
$subpage = "Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
    print "<p align =\"center\" class=\"heading\">BANK</p>\n";
	if ($accessRights[$page]) {
		if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
		if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
        
		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT h.year, h.month, h.day, h.amount, h.ccid, c.title AS cctitle
				  		FROM bank_payroll_headers AS h
                    	LEFT JOIN costcentres AS c ON c.ID = h.ccid
						WHERE h.ID = '$id'
				";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$year   = $row['year'];
                    $month  = $row['month'];
                    $day    = $row['day'];
					$total  = $row['amount'];
					$cctitle = $row['cctitle'];

					include('bank-payroll-submenu.php');
                    print "<p align=\"center\">$menustr</p>\n";
                    print "<p class=\"larger\" align=\"center\">Payroll $cctitle $day-$month-$year</p>\n";

					// get results
					$sql = "SELECT l.amount, s.ID, s.username
							FROM bank_payroll_lines AS l
							LEFT JOIN staff AS s ON s.ID = l.sid
							WHERE hid = '$id'
							ORDER BY s.ID
					";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Staff ID</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Staff Name</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
						print "</tr>\n";

						// row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						while ($row = $result->fetch_assoc()) {
							$sid       = $row['ID'];
							$staffname = $row['username'];
							$amount    = $row['amount'];

							if (!$staffname) {
								$staffname = "Staff Member Deleted";
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$sid</td>\n";
							print "	<td>$staffname</td>\n";
							print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
							print "</tr>\n";
						}
						$numrows = $result->num_rows;
                		print "<tr>\n";
                		print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
						print "	<td colspan=\"2\" align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($total,2)."</b></td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\"><b>Nothing Found!<br></b>".$dbcnx->error."</p>\n";
					}

				} else {
					$resultMessage = "Could not find payment<br />" . $dbcnx->error;
				}
				$dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
		} else {
			$resultMessage = "Page called without payment id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bank-payroll-payments-list.php?y=$y\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
