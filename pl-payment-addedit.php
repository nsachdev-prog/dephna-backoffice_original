<?php
$page = "Purchases";
$subpage = "Add Payment";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	include('purchases-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }   // suppid
		if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; }  // tx header id (editing)
		if ($_REQUEST['se'])  { $se  = $_REQUEST['se']; }
		if ($_REQUEST['az'])  { $az  = $_REQUEST['az']; }
		if ($_REQUEST['ac'])  { $ac  = $_REQUEST['ac']; }
		if ($_REQUEST['pa'])  { $pa  = $_REQUEST['pa']; }
		$qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa";

		if ($hid) {
			$heading = "Edit Payment";
		} else {
			$heading = "Add Payment";
		}

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				$sql = "SELECT suppref, suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];

					print "<p align =\"center\" class=\"larger\">$suppname</p>\n";
					print "<p align =\"center\" class=\"larger\">$heading</p>\n";

					if ($_POST['posted']) {
						foreach ($_POST as $key => $value) {
							$_POST[$key] = strip_tags($_POST[$key]);
						}

						$txndate  = $_POST['txndate'];
						$txtype   = $_POST['txtype'];
						$amount   = $_POST['amount'];
						$reference = $_POST['reference'];
						$cleared  = $_POST['cleared'];

						if (!$amount) {
							$eramount = "Required"; $e++;
						} else {
							$amount = trim(str_replace(",","",$amount));
							if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
						}

						if (!$e) {

							$now = time();
							list($d,$m,$y) = explode("/",$txndate);

							if ($hid) {
								// reverse original allocation
								$sql = "SELECT invid, amount FROM pl_allocations_items WHERE pmtid = '$hid'";
								$result = $dbcnx->query($sql);
								if (($result) AND ($result->num_rows)) {
									while ($row = $result->fetch_assoc()) {
										$invid   = $row['invid'];
										$tamount = $row['amount'];
										$sql = "UPDATE purchases SET outstanding = outstanding + '$tamount' WHERE ID = '$invid'";
										if ($dbcnx->query($sql)) {
											$rdb .= $sql.";\n";
											$resultMessage .= "Allocation $tamount Undone<br>\n";
										}
									}
								}
								// delete allocations
								$sql = "DELETE FROM pl_allocations_items WHERE pmtid = '$hid'";
								if ($dbcnx->query($sql)) {
									$rdb .= $sql.";\n";
									$resultMessage .= "Allocation Lines Deleted<br>\n";
									$sql = "DELETE FROM pl_allocations_headers WHERE pmtid = '$hid'";
									if ($dbcnx->query($sql)) {
										$rdb .= $sql.";\n";
										$resultMessage .= "Allocation Header Deleted<br>\n";
									}
								} 
							}

							print "<script type=\"text/javascript\">\n";
							print "	window.location=\"pl-payment-allocate.php?id=$id&hid=$hid&y=$y&m=$m&d=$d&amount=$amount&txtype=$txtype&reference=$reference&cleared=$cleared\"\n";
							print "</script>\n";
								
						}
					}

					elseif ($hid) {
						$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$txtype   = $row['txtype'];
							$txnnum   = $row['dephnaref'];
							$reference = $row['supplierref'];
							$y        = $row['year'];
							$m        = $row['month'];
							$d        = $row['day'];
							$amount   = $row['total'];
							$lastupd  = $row['crdate'];
							$updby    = $row['crby'];
							$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
							$amount *=-1;
							$txndate = "$d/$m/$y";
							if (ctype_upper($txtype)) {
								$cleared = 1;
							}

							print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

							print "<tr>\n";
							print "<td>Payment</td>\n";
							print "<td>$txnnum</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td>Date</td>\n";
							print "<td>$txndate</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td>Amount</td>\n";
							print "<td>$amount</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td>Reference</td>\n";
							print "<td>$reference</td>\n";
							print "</tr>\n";

							print "</table>\n";

						} else {
							$resultMessage = "Payment Not Found<br />" . $dbcnx->error;
						}
					} else {
						$txndate = date("d/m/Y");
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							if (!$recdate) { $trndate = date("d/m/Y"); }

							print "<form name=\"addform\" method=\"post\" action=\"pl-payment-addedit.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							print "<input type=\"hidden\" name=\"txtype\" value=\"$txtype\" />\n";
							
							print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

							if ($ertxndate) { $class = "row-error"; } else { $class = "row"; }
							print "<tr>\n";
							print "<td><b>Date</b></td>\n";
							print "<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element\" />";
							if ($ertxndate) { print "<br /><span class=\"redtext\">$ertxndate</span>"; }
							print "</td>\n";
							print "</tr>\n";

							if ($eramount) { $class = "row-error"; } else { $class = "row"; }
							print "<tr>\n";
							print "<td><b>Amount</b></td>\n";
							print "<td><input name=\"amount\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$amount\" />\n";
							if ($eramount) { print "<br /><span class=\"redtext\">$eramount</span>"; }
							print "</td>\n";
							print "</tr>\n";

							if ($_SESSION['user'] == 1) {
								print "<tr>\n";
								print "<td><b>Reference (optional)</b></td>\n";
								print "<td><input name=\"reference\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$reference\" />\n";
								if ($erreference) { print "<br /><span class=\"redtext\">$erreference</span>"; }
								print "</td>\n";
								print "</tr>\n";
							} else {
								print "<input type=\"hidden\" name=\"reference\" value=\"$reference\" />\n";
							}

							print "<tr>\n";
							print "<td><b>Post As Cleared</b></td>\n";
							print "<td><input name=\"cleared\" type=\"checkbox\""; if ($cleared) { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";

							print "</table>\n";

							print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"submit\" /></p>\n";

							print "</form>\n";

							if ($hid) {
								print "<p class=\"centered\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
							}
						}
						# print jquery date picker
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd/mm/yy'});\n";
						print "	});\n";
						print "</script>\n";
					}
				} else {
					$resultMessage = "Supplier Not Found<br>";
				}
				$dbcnx->close();
			} else {
				// select supplier
				print "<p align =\"center\" class=\"larger\">$heading</p>\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<form name=\"form1\" method=\"post\" action=\"pl-payment-addedit.php?$qString\">\n";
				$sql = "SELECT ID, suppref, suppname FROM suppliers ORDER BY suppname";
				if ($result = $dbcnx->query($sql)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier</b>:</td>\n";
					print "	<td><select name=\"id\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$thisid   = $row['ID'];
						$suppref  = $row['suppref'];
						$suppname = $row['suppname'];
						print "	<option value=\"$thisid\""; if ($thisid == $id) { print " SELECTED"; } print ">$suppname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "</table>\n";
			}
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\">";
	if ($id) {
		print "<a href=\"pl-ledger.php?id=$id&$qString\">Return</a>&nbsp;&nbsp;|&nbsp;&nbsp;\n";
	}
	print "<a href=\"pl-ledgers-list.php?$qString\">Ledgers</a>\n";
	print "</p>";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
