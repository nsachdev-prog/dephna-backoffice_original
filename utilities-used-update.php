<?php
$page = "Utilities";
$subpage = "Imported";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	// print heading
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	if ($accessRights[$page] > 2) {
		// print submenu
		include('utilities-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Update Reading</p>\n";

		$uid     = $_REQUEST['uid'];
		$posted  = $_REQUEST['posted'];
		$site    = $_REQUEST['site'];
		$comm    = $_REQUEST['comm'];
		$fmonth  = $_REQUEST['fmonth'];
		$fyear   = $_REQUEST['fyear'];
		$type    = $_REQUEST['type'];
		$custref = $_REQUEST['custref'];
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get useage data
			$result = $dbcnx->query("SELECT * FROM UtlUsedNew WHERE ID = '$uid'");
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$year      = $row['year'];
				$month     = $row['month'];
				$day       = $row['day'];
				$sitename  = $row['sitename'];
				$collector = $row['collector'];
				$deviceid  = $row['counter'];
				$custref   = $row['custref'];
				$unit      = $row['unit'];
				$commodity = $row['commodity'];
				$date      = "$year-$month-$day";

				$custresult = $dbcnx->query("SELECT compname FROM users WHERE custref='$custref'");
				$custrow  = $custresult->fetch_assoc();
				$compname = $custrow['compname'];
			}

			if ($posted) {
				$rate      = $_POST['rate'];
				$meter     = $_POST['meter'];
                $previous  = $_POST['previous'];
                $prevdate  = $_POST['prevdate'];
				$custref   = $_POST['custref'];

				if (!$meter) { $ermeter = "Enter meter"; $e++; }
				elseif (!is_numeric($meter)) { $ermeter  = "Meter must be numeric"; $e++; }
				if (!$previous) { $erprevious = "Enter previous"; $e++; }
				elseif (!is_numeric($previous)) { $erprevious = "Previous must be numeric"; $e++; }
				if (!$rate) { $errate = "Enter rate"; $e++; }
                elseif (!is_numeric($rate)) { $errate = "Rate must be numeric"; $e++; }
                if ($prevdate) {
                    if (!validateDate($prevdate)) {
                        $erprevdate = "Invalid Date"; $e++;
                    }
                } else {
                    $erprevdate = "Enter Date"; $e++;
                }

				if (!$e) {

					$used = $meter - $previous;
					$charge = $used * $rate;

					if ($uid) {
						// post to usage
						$sql = "UPDATE UtlUsedNew SET
							custref='$custref',
							meter='$meter',
							prevdate='$prevdate',
							previous='$previous',
							used='$used',
							rate='$rate',
							charge='$charge'
							WHERE ID = '$uid'
						";
						$postresult = $dbcnx->query($sql);
						if ($postresult) {
							$resultMessage .= "<b>Record successfully updated</b><br />";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref {$unitPrefixes[$sitename]} $unit $commodity $previous - $meter','$page','$now')");
						} else {
							$resultMessage = "Could not update records<br />" . $dbcnx->error;
						}
					} else {
						$resultMessage = "<b>UID not found</b><br />";
					}
				}

			}

			elseif ($uid) {
				$result = $dbcnx->query("SELECT * FROM UtlUsedNew WHERE ID = '$uid'");
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$prevdate = $row['prevdate'];
					$previous = $row['previous'];
					$rate     = $row['rate'];
					if ($_REQUEST['reading']) {
						$meter = $_REQUEST['reading'];
					} else {
						$meter = $row['meter'];
					}
				} else {
					$resultMessage = "<b>Could not find record</b><br />" . $dbcnx->error;
				}
			}

			if (!$resulMessage) {
				if ((!$posted) || ($e)) {

					print "<p align=\"center\">Enter Reading &amp; Previous.<br />Used &amp; Charge will be recalculated.</p>\n";
					print "<p align=\"center\"><b>IMPORTANT</b>: This page is used to adjust final figures prior to invoicing.<br />If you re-import utilities after making adjustments, those adjustments will be lost.</p>\n";
					print "<form name=\"addform\" method=\"post\" action=\"utilities-used-update.php\">\n";
					print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";
					print "<input type=\"hidden\" name=\"comm\" value=\"$comm\" />\n";
					print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
					print "<input type=\"hidden\" name=\"type\" value=\"$type\" />\n";
					print "<input type=\"hidden\" name=\"fyear\" value=\"$fyear\" />\n";
					print "<input type=\"hidden\" name=\"fmonth\" value=\"$fmonth\" />\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table align=\"center\" cellspacing=\"4\" cellpadding=\"0\">\n";

					print "<tr>\n";
					$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
					$usersresult = $dbcnx->query($sql);
					if (($usersresult) && ($usersresult->num_rows)) {
						print "	<td align=\"right\">Customer:</td>\n";
						print "	<td><select name=\"custref\" class=\"element\">\n";
						print "	<option value=\"-NF\">Not Found</option>\n";
						while ($usersrow = $usersresult->fetch_assoc()) {
							$compname    = $usersrow['compname'];
							$thiscustref = $usersrow['custref'];
							print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
						}
						print "	</select>";
						print "	</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Unit:</td>\n";
					print "	<td>$unit</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Collector:</td>\n";
					print "	<td>$collector</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Device ID:</td>\n";
					print "	<td>$deviceid</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Commodity:</td>\n";
					print "	<td>$commodity</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Current Meter Reading:</td>\n";
					print "	<td><input name=\"meter\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$meter\" />";
					if ($ermeter) { print "&nbsp;<span class=\"redtext\">$ermeter</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Previous Meter Reading:</td>\n";
					print "	<td><input name=\"previous\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$previous\" />";
					if ($erprevious) { print "&nbsp;<span class=\"redtext\">$erprevious</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Previous (From) Date</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"prevdate\" value=\"$prevdate\" class=\"element_small\" />\n";
					print "<br />Previous date is the date of the previous meter reading. Not the current date.";
					if ($erprevdate) { print "<br /><span class=\"redtext\">$erprevdate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Rate:</td>\n";
					print "	<td><input name=\"rate\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$rate\" />";
					if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					// jquery date picker
                    print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
                    print "	});\n";
                    print "</script>\n";
				}
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"utilities-itemised-rayleigh.php?comm=$comm&amp;site=$site&amp;custref=$custref&amp;type=$type\">Return to DP$custref</a>";
		print "</p>";
		print "<p align=\"center\">";
		print "<a href=\"utilities-itemised-rayleigh.php?comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;type=$type\">Return to Top</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
