<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Callouts";
$ty = $_REQUEST['ty'];
$ob = $_REQUEST['ob'];
if (!$ty) { $ty = "O"; }
if (!$ob) { $ob = "D"; }
if ($ty == "O") { $subpage = "Ongoing"; $search = "a.cleared = '0'"; }
else { $subpage = "Cleared"; $search = "a.cleared > '0'"; }
if ($ob == "U") {
	$orderby = "ORDER BY sitename, a.unitname, a.datetime DESC";
} else {
	$orderby = "ORDER BY a.datetime DESC";
}
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM CALLOUTS</p>\n";

	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-callouts.php\">Engineer Callouts</a> | ";
	print "<span class=\"active\">Client Callouts</span> | ";
	print "<a href=\"coldrooms-warnings-lights.php\">Light Warnings</a> | ";
	print "<a href=\"coldrooms-warnings-doors.php\">Door Warnings</a>";
	print "</p>\n";

	print "<p align=\"center\">";
	print "<a href=\"coldrooms-clientcallouts-list.php?ty=O\">"; if ($ty == "O") { print "<span class=\"active\">"; } print "Ongoing</a> | ";
	print "<a href=\"coldrooms-clientcallouts-list.php?ty=C\">"; if ($ty == "C") { print "<span class=\"active\">"; } print "Cleared</a>";
	print "</p>";

	print "<p align=\"center\">";
	print "<a href=\"coldrooms-clientcallouts-list.php?ty=$ty&amp;ob=D\">"; if ($ob == "D") { print "<span class=\"active\">"; } print "By Date</a> | ";
	print "<a href=\"coldrooms-clientcallouts-list.php?ty=$ty&amp;ob=U\">"; if ($ob == "U") { print "<span class=\"active\">"; } print "By Unit</a>";
	print "</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// setup page number
		$rowsPerPage = 20;
		$maxPageNums = 8;
		$pageNum = 1;
		if($_GET['pa']) { $pageNum = $_GET['pa']; }
		$offset = ($pageNum - 1) * $rowsPerPage;

		if ($ty == "C") {
			$limit = "LIMIT $offset, $rowsPerPage";
		} else {
			$limit = "";
		}

		$qString ="ty=$ty&amp;ob=$ob";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			// Cold Room Client Callouts
			print "<p align=\"center\"><span class=\"larger\">$subpage Client Callouts</span></p>\n";
			
			$sql = "SELECT a.*, s.title AS sitename, u.compname
					FROM ClientCallouts AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					LEFT JOIN users AS u ON u.custref = a.custref 
					WHERE $search
					$orderby
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>R</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Attending</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				if ($ty == "O") {
					print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				}
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$ccoid       = $row['ID'];
					$datetime    = $row['datetime'];
					$siteid      = $row['siteid'];
					$sitename    = $row['sitename'];
					$unitid      = $row['unitid'];
					$unitname    = $row['unitname'];
					$custref     = $row['custref'];
					$compname    = $row['compname'];
					$reminders   = $row['reminders'];
					$attendingdatetime = $row['attendingdatetime'];
					$cleared     = $row['cleared'];

					list($date,$time) = explode(" ",$datetime);
					list($y,$m,$d) = explode("-",$date);
					$datetime = "$d-$m-$y $time";

					if ($attendingdatetime) {
						list($date,$time) = explode(" ",$attendingdatetime);
						list($y,$m,$d) = explode("-",$date);
						$attendingdatetime = "$d-$m-$y $time";
					} elseif ($cleared) {
						$attendingdatetime = "Not set";
					} else {
						$attendingdatetime = "Set";
					}
					$colspan = 7;

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$datetime</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$reminders</td>\n";
					if ($cleared) {
						print "	<td>$attendingdatetime</td>\n";
					} else {
						print "	<td><a href=\"coldrooms-clientcallouts-attending.php?ccoid=$ccoid\">$attendingdatetime</a></td>\n";
					}
					print "	<td><a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid&amp;ty=$ty&amp;ob=$ob\">View</a></td>\n";
					if ($ty == "O") {
						$colspan++;
						print "	<td><a href=\"coldrooms-clientcallouts-clear.php?ccoid=$ccoid&amp;ty=$ty&amp;ob=$ob\">Clear</a></td>\n";
					}
					print "</tr>\n";
				}

				print "<tr>\n";
				print "<td colspan=\"$colspan\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				if ($ty == "C") {
					// pagination block
					$self  = $_SERVER['PHP_SELF'];
					$query = "SELECT COUNT(*) AS numrows FROM ClientCallouts AS a WHERE $search";
					$result = $dbcnx->query($query);
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
								$first = " <a href=\"$self?pa=1&amp;$qString&amp;ac=$ac\">1</a> ... ";
							}
							$end = $start+$maxPageNums;
							if ($end > $maxPage-2) {
								$end = $maxPage;
								$start = $end-$maxPageNums;
							} else {
								$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString&amp;ac=$ac\">$maxPage</a> ";
							}
						} else {
							$start = 1;
							$end = $maxPage;
						}

						for($pa = $start; $pa <= $end; $pa++) {
							if ($pa == $pageNum) {
								$nav .= " <span class=\"orangeText\">$pa</span> ";
							} else {
								$nav .= " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">$pa</a> ";
							}
						}

						if ($pageNum > 1) {
							$pa  = $pageNum - 1;
							$prev  = " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Prev</a> |";
						} else {
							$prev  = '&nbsp;';
						}
						if ($pageNum < $maxPage) {
							$pa = $pageNum + 1;
							$next = "| <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Next</a> ";
						} else {
							$next = '&nbsp;';
						}
						print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
					}
					// pagination end
				}

			} else {
				print "<p align=\"center\">None Found</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
		}
		print "<p align=\"center\"><a href=\"coldrooms-clientcallouts-addedit.php\">Start New Client Callout</a></p>\n";

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
