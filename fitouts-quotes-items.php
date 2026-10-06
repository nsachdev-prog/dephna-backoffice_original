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
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Quote</p>\n";
			
		if ($qid) {
		
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

					print "<p align=\"center\"><b>Units</b></p>\n";
					$unitcount = 0;
					$sql = "SELECT * FROM fitouts_quotes_units WHERE qid = '$qid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\" width=\"60%\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "	</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$unitname = $row['unitname'];
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\">$unitname</td>\n";
							print "</tr>\n";
							$unitcount++;
						}
						print "<tr>\n";
						print "<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
						$unitspower = $unitcount * $unit_amps;
						$unitskws   = $unitcount * $unit_kws;
						$power_available = $unitspower;
						$kws_available   = $unitskws;
						// $unit_amps/kws comes from settings.php
					} else {
						print "<p align=\"center\">No Units Added Yet</p>\n";
					}
					
					if ($_POST['items-posted']) {
						$newitems = $_POST['newitems'];
						if ($newitems) {
							// delete current
							$sql = "DELETE FROM fitouts_quotes_items WHERE qid = '$qid'";
							$dbcnx->query($sql);
							// post new
							foreach($newitems as $item => $itemdata) {
								if ($itemdata['qty']) {
									$qty       = $itemdata['qty'];
									$title     = $itemdata['title'];
									$details   = $itemdata['details'];
									$power     = $itemdata['power'];
									$powerg    = $itemdata['powerg'];
									$notes	   = $itemdata['notes'];
									$custtitle = $itemdata['custtitle'];
									
									if ($custtitle) {
										$custtitle = $dbcnx->real_escape_string($custtitle);
										$custtitle = strip_tags($custtitle);
									}
									$sql = "INSERT INTO fitouts_quotes_items VALUES (
										null,
										'$qid',
										'$item',
										'$title',
										'$details',
										'$power',
										'$powerg',
										'$notes',
										'$qty',
										'$custtitle'
									)";
									if (!$dbcnx->query($sql)) {
										$resultMessage .= "<span class=\"redtext\"><b>Could Not Add Item!</b></span><br>" . $dbcnx->error . "<br>";
									}
								}
							}
						}
					}

					elseif ($_POST['notes-posted']) {
						$inotes = $_POST['inotes'];
						// sanitize
						$inotes = strip_tags($inotes);
						$inotes = str_replace('\n', "<br>", $inotes);
						$inotes = $dbcnx->real_escape_string($inotes);
						$sql = "UPDATE fitouts_quotes SET inotes = '$inotes' WHERE ID = '$qid'";
						$dbcnx->query($sql);
					}

					print "<p align=\"center\" class=\"larger\">Items Added</p>\n";

					$currentitems = array();
					
					$sql = "SELECT qi.*, i.upgrade, i.type
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
							ORDER BY i.type, qi.title
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Cust Item Name</b></td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Qty</b></td>\n";
						print "	    <td colspan=\"2\" style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amps</b></td>\n";
						print "	    <td colspan=\"2\" style=\"border-bottom:1px solid #999\" align=\"right\"><b>Kws</b></td>\n";
						print "	</tr>\n";

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "<td><i>Power Available from $unitcount Units</i></td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td align=\"right\">".number_format($unitspower,1)."</td>\n";
						print "<td align=\"center\">+</td>\n";
						print "<td align=\"right\">".number_format($unitskws,1)."</td>\n";
						print "<td align=\"center\">+</td>\n";
						print "</tr>\n";

						$totalamps = 0;
						$totalkws  = 0;

						$lasttype = "";

						while ($row = $result->fetch_assoc()) {
							$iid     = $row['ID'];
							$title   = $row['title'];
							$details = $row['details'];
							$type	 = $row['type'];
							$notes   = $row['notes'];
							$power   = $row['power'];
							$powerg  = $row['powerg'];
							$upgrade = $row['upgrade'];
							$custtitle = $row['custtitle'];
							$itmid   = $row['itmid'];
							$qty	 = $row['qty'];

							$currentitems[$itmid]['custtitle'] = $custtitle;
							$currentitems[$itmid]['title']     = $title;
							$currentitems[$itmid]['details']   = $details;
							$currentitems[$itmid]['notes']     = $notes;
							$currentitems[$itmid]['power']     = $power;
							$currentitems[$itmid]['powerg']    = $powerg;
							$currentitems[$itmid]['qty']       = $qty;

							$power  *= $qty;
							$powerg *= $qty;
							if ($upgrade) {
								if ($power) {
									$power_available += $power;
									$ampssign = "+";
								} else {
									$ampssign = "";
								}
								if ($powerg) {
									$kws_available += $powerg;
									$kwssign = "+";
								} else {
									$kwssign = "";
								}
							} else {
								if ($power) {
									$totalamps += $power;
									$ampssign = "-";
								} else {
									$ampssign = "";
								}
								if ($powerg) {
									$totalkws += $powerg;
									$kwssign = "-";
								} else {
									$kwssign = "";
								}
							}

							if ($type != $lasttype) {
								print "<tr>\n";
								print "	<td colspan=\"7\" style=\"background-color:#0c4173;font-size:14px;color:#eee\"><b>&nbsp;$type</b></td>\n";
								print "</tr>\n";
								$lasttype = $type;
							}
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\">$title</td>\n";
							print "<td valign=\"top\">$custtitle</td>\n";
							print "<td valign=\"top\" align=\"center\">$qty</td>\n";
							print "<td valign=\"top\" align=\"right\">".number_format($power,1)."</td>\n";
							print "<td valign=\"top\" align=\"center\">$ampssign</td>\n";
							print "<td valign=\"top\" align=\"right\">".number_format($powerg,1)."</td>\n";
							print "<td valign=\"top\" align=\"center\">$kwssign</td>\n";
							print "</tr>\n";
						}

						$ampsused = $power_available - $totalamps;
						$kwsused  = $kws_available - $totalkws;

						if ($ampsused < 0) {
							$ampsstyle = "color:Red;";
							$amps_exceeded = 1;
						} else {
							$ampsstyle = "color:Green;";
						}
						if ($kwsused < 0) {
							$kwsstyle = "color:Red;";
							$kws_exceeded = 1;
						} else {
							$kwsstyle = "color:Green;";
						}

						print "<tr>\n";
						print "	<td colspan=\"3\" style=\"border-top: double #999\">Power Available</td>\n";
						print "	<td align=\"right\" style=\"border-top: double #999\">".number_format($power_available,1)."</td>\n";
						print "	<td style=\"border-top: double #999\">&nbsp;</td>\n";
						print "	<td align=\"right\" style=\"border-top: double #999\">".number_format($kws_available,1)."</td>\n";
						print "	<td style=\"border-top: double #999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"3\" style=\"border-top:1px solid #999\">Power Used</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999\">".number_format($totalamps,1)."</td>\n";
						print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999\">".number_format($totalkws,1)."</td>\n";
						print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"3\" style=\"border-top:1px solid #999\">Power Remaining</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999; $ampsstyle\">".number_format($ampsused,1)."</td>\n";
						print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "	<td align=\"right\" style=\"border-top:1px solid #999; $kwsstyle\">".number_format($kwsused,1)."</td>\n";
						print "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\">No Items Added Yet</p>\n";
					}

					if ($amps_exceeded OR $kws_exceeded) {
						print "<p style=\"text-align:center;font-size:1.2em;color:red\">Power Exeeded!</p>\n";
						print "<p align=\"center\"><b>Please add a <span style=\"background-color:#ff9090\"> Power Upgrade</span> or Another Unit.</b></p>\n";
					}

					print "<p align=\"center\" class=\"larger\">Add/Update Items</p>\n";
					// get items
					$sql = "SELECT * FROM fitouts_items ORDER BY upgrade DESC, `type`, title";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<style>
							.element { width:400px; }
							.tdclass { padding: 10px 4px 10px 4px; }
							.tdclassamount { padding: 12px 4px 8px 4px; }
						</style>\n";

						print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-items.php\">\n";
						print "<input type=\"hidden\" name=\"items-posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999\"><b>Item/Notes</b></td>\n";
						print " <td style=\"border-bottom:1px solid #999\"><b>Customer Calls It/Details</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\"><b>Amps/Kws</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Amount</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Qty</b></td>\n";
						print "</tr>\n";

						$rownum = 1;

						$lasttype = "";

						while ($row = $result->fetch_assoc()) {
							$thisiid = $row['ID'];
							$upgrade = $row['upgrade'];
							$type	 = $row['type'];
							$amount  = $row['amount'];
							if ($currentitems[$thisiid]['title'])     { $title = $currentitems[$thisiid]['title']; }         else { $title = $row['title']; }
							if ($currentitems[$thisiid]['notes'])     { $notes = $currentitems[$thisiid]['notes']; }         else { $notes = $row['notes']; }
							if ($currentitems[$thisiid]['custtitle']) { $custtitle = $currentitems[$thisiid]['custtitle']; } else { $custtitle = $row['custtitle']; }
							if ($currentitems[$thisiid]['details'])   { $details = $currentitems[$thisiid]['details']; }     else { $details = $row['details']; }
							if ($currentitems[$thisiid]['power'] > 0.00)  { $power = $currentitems[$thisiid]['power']; }         else { $power = $row['power']; }
							if ($currentitems[$thisiid]['powerg'] > 0.00) { $powerg = $currentitems[$thisiid]['powerg']; }       else { $powerg = $row['powerg']; }
							if ($currentitems[$thisiid]['qty'] > 0)    { $qty = $currentitems[$thisiid]['qty']; }             else { $qty = ""; }

							if ($type != $lasttype) {
								print "<tr>\n";
								print "	<td colspan=\"6\" style=\"background-color:#0c4173;font-size:14px;color:#eee\"><b>&nbsp;$type</b></td>\n";
								print "</tr>\n";
								$lasttype = $type;
							}
						
							if ($upgrade) {
								print "<tr style=\"background-color:#ff9090\">\n";
							} else {
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							}
							
							print " <td class=\"tdclass\" align=\"right\" valign=\"top\">$rownum</td>\n";
							print "	<td class=\"tdclass\"><input name=\"newitems[$thisiid][title]\" type=\"text\" class=\"element\" maxlength=\"150\" value=\"$title\" /><br>\n";
							print "	<input name=\"newitems[$thisiid][notes]\" type=\"text\" class=\"element\" maxlength=\"150\" value=\"$notes\" /></td>\n";
							print "	<td class=\"tdclass\"><input name=\"newitems[$thisiid][custtitle]\" type=\"text\" class=\"element_medium\" maxlength=\"100\" value=\"$custtitle\" /><br>\n";
							print "	<input name=\"newitems[$thisiid][details]\" type=\"text\" class=\"element_medium\" maxlength=\"100\" value=\"$details\" /></td>\n";
							print "	<td class=\"tdclass\" align=\"right\"><input name=\"newitems[$thisiid][power]\" type=\"text\" class=\"element_tiny\" maxlength=\"10\" value=\"$power\" /><br>\n";
							print "	<input name=\"newitems[$thisiid][powerg]\" type=\"text\" class=\"element_tiny\" maxlength=\"10\" value=\"$powerg\" /></td>\n";
							print "	<td class=\"tdclassamount\" align=\"right\" valign=\"top\">$amount</td>\n";
							print "	<td class=\"tdclass\" valign=\"top\" align=\"right\"><input name=\"newitems[$thisiid][qty]\" type=\"text\" class=\"element_tiny\" maxlength=\"3\" value=\"$qty\" /></td>\n";
							
							$rownum++;
						}

						print "<tr>\n";
						print "	<td colspan=\"6\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						
						print "</table>\n";
						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
						print "</form>\n";
						
					} else {
						$resultMessage .= "<b>No Items Found</b>";
					}

					// notes form
					$sql = "SELECT inotes FROM fitouts_quotes WHERE ID = '$qid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$inotes = $row['inotes'];
					}

					$displaynotes = str_replace('\n', "<br>", $inotes);
					print "<blockquote><p class=\"larger\">Notes</p><p>$displaynotes</p></blockquote>\n";

					print "<blockquote>\n";
					print "<p><b>Add/Update Notes</b></p>\n";
					print "<form name=\"notesform\" method=\"post\" action=\"fitouts-quotes-items.php\">\n";
					print "<input type=\"hidden\" name=\"notes-posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
					print "<p><textarea name=\"inotes\" class=\"element_box\" cols=\"50\" rows=\"15\">$inotes</textarea></p>\n";
					print "<p><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
					print "</form>\n";
					print "</blockquote>\n";

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
			$resultMessage .= "<span class=\"redtext\"><b>Page Called Without ID</b></span><br>";
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

// print arrays
// print "<pre>";
// print_r($_POST);
// print_r($currentitems);
// print "</pre>";

print "</td>\n";
include('footer.php');
?>
