<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	print "<div id=\"LoadingOnClick\">\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Meters On Gateways</p>\n";
	print "<p align=\"center\"><a href=\"utilities-collectors-view.php\">Go To Manual Meters</a></p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 48;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			if ($_REQUEST['siteid'] != "All") { $siteid = $_REQUEST['siteid']; }
			if ($siteid) { $search[] = "m.siteid = '$siteid'"; }
			if ($_REQUEST['comm'] != "All") { $comm = $_REQUEST['comm']; }
			if ($comm) { $search[] = "m.commodity = '$comm'"; }
			if ($_REQUEST['gid'] != "All") { $gid = $_REQUEST['gid']; }
			if ($gid) { $search[] = "m.gatewayid = '$gid'"; }

			if ($search) { $search = "WHERE " . implode(" AND ", $search); }
			$qString = "siteid=$siteid&amp;comm=$comm&amp;gid=$gid";

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"utilities-meters-list.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"siteid\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT ID, title FROM sites ORDER BY title";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$thissiteid = $row['ID'];
					$thistitle  = $row['title'];
					print "		<option value=\"$thissiteid\""; if ($siteid == $thissiteid) { print " SELECTED"; } print ">$thistitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			if ($siteid) {

				$sql = "SELECT commodity FROM UtlCommodities GROUP BY commodity ORDER BY commodity";
				$commsresult = $dbcnx->query($sql);
				if (($commsresult) && ($commsresult->num_rows)) {
					print "		<td align=\"right\"><b>Commodity</b></td>\n";
					print "		<td><select name=\"comm\" class=\"element_small\">\n";
					print "		<option value=\"All\">All</option>\n";
					while ($commsrow = $commsresult->fetch_assoc()) {
						$thiscomm = $commsrow['commodity'];
						print "		<option value=\"$thiscomm\""; if ($thiscomm == $comm) { print " SELECTED"; } print ">$thiscomm</option>\n";
					}
					print "		</select>";
					print "		</td>\n";
				}

				$sql = "SELECT ID, title FROM UtlGateways WHERE siteid = '$siteid' ORDER BY title";
				$gwsresult = $dbcnx->query($sql);
				if (($gwsresult) && ($gwsresult->num_rows)) {
					print "		<td align=\"right\"><b>Gateway</b></td>\n";
					print "		<td><select name=\"gid\" class=\"element_small\">\n";
					print "		<option value=\"All\">All</option>\n";
					while ($gwsrow = $gwsresult->fetch_assoc()) {
						$thisgw = $gwsrow['ID'];
						$thistitle = $gwsrow['title'];
						print "		<option value=\"$thisgw\""; if ($thisgw == $gid) { print " SELECTED"; $gtitle = $thistitle; } print ">$thistitle</option>\n";
					}
					print "		</select>";
					print "		</td>\n";
				}
			}

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form><br />\n";

			if ($siteid AND ($comm OR $gid)) {
				if ($comm) { $disp = $comm; } else { $disp = $gtitle; }
				print "<p align=\"center\"><a href=\"utilities-meters-readall.php?siteid=$siteid&amp;comm=$comm&amp;gid=$gid&amp;pa=$pageNum\" class=\"displayloader\">Read All $disp Meters</a></p>\n";
			}

			$sql = "SELECT m.*, g.title AS gateway, t.title AS metertype, s.title AS sitename
					FROM UtlMeters AS m
					LEFT JOIN UtlGateways AS g ON g.ID = m.gatewayid
					LEFT JOIN UtlTypes AS t ON t.ID = m.typeid
					LEFT JOIN sites AS s ON s.ID = m.siteid
					$search 
					ORDER BY sitename, unitname
					LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Gateway</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Device ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Meter Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Notes</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"32px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$gateway   = $row['gateway'];
					$deviceid  = $row['deviceid'];
					$metertype = $row['metertype'];
					$unitname  = $row['unitname'];
					$commodity = $row['commodity'];
					$custref   = $row['custref'];
					$notes     = $row['notes'];

                    if (strlen($notes) > 20) {
                        $notes = substr($notes, 0, 20);
                    }

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</a></td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$commodity</td>\n";
					print "	<td>$gateway</td>\n";
					print "	<td>$deviceid</td>\n";
					print "	<td>$metertype</td>\n";
					print "	<td>$notes</td>\n";
					print "	<td align=\"center\"><a href=\"utilities-meters-read.php?id=$id&amp;siteid=$siteid&amp;comm=$comm&amp;gid=$gid&amp;pa=$pageNum\" class=\"displayloader\">Read</a></td>\n";
					print "	<td align=\"center\"><a href=\"utilities-meters-history.php?mid=$id&amp;siteid=$siteid&amp;comm=$comm&amp;gid=$gid&amp;lpa=$pageNum\">History</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"utilities-meters-addedit.php?id=$id&amp;siteid=$siteid&amp;comm=$comm&amp;gid=$gid&amp;pa=$pageNum\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"utilities-meters-delete.php?id=$id&amp;siteid=$siteid&amp;comm=$comm&amp;gid=$gid&amp;pa=$pageNum\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"11\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self = $_SERVER['PHP_SELF'];
				$query   = "SELECT COUNT(*) AS numrows FROM UtlMeters AS m $search";
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
				print "<p align=\"center\"><a href=\"utilities-meters-addedit.php?pa=$pageNum\">Add New Meter</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Meter</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Wait! Collecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
