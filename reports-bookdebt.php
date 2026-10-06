<?php
$page = "Reporting";
$subpage = "Bookdebt Report";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">REPORTING</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering if any and build search array
			if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; } else { $ye = date("Y"); }
			if (($_REQUEST['ty']) AND ($_REQUEST['ty'] != "All")) {
				$ty = $_REQUEST['ty'];
				if ($ty == "Debtors") {
					$search1 = "WHERE u.tim != 'Yes' AND u.ad_managed != 'Yes'";
					$search2 = "AND u.tim != 'Yes' AND u.ad_managed != 'Yes'";
				}
			}

			$months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			// print heading and submenu
			print "<p align=\"center\"><span class=\"larger\">Bookdebt Report</span><br /><b>{$months[$mo]} $ye</b></p>\n";
			include('reporting-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"reports-bookdebt.php\">\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
			print "<tr>\n";

			// available years
			print "		<td align=\"right\"><b>Year</b></td>\n";
			print "		<td><select name=\"ye\" class=\"element_smaller\">\n";
			print "		<option value=\"2020\""; if ($ye == 2020) { print " SELECTED"; } print ">2020</option>\n";
			print "		<option value=\"2019\""; if ($ye == 2019) { print " SELECTED"; } print ">2019</option>\n";
			print "		</select>";
			print "		</td>\n";

			// filters
			print "		<td align=\"right\"><b>Type</b></td>\n";
			print "		<td><select name=\"ty\" class=\"element_smaller\">\n";
			print "		<option value=\"All\"";        if ($ty == "All") { print " SELECTED"; } print ">All</option>\n";
			print "		<option value=\"Debtors\"";    if ($ty == "Debtors") { print " SELECTED"; } print ">Debtors</option>\n";
			// print "		<option value=\"Restricted\""; if ($ty == "Restricted") { print " SELECTED"; } print ">Restricted</option>\n";
			// print "		<option value=\"Managed\"";    if ($ty == "Managed") { print " SELECTED"; } print ">Managed</option>\n";
			// print "		<option value=\"Credits\"";    if ($ty == "Credits") { print " SELECTED"; } print ">Credits</option>\n";
			print "		</select>";
			print "		</td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
			
			print " <td><a href=\"reports-bookdebt.php\">Reset</a></td>\n";
			print "</tr>";
			print "</table>\n";
			print "</form>\n";

			// get current book debt

			print "<style>\n";
			print "	.rotate {\n";
			print "		width: 8px;\n";
			print "		height: 200px;\n";
			print "		padding: 0px 0px 5px 0px;\n";
			print "		text-align: right;\n";
			print "	}\n";
			print "</style>\n";
			
			if ($ty) {
				$sql = "SELECT SUM(h.outstanding) AS currentBookDebt
						FROM inv_headers AS h
						LEFT JOIN users AS u ON u.custref = h.custref
						$search1
				";
			} else {
				$sql = "SELECT SUM(outstanding) AS currentBookDebt FROM inv_headers";
			}
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$currentBookDebt = $row['currentBookDebt'] / 1000;

				foreach ($months AS $mo => $month) {

					$thismonth = date("Y-m");

					if ("$ye-$mo" <= $thismonth) {

						print "<p align=\"center\" class=\"larger\"><br />$month $ye</p>";
					
						// get days to process
						$lastDay = date("t",strtotime("$ye-$mo-15"));
						$runningDay = 1;
						$highest = 0;
						$bookDebt = array();
						$turnover = array();

						// loop through each day
						while ($runningDay <= $lastDay) {

							$thisday = sprintf("%02s",$runningDay);
							$today = date("Y-m-d");

							if ("$ye-$mo-$thisday" <= $today) {

								// get book debt
								if ($ty) {
									$sql = "SELECT SUM(h.invtotal) AS txSince
											FROM inv_headers AS h
											LEFT JOIN users AS u ON u.custref = h.custref
											WHERE CONCAT(h.year,h.month,h.day) > '$ye$mo$thisday'
											$search2
									";
								} else {
									$sql = "SELECT SUM(invtotal) AS txSince
											FROM inv_headers
											WHERE CONCAT(year,month,day) > '$ye$mo$thisday'
									";
								}
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$txSince = $row['txSince'] / 1000;
									$bookDebt[$runningDay] = $currentBookDebt - $txSince;
									if (abs($bookDebt[$runningDay]) > $highest) { $highest = abs($bookDebt[$runningDay]); }
								}
							
								// get turnover
								$sql = "SELECT SUM(invtotal) AS turnover
										FROM inv_headers
										WHERE CONCAT(year,month,day) = '$ye$mo$thisday'
										AND (txtype = 'I' OR txtype = 'C')
								";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$tturnover = $row['turnover'] / 1000;
									if (abs($tturnover) > $highest) { $highest = abs($tturnover); }
									$turnover[$runningDay] = $tturnover;
								}
							}
							$runningDay++;
						}

						$highest = ceil($highest);
						$half = ceil($highest/2);
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"0\">\n";
						print "<tr>\n";
						print "<td width=\"50px\" align=\"right\" valign=\"top\" class=\"smalltext2\">";
						print "<img src=\"images/clear.gif\" width=\"50px\" height=\"6px\" alt=\"\" />";
						print "$highest-&nbsp;<br />";
						print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" />";
						print "$half-&nbsp;<br />";
						print "<img src=\"images/clear.gif\" width=\"50px\" height=\"135px\" alt=\"\" />";
						print "0-&nbsp;";
						print "</td>\n";

						$runningDay = 1;

						while ($runningDay <= $lastDay) {
							$thisday = sprintf("%02s",$runningDay);

							if ($turnover[$runningDay]) {
								$percent = ceil((abs($turnover[$runningDay])/$highest)*300);
								$clear = 300-$percent;
								print "<td width=\"8px\" align=\"center\" valign=\"top\" style=\"position:relative\" class=\"smalltext2\">";
								print "<div class=\"rotate\" style=\"padding-left:15px\">".number_format($turnover[$runningDay],0)."</div>";
								print "<br /><img src=\"images/lightgrey.gif\" width=\"8px\" height=\"{$clear}px\" alt=\"\" />";
								print "<img src=\"images/green.gif\" width=\"8px\" height=\"{$percent}px\" alt=\"\" />";
								print "<br />" . $thisday . "<br />";
								print "</td>\n";
							} else {
								print "<td width=\"8px\" align=\"center\" valign=\"top\" style=\"position:relative\" class=\"smalltext2\">";
								print "<div class=\"rotate\" style=\"padding-left:15px\">0</div>";
								print "<br /><img src=\"images/lightgrey.gif\" width=\"8px\" height=\"300px\" alt=\"\" />";
								print "<br />" . $thisday . "<br />";
								print "</td>\n";
							}
							if ($bookDebt[$runningDay]) {
								$percent = ceil((abs($bookDebt[$runningDay])/$highest)*300);
								$clear = 300-$percent;
								print "<td width=\"8px\" align=\"center\" valign=\"top\" style=\"position:relative\" class=\"smalltext2\">";
								print "<br /><img src=\"images/lightgrey.gif\" width=\"8px\" height=\"{$clear}px\" alt=\"\" />";
								print "<img src=\"images/red.gif\" width=\"8px\" height=\"{$percent}px\" alt=\"\" />";
								print "<br /><br /><br /><div class=\"rotate\">".number_format($bookDebt[$runningDay],0)."</div>";
								print "</td>\n";
							} else {
								print "<td width=\"8px\" align=\"center\" valign=\"top\" style=\"position:relative\" class=\"smalltext2\">";
								print "<br /><img src=\"images/lightgrey.gif\" width=\"8px\" height=\"300px\" alt=\"\" />";
								print "<br /><br /><br /><div class=\"rotate\">0</div>";
								print "<br /><br />";
								print "</td>\n";
							}
							print "<td width=\"2px\"></td>\n";
							$runningDay++;
						}
						print "	</tr>\n";
						print "</table>\n";
					}
				}

				print "<p>&nbsp;</p>\n";

				?>
				<script>
					$(document).ready(function() {
						$('.rotate').css('height', $('.rotate').width());
					});
				</script>
				<?php

			} else {
				$resultMessage .= "Cannot get current Book Debt<br />" . $dbcnx->error . "<br />";
			}      
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
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
include('footer.php');
?>
