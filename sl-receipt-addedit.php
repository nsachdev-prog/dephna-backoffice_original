<?php
$page = "Sales_Ledger";
$subpage = "Add Receipt";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	include('sl-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }   // custid
		if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; }  // tx header id (editing)
		if ($_REQUEST['se'])  { $se  = $_REQUEST['se']; }
		if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp']; }
		if ($_REQUEST['lo'])  { $lo  = $_REQUEST['lo']; }
		if ($_REQUEST['az'])  { $az  = $_REQUEST['az']; }
		if ($_REQUEST['ac'])  { $ac  = $_REQUEST['ac']; }
		if ($_REQUEST['pa'])  { $pa  = $_REQUEST['pa']; }
		$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

		if ($hid) {
			$heading = "Edit Receipt";
		} else {
			$heading = "Add Receipt";
		}

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				$sql = "SELECT custref, compname, tim FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref   = $row['custref'];
					$compname  = $row['compname'];
					$tim       = $row['tim'];       // now used as restricted toggle


					print "<p align =\"center\" class=\"larger\">$compname</p>\n";
					print "<p align =\"center\" class=\"larger\">$heading</p>\n";

					if ($_POST['posted']) {
						foreach ($_POST as $key => $value) {
							$_POST[$key] = strip_tags($_POST[$key]);
						}

						$txndate  = $_POST['txndate'];
						$amount   = $_POST['amount'];
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
								$sql = "SELECT invid, amount FROM sl_allocations_items WHERE recid = '$hid'";
								$result = $dbcnx->query($sql);
								if (($result) AND ($result->num_rows)) {
									while ($row = $result->fetch_assoc()) {
										$invid   = $row['invid'];
										$tamount = $row['amount'];
										$sql = "UPDATE inv_headers SET outstanding = outstanding + '$tamount' WHERE ID = '$invid'";
										if ($result = $dbcnx->query($sql)) {
											// add to rdb
											$rdb .= $sql.";\n";
										}
									}
								}
								// delete allocations
								$sql = "DELETE FROM sl_allocations_headers WHERE recid = '$hid'";
								if ($result = $dbcnx->query($sql)) {
									// add to rdb
									$rdb .= $sql.";\n";
								}
								$sql = "DELETE FROM sl_allocations_items WHERE recid = '$hid'";
								if ($result = $dbcnx->query($sql)) {
									// add to rdb
									$rdb .= $sql.";\n";
								}

								if ($rdb) {
									include_once("rdb-save.php");
									include_once("rdb-mail.php");
								}
								
							}

							print "<script type=\"text/javascript\">\n";
							print "	window.location=\"sl-receipt-allocate.php?id=$id&hid=$hid&y=$y&m=$m&d=$d&amount=$amount&cleared=$cleared\"\n";
							print "</script>\n";
								
						}
					}

					elseif ($hid) {
						$sql = "SELECT * FROM inv_headers WHERE ID = '$hid'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$txtype   = $row['txtype'];
							$txnnum   = $row['invnumber'];
							$y        = $row['year'];
							$m        = $row['month'];
							$d        = $row['day'];
							$amount   = $row['invtotal'];
							$lastupd  = $row['crdate'];
							$updby    = $row['crby'];
							$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
							$amount *=-1;
							$txndate = "$d/$m/$y";
							// check if txtype is uppercase, if so, set cleared to 1
							if (ctype_upper($txtype)) {
								$cleared = 1;
							}

							print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

							print "<tr>\n";
							print "<td>Receipt</td>\n";
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

							print "</table>\n";

						} else {
							$resultMessage = "Receipt Not Found<br />" . $dbcnx->error;
						}
					} else {
						$txndate = date("d/m/Y");
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							print "<form name=\"addform\" method=\"post\" action=\"sl-receipt-addedit.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							
							print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

							if ($ertxndate) { $class = "row-error"; } else { $class = "row"; }
							print "<tr>\n";
							print "<td align=\"right\"><b>Date</b></td>\n";
							print "<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element\" />";
							if ($ertxndate) { print "<br /><span class=\"redtext\">$ertxndate</span>"; }
							print "</td>\n";
							print "</tr>\n";

							if ($eramount) { $class = "row-error"; } else { $class = "row"; }
							if ($_GET['amount']) { $amount = $_GET['amount']; }
							print "<tr>\n";
							print "<td align=\"right\"><b>Amount</b></td>\n";
							print "<td><input name=\"amount\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$amount\" />\n";
							if ($eramount) { print "<br /><span class=\"redtext\">$eramount</span>"; }
							print "</td>\n";
							print "</tr>\n";

							if ($tim != "Yes") {
								print "<tr>\n";
								print "<td align=\"right\"><b>Post As Cleared</b></td>\n";
								print "<td><input name=\"cleared\" type=\"checkbox\""; if ($cleared) { print " CHECKED"; } print " /></td>\n";
								print "</tr>\n";
							}

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
					$resultMessage = "Customer Not Found<br>";
				}
				$dbcnx->close();
			} else {
				// select customer
				print "<p align =\"center\" class=\"larger\">$heading</p>\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<form name=\"form1\" method=\"post\" action=\"sl-receipt-addedit.php?$qString\">\n";
				$sql = "SELECT ID, custref, compname FROM users ORDER BY compname";
				if ($result = $dbcnx->query($sql)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer</b>:</td>\n";
					print "	<td><select name=\"id\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$thisid   = $row['ID'];
						$custref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$thisid\""; if ($thisid == $id) { print " SELECTED"; } print ">$compname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "<tr><td colspan=\"3\" align=\"center\"><b>OR</b></td></tr></p>\n";
				print "<form name=\"form2\" method=\"post\" action=\"sl-receipt-addedit.php?$qString\">\n";
				$sql = "SELECT ID, custref, compname FROM users ORDER BY custref";
				if ($result = $dbcnx->query($sql)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Custref</b>:</td>\n";
					print "	<td><select name=\"id\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$thisid   = $row['ID'];
						$custref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$thisid\""; if ($thisid == $id) { print " SELECTED"; } print ">DP$custref - $compname</option>\n";
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
		print "<a href=\"sl-ledger.php?id=$id&$qString\">Ledger | </a>\n";
	}
	print "<a href=\"sl-ledgers-list.php?$qString\">List</a>\n";
	print "</p>";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
