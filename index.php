<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Home";
include('settings.php');
include('session.php');
include('header.php');
?>

	<script type="text/javascript">

		$(document).ready(function() {

			  setInterval( function() {
			  var seconds = new Date().getSeconds();
			  var sdegree = seconds * 6;
			  var srotate = "rotate(" + sdegree + "deg)";

			  $("#sec").css({"-moz-transform" : srotate, "-webkit-transform" : srotate});

			  }, 1000 );


			  setInterval( function() {
			  var hours = new Date().getHours();
			  var mins = new Date().getMinutes();
			  var hdegree = hours * 30 + (mins / 2);
			  var hrotate = "rotate(" + hdegree + "deg)";

			  $("#hour").css({"-moz-transform" : hrotate, "-webkit-transform" : hrotate});

			  }, 1000 );


			  setInterval( function() {
			  var mins = new Date().getMinutes();
			  var mdegree = mins * 6;
			  var mrotate = "rotate(" + mdegree + "deg)";

			  $("#min").css({"-moz-transform" : mrotate, "-webkit-transform" : mrotate});

			  }, 1000 );

		});

	</script>
	<!-- BLINK element for Stock Alert -->
	<script type="text/javascript">
  setInterval(function(){ $('blink').each( function(){ $(this).css('visibility' , $(this).css('visibility') === 'hidden' ? '' : 'hidden') } ); }, 750);
</script>

<?php

if ($_POST['changepassword']) {
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		$newpassword    = $_POST['newpassword'];
		$retypepassword = $_POST['retypepassword'];
		if ((!$newpassword) || (!$retypepassword)) { $resultMessage = "Please enter new password in both boxes."; }
		elseif ($newpassword != $retypepassword) { $resultMessage = "New password and retype password do not match"; }
		elseif (strlen($newpassword) < 6) { $resultMessage = "New password must be at least 6 characters"; }
		elseif ($newpassword == $_SESSION['pwd']) { $resultMessage = "You cannot reuse your existing password"; }
		else {
			$thispassword = $dbcnx->real_escape_string($newpassword);
			$Salt = generateRandomString();
			$Algo = '6';
			$Rounds = '5000';
			$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
			$hashed_password = crypt($thispassword, $CryptSalt);
			$expires = strtotime("+$changePasswordMonths months");
			$sql = "UPDATE staff SET password='$hashed_password', expires='$expires' WHERE ID = '{$_SESSION['user']}'";
			$cpresult = $dbcnx->query($sql);
			if ($cpresult) {
				$_SESSION['pwd'] = $newpassword;
				$now = time();
				$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Changed Password','','Session','$now')");
				$expiryDate = date("M jS Y",$expires);
				$expiryTime = date("H:i",$expires);
				print "<td class=\"main\">\n";
				print "<p align=\"center\"><b>Password Successfully Changed</b></p>\n";
				print "<p align=\"center\">Your new password expires at $expiryTime on $expiryDate.</p>\n";
				print "<p align=\"center\"><a href=\"index.php\">Continue</a></p>\n";
				print "</td>\n";
				include('footer.php');
				exit;
			} else {
				$resultMessage = "<b>Password Change Failed for user {$_SESSION['user']}</b><br />" . $dbcnx->error . "<br />&nbsp;<br />Please contact the admin.";
			}
		}
		$dbcnx->close();
	} else {
		$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
	}

}

if ($passwordExpired) {
	$expiryDate = date("M jS Y",$passwordExpires);
	$expiryTime = date("H:i",$passwordExpires);
	print "<td class=\"main\">\n";
	print "<p class=\"heading\" align=\"center\">PASSWORD EXPIRED</p>\n";
	print "<p align=\"center\">Passwords are valid for $changePasswordMonths months.<br />Your password expired at $expiryTime on $expiryDate.</p>\n";
	print "<p align=\"center\">Passwords must be at least 6 characters in length.<br />You cannot reuse your existing password.</p>\n";
	print "<p align=\"center\" class=\"redtext\">$resultMessage</p>
	<form method=\"post\" action=\"index.php\">
	<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">
	<input type=\"hidden\" name=\"changepassword\" value=\"1\" />
	<tr>
		<td align=\"right\" class=\"bodytext\">New Password:</td>
		<td><input class=\"element\" type=\"password\" name=\"newpassword\" size=\"40\" /></td>
	</tr>
	<tr>
		<td align=\"right\" class=\"bodytext\">Retype New Password:</td>
		<td><input class=\"element\" type=\"password\" name=\"retypepassword\" size=\"40\" /></td>
	</tr>
	<tr>
		<td colspan=\"2\" align=\"right\" class=\"bodytext\">
		<input type=\"submit\" value=\"Submit\" class=\"button\">
		</td>
	</tr>
	</table>
	</form>
	</td>\n";
}

elseif ($_SESSION['user']) {

	print "		<td class=\"main\" align=\"center\" valign=\"top\">";
	print "		<div class=\"indextop\" style=\"text-align:center;width:100%;\">\n";

	if ($homePageSections['StockLevelWarning'] == "on") {
		# Cleaning Stock Below Minimum
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			if ($accessRights['Cleaning'] == 2) {
				# get users site
				$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$urow = $uresult->fetch_assoc();
					$userssite = $urow['site'];
					# get stock below minimum at this site
					$sql = "SELECT ID FROM `cl_stock` WHERE `level`<`threslow` AND `site`='$userssite'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						# see if they are already on any orders
						$notordered = 0;
						$ordered    = 0;
						while ($row = $result->fetch_assoc()) {
							$stid = $row['ID'];
							$sql = "SELECT ID FROM `cl_polines` WHERE `stid`='$stid' AND `completed`='0'";
							$ordersresult = $dbcnx->query($sql);
							if ($ordersresult->num_rows) {
								$ordered++;
							} else {
								$notordered++;
							}
						}
						if ($notordered) {
							# something is not on order
							print "<div id=\"stock-alert\">";
							print "<p align=\"center\">";
							print "<b>STOCK LEVEL WARNING</b><br />";
							print "<blink><b>Cleaning stock levels are below minimum!</b></blink><br />";
							print "<a href=\"cl-porders-generate.php?site=$userssite\">Start Purchase Order Now</a>";
							print "</p>\n";
							print "</div>\n";
						}
					}
				}
			}
			$dbcnx->close();
		}
	}
	print "</div>\n";

	print "<p style=\"font-size:16px;margin-bottom:10px;\">BLANK CONTRACTS</p>\n";
	print "<p style=\"font-size:12px;margin-top:0px;padding:0;\">";
	print "<a href=\"/backoffice/bk-contract-combined-pdf.php\" target=\"_blank\">Blank Contract</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
	print "<a href=\"dephna-directdebit-form.php\" target=\"_blank\">Direct Debit Form</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
	print "<a href=\"brochures-list.php\">Brochures</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
	print "<a href=\"pdf/documents/Client Check In document (V2).pdf\" target=\"_blank\">Check-In Document</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
	print "<a href=\"pdf/documents/Client Check Out document.pdf\" target=\"_blank\">Check-Out Document</a></p>\n";
	print "<p style=\"font-size:14px;margin-top:10px;\"><a href=\"dephna-prices.php\">DEPHNA PRICES</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"leads-new.php\">ADD A LEAD</a></p>\n";

	// report XWeb errors
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		$sql = "SELECT x.xsub, x.fldate, s.title AS sitename FROM XWebAuth AS x LEFT JOIN sites AS s ON s.ID = x.siteid WHERE x.flagged = '1'";
		if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
			print "<p align=\"center\" class=\"redtext\">\n";
			while ($row = $result->fetch_assoc()) {
				$sitename = $row['sitename'];
				$xsub     = $row['xsub'];
				$fldate   = $row['fldate'];
				$fldate = date("H:i \o\\n d-m-Y",$fldate);
				print "XWeb $sitename ($xsub) has not responded since $fldate<br>\n";
			}
			print "</p>\n";
		}
		$dbcnx->close();
	}

	if ($homePageSections['PaymentsApproved'] == "on") {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			print "<p align=\"center\" class=\"larger\">Payments Awaiting Authorisation</p>\n";
			// get approved requests from purchases
			$sql = "SELECT SUM(p.total) AS total, s.suppname, s.ID AS suppid
					FROM purchases p, suppliers s
					WHERE p.suppid = s.ID AND p.topay > '1'
					GROUP BY p.suppid
					ORDER BY s.suppname
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "</tr>\n";
				$grandTotal = 0;
				while ($row = $result->fetch_assoc()) {
					$suppid   = $row['suppid'];
					$suppname = $row['suppname'];
					$total    = $row['total'];
					$grandTotal += $total;
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$suppname</td>\n";
					print "	<td align=\"right\">" . number_format($total,2) . "</td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #999999\"><b>Total</b></td>\n";
				print " <td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($grandTotal,2) . "</b></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><a href=\"purchases-paymentrun.php\">Approve Payments</a></p>\n";
			} else {
				print "<p align=\"center\">No Payments Awaiting Authorisation.</p>\n";
			}
			// get approved requests from deposits
			$sql = "SELECT d.amount, c.compname
					FROM deposit_requests AS d
					LEFT JOIN users AS c ON d.custref = c.custref
					WHERE d.refunded = '0' AND d.approve = '1' AND d.topay = '1'
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				print "<p align=\"center\" class=\"larger\">Deposit Requests Awaiting Authorisation</p>\n";
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "</tr>\n";
				$grandTotal = 0;
				while ($row = $result->fetch_assoc()) {
					$compname = $row['compname'];
					$amount   = $row['amount'];
					$grandTotal += $amount;
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$compname</td>\n";
					print "	<td align=\"right\">" . number_format($amount,2) . "</td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #999999\"><b>Total</b></td>\n";
				print " <td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($grandTotal,2) . "</b></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><a href=\"deposits-paymentrun.php\">Approve Deposits</a></p>\n";
			} else {
				print "<p align=\"center\">No Deposits Awaiting Authorisation</p>\n";
			}
			$dbcnx->close();
		}
	}

	if ($homePageSections['CleaningOrders'] == "on") {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			print "<p align=\"center\" class=\"larger\">Cleaning Orders Awaiting Approval</p>\n";
			$sql = "SELECT o.ID, o.site, o.orddate, o.ordertotal, o.status, s.suppname
					FROM cl_poheaders AS o
					LEFT JOIN suppliers AS s ON o.suppid = s.ID
					WHERE o.status = '0'
					ORDER BY o.orddate DESC
			";
			if (($clresult = $dbcnx->query($sql)) AND ($clresult->num_rows)) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Order</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Entered</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Total</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $clresult->fetch_assoc()) {
					$orddate = $row['orddate'];
					$orddate = date("d-m-Y",$orddate);
					$thisstatus = $row['status'];
					$ordernum = $row['ID'];
					$ordernum = str_pad($ordernum, 5, "0", STR_PAD_LEFT);
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td align=\"left\">$ordernum</td>\n";
					print "	<td align=\"left\">$orddate</td>\n";
					print "	<td align=\"left\">{$row['site']}</td>\n";
					print "	<td align=\"left\">{$row['suppname']}</td>\n";
					print "	<td align=\"left\">{$row['ordertotal']}</td>\n";
					print "	<td align=\"center\"><a href=\"cl-porders-view.php?hid={$row['ID']}\">View</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Orders Awaiting Approval.</p>\n";
			}
			$dbcnx->close();
		}
	}

	if ($homePageSections['DebtorsReminders'] == "on") {

		// debtors reminders
		print "<p align=\"center\" class=\"larger\">Debtors - Reminders Set for Today</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$remdate = date("Y-m-d");
			$sql = "SELECT n.*, u.compname, u.ID AS custid
					FROM ad_notes AS n
					LEFT JOIN users AS u ON u.custref = n.custref
					WHERE n.remdate = '$remdate'
			";
			if (($remresult = $dbcnx->query($sql)) AND ($remresult->num_rows)) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Note Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"350\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Noted By</b></td>\n";
				print "</tr>\n";
				$moreRowCount = 1;
				while($remrow = $remresult->fetch_assoc()) {
					$notedate = $remrow['date'];
					$compname = $remrow['compname'];
					$details  = $remrow['details'];
					$crby     = $remrow['crby'];
					$custid   = $remrow['custid'];
					// $details = str_replace("£","&pound;",$details);
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 17) . "..."; }
					if ($moreRowCount > 4) {
						print "<tr class=\"moreRows_1 r" . ($i++ % 2) . "\">\n";
					} else {
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					}
					print "	<td valign=\"top\">$notedate</td>\n";
					print "	<td valign=\"top\"><a href=\"sl-ledger.php?id=$custid\">$compname</a></td>\n";
					print "	<td valign=\"top\" width=\"350\">$details</td>\n";
					print "	<td valign=\"top\">$crby</td>\n";
					print "</tr>\n";
					$moreRowCount++;
				}
				if ($remresult->num_rows > 4) {
					print "<tr>\n";
					print " <td colspan=\"4\" align=\"center\" style=\"border-top:1px solid #999999\">[ <a href=\"#\"><span class=\"btToggle_1\">more</span></a> ]</td>\n";
					print "</tr>\n";
				} else {
					print "<tr>\n";
					print " <td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";

			} else {
				print "<p align=\"center\">No set reminders for today.</p>\n";
			}
			$dbcnx->close();
		}
	}

	if ($homePageSections['KeyFobRequests'] == "on") {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			// key fob requests
			/* if ($_REQUEST['delfq']) {
				$fobreqid = $_REQUEST['fobreqid'];
				if ($fobreqid) {
					$sql = "DELETE FROM pax_user_requests WHERE ID = '$fobreqid'";
					$result = $dbcnx->query($sql);
				}
			} */
			$sql = "SELECT r.*, s.title AS sitename, u.compname AS compname
					FROM pax_user_requests AS r
					LEFT JOIN sites AS s ON s.ID = r.siteid
					LEFT JOIN users AS u ON u.ID = r.custid
					WHERE r.processed = '0'
					ORDER BY r.xdatetime DESC
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<p align=\"center\" class=\"larger\">Key Fob Requests</p>\n";
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>User</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Notes</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$fobreqid   = $row['ID'];
					$xdatetime  = $row['xdatetime'];
					$siteid     = $row['siteid'];
					$custid     = $row['custid'];
					$custref    = $row['custref'];
					$userid     = $row['userid'];
					$sitename   = $row['sitename'];
					$compname   = $row['compname'];
					$firstName  = $row['firstName'];
					$middleName = $row['middleName'];
					$lastName   = $row['lastName'];
					$telephone  = $row['telephone'];
					$notes      = $row['notes'];
					if ($siteid == 11) { $siteid = 34; } // Coronation uses Brittania Way
					list($xdate,$xtime) = explode(" ",$xdatetime);
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
					if (strlen($notes) > 20) { $notes = substr($notes, 0, 20) . "..."; }
					$usernm = $firstName;
					if ($lastName) {
						$usernm .= " $lastName";
					}
					if (strlen($usernm) > 20) { $usernm = substr($usernm, 0, 20); }
					$customer = "$custid--$custref--$compname";
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$xdate</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$usernm</td>\n";
					print "	<td>$notes</td>\n";
					print "	<td>Tap Fob</td>\n";
					print "	<td>";
					if ($userid) {
						print "<form method=\"post\" action=\"pax-users-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
						print "<input type=\"hidden\" name=\"fobreqid\" value=\"$fobreqid\" />\n";
						print "<input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Process\">\n";
						print "</form>\n";
					} else {
						print "<form method=\"post\" action=\"pax-users-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"fobreqid\" value=\"$fobreqid\" />\n";
						print "<input type=\"hidden\" name=\"firstName\" value=\"$firstName\" />\n";
						print "<input type=\"hidden\" name=\"middleName\" value=\"$middleName\" />\n";
						print "<input type=\"hidden\" name=\"lastName\" value=\"$lastName\" />\n";
						print "<input type=\"hidden\" name=\"telephone\" value=\"$telephone\" />\n";
						print "<input type=\"hidden\" name=\"isAntiPassbackUser\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"customer\" value=\"$customer\" />\n";
						print "<input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Process\">\n";
						print "</form>\n";
					}
					print "</td>";
					print "	<td><a href=\"pax-users-request.php?siteid=$siteid&amp;delfq=1&reqid=$fobreqid\">Delete</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			}
			$dbcnx->close();
		}
	}

	if ($homePageSections['ColdRoomTests'] == "on") {
		// get cold room tests
		print "<p align=\"center\" class=\"larger\">Cold Room Tests</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			// get coldroom tests with status 1 and not resdate
			$sql = "SELECT t.*, s.title AS sitename, u.title AS unitname
					FROM xweb_test_headers AS t
					LEFT JOIN sites AS s ON s.ID = t.siteid
					LEFT JOIN units AS u ON u.ID = t.unitid
					WHERE t.status = '1'
					  AND t.resdate = '0'
					ORDER BY t.crdate DESC
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Started</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Ended</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$testid    = $row['ID'];
					$sitename  = $row['sitename'];
					$unitname  = $row['unitname'];
					$siteid	   = $row['siteid'];
					$unitid	   = $row['unitid'];
					$crdate    = $row['crdate'];
                    $testHours = $row['testHours'];
                    $testUntil = $row['testUntil'];
                    $crby      = $row['crby'];
                    $crdate    = date('d/m/Y H:i', $crdate);
                    $testUntil = date('d/m/Y H:i', $testUntil);
					list($junk,$unitname) = explode(" ",$unitname);
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\">$crdate</td>\n";
					print "	<td valign=\"top\">$crby</td>\n";
					print "	<td valign=\"top\">$sitename</td>\n";
					print "	<td valign=\"top\">$unitname</td>\n";
					print "	<td valign=\"top\">$testUntil</td>\n";
					print "	<td valign=\"top\"><a href=\"coldrooms-xweb-tests-view.php?siteid=$siteid&amp;unitid=$unitid&amp;testid=$testid\">Complete</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No uncompleted cold room tests.</p>\n";
			}
			$dbcnx->close();
		}
	}

	if ($homePageSections['TrainingDiary'] == "on") {
	// training diary
		print "<p align=\"center\" class=\"larger\">Training Diary</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			# get some records
			$sql = "SELECT t.ID, t.sid, t.cid, t.started, s.title AS sitename, u.compname AS compname, i.ID AS iid
					FROM training_sessions AS t
					LEFT JOIN sites AS s ON t.sid = s.ID
					LEFT JOIN users AS u ON t.cid = u.ID
					LEFT JOIN training_sessions_included AS i ON t.ID = i.tsid
					WHERE !t.completed AND i.ID
					ORDER BY t.started DESC, sitename, compname
					LIMIT 0,8
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Started</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "</tr>\n";
				$moreRowCount = 1;
				while ($row = $result->fetch_assoc()) {
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$started  = $row['started'];
					list($y,$m,$d,$t) = explode("-",$started);
					$started = "$d-$m-$y";
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 17) . "..."; }
					if ($moreRowCount > 4) {
						print "<tr class=\"moreRows_2 r" . ($i++ % 2) . "\">\n";
					} else {
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					}
					print "	<td valign=\"top\">$started</td>\n";
					print "	<td valign=\"top\">$sitename</td>\n";
					print "	<td valign=\"top\">$compname</td>\n";
					print "	<td valign=\"top\"><a href=\"training-sessions-print.php?id=$id\" target=\"_blank\">Print</a></td>\n";
					print "	<td valign=\"top\"><a href=\"training-sessions-complete.php?id=$id\">Complete</a></td>\n";
					print "</tr>\n";
					$moreRowCount++;
				}
				if ($result->num_rows > 4) {
					print "<tr>\n";
					print " <td colspan=\"5\" align=\"center\" style=\"border-top:1px solid #999999\">[ <a href=\"#\"><span class=\"btToggle_2\">more</span></a> ]</td>\n";
					print "</tr>\n";
				} else {
					print "<tr>\n";
					print " <td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
		
			} else {
				print "<p align=\"center\">No uncompleted training found.</p>\n";
			}
			// if david, mat, raj
			if (in_array( $_SESSION['user'], array(1,3,6))) {
				// get completed, unsigned
				$sql = "SELECT COUNT(*) AS numrows FROM training_sessions WHERE completed AND !confirmed";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$numrows = $row['numrows'];
					if ($numrows > 1) { $trses = "Sessions"; } else { $trses = "Session"; }
					print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>COMPLETED</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\">$numrows Training $trses to <a href=\"training-sessions-list.php\">Sign Off</a></tr>";
					print "</tr>\n";
					print "</table>\n";
				}
			}
			$dbcnx->close();
		}
	}

	// Holiday Diary
	if ($homePageSections['HolidayDiary'] == "on") {
		print "<p align=\"center\" class=\"larger\">Holiday Diary</p>\n";
		// get holidays booked in the next $diaryperiod days.  Set in settings.
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			if ($diaryperiod > 0) {
				
				// who is normally off during this period
				$runningday = date("w");
				$thisdate = date("Y-m-d");
				$offarray = array();
				for ($i = 0; $i <= $diaryperiod; $i++) {
					if ($runningday == 1) {
						$search = "_-n-_-_-_-_-_";
					} elseif ($runningday == 2) {
						$search = "_-_-n-_-_-_-_";
					} elseif ($runningday == 3) {
						$search = "_-_-_-n-_-_-_";
					} elseif ($runningday == 4) {
						$search = "_-_-_-_-n-_-_";
					} elseif ($runningday == 5) {
						$search = "_-_-_-_-_-n-_";
					} else {
						$search = "";
					}
					if ($search) {
						$sql = "SELECT `sid` FROM `pr_contracts` WHERE `workingdays` LIKE '$search'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							while ($row = $result->fetch_assoc()) {
								$sid = $row['sid'];
								$sql = "SELECT `username` FROM `staff` WHERE `ID` = '$sid'";
								if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
									$srow = $sresult->fetch_assoc();
									$staffname = $srow['username'];
									$offarray[$thisdate][] = $staffname;
								}
							}
						}
					}
					$runningday++;
					if ($runningday > 6) { $runningday = 0; }
					$thisdate = date("Y-m-d",strtotime("$thisdate +1 Day"));
				}

				// get holidays booked in the next $diaryperiod days
				$frday = date("Ymd");
				$today = date("Ymd",strtotime("+$diaryperiod Days"));
				$sql = "SELECT p.sid, s.username, p.year, p.month, p.day
						FROM pr_calendar AS p
						LEFT JOIN staff AS s ON s.ID = p.sid
						WHERE CONCAT(p.year,LPAD(p.month, 2, 0),LPAD(p.day, 2, 0)) >= '$frday'
						AND   CONCAT(p.year,LPAD(p.month, 2, 0),LPAD(p.day, 2, 0)) <= '$today'
						AND   (p.type = 'hp' OR p.type = 'hu' OR p.type = 'wphp')
						ORDER BY p.year, p.month, p.day
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					// add $result to $offarray
					while ($row = $result->fetch_assoc()) {
						$staffname = $row['username'];
						if ($staffname) {
							$y = $row['year'];
							$m = sprintf("%02s",$row['month']);
							$d = sprintf("%02s",$row['day']);
							$offarray["$y-$m-$d"][] = $staffname;
						}
					}
				}
				if (($offarray) && (count($offarray))) {
					print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Staff Name</b></td>\n";
					print "</tr>\n";
					foreach ($offarray AS $date => $staffarray) {
						foreach ($staffarray AS $staffname) {
							$nicedate = date("jS F Y, l",strtotime("$date"));
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td>$nicedate</td>";
							print "<td>$staffname</td>\n";
							print "</tr>\n";
						}
					}
					print "<tr>\n";
					print " <td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				} else {
					print "<p align=\"center\">Nothing in the next $diaryperiod Days.</p>\n";
				}
			} else {
				print "<p align=\"center\">Diary Period Invalid.</p>\n";
			}
			$dbcnx->close();
		}
	}

	// leads diary
	if ($homePageSections['LeadsDiary'] == "on") {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$todaystart = date("Y-m-d 00:00");
			$events = array();
			print "<p align=\"center\" class=\"larger\">Leads Diary</p>\n";
		
			// OLM
			$sql = "SELECT `ID`, `applfullname`, `applfirstname`, `appllastname`, `applcompname`, `viewdatetime`
					FROM `olm_leads`
					WHERE `viewdatetime` AND SUBSTRING(`viewdatetime`, 1, 16) > '$todaystart'
					ORDER BY `viewdatetime`
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$lid           = $row['ID'];
					$applfullname  = $row['applfullname'];
					$applfirstname = $row['applfirstname'];
					$appllastname  = $row['appllastname'];
					$applcompname  = $row['applcompname'];
					$meeting       = $row['viewdatetime'];
					list($meeting,$type,$siteid) = explode("--",$meeting);
					$meeting = date("d-m-Y H:i",strtotime($meeting));
					if (!$applfullname) { $applfullname = $row['applfirstname']." ".$row['appllastname']; }
					if ($siteid) {
						// get sitename
						$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
						if ($siteresult = $dbcnx->query($sql) AND $siteresult->num_rows) {
							$srow = $siteresult->fetch_assoc();
							$sitename = $srow['title'];
						}
					}
					$events[] = array(
						'section'  => 'Office Lead',
						'lid'      => $lid,
						'meeting'  => $meeting,
						'type'     => $type,
						'custname' => $applfullname,
						'compname' => $applcompname,
						'sitename' => $sitename
					);
				}
			}

			// Web Leads
			/* $sql = "SELECT `ID`, `applfullname`, `applcompname`, `viewdatetime`
					FROM `olm_wleads`
					WHERE `viewdatetime` AND SUBSTRING(`viewdatetime`, 1, 16) > '$todaystart'
					ORDER BY `viewdatetime`
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$lid           = $row['ID'];
					$applfullname  = $row['applfullname'];
					$applcompname  = $row['applcompname'];
					$meeting       = $row['viewdatetime'];
					list($meeting,$type,$siteid) = explode("--",$meeting);
					$meeting = date("d-m-Y H:i",strtotime($meeting));
					if ($siteid) {
						// get sitename
						$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
						if ($siteresult = $dbcnx->query($sql) AND $siteresult->num_rows) {
							$srow = $siteresult->fetch_assoc();
							$sitename = $srow['title'];
						}
					}
					$events[] = array(
						'section'  => 'Web Lead',
						'lid'      => $lid,
						'meeting'  => $meeting,
						'type'     => $type,
						'custname' => $applfullname,
						'compname' => $applcompname,
						'sitename' => $sitename
					);
				}
			} */

			// Leads
			$sql = "SELECT `ID`, `fullname`, `compname`, `meeting`, `sites`
					FROM `leads_tickets`
					WHERE `meeting` > '$todaystart'
					ORDER BY `meeting`
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$lid       = $row['ID'];
					$fullname  = $row['fullname'];
					$compname  = $row['compname'];
					$meeting   = $row['meeting'];
					$sites     = $row['sites'];
					if ($sites) {
						list($sitename,$junk) = explode(",",$sites);
					} else {
						$sitename = "Unknown";
					}
					$meeting = date("d-m-Y H:i",strtotime($meeting));
					$events[] = array(
						'section'  => 'Leads',
						'lid'      => $lid,
						'meeting'  => $meeting,
						'type'     => "Viewing",
						'custname' => $fullname,
						'compname' => $compname,
						'sitename' => $sitename
					);
				}
			}
			
			if ($events) {
				usort($events, function($a, $b) {
					return $a['meeting'] <=> $b['meeting'];
				});
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>From</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "</tr>\n";

				$moreRowCount = 1;
				foreach ($events AS $event) {
					if ($moreRowCount > 4) {
						print "<tr class=\"moreRows_4 r" . ($i++ % 2) . "\">\n";
					} else {
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					}
					print "<td>{$event['meeting']}</td>";
					print "<td>{$event['type']}</td>";
					print "<td>{$event['custname']}</td>";
					print "<td>{$event['compname']}</td>";
					print "<td>{$event['sitename']}</td>";
					print "<td>{$event['section']}</td>";
					if ($event['section'] == "Office Lead") {
						print "<td><a href=\"olm-leads-view.php?lid={$event['lid']}\">View</a></td>";
					}
					elseif ($event['section'] == "Leads") {
						print "<td><a href=\"leads-view.php?tid={$event['lid']}\">View</a></td>";
					}
					print "	</tr>\n";
					$moreRowCount++;
				}
				if ($result->num_rows > 4) {
					print "<tr>\n";
					print " <td colspan=\"7\" align=\"center\" style=\"border-top:1px solid #999999\">[ <a href=\"#\"><span class=\"btToggle_4\">more</span></a> ]</td>\n";
					print "</tr>\n";
				} else {
					print "<tr>\n";
					print " <td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\">No leads found.</p>\n";
			}
			$dbcnx->close();
		}
	}

	// support tickets
	/* if ($homePageSections['SupportTickets'] == "on") {
		print "<p align=\"center\" class=\"larger\">New Support Tickets</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$sql = "SELECT t.*, u.compname, u.location, s.username AS assigned
					FROM support_tickets AS t
					LEFT JOIN users AS u ON u.custref = t.custref
					LEFT JOIN staff AS s ON s.ID = t.assignedto
					WHERE t.replied = '0'
					  AND t.type = 'Support'
					  AND t.status = 'Open'
					ORDER BY `type`, pending, replied, tdatetime DESC
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">#</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Subject</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Assigned To</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";

				// array of sites
				$sql = "SELECT * FROM sites";
				$siteresult = $dbcnx->query($sql);
				if (($siteresult) && ($siteresult->num_rows)) {
					while ($siterow = $siteresult->fetch_assoc()) {
						$title     = $siterow['title'];
						$ipaddress = $siterow['ipaddress'];
						$locations[$ipaddress] = $title;
					}
				}

				while ($row = $result->fetch_assoc()) {
					$tid        = $row['ID'];
					$tdatetime  = $row['tdatetime'];
					$ticketno   = $row['ticketno'];
					$custref    = $row['custref'];
					$compname   = $row['compname'];
					$subject    = $row['subject'];
					$viewed     = $row['viewed'];
					$section    = $row['type'];
					$assigned   = $row['assigned'];
					$replied    = $row['replied'];
					$location   = $row['location'];
					if ($location) {
						$sitename = $locations[$location];
					} else {
						$sitename = "Unknown";
					}

					if (!$compname) { $compname = "Unknown"; }
					if (!$assigned) {
						$assigned = "Unassigned";
					} else {
						$assigned = str_replace(array("Mr","Mrs","Miss"),array("",""),$assigned);
						$assigned = trim($assigned);
					}

					list($date,$time) = explode(" ",$tdatetime);
					list($y,$m,$d) = explode("-",$date);
					$tdatetime = "$d/$m $time";
					
					if (strlen($compname) > 16) { $compname = substr($compname, 0, 16); }
					if (strlen($subject)  > 20) { $subject  = substr($subject, 0, 20); }
					if (strlen($sitename) > 10) { $sitename = substr($sitename, 0, 10); }

					if (!$viewed) {
						$styleviewed = " notviewed";
					} else {
						$styleviewed = "";
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td class=\"$class{$styleviewed}\">$ticketno</td>\n";
					print "	<td class=\"$class{$styleviewed}\">$tdatetime</td>\n";
					print "	<td class=\"$class{$styleviewed}\">$compname</td>\n";
					print "	<td class=\"$class{$styleviewed}\">$sitename</td>\n";
					print "	<td class=\"$class{$styleviewed}\">$subject</td>\n";
					print "	<td class=\"$class{$styleviewed}\">$assigned</td>\n";
					print "	<td class=\"$class{$styleviewed}\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref\">View</a></td>\n";
					print "</tr>\n";

				}

				print "<tr>\n";
				print " <td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				
				print "</table>\n";
			} else {
				print "<p align=\"center\">None Found.</p>\n";
			}
			$dbcnx->close();
		}
	} */

	// deposits diary
	/* if (in_array( $_SESSION['user'], array(1,3,5,6,12,135,136))) {
		print "<p align=\"center\" class=\"larger\">Deposits Diary</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$today = date("Y-m-d");
			$sql = "SELECT d.ID, d.custref, d.amount, d.duedate, d.approve, d.topay, d.refunded, c.compname
					FROM deposit_requests AS d
					LEFT JOIN users AS c ON d.custref = c.custref
					WHERE d.duedate >= '$today' AND d.approve = '1'
					ORDER BY d.duedate";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table width=\"90%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Amount</b></td>\n";
				print "</tr>\n";
				$moreRowCount = 1;
				while ($row = $result->fetch_assoc()) {
					$thisid   = $row['ID'];
					$compname = $row['compname'];
					$duedate  = $row['duedate'];
					$amount   = $row['amount'];
					$nicedate = date("dS M Y, l",strtotime($duedate));
					if ($moreRowCount > 4) {
						print "<tr class=\"moreRows_5 r" . ($i++ % 2) . "\">\n";
					} else {
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					}
					print "	<td>$nicedate</td>";
					print "	<td><a href=\"deposits-view.php?id=$thisid\">$compname</a></td>";
					print "	<td>$amount</td>";
					print "</tr>";
				}
				if ($result->num_rows > 4) {
					print "<tr>\n";
					print " <td colspan=\"3\" align=\"center\" style=\"border-top:1px solid #999999\">[ <a href=\"#\"><span class=\"btToggle_5\">more</span></a> ]</td>\n";
					print "</tr>\n";
				} else {
					print "<tr>\n";
					print " <td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\">No deposits found.</p>\n";
			}
			$dbcnx->close();
		}
	} */

	$typecolours = array(
		'K' => 'red',
		'S' => 'green',
		'C' => 'purple',
		'O' => 'blue'
	);
	
	// unconfirmed check ins
	if ($homePageSections['CheckInOut'] == "on") {
		print "<p align=\"center\" class=\"larger\">Unconfirmed Check-ins</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// build array of sites (database holds ip address instead of sitename).
			$sql = "SELECT title, ipaddress FROM sites";
			$sitesresult = $dbcnx->query($sql);
			if ($sitesresult->num_rows) {
				while ($sitesrow = $sitesresult->fetch_assoc()) {
					$title     = $sitesrow['title'];
					$ipaddress = $sitesrow['ipaddress'];
					$locations[$title] = $ipaddress;
				}
			}

			$sql = "SELECT i.*, u.compname, u.custref, u.location, u.ID AS cid
					FROM inventory AS i
					LEFT JOIN users AS u ON u.custref = i.custref
					WHERE i.inout = 'in' AND NOT i.confirmed AND i.txndate > '2020-10-31'
					ORDER BY i.txndate, u.compname
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"90\"><b>Units</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>CS Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Called</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Check-in</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Sent</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$cid       = $row['cid'];
					$compname  = $row['compname'];
					$custid    = $row['custid'];
					$custref   = $row['custref'];
					$txndate   = $row['txndate'];
					$location  = $row['location'];
					$confirmed = $row['confirmed'];
					$sent      = $row['sent'];
					$called    = $row['called'];
					$meeting   = $row['meeting'];

					if ($sent) {
						$sentdate = date("d-m",$sent);
						$active = $now - $sent;
						if ($active > 60*60*48) {
							$conf = "<span style=\"color:green;font-weight:bold\">Conf</span>"; // 48 hours have passed since contract was sent
						} else {
							$conf = "<span style=\"color:#999;font-weight:bold\">Conf</span>";
						}
					} else {
						$sentdate = "&nbsp;";
						$conf = "<span style=\"color:#999;font-weight:bold\">Conf</span>";
					}

					if ($called) {
						$called = date("d-m",$called);
					} else {
						$called = "<a href=\"inventory-meeting.php?id=$id&amp;custref=$custref\">Called</a>";
					}

					if ($meeting) {
						list($mdate,$mtime) = explode(" ",$meeting);
						list ($my,$mm,$md) = explode("-",$mdate);
						$meeting = "$md-$mm $mtime";
					} else {
						$meeting = "&nbsp;";
					}

					list($txy,$txm,$txd) = explode("-",$txndate);
					$txndate = "$txd-$txm-$txy";
					
					// get units
					$sql = "SELECT ID, unitname, unittype FROM inventory_units WHERE iid = '$id'";
					$uresult = $dbcnx->query($sql);
					if ($uresult->num_rows) {
						$units = array();
						$unittypes = array();
						while ($urow = $uresult->fetch_assoc()) {
							$uid      = $urow['ID'];
							$unit     = $urow['unitname'];
							list($prefix,$unitname) = explode(" ",$unit);
							$units[]  = $unitname;
							$unittypes[] = $urow['unittype'];
						}
						$units = implode(", ",$units);
						$unittypes = array_unique($unittypes);
						sort($unittypes);
					} else {
						$units = "No Units Added";
					}
					
					if (strlen($compname) > 12) { $compname = substr($compname, 0, 12); }
					if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\"><a href=\"customer-view.php?id=$cid\">$compname</a></td>\n";
					print "	<td valign=\"top\">$custref</td>\n";
					print "	<td valign=\"top\">$location</td>\n";
					print "	<td valign=\"top\">$units</td>\n";
					print "	<td valign=\"top\">";
					if ($unittypes) {
						foreach ($unittypes AS $unittype) {
							print "<span style=\"color:{$typecolours[$unittype[0]]};font-weight:bold\">{$unittype[0]}</span>";
						}
					} else {
						print "&nbsp;";
					}
					print "</td>\n";
					print "	<td valign=\"top\">$txndate</td>\n";
					print "	<td valign=\"top\">$called</td>\n";
					print "	<td valign=\"top\">$meeting</td>\n";
					print "	<td valign=\"top\">$sentdate</td>\n";
					print "	<td valign=\"top\"><a href=\"inventory-summary.php?id=$id&amp;custref=$custref\">Sum</a></td>\n";
					if ($confirmed) {
						print "	<td valign=\"top\"><span class=\"noAccess\">Conf</span></td>\n";
					} else {
						print "	<td valign=\"top\"><a href=\"inventory-confirm.php?id=$id&amp;custref=$custref\">$conf</a></td>\n";
					}
					print "</tr>\n";
					
				}
				print "<tr>\n";
				print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">No unconfirmed checkins found.</p>\n";
			}
			$dbcnx->close();
		}

		// unconfirmed check outs
		print "<p align=\"center\" class=\"larger\">Unconfirmed Check-outs</p>\n";
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT i.*, u.compname, u.custref, u.location, u.ID AS cid
					FROM inventory AS i
					LEFT JOIN users AS u ON u.custref = i.custref
					WHERE i.inout = 'out' AND NOT i.confirmed AND i.txndate > '2020-10-31'
					ORDER BY i.txndate, u.compname
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"90\"><b>Units</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>CE Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Called</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Check-out</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Sent</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$cid       = $row['cid'];
					$compname  = $row['compname'];
					$custid    = $row['custid'];
					$custref   = $row['custref'];
					$txndate   = $row['txndate'];
					$location  = $row['location'];
					$confirmed = $row['confirmed'];
					$sent      = $row['sent'];
					$called    = $row['called'];
					$meeting   = $row['meeting'];

					if ($sent) {
						$sentdate = date("d-m-Y",$sent);
						$active = $now - $sent;
						if ($active > 60*60*48) {
							$conf = "<span style=\"color:green;font-weight:bold\">Conf</span>"; // 48 hours have passed since contract was sent
						} else {
							$conf = "<span style=\"color:#999;font-weight:bold\">Conf</span>";
						}
					} else {
						$sentdate = "&nbsp;";
						$conf = "<span style=\"color:#999;font-weight:bold\">Conf</span>";
					}

					if ($called) {
						$called = date("d-m-Y",$called);
					} else {
						$called = "<a href=\"inventory-meeting.php?id=$id&amp;custref=$custref\">Called</a>";
					}

					if ($meeting) {
						list($mdate,$mtime) = explode(" ",$meeting);
						list ($my,$mm,$md) = explode("-",$mdate);
						$meeting = "$md-$mm $mtime";
					} else {
						$meeting = "&nbsp;";
					}

					list($txy,$txm,$txd) = explode("-",$txndate);
					$txndate = "$txd-$txm-$txy";
					
					// get units
					$sql = "SELECT ID, unitname, unittype FROM inventory_units WHERE iid = '$id'";
					$uresult = $dbcnx->query($sql);
					if ($uresult->num_rows) {
						$units = array();
						$unittypes = array();
						while ($urow = $uresult->fetch_assoc()) {
							$uid      = $urow['ID'];
							$unit     = $urow['unitname'];
							list($prefix,$unitname) = explode(" ",$unit);
							$units[]  = $unitname;
							$unittypes[] = $urow['unittype'];
						}
						$units = implode(", ",$units);
						$unittypes = array_unique($unittypes);
						sort($unittypes);
					} else {
						$units = "No Units Added";
					}
					
					if (strlen($compname) > 12) { $compname = substr($compname, 0, 12); }
					if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\"><a href=\"customer-view.php?id=$cid\">$compname</a></td>\n";
					print "	<td valign=\"top\">$custref</td>\n";
					print "	<td valign=\"top\">$location</td>\n";
					print "	<td valign=\"top\">$units</td>\n";
					print "	<td valign=\"top\">";
					if ($unittypes) {
						foreach ($unittypes AS $unittype) {
							print "<span style=\"color:{$typecolours[$unittype[0]]};font-weight:bold\">{$unittype[0]}</span>";
						}
					} else {
						print "&nbsp;";
					}
					print "</td>\n";
					print "	<td valign=\"top\">$txndate</td>\n";
					print "	<td valign=\"top\">$called</td>\n";
					print "	<td valign=\"top\">$meeting</td>\n";
					print "	<td valign=\"top\">$sentdate</td>\n";
					print "	<td valign=\"top\"><a href=\"inventory-summary.php?id=$id&amp;custref=$custref&amp;$qString\">Sum</a></td>\n";
					if ($confirmed) {
						print "	<td valign=\"top\"><span class=\"noAccess\">Conf</span></td>\n";
					} else {
						print "	<td valign=\"top\"><a href=\"inventory-confirm.php?id=$id&amp;custref=$custref&amp;$qString\">$conf</a></td>\n";
					}
					print "</tr>\n";
					
				}
				print "<tr>\n";
				print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">No unconfirmed checkouts found.</p>\n";
			}
			$dbcnx->close();
		}
	}

	if ($homePageSections['ClientCallouts'] == "on") {
		// Cold Room Client Callouts
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			print "<p align=\"center\" class=\"larger\">Cold Room Client Callouts</p>\n";
						
			$sql = "SELECT a.*, s.title AS sitename, u.compname
					FROM ClientCallouts AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					LEFT JOIN users AS u ON u.custref = a.custref 
					WHERE a.cleared = '0'
					ORDER BY a.datetime
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>R</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Attending</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$ccoid       = $row['ID'];
					$datetime    = $row['datetime'];
					$siteid      = $row['siteid'];
					$sitename    = $row['sitename'];
					$unitid      = $row['unitid'];
					$unitname    = $row['unitname'];
					$custref     = $row['custref'];
					$compname    = $row['compname'];
					$reminders   = $row['reminders'];
					$attendingdatetime = $row['attendingdatetime'];
					$cleared     = $row['cleared'];

					list($date,$time) = explode(" ",$datetime);
					list($y,$m,$d) = explode("-",$date);
					$datetime = "$d-$m-$y $time";

					if ($attendingdatetime) {
						list($date,$time) = explode(" ",$attendingdatetime);
						list($y,$m,$d) = explode("-",$date);
						$attendingdatetime = "$d-$m-$y $time";
					} elseif ($cleared) {
						$attendingdatetime = "Not set";
					} else {
						$attendingdatetime = "Set";
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$datetime</td>\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$unitname</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>$reminders</td>\n";
					if ($cleared) {
						print "	<td>$attendingdatetime</td>\n";
					} else {
						print "	<td><a href=\"coldrooms-clientcallouts-attending.php?ccoid=$ccoid\">$attendingdatetime</a></td>\n";
					}
					print "	<td><a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid&amp;ty=$ty&amp;ob=$ob\">View</a></td>\n";
					print "	<td><a href=\"coldrooms-clientcallouts-clear.php?ccoid=$ccoid&amp;ty=$ty&amp;ob=$ob\">Clear</a></td>\n";
					print "</tr>\n";
				}

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">None Found</p>\n";
			}
			print "<span align=\"center\"><a href=\"coldrooms-clientcallouts-addedit.php\">New Client Callout</a></span>\n";
			$dbcnx->close();
		}
	}	

	if ($homePageSections['BatteryReport'] == "on") {
		print "<p align=\"center\" class=\"larger\">Paxton Battery Report</p>\n";
		$filename = "../../dis2_db/bat/" . date("Y-m-d") . ".csv";
		if(file_exists($filename)) {
			$records = file($filename);
			print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
			print "<tr>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Sitename</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Device</b></td>\n";
			print "	<td style=\"border-bottom:1px solid #999999\"><b>Status</b></td>\n";
			print "</tr>\n";
			for ($n=0; $n < count($records); $n++) {
				list($siteid,$sitename,$device,$detail,) = explode(",",trim($records[$n]));
				print "<tr class=\"r" . ($i++ % 2) . "\">\n";
				print "	<td valign=\"top\">$sitename</td>\n";
				print "	<td valign=\"top\">$device</td>\n";
				print "	<td valign=\"top\">$detail</td>\n";
				print "</tr>\n";
			}
			print "<tr>\n";
			print "	<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
			print "</tr>\n";
			print "</table>\n";
		} else {
			print "<p align=\"center\">File Not Found</p>";
		}
	}
	?>
	<script type="text/javascript">
		$.fn.toggleText = function(t1, t2){
			if (this.text() == t1) this.text(t2);
			else                   this.text(t1);
			return this;
		};
		$('.moreRows_1').hide(); $('.moreRows_2').hide(); $('.moreRows_3').hide(); $('.moreRows_4').hide(); $('.moreRows_5').hide();
		$('.btToggle_1').click(function() {
			$('.moreRows_1').toggle();
			$('.btToggle_1').toggleText('more', 'less');
			return false;
		});
		$('.btToggle_2').click(function() {
			$('.moreRows_2').toggle();
			$('.btToggle_2').toggleText('more', 'less');
			return false;
		});
		$('.btToggle_3').click(function() {
			$('.moreRows_3').toggle();
			$('.btToggle_3').toggleText('more', 'less');
			return false;
		});
		$('.btToggle_4').click(function() {
			$('.moreRows_4').toggle();
			$('.btToggle_4').toggleText('more', 'less');
			return false;
		});
		$('.btToggle_5').click(function() {
			$('.moreRows_5').toggle();
			$('.btToggle_5').toggleText('more', 'less');
			return false;
		});
	</script>
	<?php

	// Happy Birthday stuff
	// print "<p><img src=\"images/graphics-happy-birthday-161793.gif\" width=\"280\" height=\"176\" alt=\"\"/><br /><span style=\"font-size:64px;color:purple;font-weight:bold;\"></span></p>\n";

	include('fam-index-include.php');
	// print "<p align=\"center\">VAT Period Lock: $VAT_Period_Lock</p>\n";

}

else {
	print "<td class=\"main\">\n";
	print "<p class=\"heading\" align=\"center\">LOGIN</p>\n";
	print "<p align=\"center\">Please enter your email address and password to login</p>\n";
	print "<p align=\"center\" class=\"redtext\">$resultMessage</p>
	<form method=\"post\" action=\"index.php\">
	<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">
	<tr>
		<td align=\"right\" class=\"bodytext\">email:</td>
		<td><input class=\"element\" type=\"text\" name=\"login\" size=\"40\"></td>
	</tr>
	<tr>
		<td align=\"right\" class=\"bodytext\">password:</td>
		<td><input class=\"element\" type=\"password\" name=\"password\" size=\"40\"></td>
	</tr>
	<tr>
		<td colspan=\"2\" align=\"right\" class=\"bodytext\">
		<input type=\"submit\" value=\"Submit\" class=\"button\">
		</td>
	</tr>
	</table>
	</form>
	<p align=\"center\"><a href=\"staff-forgot.php\">Forgot Password</a></p>
	</td>\n";
}
include('footer.php');

function generateRandomString($length = 16) {
   return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}
?>
