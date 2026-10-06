<?php
$page = "Cleaning";
$subpage = "Orders";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$hid = $_REQUEST['hid'];

		if ($hid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$status = array(
					0 => "Entered",
					1 => "Approved",
					2 => "Ordered",
					3 => "Delivered",
					4 => "Invoiced",
					5 => "Completed"
				);
	
				$actions = array(
					0 => "Approve Order",
					1 => "Send Order",
					2 => "Log Delivery",
					3 => "Approve Invoice",
					4 => "Complete",
					5 => "Cleaning Order"
				);

				// get the users cladmin rights
				$sql = "SELECT cladmin FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$row = $uresult->fetch_assoc();
					$cladmin = $row['cladmin'];
				}

				// get header
				$sql = "SELECT o.*, s.siteaddress, sup.suppname, sup.email
						FROM cl_poheaders AS o
						LEFT JOIN sites AS s ON o.site = s.title
						LEFT JOIN suppliers AS sup ON o.suppid = sup.ID
						WHERE o.ID='$hid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$hrow = $result->fetch_assoc();
					$thisstatus  = $hrow['status'];
					$siteaddress = $hrow['siteaddress'];
					$crdate      = date("d-m-Y",$hrow['crdate']);
					if ($hrow['appdate'])    { $appdate	     = date("d-m-Y",$hrow['appdate']); }
					if ($hrow['sentdate'])   { $sentdate	 = date("d-m-Y",$hrow['sentdate']); }
					if ($hrow['payappdate']) { $payappdate	 = date("d-m-Y",$hrow['payappdate']); }
					$site        = $hrow['site'];
					$ordernum    = str_pad($hrow['ID'], 5, "0", STR_PAD_LEFT);
					if ($hrow['deldate']) { $deldate = date("d-m-Y",$hrow['deldate']); } else { $deldate = "&nbsp;"; }

					print "<p align=\"center\" class=\"larger\">{$actions[$thisstatus]}</p>\n";

					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Supplier:</b></td>\n";
					print "<td>{$hrow['suppname']}</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Supplier Email:</b></td>\n";
					print "<td>{$hrow['email']}</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Order Number:</b></td>\n";
					print "<td>$ordernum</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Site:</b></td>\n";
					print "<td>$site</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Delivery Addreess:</b></td>\n";
					print "<td>" . nl2br("$siteaddress") . "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Entered:</b></td>\n";
					print "<td>$crdate by {$hrow['crby']}</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Order Approved:</b></td>\n";
					if ($appdate) {
						print "<td>$appdate by {$hrow['appby']}</td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Order Sent:</b></td>\n";
					if ($sentdate) {
						print "<td>$sentdate by {$hrow['sentby']}</td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Payment Approved:</b></td>\n";
					if ($payappdate) {
						print "<td>$payappdate by {$hrow['payappby']}</td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\" valign=\"top\"><b>Completed:</b></td>\n";
					if ($thisstatus == 5) {
						print "<td>Yes</td>\n";
					} else {
						print "<td>No</td>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes To Supplier:</b></td>\n";
					print "	<td>{$hrow['notes']}</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\">&nbsp;</p>\n";

					# get lines
					$sql = "SELECT * FROM cl_polines WHERE hid='$hid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td><i>Product</i></td>\n";
						print "	<td><i>Ref</i></td>\n";
						print "	<td width=\"10px\">&nbsp;</td>\n";
						print "	<td align=\"right\"><i>Qty</i></td>\n";
						print "	<td align=\"right\"><i>Each</i></td>\n";
						print "	<td align=\"right\"><i>Total</i></td>\n";
						print "	<td align=\"right\"><i>Received</i></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"7\" height=\"1px\"><img src=\"images/blue.gif\" width=\"100%\" height=\"1px\" alt=\"\" /></td>\n";
						print "</tr>\n";

						while ($lrow = $result->fetch_assoc()) {
							print "<tr>\n";
							print "	<td>{$lrow['prodname']}</td>\n";
							// added by Mat 27.01.14
							$sql = "SELECT suppref FROM cl_products WHERE ID='{$lrow['prodid']}'";
							$pidresult = $dbcnx->query($sql);
							if ($pidresult->num_rows) {
									$pidrow = $pidresult->fetch_assoc();
									$suppref = $pidrow['suppref'];
							}
							print "	<td>$suppref</td>\n";
							print "	<td width=\"10px\">&nbsp;</td>\n";
							print "	<td align=\"right\">{$lrow['ordqty']}</td>\n";
							print "	<td align=\"right\">{$lrow['ordprice']}</td>\n";
							print "	<td align=\"right\">{$lrow['ordlinetotal']}</td>\n";
							print "	<td align=\"right\">{$lrow['delqty']}</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td colspan=\"7\" height=\"1px\"><img src=\"images/blue.gif\" width=\"100%\" height=\"1px\" alt=\"\" /></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"5\"><i>Order Total</i></td>\n";
						print "	<td align=\"right\"><b>{$hrow['ordertotal']}</b></td>\n";
						print "	<td>&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\">No Order Lines Found!<br>" . $dbcnx->error . "</p>\n";
					}

					if ($thisstatus < 5) {
						print "<p align=\"center\">";
						if ($thisstatus == 2) {
							print "	<a href=\"cl-porders-delivery.php?hid=$hid&amp;update=1\">Yes {$actions[$thisstatus]}</a>&nbsp;|&nbsp;<a href=\"cl-porders-list.php\">No DON'T {$actions[$thisstatus]}</a>";
						} else {
							print "	<a href=\"cl-porders-update.php?hid=$hid&amp;update=1\">Yes {$actions[$thisstatus]}</a>&nbsp;|&nbsp;<a href=\"cl-porders-list.php\">No DON'T {$actions[$thisstatus]}</a>";
						}
						print "</p>\n";
					}
				} else {
					print "<p align=\"center\">No Orders Found!<br>" . $dbcnx->error . "</p>\n";
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
