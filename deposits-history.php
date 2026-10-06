<?php
$page = "Deposits";
$subpage = "Deposit History";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DEPOSITS</p>\n";
	if ($accessRights[$page]) {

		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			# setup page number
			$rowsPerPage = 30;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			# get filtering if any
			if (($_REQUEST['custref']) AND ( $_REQUEST['custref'] != "All")) { $custref = $_REQUEST['custref']; }
			if ($custref) { $search = "h.custref='$custref' AND"; }
			$qString = "custref=$custref";

			# filter form
			print "<form name=\"filterform\" method=\"post\" action=\"deposits-history.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			# get customer list
			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);
			if ($usersresult->num_rows) {
				print "	<td align=\"right\">Customer</td>\n";
				print "	<td><select name=\"custref\" class=\"element_medium\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($row = $usersresult->fetch_assoc()) {
					$compname = $row['compname'];
					$thiscustref = $row['custref'];
					print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
			}
			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT 	l.details2, l.linetotal,
							h.txtype, h.invnumber, h.year, h.month, h.day, h.custref,
							c.compname
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON l.hid = h.ID
						LEFT JOIN users AS c ON h.custref = c.custref
						WHERE $search l.unittype = 'Deposit'
						ORDER BY h.year DESC, h.month DESC, h.day DESC
						LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>T</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Number</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$year   = $row['year'];
					$month  = $row['month'];
					$day    = $row['day'];
					$txtype = $row['txtype'];
					$invnum = $row['invnumber'];
					$amount = $row['linetotal'];
					$details = $row['details2'];
					$compname = $row['compname'];
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$day/$month/$year</td>\n";
					print "	<td>$txtype</td>\n";
					print "	<td>$invnum</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$details</td>\n";
					print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"6\" align=\"right\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
					
				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$sql = "SELECT 	COUNT(*) AS numrows
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON l.hid = h.ID
						WHERE $search l.unittype = 'Deposit'
				";
				$result = $dbcnx->query($sql);
				$row = $result->fetch_assoc();
				$numrows = $row['numrows'];
				$maxPage = ceil($numrows/$rowsPerPage);
				if ($maxPage > 1) {
					$nav  = '';

					if ($maxPage > $maxPageNums+2) {
						$start = ceil($pageNum-$maxPageNums/2);
						if ($start < 3) {
							$start = 1;
						} else {
							$first = " <a href=\"$self?pa=1&amp;$qString\">1</a> ... ";
						}
						$end = $start+$maxPageNums;
						if ($end > $maxPage-2) {
							$end = $maxPage;
							$start = $end-$maxPageNums;
						} else {
							$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString\">$maxPage</a> ";
						}
					} else {
						$start = 1;
						$end = $maxPage;
					}

					for($pa = $start; $pa <= $end; $pa++) {
						if ($pa == $pageNum) {
							$nav .= " <span class=\"orangeText\">$pa</span> ";
						} else {
							$nav .= " <a href=\"$self?pa=$pa&amp;$qString\">$pa</a> ";
						}
					}

					if ($pageNum > 1) {
						$pa  = $pageNum - 1;
						$prev  = " <a href=\"$self?pa=$pa&amp;$qString\">Prev</a> |";
					} else {
						$prev  = '&nbsp;';
					}
					if ($pageNum < $maxPage) {
						$pa = $pageNum + 1;
						$next = "| <a href=\"$self?pa=$pa&amp;$qString\">Next</a> ";
					} else {
						$next = '&nbsp;';
					}
					print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
				}
				# pagination end
					
			} else {
				print "<p align=\"center\"><b>No Deposits Found</b></p>\n";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
