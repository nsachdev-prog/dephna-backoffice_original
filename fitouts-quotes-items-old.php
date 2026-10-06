<?php
$page = "Fitouts";
$subpage = "Items";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		if ($_REQUEST['qid']) { $qid = $_REQUEST['qid']; }
		if ($_REQUEST['uid']) { $uid = $_REQUEST['uid']; }
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Quote</p>\n";
			
		if ($qid AND $uid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT q.*, u.compname, s.title AS sitename
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					LEFT JOIN sites AS s ON q.siteid = s.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
					$siteid    = $row['siteid'];
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					// get unit count
					$sql = "SELECT count(*) AS unitcount FROM fitouts_quotes_units WHERE qid = '$qid'";
					if ($uresult = $dbcnx->query($sql) AND $uresult->num_rows) {
						$urow = $uresult->fetch_assoc();
						$unitcount = $urow['unitcount'];
						$unitspower = $unitcount * $unit_amps;
						// $unit_amps comes from settings.php
					}
					
					if ($_POST['item-posted']) {
						$item      = $_POST['item'];
						$custtitle = $_POST['custtitle'];
						if ($item != "Select") {
							$sql = "INSERT INTO fitouts_quotes_items VALUES (
								null,
								'$qid',
								'$item',
								'$custtitle'
							)";
							$dbcnx->query($sql);
						}
					}

					elseif ($_GET['delete']) {
						$iid = $_GET[iid];
						$sql = "DELETE FROM fitouts_quotes_items WHERE ID = '$iid'";
						$dbcnx->query($sql);
					}

					print "<p align=\"center\"><b>Items Added</b></p>\n";
					
					$sql = "SELECT qi.*, i.title AS ititle, i.power, i.upgrade
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Cust Item Name</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amps</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" width=\"8\">&nbsp;</td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" width=\"30\">&nbsp;</td>\n";
						print "	</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "<td><i>Power Available from Units</i></td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td align=\"right\">".number_format($unitspower,1)."</td>\n";
						print "<td align=\"center\">+</td>\n";
						print "<td>&nbsp;</td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$iid     = $row['ID'];
							$ititle  = $row['ititle'];
							$power   = $row['power'];
							$upgrade = $row['upgrade'];
							$ctitle  = $row['custtitle'];
							if ($upgrade) {
								$totalamps -= $power;
								$sign = "+";
							} else {
								$totalamps += $power;
								$sign = "-";
							}
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td>$ititle</td>\n";
							print "<td>$ctitle</td>\n";
							print "<td align=\"right\">$power</td>\n";
							print "<td align=\"center\">$sign</td>\n";
							print "<td><a href=\"fitouts-quotes-items.php?qid=$qid&iid=$iid&delete=1\">Delete</a></td>\n";
							print "</tr>\n";
						}

						$ampsused = $unitspower - $totalamps;

						if ($ampsused < 0) {
							$ampsstyle = "color:Red;";
							$amps_exceeded = 1;
						} else {
							$ampsstyle = "color:Green;";
						}

						print "<tr>\n";
						print "	<td colspan=\"2\" style=\"border-top:1px solid #999\">Power Still Available</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999; $ampsstyle\">".number_format($ampsused,1)."</td>\n";
						print "	<td colspan=\"2\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\">No Items Added Yet</p>\n";
					}

					if ($amps_exceeded) {
						print "<p style=\"text-align:center;font-size:1.2em;color:red\">Power Exeeded!</p>\n";
						print "<p align=\"center\">Please add a Power Upgrade or Another Unit.</p>\n";
						$search = "WHERE upgrade = 'on'";
					}

					print "<p align=\"center\"><b>New Item</b></p>\n";

					print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-items.php\">\n";
					print "<input type=\"hidden\" name=\"item-posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
					print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"3\">\n";

					// get items
					$sql = "SELECT ID, title FROM fitouts_items $search ORDER BY title";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<tr>\n";
						print "	<td align=\"right\"><b>Item:</b></td>\n";
						print "	<td><select name=\"item\" class=\"element\">\n";
						print "		<option>Select</option>\n";
						while ($row = $result->fetch_assoc()) {
							$iid   = $row['ID'];
							$title = $row['title'];
							print "		<option value=\"$iid\">$title</option>\n";
						}
						print "	</select>";
						print "</td>\n";
						print "</tr>\n";
						
					} else {
						$resultMessage .= "<b>No Items Found</b>";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer Calls It:</b></td>\n";
					print "	<td><input name=\"custtitle\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$custtitle\" />\n";
					if ($ercusttitle) { print "&nbsp;<span class=\"redtext\">$ercusttitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";

					print "</form>\n";

					print "<p align=\"center\">";
					print "<a href=\"fitouts-quotes-units.php?qid=$qid\">Back</a> | ";
					print "<a href=\"fitouts-quotes-images.php?qid=$qid\">Next</a>";
					print "</p>\n";

				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fitout Quote Not Found!</b></span><br>" . $dbcnx->error;
				}

				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Page Called Without IDs</b></span><br>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
