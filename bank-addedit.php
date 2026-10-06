<?php
$page = "Bank";
$purch = "Purchases";
$subpage = "Add Transaction";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($_REQUEST['y']) { $y = $_REQUEST['y']; }

	if ($accessRights[$page] OR $accessRights[$purch]) {

		print "<p align = \"center\" class=\"heading\">BANK</p>\n";
		
		if ($accessRights[$page]) {
			include('bank-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
		}
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$txtypes = array("DR"=>"Debit","CR"=>"Credit","dr"=>"Pending Debit","cr"=>"Pending Credit");
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Transaction";
			} else {
				$heading = "New Transaction";
			}
			print "<p align = \"center\" class=\"larger\">$heading</p>\n";
			
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$txtype     = $_POST['txtype'];
				$bankref    = $_POST['bankref'];
				$costcentre = $_POST['costcentre'];
				$tally_guid = $_POST['tally_guid'];
				$dephnaref  = $_POST['dephnaref'];
				$details    = $_POST['details'];
				$ymd        = $_POST['ymd'];
				$amount     = $_POST['amount'];
				$cleared    = $_POST['cleared'];
				$notes      = trim($_POST['notes']);

				if ($costcentre == "Select") {
					$ercostcentre = " Select a costcentre"; $e++;
				} else {
					list($ccid,$cctitle) = explode("--",$costcentre);
				}

				if (!$ymd) {
					$erymd = "Select a transaction date"; $e++;
				} elseif (!validateDate($ymd)) {
					$erymd = "Invalid Date"; $e++;
				} else {
					list($year,$month,$day) = explode("-", $ymd);
				}

				if (!$amount) {
					$eramount = "Required"; $e++;
				} else {
					$amount = trim(str_replace(",","",$amount));
					if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
				}

				if (!$e) {

					if (!$id) {
						// Auto generate next dephnaref
						$sql = "SELECT `dephnaref` FROM `bank` ORDER BY `dephnaref` DESC LIMIT 0, 1";
						if ($srresult = $dbcnx->query($sql)) {
							if ($srresult->num_rows) {
								$srrow = $srresult->fetch_assoc();
								$dephnaref = $srrow['dephnaref'];
								$dephnaref++;	
							} else {
								$dephnaref = 1001;
							}
						}
					}

					$notes   = htmlentities($notes, ENT_QUOTES);
					$details = htmlentities($details, ENT_QUOTES);
					$bankref = addslashes($bankref);
					$now     = time();
					if ($cleared) { 
						$txtype = strtoupper($txtype);
					} else {
						$txtype = strtolower($txtype);
					}
					if ($id) {
						$sql = "UPDATE bank SET 
							txtype='$txtype',
							bankref='$bankref',
							dephnaref='$dephnaref',
							details='$details',
							`year`='$year',
							`month`='$month',
							`day`='$day',
							amount='$amount',
							costcentre='$ccid',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}' 
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "Transaction Successfully Updated in CAM<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','{$txtypes[$txtype]} $dephnaref $amount','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record in CAM</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$txtype = strtolower($txtype);
						$sql = "INSERT INTO bank VALUES (
							null,
							'$txtype',
							'$bankref',
							'$dephnaref',
							'',
							'$details',
							'$year',
							'$month',
							'$day',
							'$amount',
							'$ccid',
							'$notes',
							'',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$hid = $dbcnx->insert_id;
							$rdb .= "INSERT INTO bank VALUES ('$hid','$txtype','$bankref','$dephnaref','','$details','$year','$month','$day','$amount','$ccid','$notes','','$now','{$_SESSION['username']}');\n";
							$resultMessage .= "{$txtypes[$txtype]} Successfully Added to CAM<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','{$txtypes[$txtype]} $dephnaref $amount','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record to CAM</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM bank WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$txtype      = $row['txtype'];
					$bankref     = $row['bankref'];
					$dephnaref   = $row['dephnaref'];
					$tally_guid  = $row['tally_guid'];
					$details     = $row['details'];
					$year        = $row['year'];
					$month       = $row['month'];
					$day         = $row['day'];
					$amount      = $row['amount'];
					$ccid        = $row['costcentre'];
					$notes       = $row['notes'];
					$lastupd     = $row['lastupd'];
					$updby       = $row['updby'];
					$lastupd     = date("D jS M Y \a\\t H:i",$lastupd);
					$ymd         = "$year-$month-$day";
					if ($txtype == "DR" OR $txtype == "CR") {
						$cleared++;
					} else {
						$cleared = 0;
					}
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Transaction Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					if (!$ymd) { $ymd = date("Y-m-d"); }
						
					print "<form name=\"addform\" method=\"post\" action=\"bank-addedit.php?y=$y\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"dephnaref\" value=\"$dephnaref\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Transaction Date:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element\" />\n";
					if ($erymd) { print "<br /><span class=\"redtext\">$erymd</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Transaction Type</b></td>\n";
					print "	<td><select name=\"txtype\" class=\"element\">\n";
					print "		<option value=\"DR\""; if ($txtype == "DR" OR $txtype == "dr") { print " SELECTED"; } print ">Debit (Payment)</option>\n";
					print "		<option value=\"CR\""; if ($txtype == "CR" OR $txtype == "cr") { print " SELECTED"; } print ">Credit (Receipt)</option>\n";
					print "	</select>";
					if ($ertxtype) { print "<br /><span class=\"redtext\">$ertxtype</span>"; }
					print "</td>\n";
					print "</tr>\n";
						
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Reference</b></td>\n";
					print "	<td><input name=\"bankref\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$bankref\" />\n";
					if ($erbankref) { print "<br /><span class=\"redtext\">$erbankref</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Details</b></td>\n";
					print "	<td><input name=\"details\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$details\" />\n";
					if ($erdetails) { print "<br /><span class=\"redtext\">$erdetails</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Cost Centre</b></td>\n";
					print "	<td><select name=\"costcentre\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					$sql = "SELECT * FROM costcentres WHERE ccgroup = '3' ORDER BY title";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						while ($row = $result->fetch_assoc()) {
							$thisccid = $row['ID'];
							$cctitle  = $row['title'];
							print "		<option value=\"$thisccid--$cctitle\""; if ($thisccid == $ccid) { print " SELECTED"; } print ">$cctitle</option>\n";
						}
					}
					print "	</select>";
					if ($ercostcentre) { print "<br /><span class=\"redtext\">$ercostcentre</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Amount</b></td>\n";
					print "	<td><input name=\"amount\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$amount\" />\n";
					if ($eramount) { print "<br /><span class=\"redtext\">$eramount</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td align=\"right\"><b>Post As Cleared</b></td>\n";
					print "<td><input name=\"cleared\" type=\"checkbox\""; if ($cleared) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Notes:</b><br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					if ($id) {
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					}

					# print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		
		print "<p align=\"center\"><a href=\"bank-ledger.php?ccid=$ccid&amp;y=$y\">Return</a>\n";
		
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
