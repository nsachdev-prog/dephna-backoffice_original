<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
$page = "Coldroom_Monitors";
$subpage = "Consumption";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">COLD ROOM MONITORS</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid  = $_REQUEST['siteid'];
			if ($siteid) { $search[] = "c.siteid = '$siteid'"; }

            $date = $_REQUEST['date'];
            // if no date is set, use yesterday
            if (!$date) { $date = date("Y-m-d",strtotime("yesterday")); }
            $tomorrow = date("Y-m-d",strtotime("$date +1 Day"));
            $search[] = "c.xdate = '$date'";

            $type = $_REQUEST['type'];
            if ($type == "Freezer") {
                $search[] = "c.setpoint < -6";
            } elseif ($type == "Chiller") {
                $search[] = "c.setpoint > -7";
            }

            $search = implode(" AND ",$search);
            $qString = "siteid=$siteid&type=$type";

            $datename = date("l, jS F Y",strtotime($date));
            print "<p align=\"center\" class=\"larger\">Power Consumption for $datename</p>\n";
            print "<p align=\"center\">From: <b>$date 05:30</b> to: <b>$tomorrow 05:30</b></p>\n";

            $prev = date("Y-m-d",strtotime("$date -1 Day"));
            $next = date("Y-m-d",strtotime("$date +1 Day"));

            print "<form id=\"rform\" method=\"post\" action=\"coldrooms-consumption.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-consumption.php?$qString&amp;date=$prev\"><b>&lt;&lt;</b></a></td>\n";
            print "<td><b>Day</b></td>";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>";
            // sites dropdown
            print "<td><b>Site</b></td>";
            print "<td><select name=\"siteid\" class=\"element_small\">\n";
            print "<option value=\"\">All</option>\n";
            $sql = "SELECT `ID`, `title` FROM `sites` WHERE `ID` != '26' AND `ID` != '27' ORDER BY `title`";
            if (($siteresult = $dbcnx->query($sql)) && $siteresult->num_rows) {
                while ($siterow = $siteresult->fetch_assoc()) {
                    $selected = ($siterow['ID'] == $siteid) ? " selected" : "";
                    print "<option value=\"{$siterow['ID']}\"$selected>{$siterow['title']}</option>\n";
                }
            }
            print "</select></td>\n";
            // type dropdown
            print "<td><b>Type</b></td>";
            print "<td><select name=\"type\" class=\"element_small\">\n";
            print "<option value=\"\">All</option>\n";
            print "<option value=\"Freezer\""; if ($type == "Freezer") { print " selected"; } print ">Freezer</option>\n";
            print "<option value=\"Chiller\""; if ($type == "Chiller") { print " selected"; } print ">Chiller</option>\n";
            print "</select></td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-consumption.php?$qString\"><b>&bull;</b></a></td>\n";
            print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-consumption.php?$qString&amp;date=$next\"><b>&gt;&gt;</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "</form>\n";

            // get data from cr_consumption
            $sql = "SELECT c.*, s.title AS sitename, u.title AS unitname
                    FROM cr_consumption AS c
                    LEFT JOIN sites AS s ON c.siteid = s.ID
                    LEFT JOIN units AS u ON c.unitid = u.ID
                    WHERE $search
                    ORDER BY s.title, u.title
            ";
            if (($result = $dbcnx->query($sql)) && $result->num_rows) {
                print "<table align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Commodity</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Previous</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Current</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Used</b></td>\n";
                print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Setpoint</b></td>\n";
                print "</tr>\n";
                while ($row = $result->fetch_assoc()) {
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    $unitname = $row['unitname'];
                    $used = $row['consumption'];
                    $floored = floor($used);
                    $floors[$floored]++;
                    list($prefix,$unitname) = explode(" ",$unitname);
                    print "	<td>{$row['sitename']}</td>\n";
                    print "	<td>$unitname</td>\n";
                    print "	<td>{$row['commodity']}</td>\n";
                    print "	<td align=\"right\">{$row['previous']}</td>\n";
                    print "	<td align=\"right\">{$row['current']}</td>\n";
                    print "	<td align=\"right\">$used</td>\n";
                    print "	<td align=\"right\">{$row['setpoint']}&deg;</td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
                
                // print $floors array highest to lowest
                ksort($floors);
                print "<table align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Usage</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Number</b></td>\n";
                print "</tr>\n";
                foreach ($floors AS $floor => $count) {
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$floor</td>\n";
                    print "	<td align=\"right\">$count</td>\n";
                    print "</tr>\n";
                }
                print "</table>\n";

            } else {
                print "<p align=\"center\"><b>No Data</b></p>\n";
            }

            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
            print "	});\n";
            print "</script>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
