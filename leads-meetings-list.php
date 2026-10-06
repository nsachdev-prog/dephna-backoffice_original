<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$print = $_REQUEST['print'];
$page = "Leads";
$subpage = "Meetings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">LEADS</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            // setup page number
			$rowsPerPage = 30;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;
            $thisdate = date("Y-m-d 00:00");

			// get filtering if any
            $search[] = "meeting > '$thisdate'";
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['al'] != "All") { $al = $_REQUEST['al']; }
			if ($se) { $search[] = "(t.compname LIKE '%$se%' OR t.fullname LIKE '%$se%' OR t.aemails LIKE '%$se%' OR t.crby LIKE '%$se%' OR t.unittypes LIKE '%$se%')"; }
			if ($al) { $search[] = "t.assignedto = '$al'"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}
			if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
			$qString ="se=$se&amp;al=$al&amp;fr=meet&amp;ty=$ty";

			include('leads-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Meetings Calendar</p>\n";

            // filter form
            print "<form name=\"addform\" method=\"post\" action=\"leads-meetings-list.php?ty=$ty\">\n";
            print "<table class=\"filterBlock\" align=\"center\">\n";
            print "	<tr>\n";

            print "		<td align=\"right\"><b>Search</b></td>\n";
            print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

            print "     <td align=\"right\"><b>Alloc To</b></td>\n";
            print "     <td><select name=\"al\" class=\"element_small\">\n";
            print "		<option>All</option>\n";
            $sql = "SELECT ID, username FROM staff WHERE onsupport = 'on' ORDER BY username";
            $staffresult = $dbcnx->query($sql);
            while ($staffrow = $staffresult->fetch_assoc()) {
                $thisid   = $staffrow['ID'];
                $thisname = $staffrow['username'];
                print "		<option value=\"$thisid\""; if ($thisid == $al) { print " SELECTED"; } print ">$thisname</option>\n";
            }
            print "	</select>";
            print "</td>\n";

            print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

            print "		<td><a href=\"leads-meetings-list.php?ty=$ty\"><b>Reset</b></a></td>\n";

            print "	</tr>\n";
            print "</table>\n";
            print "</form>\n";

			print "<br>\n";

			$sql = "SELECT t.*, s.username AS assigned
                    FROM leads_tickets AS t
                    LEFT JOIN staff AS s ON s.ID = t.assignedto
					$search
					ORDER BY t.meeting
                    LIMIT $offset, $rowsPerPage
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";

				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Subject</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Uts</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>From</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Source</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Asgn To</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$tid        = $row['ID'];
					$ticketno   = $row['ticketno'];
					$fullname   = $row['fullname'];
					$subject    = $row['subject'];
					$from       = $row['aemails'];
                    // $crdate     = $row['crdate'];
                    $crby       = $row['crby'];
                    $assigned   = $row['assigned'];
                    $meeting    = $row['meeting'];

                    list($meetingdate,$meetingtime) = explode(" ",$meeting);
                    list($y,$m,$d) = explode("-",$meetingdate);
                    $meetingdate = "$d-$m-$y";

                    // $crdate = date("d-m-Y",$crdate);

                    if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
					}
					
					if (strlen($from) > 36) { $from = substr($from, 0, 30); }
					if (strlen($subject) > 30) { $subject = substr($subject, 0, 30); }

					// get unit types
                    $utdisp = "";
					if ($row['unittypes']) {
						$unittypes = explode("|",$row['unittypes']);
						foreach ($unittypes AS $unittype) {
							$typeimage = strtolower($unittype).".png";
							$typeimage = str_replace(" ","",$typeimage);
							$utdisp .= "<img src=\"images/$typeimage\" width=\"11px\" height=\"11px\" alt=\"\" />";
						}
                        $utdisp .= "&nbsp;";
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$meetingdate</td>\n";
                    print "	<td>$meetingtime</td>\n";
					print "	<td><a href=\"leads-view.php?tid=$tid&amp;$qString\">$subject</a></td>\n";
					print "	<td>$utdisp</td>\n";
					print "	<td>$from</td>\n";
                    print "	<td>$crby</td>\n";
                    print "	<td>$assigned</td>\n";
                    if ($accessRights[$page] > 2) {
                        print "	<td><a href=\"leads-meeting.php?tid=$tid&amp;$qString\">Update</a></td>\n";
						print "	<td><a href=\"leads-meetings-delete.php?tid=$tid&amp;$qString\">Delete</a></td>\n";
                    } else {
                        print "	<td><span class=\"noAccess\">Update</span></td>\n";
						print "	<td><span class=\"noAccess\">Delete</span></td>\n";
                    }
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"9\" style=\"border-top:1px solid #999999\"></td>\n";
				print "</tr>\n";
				print "</table>\n";

                // pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM leads_tickets AS t $search";
				$result = $dbcnx->query($query);
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
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
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
if ($print) {
	?>
	</body>
</html>
<?php
} else {
	print "</td>\n";
	include('footer.php');
}
?>