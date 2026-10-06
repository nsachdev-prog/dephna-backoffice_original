<?php
$page = "Utilities";
$subpage = "Report";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	 
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";

	// print submenu
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $date = $_REQUEST['date'];
            if (!$date) { $date = date("m-Y",strtotime("-1 month")); } 
            list($m,$y) = explode("-",$date);
            $prevmon = date("m-Y",strtotime("$y-$m-15 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-15 +1 Month"));
            $thismonth = date("F Y",strtotime("$y-$m-01"));
			list($lm,$ly) = explode("-",$prevmon);
			
			if ($_REQUEST['oldnew']) {
				$oldnew = $_REQUEST['oldnew'];
			} else {
				$oldnew = "New";
			}

            print "<form name=\"addform\" method=\"post\" action=\"utilities-reports-used.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"utilities-reports-used.php?date=$prevmon&amp;oldnew=$oldnew\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.1em\"><b>Month</b></td>\n";
			print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
			print "	<td align=\"right\"><b>Database:</b></td>\n";
			print "	<td><select name=\"oldnew\" class=\"element_small\">\n";
			print "		<option value=\"New\""; if ($oldnew == "New") { print " SELECTED"; } print ">New Database</option>\n";
			print "		<option value=\"Old\""; if ($oldnew == "Old") { print " SELECTED"; } print ">Old Database</option>\n";
			print "	</select></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"utilities-reports-used.php?date=$nextmon&amp;oldnew=$oldnew\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";
            
            print "<p align=\"center\" class=\"larger\">$thismonth - $oldnew Utils Database</p>\n";

			// get sites
			$sql = "SELECT ID, title FROM sites";
			if (($sresult = $dbcnx->query($sql)) && ($sresult->num_rows)) {
				while ($srow = $sresult->fetch_assoc()) {
					$siteid = $srow['ID'];
					$sitename = $srow['title'];
					
					if ($siteid != 3
						AND $siteid != 26
						AND $siteid != 27
					)
					{

						print "<p align=\"center\" class=\"larger\">$sitename</p>\n";

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "	<tr>\n";
						print "	<td><i>Imported (On 1st)</i></td>\n";
						print "	<td align=\"right\"><i>Supplemental (After 1st)</i></td>\n";
						print "	</tr>\n";
						print "	<tr>\n";
						print "	<td valign=\"top\">\n";

						// get data for site from utils used

						if ($oldnew == "Old") {

							$sql = "SELECT SUM(used) AS used, commodity, SUM(charge) AS charge
									FROM UtlUsed
									WHERE site = '$sitename'
										AND custref != '-NF'
										AND `year` = '$y'
										AND `month` = '$m'
									GROUP BY commodity
									ORDER BY commodity
							";

						} elseif ($oldnew == "New") {

							$sql = "SELECT SUM(used) AS used, commodity, SUM(charge) AS charge
									FROM UtlUsedNew
									WHERE siteid='$siteid'
										AND custref != '-NF'
										AND `year` = '$y'
										AND `month` = '$m'
									GROUP BY commodity
									ORDER BY sub
							";

						}
						
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							print "	<tr>\n";
							print "		<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
							print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Used</b></td>\n";
							print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Charged</b></td>\n";
							print "	</tr>\n";

							// row background colours
							print "<style>\n";
							print ".r0 { background-color:#fff }\n";
							print ".r1 { background-color:#ddd }\n";
							print "</style>\n";

							while ($row = $result->fetch_assoc()) {
								$commodity = $row['commodity'];
								$used      = $row['used'];
								$charge    = $row['charge'];
								
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td>$commodity</td>\n";
								print "	<td align=\"right\">".number_format($used,3)."</td>\n";
								print "	<td align=\"right\">".number_format($charge,2)."</td>\n";
								print "</tr>\n";
							}

							print "<tr>\n";
							print "<td colspan=\"3\" style=\"border-top:1px solid #666\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";

						} else {
							print "<p align=\"center\"><b>Nothing Found</b></p>";
						}

						print "</td>\n";
						print "<td valign=\"top\">\n";

						// get data for site from invoicing

						$sql = "SELECT
									SUM(CASE WHEN details2 LIKE '%Electricity%' THEN linenet END) as Electricity,
									SUM(CASE WHEN details2 LIKE '%Hot%' THEN linenet END) as Hot,
									SUM(CASE WHEN details2 LIKE '%Cold%' THEN linenet END) as Cold,
									SUM(CASE WHEN details2 LIKE '%Gas%' THEN linenet END) as Gas
								FROM inv_lines AS l
								LEFT JOIN inv_headers AS h ON l.hid = h.ID
								WHERE   h.year = '$y'
									AND h.month = '$m'
									AND h.day > '01'
									AND h.txtype = 'I'
									AND l.location LIKE '%$sitename%'
						";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

							$electricity = 0;
							$hot = 0;
							$cold = 0;
							$gas = 0;

							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							print "	<tr>\n";
							print "		<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
							print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Charged</b></td>\n";
							print "	</tr>\n";

							// row background colours
							print "<style>\n";
							print ".r0 { background-color:#fff }\n";
							print ".r1 { background-color:#ddd }\n";
							print "</style>\n";

							while ($row = $result->fetch_assoc()) {
								$electricity = $row['Electricity'];
								$hot  = $row['Hot'];
								$cold = $row['Cold'];
								$gas  = $row['Gas'];

								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td>Electricity</td>\n";
								print "	<td align=\"right\">".number_format($electricity,2)."</td>\n";
								print "</tr>\n";
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td>Hot</td>\n";
								print "	<td align=\"right\">".number_format($hot,2)."</td>\n";
								print "</tr>\n";
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td>Cold</td>\n";
								print "	<td align=\"right\">".number_format($cold,2)."</td>\n";
								print "</tr>\n";
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td>Gas</td>\n";
								print "	<td align=\"right\">".number_format($gas,2)."</td>\n";
								print "</tr>\n";
							}

							print "<tr>\n";
							print "<td colspan=\"2\" style=\"border-top:1px solid #666\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";

						} else {
							print "<p align=\"center\"><b>Nothing Found</b></p>";
						}

						print "</td>\n";
						print "</tr>\n";
						print "</table>\n";

					}

				}

				print "<p align=\"center\">Supplemental = Charges after 1st of the month.</p>\n";
				
            }
            
            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'mm-yy'});\n";
            print "	});\n";
            print "</script>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
