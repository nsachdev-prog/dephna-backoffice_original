<?php
$page = "Sales_Ledger";
$subpage = "Add Payment";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	include('sl-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
		if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
		if ($_REQUEST['se'])  { $se = $_REQUEST['se']; }
		if ($_REQUEST['dp'])  { $dp = $_REQUEST['dp']; }
		if ($_REQUEST['lo'])  { $lo = $_REQUEST['lo']; }
		if ($_REQUEST['az'])  { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac'])  { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa'])  { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

		if ($hid) {
			$heading = "Edit Payment";
		} else {
			$heading = "Add Payment";
		}

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref      = $row['custref'];
					$compname     = $row['compname'];

					print "<p align =\"center\" class=\"larger\">$compname</p>\n";
					print "<p align =\"center\" class=\"larger\">$heading</p>\n";

					if ($_POST['posted']) {
						foreach ($_POST as $key => $value) {
							$_POST[$key] = strip_tags($_POST[$key]);
						}

						$slexTypes = array(
							"RC" => "Returned Cheque",
							"BD" => "Bounced DD",
							"DR" => "Deposit Repayment",
							"BC" => "Bank Charges"
						);

						$txndate = $_POST['txndate'];
						$amount  = $_POST['amount'];
						$type    = $_POST['type'];
						$details = $_POST['details'];
						$slexid  = $_POST['slexid'];

						if (!$amount) {
							$eramount = "Required"; $e++;
						} else {
							$amount = trim(str_replace(",","",$amount));
							if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
						}

						if (!$e) {

							$now = time();
							list($d,$m,$y) = explode("/",$txndate);
							$details = htmlentities($details, ENT_QUOTES);
							
							if ($hid) {
								// we're editing
								$txnnum = $_POST['txnnum'];
								$sql = "UPDATE inv_headers SET 
									year='$y',
									month='$m',
									day='$d',
									invtotal='$amount',
									outstanding='$amount',
									crdate='$now',
									crby='{$_SESSION['username']}' 
									WHERE ID='$hid'
								";
								if ($result = $dbcnx->query($sql)) {
									$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
									$resultMessage .= "Payment Updated.<br>";
									if ($slexid) {
										// update sl_extra (might be editing and old payment with no extra record)
										$sql = "UPDATE sl_extra SET 
											type='$type',
											details='$details' 
											WHERE ID='$slexid'
										";
										if ($result = $dbcnx->query($sql)) {
											$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
											$resultMessage .= "Extra Details Updated.<br>";
										} else {
											$resultMessage .= "Could NOT Update Extra Details.<br>";
										}
									} else {
										// create sl_extra 
										$sql = "INSERT INTO sl_extra VALUES (
											null,
											'$hid',
											'$txnnum',
											'$custref',
											'$type',
											'$details'
										)";
										if ($result = $dbcnx->query($sql)) {
											$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
											$resultMessage .= "Extra Details Created<br>";
										} else {
											$resultMessage .= "Could NOT Create Extra Details<br>";
										}
									}
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$compname Payment $txnnum','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT update payment.</span><br>" . $dbcnx->error;
								}
							} else {
								// get next payment number
								$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'P' ORDER BY `invnumber` DESC LIMIT 0, 1";
								$result = $dbcnx->query($sql);
								if (($result) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$txnnum = $row['invnumber'];
									$txnnum++;
								} else {
									$txnnum = 1001;
								}
								// create payment header
								$sql = "INSERT INTO inv_headers VALUES (
									null,
									'p',
									'$txnnum',
									'$y',
									'$m',
									'$d',
									'$custref',
									'$amount',
									'$amount',
									'No',
									'',
									'$now',
									'{$_SESSION['username']}')
								";
								if ($result = $dbcnx->query($sql)) {
									$hid = $dbcnx->insert_id;
									$rdb .= "INSERT INTO inv_headers VALUES ('$hid','p','$txnnum','$y','$m','$d','$custref','$amount','$amount','No','','$now','{$_SESSION['username']}');\n";
									$resultMessage .= "Payment Created<br>";
									// create sl_extra 
									$sql = "INSERT INTO sl_extra VALUES (
										null,
										'$hid',
										'$txnnum',
										'$custref',
										'$type',
										'$details'
									)";
									if ($result = $dbcnx->query($sql)) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										$resultMessage .= "Extra Details Created<br>";
									} else {
										$resultMessage .= "Could NOT Create Extra Details<br>";
									}
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname Payment $txnnum','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Create Payment</span><br>" . $dbcnx->error;
								}
							}

							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}
						}

					} elseif ($hid) {
						$sql = "SELECT * FROM inv_headers WHERE ID = '$hid'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$txnnum   = $row['invnumber'];
							$y        = $row['year'];
							$m        = $row['month'];
							$d        = $row['day'];
							$amount   = $row['invtotal'];
							$lastupd  = $row['crdate'];
							$updby    = $row['crby'];
							$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
							$txndate = "$d/$m/$y";

							$sql = "SELECT * FROM sl_extra WHERE hid = '$hid'";
							if (($result) AND ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$slexid = $row['ID'];
								$type = $row['type'];
								$details = $row['details'];
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

							print "<form name=\"addform\" method=\"post\" action=\"sl-payment-addedit.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							print "<input type=\"hidden\" name=\"txnnum\" value=\"$txnnum\" />\n";
							print "<input type=\"hidden\" name=\"slexid\" value=\"$slexid\" />\n";
							
							print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

							if ($ertxndate) { $class = "row-error"; } else { $class = "row"; }
							print "<tr>\n";
							print "<td align=\"right\"><b>Date</b></td>\n";
							print "<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element\" />";
							if ($ertxndate) { print "<br /><span class=\"redtext\">$ertxndate</span>"; }
							print "</td>\n";
							print "</tr>\n";

							if ($eramount) { $class = "row-error"; } else { $class = "row"; }
							print "<tr>\n";
							print "<td align=\"right\"><b>Amount</b></td>\n";
							print "<td><input name=\"amount\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$amount\" />\n";
							if ($eramount) { print "<br /><span class=\"redtext\">$eramount</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Type</b></td>\n";
							print "	<td><select name=\"type\" class=\"element\">\n";
							print "		<option value=\"BD\""; if ($type == "BD") { print " SELECTED"; } print ">Bounced DD</option>\n";
							print "		<option value=\"RC\""; if ($type == "RC") { print " SELECTED"; } print ">Returned Cheque</option>\n";
							print "		<option value=\"DR\""; if ($type == "DR") { print " SELECTED"; } print ">Deposit Repayment</option>\n";
							print "		<option value=\"BC\""; if ($type == "BC") { print " SELECTED"; } print ">Bank Charges</option>\n";
							print "	</select>";
							if ($erBilling) { print "<br /><span class=\"redtext\">$erBilling</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Notes</b></td>\n";
							print "<td> <textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
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
					$resultMessage = "Customer Not Found<br>";
				}
				$dbcnx->close();
			} else {
				// select customer
				print "<p align =\"center\" class=\"larger\">$heading</p>\n";
				print "<form name=\"slform\" method=\"post\" action=\"sl-payment-addedit.php?$qString\">\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				$sql = "SELECT ID, compname FROM users ORDER BY compname";
				if ($result = $dbcnx->query($sql)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer</b>:</td>\n";
					print "	<td><select name=\"id\" class=\"element\">\n";
					while ($row = $result->fetch_assoc()) {
						$thisid   = $row['ID'];
						$compname = $row['compname'];
						print "	<option value=\"$thisid\""; if ($thisid == $id) { print " SELECTED"; } print ">$compname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
				print "</form>\n";
				
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
		print "<a href=\"sl-ledger.php?id=$id&$qString\">Ledger</a> | ";
	}
	print "<a href=\"sl-ledgers-list.php\">List</a>";
	print "</p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
