<?php
$page = "Sales_Ledger";
$subpage = "Analysis";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
    print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	if ($accessRights[$page]) {
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
        if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$fr = $_REQUEST['fr'];
		$to = $_REQUEST['to'];
        if (!$fr) { $fr = date("Y-m", strtotime("- 2 Months"));
            $to = date("Y-m");
        }
        $prevfr = date("Y-m", strtotime("$fr-01 -1 month"));
        $prevto = date("Y-m", strtotime("$to-01 -1 month"));
        $nextfr = date("Y-m", strtotime("$fr-01 +1 month"));
        $nextto = date("Y-m", strtotime("$to-01 +1 month"));
        $frtxt = date("M Y", strtotime("$fr-01"));
        $totxt = date("M Y", strtotime("$to-01"));
        $ty = $_REQUEST['ty'];
        if (!$ty) { $ty = "All"; }
        if ($ty != "All") {
            $tysql = " AND l.unittype = '$ty'";
        } else {
            $tysql = "";
        }

        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa&amp;ty=$ty";

		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref      = $row['custref'];
					$compname     = $row['compname'];

					print "<p class=\"larger\" align=\"center\">$compname</p>\n";
					include('sl-ledger-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";

                    print "<p align=\"center\" class=\"larger\">Analysis</p>\n";

                    print "<form id=\"rform\" method=\"post\" action=\"sl-analysis.php?id=$id&amp;$qString\">\n";
                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                    print "<tr>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"sl-analysis.php?id=$id&amp;$qString&amp;fr=$prevfr&amp;to=$prevto\"><b>&lt;&lt;</b></a></td>\n";
                    print "<td><b>From</b></td>";
                    print "<td><input type=\"text\" id=\"datepicker1\" name=\"fr\" value=\"$fr\" class=\"element_small\" /></td>";
                    print "<td><b>To</b></td>";
                    print "<td><input type=\"text\" id=\"datepicker2\" name=\"to\" value=\"$to\" class=\"element_small\" /></td>";
                    print "		<td align=\"right\"><b>Type</b></td>\n";
                    print "		<td><select name=\"ty\" class=\"element_small\">\n";
                    print "		<option value=\"All\">All</option>\n";
                    $tresult = $dbcnx->query("SELECT title FROM types ORDER BY title");
                    if (($tresult) && ($tresult->num_rows)) {
                        while ($trow = $tresult->fetch_assoc()) {
                            $ttitle  = $trow['title'];
                            print "		<option value=\"$ttitle\""; if ($ty == $ttitle) { print " SELECTED"; } print ">$ttitle</option>\n";
                        }
                        print "		<option value=\"Car Parking\"";    if ($ty == "Car Parking")    { print " SELECTED"; } print "><i>Car Parking</i></option>\n";
			            print "		<option value=\"Virtual Office\""; if ($ty == "Virtual Office") { print " SELECTED"; } print "><i>Virtual Office</i></option>\n";
                        print "		<option value=\"Utilities\""; if ($ty == "Utilities") { print " SELECTED"; } print "><i>Utilities</i></option>\n";
                    }
                    print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                    print "<td style=\"font-size:3.6em\"><a href=\"sl-analysis.php?id=$id&amp;$qString\"><b>&bull;</b></a></td>\n";
                    print "<td style=\"font-size:1.8em\"><a href=\"sl-analysis.php?id=$id&amp;$qString&amp;fr=$nextfr&amp;to=$nextto\"><b>&gt;&gt;</b></a></td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                    print "</form>\n";

                    print "<p align=\"center\" class=\"larger\">$frtxt to $totxt</p>\n";

                    $sql = "SELECT l.office, SUM(l.linetotal) AS total, COUNT(l.office) AS uttotal
					FROM inv_lines AS l
					LEFT JOIN inv_headers AS h ON l.hid = h.ID
					WHERE h.custref = '$custref' AND CONCAT(h.year,'-',h.month) BETWEEN '$fr' AND '$to'
                    $tysql
					GROUP BY l.office
                    ORDER BY l.office";
                    $result = $dbcnx->query($sql);
                    if (($result) && ($result->num_rows)) {
                        print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                        print "<tr>\n";
                        print "	<td><b>Type</b></td>\n";
                        print "	<td align=\"right\"><b>Units Sold</b></td>\n";
                        print "	<td align=\"right\"><b>Total Sales</b></td>\n";
                        print "</tr>\n";

                        $rowcount = 1;
                        while ($row = $result->fetch_assoc()) {
                            $office = $row['office'];
                            $total    = $row['total'];
                            $uttotal  = $row['uttotal'];
                            $grandtotal += $total;
                            $granduttotal += $uttotal;

                            if ($rowcount % 2) {
                                print "<tr class=\"odd\">\n";
                            } else {
                                print "<tr class=\"even\">\n";
                            }
                            print "	<td>$office</td>\n";
                            print "	<td align=\"right\">$uttotal</td>\n";
                            print "	<td align=\"right\">" . number_format($total,2) . "</td>\n";
                            print "</tr>\n";
                        }
                        // spacer
                        print "<tr>\n";
                        print "	<td colspan=\"3\">&nbsp;</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td><b>Totals</b></td>\n";
                        print "	<td align=\"right\">$granduttotal</td>\n";
                        print "	<td align=\"right\">" . number_format($grandtotal,2) . "</td>\n";
                        print "</table>\n";
                    } else {
                        print "<p align=\"center\">No sales found for this period</p>\n";
                    }

                    // datepickers
                    print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    print "    $(\"#datepicker1\").datepicker({ dateFormat: 'yy-mm' });\n";
                    print "    $(\"#datepicker2\").datepicker({ dateFormat: 'yy-mm' });\n";
                    print "});\n";
                    print "</script>\n";

				} else {
					$resultMessage = "Could not find customer<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without user id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&amp;$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
