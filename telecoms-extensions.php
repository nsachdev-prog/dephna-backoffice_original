<?php
$page = "Telecoms";
$subpage = "Extensions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Virtual Extensions</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# setup page number
			$rowsPerPage = 100;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
			if ($lo) { $search[] = "sitename = '$lo'"; }
			if ($_REQUEST['ex']) { $ex = $_REQUEST['ex']; }
			if ($ex) { $search[] = "extention LIKE '%$ex%'"; }

			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"telecoms-extensions.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Site</b></td>\n";
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

			print "		<td align=\"right\"><b>&nbsp;Ext</b></td>\n";
			print "		<td><input type=\"text\" name=\"ex\" value=\"$ex\" class=\"element_small\" /></td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT * FROM telecoms $search ORDER BY ABS(extention) LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Extension</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Customer</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Notes</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$sitename  = $row['sitename'];
					$extention = $row['extention'];
					$custref   = $row['custref'];
					$custname  = $row['custname'];
					$details   = $row['details'];
					$notes     = $row['notes'];
					if (!$sitename)  { $sitename = "&nbsp;"; }
					if (!$extention) { $extention = "&nbsp;"; }
					if (!$ddi)       { $ddi = "&nbsp;"; }
					if (!$custname)  { $custname = "&nbsp;"; }
					if (!$details)   { $details = "&nbsp;"; }
					if ($notes) {
						$notes = stripslashes($notes);
						if (strlen($notes) > 18) { $notes = substr($notes, 0, 15) . "..."; }
					} else {
						$notes = "&nbsp;";
					}
					if ($custref) { $custref = "DP" . $custref; } else { $custref = "&nbsp;"; }
					if (strlen($custname) > 18) { $custname = substr($custname, 0, 15) . "..."; }
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$extention</td>\n";
					print "	<td>$custref</td>\n";
                    print "	<td>$custname</td>\n";
                    print "	<td>$sitename</a></td>\n";
					print "	<td>$details</td>\n";
					print "	<td>$notes</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"telecoms-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"telecoms-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"9\">&nbsp;</td>\n";
                print "</tr>\n";

				print "</table>\n";

				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$qString ="lo=$lo&amp;di=$di&amplex=$ex";
				$query   = "SELECT COUNT(*) AS numrows FROM telecoms $search";
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
				print "<p align=\"center\"><a href=\"telecoms-addedit.php\">Add New Extension</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Extension</p>\n";
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
