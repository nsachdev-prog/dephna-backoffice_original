<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Payment Plans";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
	include('ad-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az";

			$ppid = $_REQUEST['ppid'];
			if ($_GET['custref']) {
				$custref = $_GET['custref'];
			}

			if ($ppid) {
				$heading = "Update Payment Plan";
			} else {
				$heading = "New Payment Plan";
			}

			print "<p align=\"center\" class=\"larger\">$heading</p>\n";
				
			if ($_POST['posted']) {

				$custref = strip_tags($_POST['custref']);
				$aemails = strip_tags($_POST['aemails']);
				$notes   = strip_tags($_POST['notes']);
				$txndate = strip_tags($_POST['txndate']);

				if (!$custref) {
					$ercustref = "Select A Customer"; $e++;
				} else {
					if (!$ppid) {
						// new plan. check that client does not already have an active plan
						$sql = "SELECT ID FROM pplans_headers WHERE custref = '$custref' AND `status` < '2'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$exid = $row['ID'];
							$ercustref = "Customer already has an active plan. <a href=\"pplans-addedit.php?ppid=$exid\">Edit</a>"; $e++;
						}
					}
				}

				if ($aemails) {	
					$aemails = explode(",",$aemails);
					foreach ($aemails AS $email) {
						$email = trim($email);
						if (!validate_email($email)) { $eremail = "Invalid email address"; $e++; }
					}
					$aemails = implode(",",$aemails);
				} else {
					if ($custref) {
						$sql = "SELECT email, aemails FROM users WHERE custref = '$custref'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$email   = $row['email'];
							$aemails = $row['aemails'];
							if (!$aemails) { $aemails = $email; }
						}
					}
				}
				
				if (!$e) {
					
					$notes = addslashes($notes);
					$notes = str_replace('\n',"<br>",$notes);
					$now   = time();
								
					if ($ppid) {
						$sql = "UPDATE pplans_headers SET 
							aemails = '$aemails',
							notes   = '$notes',
							lastupd = '$now',
							updby   = '{$_SESSION['username']}' 
							WHERE ID = '$ppid'
						";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Plan Details Updated<br>";
							$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Updated Details','')");
						} else {
							$resultMessage .= "Could NOT Update Plan Details<br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO pplans_headers VALUES (
							null,
							'$txndate',
							'$custref',
							'$aemails',
							'$notes',
							'0',
							'0',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$ppid = $dbcnx->insert_id;
							$resultMessage .= "New Plan Started<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref Payment Plan $ppid','$page','$now')");
							$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Created Plan','')");
						} else {
							$resultMessage .= "<b>Could NOT add record to database</b><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($_POST['addline']) {
				$newduedate = strip_tags($_POST['newduedate']);
				$newamount  = strip_tags($_POST['newamount']);
				$newtxtype  = strip_tags($_POST['newtxtype']);
				$now = time();
				if ($newduedate) {
					if (!validateDate($newduedate)) {
						$eraddline = "Invalid Date<br>"; $e++;
					}
					elseif (date("N",strtotime($newduedate)) > 5) {
						$eraddline = "Must Be A Week Day<br>"; $e++;
					}
				} else {
					$eraddline = "Enter Due Date<br>"; $e++;
				}
				if ($newamount) {
					if (!is_numeric($newamount)) {
						$eraddline = "Amount Not Numeric"; $e++;
					}
				} else {
					$eraddline = "Enter Amount<br>"; $e++;
				}
				if (!$e) {
					if ($newtxtype == "DR") { $txtxt = "Due"; } else { $txtxt = "Rec"; }
					$sql = "INSERT INTO pplans_lines VALUES (
						null,
						'$ppid',
						'$newduedate',
						'$newamount',
						'$newtxtype',
						'0',
						'$now',
						'{$_SESSION['username']}'
					)";
					if ($dbcnx->query($sql)) {
						$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Added $newamount $txtxt On $newduedate','')");
						$newamount  = "";
						$newduedate = "";
						$newtxtype  = "";
					} else {
						$resultMessage .= "<b>Could NOT add new line to plan</b><br>" . $dbcnx->error . "<br>";
					}
				}
			}

			elseif ($_REQUEST['delid']) {
				$delid = $_REQUEST['delid'];
				if ($delid) {
					$now = time();
					$sql = "SELECT * FROM pplans_lines WHERE ID = '$delid'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$duedate = $row['duedate'];
						$amount  = $row['amount'];
						$txtype  = $row['txtype'];
						if ($txtype == "DR") { $txtype = "Due"; } else { $txtype = "Rec"; }
						$sql = "DELETE FROM pplans_lines WHERE ID = '$delid'";
						if ($dbcnx->query($sql)) {
							$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Deleted $amount $txtype On $duedate','')");
						} else {
							$resultMessage .= "<b>Could NOT Delete Line From Plan</b><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$resultMessage .= "<b>Could NOT Delete Line, Plan NOT Found</b><br>" . $dbcnx->error . "<br>";
					}
				}
			}

			if ($ppid) {
				$sql = "SELECT p.*, c.compname, c.ID AS custid
						FROM pplans_headers AS p
						LEFT JOIN users AS c ON c.custref = p.custref
						WHERE p.ID = '$ppid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$txndate  = $row['txndate'];
					$custref  = $row['custref'];
					$custid   = $row['custid'];
					$compname = $row['compname'];
					$aemails  = $row['aemails'];
					$notes    = $row['notes'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
					$status   = $row['status'];
					$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
					$notes    = str_replace("<br>","\n",$notes);
					print "<p align=\"center\" class=\"larger\">$compname</p>\n";
				} else {
					$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
				}
			}
						
			print "<form name=\"addform\" method=\"post\" action=\"pplans-addedit.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

			if (!$ppid) {
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Customer</b></td>\n";
				$sql = "SELECT custref, compname FROM users ORDER BY compname";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$thiscustref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
					}
					print "	</select>";
					if ($ercustref) { print "<br><span class=\"redtext\">$ercustref</span>"; }
					print "</td>\n";
				}
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Agreement Date</b></td>\n";
				$txndate = date("Y-m-d");
				print "<input type=\"hidden\" name=\"txndate\" value=\"$txndate\" />\n";
				print "	<td>$txndate</td>\n";
				print "</td>\n";
				print "</tr>\n";

			} else {

				print "<input type=\"hidden\" name=\"txndate\" value=\"$txndate\" />\n";
				print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
				list($y,$m,$d) = explode("-",$txndate);
				print "<tr>\n";
				print "	<td align=\"right\"><b>Agreement Date</b></td>\n";
				print "	<td>$d-$m-$y</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\"><b>Email Address(es <i>Comma Separated</i>)</b></td>\n";
				print "	<td><input name=\"aemails\" type=\"text\" class=\"element_box\" maxlength=\"250\" value=\"$aemails\" />";
				if ($eremail) { print "<br><span class=\"redtext\">$eremail</span>"; }
				print "</td>\n";
				print "</tr>\n";

				// get outstanding
				$sql = "SELECT SUM(outstanding) AS outstanding FROM inv_headers WHERE custref = '$custref'";
				if (($result = $dbcnx->query($sql) AND ($result->num_rows))) {
					$row = $result->fetch_assoc();
					$outstanding = $row['outstanding'];
				} else {
					$outstanding = 0.00;
				}

				print "<tr>\n";
				print "	<td align=\"right\"><b>Outstanding</b></td>\n";
				print "	<td>" . number_format($outstanding,2) . "</td>\n";
				print "</tr>\n";

			}

			print "<tr>\n";
			print "	<td align=\"right\" valign=\"top\"><b>Internal Notes</b></td>";
			print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
			print "</tr>\n";

			print "</table>\n";
			print "</form>\n";

			if ($resultMessage) {
				print "<p align=\"center\">$resultMessage</p>\n";
			}

			if ($ppid) {
				print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";

				print "<br><p align=\"center\" class=\"larger\">Payments</p>\n";
				// get current plan
				$sql = "SELECT * FROM pplans_lines WHERE ppid = '$ppid' ORDER BY duedate";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<p align=\"center\">Warning: Delete does not ask for confirmation, it deletes immediately.</p>\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
					print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Due</b></td>\n";
					print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					$totdr = 0;
					$totcr = 0;
					while ($row = $result->fetch_assoc()) {
						$lid     = $row['ID'];
						$duedate = $row['duedate'];
						$amount  = $row['amount'];
						$txtype  = $row['txtype'];
						list($y,$m,$d) = explode("-",$duedate);
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$d-$m-$y</td>\n";
						if ($txtype == "DR") {
							print "	<td align=\"right\">$amount</td>\n";
							print "	<td align=\"right\">&nbsp;</td>\n";
							$totdr += $amount;
						} else {
							print "	<td align=\"right\">&nbsp;</td>\n";
							print "	<td align=\"right\">$amount</td>\n";
							$totcr += $amount;
						}
						print "	<td><a href=\"pplans-addedit.php?ppid=$ppid&amp;delid=$lid\">Delete</td>\n";
						print "</tr>\n";

					}
					print "<tr>\n";
					print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>". number_format($totdr,2) ."</b></td>\n";
					print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>". number_format($totcr,2) ."</b></td>\n";
					print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "<form name=\"addform\" method=\"post\" action=\"pplans-email.php\">\n";
					print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Email Plan\" class=\"button\" /></p>\n";
					print "</form>\n";
				} else {
					print "<p align=\"center\">Nothing Added Yet</p>\n";
				}

				print "<br><p align=\"center\"><b>Add A New Payment And Date</b></p>\n";
				print "<form name=\"addform\" method=\"post\" action=\"pplans-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"addline\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td><b>Type</b></td>\n";
				print "	<td><b>Due Date</b></td>\n";
				print "	<td align=\"right\"><b>Amount</b></td>\n";
				print "	<td>&nbsp;</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td><select name=\"newtxtype\" class=\"element_date\">\n";
				print "		<option value=\"DR\""; if ($newtxtype == "DR") { print " SELECTED"; } print ">Expected Payment</option>\n";
				print "		<option value=\"CR\""; if ($newtxtype == "CR") { print " SELECTED"; } print ">Amount Received</option>\n";
				print "	</select>";
				print "</td>\n";
				print "	<td><input type=\"text\" id=\"datepicker2\" name=\"newduedate\" value=\"$newduedate\" class=\"element_date\" />";
				print "	<td><input type=\"text\" name=\"newamount\" value=\"$newamount\" class=\"element_date\" />";
				print "	<td><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>";
				print "</tr>\n";
				if ($eraddline) {
					print "<tr>\n";
					print "<td colspan=\"3\" class=\"redtext\">$eraddline</td>\n";
					print "<tr>\n";
				}
				print "</table>\n";
				print "</form>\n";
			}

			// jquery date pickers
			print "<script type=\"text/javascript\">\n";
			print "	$(document).ready(function() {\n";
			print "		$(\"#datepicker1\").datepicker({dateFormat: 'yy-mm-dd'});\n";
			print "		$(\"#datepicker2\").datepicker({dateFormat: 'yy-mm-dd'});\n";
			print "	});\n";
			print "</script>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		print "<br><p align=\"center\">";
		if ($ppid) {
			print "<a href=\"pplans-view.php?ppid=$ppid&amp;id=$custid\">View Plan</a> | ";
		}
		print "<a href=\"ad-list.php?$qString\">Debtors</a> | ";
		print "<a href=\"pplans-list.php?$qString\">Plans</a>";
		print "</p>\n";

		print "<br><br><blockquote><b>Instructions</b><p>";

		print "<p>";
		print "<b>1. Start/Update Plan</b><br>";
		print "Select Customer.<br>";
		print "Agreement date will normally be today.<br>";
		print "Add a note if you like.<br>";
		print "Click Submit<br>";
		print "<br>";
		print "</p>\n";

		print "<p>";
		print "<b>2. Add The Payments</b></b><br>";
		print "Add the expected payments and dates on which the customer has agreed to pay.<br>";
		print "Don't change the Expected Payment dropdown.<br>";
		print "You can delete a line if you get it wrong.<br>";
		print "Change the email addresses if they are wrong.<br>";
		print "</p>\n";

		print "<p>";
		print "<b>3. Email Plan</b><br>";
		print "When you email the plan the status will change from pending to active.<br>";
		print "</p>\n";

		print "<p>";
		print "<b>Auto Restrict</b><br>";
		print "Customers with a payment plan will be restricted during the evening if they default on the plan.<br>";
		print "</p></blockquote>\n";

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
function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
?>
