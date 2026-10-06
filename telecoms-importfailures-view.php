<?php
$page = "Telecoms";
$subpage = "Failed Calls";
$subsubpage = "All";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Import Failures</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 20;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			$sql = "SELECT * FROM TelCallFailures ORDER BY callyear, callmonth, callday, calltime LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Extension</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Called Num</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Duration</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Charge</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Reason</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id           = $row['ID'];
					$callyear     = $row['callyear'];
					$callmonth    = $row['callmonth'];
					$callday      = $row['callday'];
					$calltime     = $row['calltime'];
					$custref      = $row['custref'];
					$extention    = $row['extention'];
					$numberdialed = $row['numberdialed'];
					$callduration = $row['callduration'];
					$totalcharge  = $row['totalcharge'];
					$failreason   = $row['failreason'];

					if (!$sitename)  { $sitename = "&nbsp;"; }
					if (!$extention) { $extention = "&nbsp;"; }
					if (!$numberdialed) { $numberdialed = "&nbsp;"; }
					if ($custref) { $custref = "DP" . $custref; } else { $custref = "&nbsp;"; }
					$calldate = "$callday/$callmonth/$callyear";
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$calldate</a></td>\n";
					print "	<td>$calltime</td>\n";
					print "	<td>$custref</td>\n";
					print "	<td>$extention</td>\n";
					print "	<td>$numberdialed</td>\n";
					print "	<td>$callduration</td>\n";
					print "	<td align=\"right\">$totalcharge</td>\n";
					print "	<td>$failreason</td>\n";
					print "	<td align=\"center\"><a href=\"telecoms-failurepost.php?id=$id&amp;pa=$pa\">Post</a></td>\n";
					print "	<td align=\"center\"><a href=\"telecoms-failuredelete.php?id=$id&amp;pa=$pa\">Delete</a></td>\n";
					print "</tr>\n";
				}

				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"10\">&nbsp;</td>\n";
                print "</tr>\n";

				print "</table>\n";

				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$qString ="";
				$query   = "SELECT COUNT(*) AS numrows FROM TelCallFailures";
				$result  = $dbcnx->query($query);
				$row     = $result->fetch_array(MYSQLI_ASSOC);
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
				print "<p align=\"center\"><b>No Failed Calls</b></p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
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
