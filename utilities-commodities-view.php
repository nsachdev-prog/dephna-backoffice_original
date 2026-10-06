<?php
$page = "Utilities";
$subpage = "Rates";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Rates</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# setup page number
			$rowsPerPage = 25;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			$sql = "SELECT * FROM UtlCommodities ORDER BY sitename, custref, commodity LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Rate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id			= $row['ID'];
					$sitename	= $row['sitename'];
					$custref	= $row['custref'];
					$commodity	= $row['commodity'];
					$rate		= $row['rate'];
					if ($custref != "Default") {
						$sql = "SELECT compname FROM users WHERE custref = '$custref'";
						$custsresult = $dbcnx->query($sql);
						if (($custsresult) && ($custsresult->num_rows)) {
							$custsrow = $custsresult->fetch_assoc();
							$custref = $custsrow['compname'];
						}
					}
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</a></td>\n";
					print "	<td>$custref</td>\n";
					print "	<td>$commodity</td>\n";
					print "	<td>$rate</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"utilities-commodities-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"utilities-commodities-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"6\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";

				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$qString ="";
				$query   = "SELECT COUNT(*) AS numrows FROM UtlCommodities";
				$result  = $dbcnx->query($query);
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
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"utilities-commodities-addedit.php\">Add New Commodity</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Collector</p>\n";
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
