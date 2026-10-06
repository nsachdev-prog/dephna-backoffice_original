<?php
$page = "Purchases";
$subpage = "Add Refund";
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
			$heading = "Edit Refund";
		} else {
			$heading = "Add Refund";
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

						$txndate     = $_POST['txndate'];
						$amount      = $_POST['amount'];
						$creditcard  = $_POST['creditcard'];
						$cleared     = $_POST['cleared'];
						
						if (!$amount) {
							$eramount = "Required"; $e++;
						} else {
							$amount = trim(str_replace(",","",$amount));
							if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
						}

						if (!$e) {

							if ($cleared) {
								$txtype = "R";
							} else {
								$txtype = "r";
							}

							$now = time();
							list($d,$m,$y) = explode("/",$txndate);

							if ($hid) {
								// we're editing a refund
								// get original txn number
								$sql = "SELECT dephnaref FROM purchases WHERE ID = '$hid'";
								$iresult = $dbcnx->query($sql);
								if (($iresult) AND ($iresult->num_rows)) {
									$irow = $iresult->fetch_assoc();
									$txnnum = $irow['dephnaref'];
								} else {
									$txnnum = "Error";
								}

								// update refund
								$sql = "UPDATE purchases SET 
									txtype='$txtype',
									year='$y',
									month='$m',
									day='$d',
									net='$amount',
									vat='0',
									total='$amount',
									outstanding='$amount',
									lastupd='$now',
									updby='{$_SESSION['username']}' 
									WHERE ID='$hid'
								";
								if ($result = $dbcnx->query($sql)) {
									$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
									$resultMessage .= "Refund Updated in CAM<br>";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$suppname Refund $txnnum','$page','$now')");
									$doAllocations = 1;
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT update original payment header.</span><br>" . $dbcnx->error;
								}
							} else {
									
								// get next txn number
								$sql = "SELECT `dephnaref` FROM `purchases` WHERE `txtype` = 'R' ORDER BY `dephnaref` DESC LIMIT 0, 1";
								$result = $dbcnx->query($sql);
								if (($result) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$txnnum = $row['dephnaref'];
									$txnnum++;
								} else {
									$txnnum = 1001;
								}

								if ($creditcard == "no") { $creditcard = ""; }
									
								// create refund
								$sql = "INSERT INTO purchases VALUES (
									null,
									'$txtype',
									'$id',
									'$txnnum',
									'$creditcard',
									'',
									'$y',
									'$m',
									'$d',
									'',
									'$amount',
									'0',
									'$amount',
									'$amount',
									'0',
									'',
									'',
									'0',
									'0',
									'0',
									'0',
									'0',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$hid = $dbcnx->insert_id;
									$rdb .= "INSERT INTO purchases VALUES ('$hid','$txtype','$id','$txnnum','','','$y','$m','$d','','$amount','0','$amount','$amount','0','','','0','0','0','0','0','$now','{$_SESSION['username']}');\n";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$suppname Refund $txnnum','$page','$now')");
									$resultMessage .= "Pending Refund Added to CAM<br>";
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Add New Record To Database</span><br>" . $dbcnx->error;
								}
							}
							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}  
						}
					}

					elseif ($hid) {
						$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {

							$row = $result->fetch_assoc();
							$txtype      = $row['txtype'];
							$txnnum      = $row['dephnaref'];
							$y           = $row['year'];
							$m           = $row['month'];
							$d           = $row['day'];
							$creditcard  = $row['supplierref'];
							$amount      = $row['total'];
							$outstanding = $row['outstanding'];
							$lastupd     = $row['crdate'];
							$updby       = $row['crby'];

							if ($txtype == "R") {
								$cleared = 1;
							} else {
								$cleared = 0;
							}

							$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
							$txndate = "$d/$m/$y";

							print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

							print "<tr>\n";
							print "<td>Refund</td>\n";
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
							$resultMessage = "Refund Not Found<br />" . $dbcnx->error;
						}
					} else {
						$txndate = date("d/m/Y");
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							if (($hid) AND ($outstanding != $amount)) {
								$resultMessage = "<b>Refund Allocated<br>Undo Allocations First.<br />";
							} else {

								if (!$recdate) { $trndate = date("d/m/Y"); }
								if (!$creditcard) { $creditcard = "no"; }

								print "<form name=\"addform\" method=\"post\" action=\"pl-refund-addedit.php?$qString\">\n";
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
								print "<tr>\n";
								print "<td align=\"right\"><b>Amount</b></td>\n";
								print "<td><input name=\"amount\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$amount\" />\n";
								if ($eramount) { print "<br /><span class=\"redtext\">$eramount</span>"; }
								print "</td>\n";
								print "</tr>\n";

								print "<tr>\n";
								print "<td align=\"right\"><b>Post As Cleared</b></td>\n";
								print "<td><input name=\"cleared\" type=\"checkbox\""; if ($cleared) { print " CHECKED"; } print " /></td>\n";
								print "</tr>\n";

								if (!$hid) {
									print "<tr>\n";
									print "	<td align=\"right\" valign=\"top\"><b>Credit Card Refund</b></td>\n";
									print "	<td>";
									print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "no") { print " CHECKED"; } print " value=\"no\">No<br>";
									print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "cc") { print " CHECKED"; } print " value=\"cc\">Barclaycard<br>";
									print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "am") { print " CHECKED"; } print " value=\"am\">American Express<br>";
									print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "cot") { print " CHECKED"; } print " value=\"cot\">Capital on Tap";
									print "</td>\n";
									print "</tr>\n";
								}

								print "</table>\n";

								print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"submit\" /></p>\n";

								print "</form>\n";

								// print jquery date picker
								print "<script type=\"text/javascript\">\n";
								print "	$( document ).ready(function() {\n";
								print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd/mm/yy'});\n";
								print "	});\n";
								print "</script>\n";

							}

							if ($hid) {
								print "<p class=\"centered\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
							}
						}
					}
				} else {
					$resultMessage = "Supplier Not Found<br>";
				}
				$dbcnx->close();
			} else {
				// select supplier
				print "<p align =\"center\" class=\"larger\">$heading</p>\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<form name=\"form1\" method=\"post\" action=\"pl-refund-addedit.php?$qString\">\n";
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
