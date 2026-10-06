<?php
$page = "SRF";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SRF</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# setup page number
			$rowsPerPage = 25;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			if ($accessRights[$page] < 3) { $search[] = "`createdby` = '{$_SESSION['username']}'"; }
			if (($_REQUEST['lo']) && ($_REQUEST['lo'] != "All")) { $lo = $_REQUEST['lo']; $search[] = "`sitename` = '$lo'"; }
			if (($_REQUEST['cu']) && ($_REQUEST['cu'] != "All")) { $cu = $_REQUEST['cu']; $search[] = "`custref` = '$cu'"; }
			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"srf-view.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Site:</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$lresult = $dbcnx->query($sql);
			if (($lresult) && ($lresult->num_rows)) {
				while ($lrow = $lresult->fetch_assoc()) {
					$ltitle  = $lrow['title'];
					print "		<option value=\"$ltitle\""; if ($lo == $ltitle) { print " SELECTED"; } print ">$ltitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td align=\"right\"><b>Cust:</b></td>\n";
			print "		<td><select name=\"cu\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT custref, compname FROM users WHERE level='2' ORDER BY compname";
			$cresult = $dbcnx->query($sql);
			if (($cresult) && ($cresult->num_rows)) {
				while ($crow = $cresult->fetch_assoc()) {
					$thiscustref  = $crow['custref'];
					$thiscustname = $crow['compname'];
					print "		<option value=\"$thiscustref\""; if ($cu == $thiscustref) { print " SELECTED"; } print ">$thiscustname</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			print "<p align=\"center\">";
			print "<a href=\"srf2-list.php\">With Signed Agreement | </a>";
			print "<span class=\"redtext\">Without Signed Agreement</span>";
			print "</p>\n";

			print "<p align=\"center\">";
			print "These SRFs are invoiced at the end of the month in the invoice run.<br />";
			print "</p>";

			$sql = "SELECT * FROM `srf` $search ORDER BY `ayear` DESC, `amonth` DESC, `aday` DESC LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Created</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Approved</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>VAT</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Created By</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
				print "</tr>\n";

				$cyear = date("Y");
				$cmonth = date("m");
				$cmy = $cyear.$cmonth;

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$ayear     = $row['ayear'];
					$amonth    = $row['amonth'];
					$aday      = $row['aday'];
					$sitename  = $row['sitename'];
					$custname  = $row['custname'];
					$details   = $row['details'];
					$amount    = $row['amount'];
					$vat       = $row['vat'];
					$createdby = $row['createdby'];
					$created   = "$day/$month/$year";
					if ($ayear) {
						$approved = "$aday/$amonth/$ayear";
					} else {
						$approved = "";
					}
					$amy = $ayear.$amonth;
					if (strlen($custname) > 12) { $custname = substr($custname, 0, 9) . "..."; }
					if (strlen($details) > 20)  { $details  = substr($details, 0, 17)  . "..."; }
					$amount = number_format($amount,2);
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$created</td>\n";
					if ($approved) {
						print "	<td>$approved</td>\n";
					} else {
						print "	<td>&nbsp;</td>\n";
					}
					print "	<td>$custname</a></td>\n";
					print "	<td>$sitename</a></td>\n";
					print "	<td>$details</td>\n";
					print "	<td align=\"right\">$amount</td>\n";
					print "	<td>$vat</td>\n";
					print "	<td>$createdby</td>\n";
					if (!$approved) {
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\"><a href=\"srf-approve.php?id=$id&amp;ty=app\">Appr</a></td>\n";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Approve</span></td>\n";
						}
					} else {
						if ($amy == $cmy) {
							if ($accessRights[$page] > 2) {
								print "	<td align=\"center\"><a href=\"srf-approve.php?id=$id&amp;ty=unapp\">Unap</a></td>\n";
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Unappr</span></td>\n";
							}
						} else {
							print "	<td align=\"center\">&nbsp;</td>\n";
						}
					}
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"srf-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"srf-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$qString ="lo=$lo&amp;cu=$cu";
				$query   = "SELECT COUNT(*) AS numrows FROM srf $search";
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
				print "<p align=\"center\"><a href=\"srf-addedit.php\">Add New SRF</a> (Without Signed Agreement)</p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New SRF</p>\n";
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
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
