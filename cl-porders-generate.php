<?php
$page = "Cleaning";
$subpage = "Orders";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		print "<p align =\"center\" class=\"heading\">CLEANING - GENERATE PURCHASE ORDER</p>\n";
		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

			$site = $_REQUEST['site'];
			if ($site == "Select") { $site = ""; }
			if (!$site) {
				if ($accessRights[$page] < 3) {
					# get the users site
					$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
					$uresult = $dbcnx->query($sql);
					if ($uresult->num_rows) {
						$row = $uresult->fetch_assoc();
						$site = $row['site'];
					}
				}
			}

			if ($site) {

				print "<p align=\"center\" class=\"larger\">Cleaning Order - $site</p>\n";
				if ($_POST['posted']) {
					$lines = $_POST['lines'];
					$notes = $_POST['notes'];

					if (!$e) {
						
						$thissuppid = 0;
						$now = time();

						foreach ($lines AS $suppid => $order) {

							$totqty = 0;
							// if order is not empty
							foreach($order as $n => $orderlines) {
								if ($orderlines['ordqty']) {
									$totqty += $orderlines['ordqty'];
								}
							}

							if ($totqty) {
							
								if ($suppid != $thissuppid) {
									$thissuppid = $suppid;
									
									if ($notes[$thissuppid]) {
										$thisnotes = $notes[$thissuppid];
									} else {
										$thisnotes = "";
									}
									
									$sql = "INSERT INTO cl_poheaders VALUES (
										null,
										'$site',
										'$suppid',
										'$now',
										'0',
										'0',
										'0',
										'0',
										'{$_SESSION['username']}',
										'$now',
										'',
										'0',
										'',
										'0',
										'',
										'0',
										'$thisnotes'
									)";
									if ($dbcnx->query($sql)) {
										$hid = $dbcnx->insert_id;
										$resultMessage .= "Purchase order header created<br>";
										foreach ($order AS $n => $orderlines) {
											$stid     = $orderlines['stid'];
											$prodid   = $orderlines['prodid'];
											$prodname = $orderlines['prodname'];
											$ordqty   = $orderlines['ordqty'];
											if ($ordqty) {
												$sql = "SELECT unitprice, suppref FROM cl_products WHERE ID='$prodid'";
												$upresult = $dbcnx->query($sql);
												if ($upresult->num_rows) {
													$uprow = $upresult->fetch_assoc();
													$unitprice = $uprow['unitprice'];
													$suppref = $uprow['suppref'];
												} else {
													$unitprice = 0;
												}
												$ordlinetotal = $ordqty*$unitprice;
												$ordertotal  += $ordlinetotal;
												$sql = "INSERT INTO cl_polines VALUES (
													null,
													'$hid',
													'$stid',
													'$prodid',
													'$prodname',
													'$ordqty',
													'0',
													'$unitprice',
													'0',
													'$ordlinetotal',
													'0',
													'0'
												)";
												if ($dbcnx->query($sql)) {
													$resultMessage .= "$prodname added to order<br>";
												} else {
													$resultMessage .= "Could not create purchase order line<br>" . $dbcnx->error;
												}
											}
										}
										$sql = "UPDATE cl_poheaders SET ordertotal='$ordertotal' WHERE ID='$hid'";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "Order total added<br>";
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$site POrder $hid','$page','$now')");
										} else {
											$resultMessage .= "Could not add order total.<br>" . $dbcnx->error;
										}
									} else {
										$resultMessage .= "Could not create purchase order header<br>" . $dbcnx->error;
									}
								}
							} else {
								$resultMessage .= "No order placed for supplier $suppid<br>";
							}
						}
					}
					
				} else {
					# get everything below the higher threshold
					$sql = "SELECT st.ID, st.prodid, pr.prodname, st.level, st.threslow, st.threshigh, pr.suppid
							FROM cl_stock st
							LEFT JOIN cl_products pr ON pr.ID = st.prodid
							WHERE st.level < st.threshigh AND st.site = '$site'
					";
					$genresult = $dbcnx->query($sql);
					if ($genresult->num_rows) {
						# put it all in an array
						$lines = array();
						$n = 0;
						while ($row = $genresult->fetch_assoc()) {
							$lines[$row['suppid']][$n]['stid']      = $row['ID'];
							$lines[$row['suppid']][$n]['prodid']    = $row['prodid'];
							$lines[$row['suppid']][$n]['prodname']  = $row['prodname'];
							$lines[$row['suppid']][$n]['level']     = $row['level'];
							$lines[$row['suppid']][$n]['threslow']  = $row['threslow'];
							$lines[$row['suppid']][$n]['threshigh'] = $row['threshigh'];
							# see if its already on any orders
							$orders = 0;
							$sql = "SELECT hid, ordqty, delqty FROM `cl_polines` WHERE `stid`='{$row['ID']}' AND `completed`='0'";
							$ordersresult = $dbcnx->query($sql);
							if ($ordersresult->num_rows) {
								while ($ordrow = $ordersresult->fetch_assoc()) {
									$orders += $ordrow['ordqty']-$ordrow['delqty'];
								}
							}
							if ($orders) {
								$lines[$row['suppid']][$n]['ordqty'] = 0;
							} else {
								$lines[$row['suppid']][$n]['ordqty'] = $row['threshigh'] - $row['level'];
							}
							$lines[$row['suppid']][$n]['orders'] = $orders;
							$lines[$row['suppid']][$n]['error']  = "";
							$n++;
						}
					} else {
						$resultMessage = "<b>No stock is below higher threshold at $site</b>";
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
						print "<form name=\"addform\" method=\"post\" action=\"cl-porders-generate.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"5\">\n";

						// sort array by suppid
						ksort($lines);

						// print "<pre>"; print_r($lines); print "</pre>";

						$thissuppid = 0;

						foreach ($lines AS $suppid => $order) {
							
							if ($suppid != $thissuppid) {
								$thissuppid = $suppid;
								$sql = "SELECT suppname FROM suppliers WHERE ID = '$thissuppid'";
								$supresult = $dbcnx->query($sql);
								if ($supresult->num_rows) {
									$suprow = $supresult->fetch_assoc();
									$thissuppname = $suprow['suppname'];
								}
								print "<tr>\n";
								print "	<td colspan=\"7\" class=\"larger\"><br>$thissuppname</td>\n";
								print "</tr>\n";
								print "<tr>\n";
								print "	<td><b>Product</b></td>\n";
								print "	<td align=\"right\"><b>Level</b></td>\n";
								print "	<td align=\"right\"><b>Lower</b></td>\n";
								print "	<td align=\"right\"><b>Higher</b></td>\n";
								print "	<td align=\"right\"><b>On Order</b></td>\n";
								print "	<td><b>Order</b></td>\n";
								print "	<td>&nbsp;</td>\n";
								print "</tr>\n";
							}
							foreach ($order AS $n => $orderlines) {
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][stid]\"      value=\"{$orderlines['stid']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][prodid]\"    value=\"{$orderlines['prodid']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][prodname]\"  value=\"{$orderlines['prodname']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][level]\"     value=\"{$orderlines['level']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][threslow]\"  value=\"{$orderlines['threslow']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][threshigh]\" value=\"{$orderlines['threshigh']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][orders]\"    value=\"{$orderlines['orders']}\" />\n";
								print "<input type=\"hidden\" name=\"lines[$suppid][$n][error]\"     value=\"{$orderlines['error']}\" />\n";
								print "<tr>\n";
								print "	<td>{$orderlines['prodname']}</td>\n";
								print "	<td align=\"right\">{$orderlines['level']}</td>\n";
								print "	<td align=\"right\">{$orderlines['threslow']}</td>\n";
								print "	<td align=\"right\">{$orderlines['threshigh']}</td>\n";
								print "	<td align=\"right\">{$orderlines['orders']}</td>\n";
								if ($orderlines['orders']) {
									print "	<td>Ordered</td>\n";
									print "<input type=\"hidden\" name=\"lines[$suppid][$n][ordqty]\" value=\"{$orderlines['ordqty']}\" />\n";
								} else {
									print "	<td><input name=\"lines[$suppid][$n][ordqty]\" type=\"text\" class=\"element_tiny\" maxlength=\"4\" value=\"{$orderlines['ordqty']}\" /></td>\n";
								}
								if ($orderlines['error']) {
									print "	<td><span class=\"redtext\">{$orderlines['error']}</span></td>\n";
								} else {
									print "	<td>&nbsp;</td>\n";
								}
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td colspan=\"6\">Notes To $thissuppname:<br />";
							print "<textarea name=\"notes[$suppid]\" wrap=\"virtual\" rows=\"5\" style=\"width:500px; border:1px solid #666666; font-family:arial,sans-serif; font-size:12px;\">{$notes[$suppid]}</textarea></td>\n";
							print "	<td>&nbsp;</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td colspan=\"7\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";
					}
				}
			} else {
				if ($accessRights[$page] > 2) {
					print "<p align=\"center\"><b>Select Site</b></p>\n";
					print "<form name=\"form1\" method=\"post\" action=\"cl-porders-generate.php\">\n";
					print "<table class=\"filterBlock\" align=\"center\">\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Site</b></td>\n";
					print "		<td><select name=\"site\" class=\"element_small\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					$sql = "SELECT title FROM sites ORDER BY title";
					$sresult = $dbcnx->query($sql);
					if ($sresult->num_rows) {
						while ($srow = $sresult->fetch_assoc()) {
							print "		<option value=\"{$srow['title']}\""; if ($lo == $srow['title']) { print " SELECTED"; } print ">{$srow['title']}</option>\n";
						}
					}
					print "		</select>";
					print "		</td>\n";
					print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "	</tr>\n";
					print "</table>\n";
					print "</form>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-porders-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
