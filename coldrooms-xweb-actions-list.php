<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM ACTIONS</p>\n";
	
	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_GET['siteid'];
			$unitid = $_GET['unitid'];

			if ($siteid AND $unitid) {

				$date = $_REQUEST['date'];
				if (!$date) { $date = date("Y-m"); } 
				$prevmon = date("Y-m",strtotime("$date-01 -1 Month"));
				$nextmon = date("Y-m",strtotime("$date-01 +1 Month"));
				$thismonth = date("F Y",strtotime("$date-01"));

				$search[] = "xdatetime LIKE '$date%'";
				$search[] = "siteid = '$siteid'";
				$search[] = "unitid = '$unitid'";
				$search = implode(" AND ",$search);
				$qString = "siteid=$siteid&amp;unitid=$unitid";

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				}

				// get unit
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
				}

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

				print "<form id=\"rform\" method=\"post\" action=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<tr>\n";
				print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid&amp;date=$prevmon\"><b>&lt;&lt;</b></a></td>\n";
				print "<td style=\"font-size:1.2em\"><b>Month</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
				print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\"><b>&bull;</b></a></td>\n";
				print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid&amp;date=$nextmon\"><b>&gt;&gt;</b></a></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "</form>\n";

				print "<p align=\"center\" class=\"larger\">$thismonth</p>\n";

				// get actions
				$sql = "SELECT *
						FROM xweb_actions
						WHERE $search
						ORDER BY xdatetime DESC
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

					$xstatuses = array(
						1 => "Callout",
						11 => "CODelay" . $CODelay,
						2 => "Pwr Off Empty",
						22 => "Pwr Off Fail",
						3 => "Keyb locked",
						33 => "Keyb lock Fail",
						4 => "Dfrst Evap",
						14 => "Defrost Fail",
						8 => "Pwr Tgl",
						18 => "Pwr Tgl Fail",
						9 => "Restarted",
						19 => "Restart Fail",
						10 => "Odc=no",
						20 => "PWR OFF"
					);

					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";

					print "<tr>\n";
					print " <td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\"><b>Action</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\"><b>Status</b></td>\n";
					print " <td style=\"border-bottom:1px solid #999999\" width=\"20\">&nbsp;</td>\n";
					print "</tr>\n";

					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";

					while ($row = $result->fetch_assoc()) {

						$aid       = $row['ID'];
						$xdatetime = $row['xdatetime'];
						$compname  = $row['compname'];
						$xstatus   = $row['xstatus'];
						$details   = $row['details'];
						$startedby = $row['startedby'];
						$cleared   = $row['cleared'];
						$clearedby = $row['clearedby'];

						if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }

						if (strpos($details, "Callout") !== FALSE) {
							if ($cleared) {
								$statustxt = "Cleared";
							} else {
								$statustxt = "Ongoing";
							}
						} else {
							$statustxt = "&nbsp;";
						}

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print " <td>$xdatetime</td>\n";
						print " <td>$compname</td>\n";
						print " <td>$details</td>\n";
						print " <td>$startedby</td>\n";
						print " <td>$xstatuses[$xstatus]</td>\n";
						print " <td><a href=\"coldrooms-xweb-actions-view.php?aid=$aid\">View</a></td>\n";
						print "</tr>\n";

					}

					print "<tr>\n";
					print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";

				} else {
					$resultMessage .= "Nothing Found<br />" . $dbcnx->error . "<br />";
				}

				// jquery date picker script
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm'});\n";
				print "	});\n";
				print "</script>\n";

			} else {
				$resultMessage .= "Page called without site or unit id<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Unit</a> | ";
	print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Site</a>";
	print "</p>";
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
