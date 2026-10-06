<?php
$page = "Cleaning";
$subpage = "Orders";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING - LOG DELIVERY</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$hid = $_REQUEST['hid'];

		if ($hid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get header
				$sql = "SELECT * FROM cl_poheaders WHERE ID='$hid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$hrow    = $result->fetch_assoc();
					$orddate = $hrow['orddate'];
					$crby    = $hrow['crby'];
					$orddate = date("d/m/Y",$orddate);
				}

				if ($_POST['posted']) {
					$lines = $_POST['lines'];
					for ($n = 0; $n < count($lines); $n++) {
						if (!is_numeric($lines[$n]['newqty'])) {
							$lines[$n]['error'] = "Not Numeric"; $e++;
						} else {
							$lines[$n]['error'] = "";
						}
					}
					if (!$e) {
						# post qties
						foreach ($lines AS $line) {
							$lid    = $line['lid'];
							$stid   = $line['stid'];
							$ordqty = $line['ordqty'];
							$delqty = $line['delqty'];
							$newqty = $line['newqty'];
							$totdel = $delqty+$newqty;
							if ($totdel >= $ordqty) {
								$lcompleted = 1;
							} else {
								$lcompleted = 0;
							}
							if ($newqty) {
								$sql = "UPDATE `cl_polines` SET `delqty`=`delqty`+'$newqty', `completed`='$lcompleted' WHERE ID='$lid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									# update stock
									$sql = "UPDATE `cl_stock` SET `level`=`level`+'$newqty' WHERE ID='$stid'";
									$result = $dbcnx->query($sql);
									if (!$result) {
										$resultMessage .= "<b>A stock line could not be updated</b><br>" . $dbcnx->error . "<br>";
									}
								} else {
									$resultMessage .= "<b>A PO line could not be updated</b><br>" . $dbcnx->error . "<br>";
								}
							}
						}
						# see if order is complete and save notes
						$sql = "SELECT * FROM `cl_polines` WHERE `hid`='$hid' AND `completed`='0'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							$resultMessage .= "<b>Incomplete Order Updated</b>";
						} else {
							$now = time();
							$sql = "UPDATE `cl_poheaders` SET status = '3', `deldate`='$now' WHERE ID='$hid'";
							$dbcnx->query($sql);
							$resultMessage .= "<b>Order Updated And Is Now Complete</b>";
						}
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','CL-Order $hid Delivery','$page','$now')");
					}

				} else {
					# get order rows
					$sql = "SELECT * FROM `cl_polines` WHERE `hid`='$hid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$lines = array();
						$n = 0;
						while ($row = $result->fetch_assoc()) {
							$lines[$n]['lid']          = $row['ID'];
							$lines[$n]['stid']         = $row['stid'];
							$lines[$n]['prodname']     = $row['prodname'];
							$lines[$n]['ordqty']       = $row['ordqty'];
							$lines[$n]['delqty']       = $row['delqty'];
							$sql = "SELECT `suppref` FROM `cl_products` WHERE ID='{$row['prodid']}'";
							$sresult = $dbcnx->query($sql);
							if ($sresult->num_rows) {
								$srow = $sresult->fetch_assoc();
								$lines[$n]['suppref'] = $srow['suppref'];
							}
							$lines[$n]['error']        = "";
							$n++;
						}
						$notes = $hrow['notes'];
						$notes = str_replace("<br>","\n",$notes);
					} else {
						print $dbcnx->error;
					}
				}
				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						print "<form name=\"addform\" method=\"post\" action=\"cl-porders-delivery.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
						print "<table align=\"center\" width=\"500px\" cellspacing=\"5\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Order Num:</b></td>\n";
						print "	<td colspan=\"7\"><b>$hid</b></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Order Date:</td>\n";
						print "	<td colspan=\"7\">$orddate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Ordered By:</td>\n";
						print "	<td colspan=\"7\">$crby</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"8\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td valign=\"bottom\"><i>Product</i></td>\n";
						print "	<td valign=\"bottom\"><i>Ref</i></td>\n";
						print "	<td valign=\"bottom\" width=\"10px\">&nbsp;</td>\n";
						print "	<td valign=\"bottom\" align=\"right\"><i>Qty</i></td>\n";
						print "	<td valign=\"bottom\" align=\"right\"><i>Already Received</i></td>\n";
						print "	<td valign=\"bottom\" align=\"right\"><i>Received Now</i></td>\n";
						print "	<td>&nbsp;</td>\n";
						print "</tr>\n";

						for ($n = 0; $n < count($lines); $n++) {
							print "<input type=\"hidden\" name=\"lines[$n][lid]\"          value=\"{$lines[$n]['lid']}\" />\n";
							print "<input type=\"hidden\" name=\"lines[$n][stid]\"         value=\"{$lines[$n]['stid']}\" />\n";
							print "<input type=\"hidden\" name=\"lines[$n][prodname]\"     value=\"{$lines[$n]['prodname']}\" />\n";
							print "<input type=\"hidden\" name=\"lines[$n][suppref]\"      value=\"{$lines[$n]['suppref']}\" />\n";
							print "<input type=\"hidden\" name=\"lines[$n][ordqty]\"       value=\"{$lines[$n]['ordqty']}\" />\n";
							print "<input type=\"hidden\" name=\"lines[$n][delqty]\"       value=\"{$lines[$n]['delqty']}\" />\n";
							print "<input type=\"hidden\" name=\"lines[$n][error]\"        value=\"{$lines[$n]['error']}\" />\n";
							print "<tr>\n";
							print "	<td>{$lines[$n]['prodname']}</td>\n";
							print "	<td>{$lines[$n]['suppref']}</td>\n";
							print "	<td width=\"10px\">&nbsp;</td>\n";
							print "	<td align=\"right\">{$lines[$n]['ordqty']}</td>\n";
							print "	<td align=\"right\">{$lines[$n]['delqty']}</td>\n";
							if ($lines[$n]['delqty'] < $lines[$n]['ordqty']) {
								print "	<td align=\"right\"><input name=\"lines[$n][newqty]\" type=\"text\" class=\"element_tiny\" maxlength=\"4\" value=\"{$lines[$n]['newqty']}\" /></td>\n";
							} else {
								print "<input type=\"hidden\" name=\"lines[$n][newqty]\" value=\"0\" />\n";
								print "<td align=\"right\">Completed</td>\n";
							}
							if ($lines[$n]['error']) {
								print "	<td><span class=\"redtext\">{$lines[$n]['error']}</span></td>\n";
							} else {
								print "	<td>&nbsp;</td>\n";
							}
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td colspan=\"8\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

					} else {
						$resultMessage = "<b>Could not find order!</b><br>" . $dbcnx->error;
					}
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without id!</b>\n";
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
include("footer.php");
?>
