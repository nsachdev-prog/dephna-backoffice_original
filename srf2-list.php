<?php
$page = "SRF";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">Service Request Forms</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 25;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			if ($accessRights[$page] < 3) { $search[] = "s.updby = '{$_SESSION['username']}'"; }
			if (($_REQUEST['lo']) && ($_REQUEST['lo'] != "All")) { $lo = $_REQUEST['lo']; $search[] = "t.title = '$lo'"; }
			if (($_REQUEST['cu']) && ($_REQUEST['cu'] != "All")) { $cu = $_REQUEST['cu']; $search[] = "s.custref = '$cu'"; }
			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"srf2-list.php\">\n";
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
			print "<span class=\"redtext\">With Signed Agreement</span> | ";
			print "<a href=\"srf-view.php\">Without Signed Agreement</a>";
			print "</p>\n";

			print "<p align=\"center\">";
			print "These SRFs produce an agreement that must be signed through Adobe Sign.<br />";
			print "Once signed, and the work is completed, click Confirm to email the invoice.<br />";
			print "</p>";

			$sql = "SELECT s.*, u.compname, t.title
                    FROM srf2 AS s
                    LEFT JOIN users AS u ON u.custref = s.custref
                    LEFT JOIN sites AS t ON t.ID = s.siteid
                    $search
                    ORDER BY `txndate` DESC
                    LIMIT $offset, $rowsPerPage
            ";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Charge</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>VAT</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Created By</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Sent</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Confirmed</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20px\">&nbsp;</td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" width=\"20px\">&nbsp;</td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" width=\"20px\">&nbsp;</td>\n";
				print "</tr>\n";

				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$txndate   = $row['txndate'];
                    $unitname  = $row['unitname'];
                    $custref   = $row['custref'];
					$compname  = $row['compname'];
					$charge    = $row['charge'];
					$vat       = $row['vat'];
                    $updby     = $row['updby'];
                    $agreementID = $row['agreementID'];
                    $confirmed = $row['confirmed'];
                    if ($vat == 'on') { $vat = "Yes"; } else { $vat = "No"; }
                    if ($agreementID) { $sent = "Yes"; } else { $sent = "&nbsp;"; }
					
                    if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$txndate</td>\n";
					print "	<td>$compname</a></td>\n";
					print "	<td>$unitname</a></td>\n";
					print "	<td align=\"right\">$charge</td>\n";
					print "	<td>$vat</td>\n";
                    print "	<td>$updby</td>\n";
                    print "	<td>$sent</td>\n";
                    print "	<td>$confirmed</td>\n";
                    
					if (!$confirmed) {
                        if ($accessRights[$page] > 1) {
                            print "	<td align=\"center\"><a href=\"srf2-addedit.php?id=$id&amp;custref=$custref\">Edit</a></td>\n";
                        } else {
                            print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
                        }
                    } else {
                        print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n"; 
                    }
                    print "	<td align=\"center\"><a href=\"srf2-summary.php?id=$id&amp;custref=$custref\">Sum</a></td>\n";
                    print "	<td align=\"center\"><a href=\"srf2-confirm.php?id=$id&amp;custref=$custref\">Conf</a></td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"srf2-delete.php?id=$id&amp;custref=$custref\">Del</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Del</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"12\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";

				// pagination block
				$self = $_SERVER['PHP_SELF'];
				$qString ="lo=$lo&amp;cu=$cu";
				$query   = "SELECT COUNT(*) AS numrows FROM srf2 $search";
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
				// pagination end

			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"srf2-addedit.php\">Add New SRF</a></p>\n";
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
