<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "Edit";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];

			if ($_REQUEST['custref']) {
				
				if ($_REQUEST['id']) {
					$id  = $_REQUEST['id']; 
					$heading = "Update Details";
					include('inventory-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
				} else {
					$heading = "New Inventory";
				}

				print "<p align=\"center\" class=\"larger\">$heading</p>\n";
				
				// get customer
				$sql = "SELECT u.compname, u.custref, s.ID AS siteid, s.title AS sitename, u.email, u.aemails
						FROM users AS u
						LEFT JOIN sites AS s ON s.ipaddress = u.location
						WHERE u.custref = '$custref'
				";
				$result = $dbcnx->query($sql);
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
					if (!$id) {
						$email   = $row['email'];
						$aemails = $row['aemails'];
						if (!$aemails) { $aemails = $email; }
					}
				}
				
				print "<p align=\"center\" class=\"larger\">$compname</p>\n";
			
				if ($_POST['posted']) {

					$details = strip_tags($_POST['details']);
					$aemails = strip_tags($_POST['aemails']);
					$notes   = strip_tags($_POST['notes']);
					$txndate = $_POST['txndate'];
					$inout   = $_POST['inout'];

					if ((!$inout) OR ($inout == "Select")) {
						$erinout = "Select Type"; $e++;
					}

					if ($txndate) {
						if (!validateDate($txndate)) {
							$ertxndate = "Invalid Date"; $e++;
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
						$eremail = "Enter email address(es <i>Comma Separated</i>)"; $e++;
					}
					
					if (!$e) {
						
						$notes  = addslashes($notes);
						$details = addslashes($details);
						$now = time();
									
						if ($id) {
							$sql = "UPDATE inventory SET 
								email='$aemails',
								txndate='$txndate',
								details='$details',
								notes='$notes',
								updby='{$_SESSION['username']}' 
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Details Updated<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref IID$id Details','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Updated Details')");
							} else {
								$resultMessage .= "Could NOT Update Details<br>" . $dbcnx->error . "<br>";
							}
						} else {
							$sql = "INSERT INTO inventory VALUES (
								null,
								'$custref',
								'$aemails',
								'$siteid',
								'$txndate',
								'$inout',
								'$details',
								'$notes',
								'',
								'',
								'0',
								'',
								'0',
								'0',
								'',
								'',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$id = $dbcnx->insert_id;
								$resultMessage .= "New Inventory Check $inout Created DP$custref<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$custref IID$id Check $inout','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Created Inventory')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
							}
						}
					}
				}

				elseif ($id) {
					$sql = "SELECT * FROM inventory WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$aemails = $row['email'];
						$txndate = $row['txndate'];
						$inout   = $row['inout'];
						$details = $row['details'];
						$notes   = $row['notes'];
						$lastupd = $row['lastupd'];
						$updby   = $row['updby'];
						$confirmed = $row['confirmed'];
						$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
					} else {
						$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
							
						print "<form name=\"addform\" method=\"post\" action=\"inventory-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						if ($id) {
							print "<input type=\"hidden\" name=\"inout\" value=\"$inout\" />\n";
							print "<tr>\n";
							print "	<td align=\"right\"><b>Type</b></td>\n";
							print "	<td>Check $inout</td>\n";
							print "</tr>\n";
						} else {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Type</b></td>\n";
							print "	<td><select name=\"inout\" class=\"element\">\n";
							print "		<option>Select</option>\n";
							print "		<option "; if ($inout == "in")     { print "SELECTED "; } print "value=\"in\">Check In</option>\n";
							print "		<option "; if ($inout == "out")    { print "SELECTED "; } print "value=\"out\">Check Out</option>\n";
							print "	</select>\n";
							if ($erinout) { print "<br /><span class=\"redtext\">$erinout</span>"; }
							print "	</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td align=\"right\"><b>Date</b></td>\n";
						print "	<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element_date\" />\n";
						if ($ertxndate) { print "<br /><span class=\"redtext\">$ertxndate</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Email Address(es <i>Comma Separated</i>)</b></td>\n";
						print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$aemails\" />";
						if ($eremail) { print "<br /><span class=\"redtext\">$eremail</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Customer Notes</b></td>";
						print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
						print "</tr>\n";
					
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Internal Notes</b></td>";
						print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
						print "</tr>\n";

						if ($confirmed) {
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><b>Confirmed</b></td>\n";
							print "</tr>\n";
						} else {
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
							print "</tr>\n";
						}

						print "</table>\n";
						print "</form>\n";

						if ($id) {
							print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
						}

						// jquery date picker
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
						print "	});\n";
						print "</script>\n";
						
					}
				}

			} else {
			
				print "<p align=\"center\" class=\"larger\">Select Customer</p>\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<form name=\"form1\" method=\"post\" action=\"inventory-addedit.php\">\n";
				$sql = "SELECT custref, compname FROM users ORDER BY compname";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer</b>:</td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$custref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$custref\">$compname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "<tr><td colspan=\"2\" align=\"center\"><b>OR</b></td></tr>\n";
				print "<form name=\"form2\" method=\"post\" action=\"inventory-addedit.php\">\n";
				$sql = "SELECT custref, compname FROM users ORDER BY custref";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Custref</b>:</td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$custref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$custref\">DP$custref - $compname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "</table>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"inventory-list.php\">Inventory</a> | ";
		print "<a href=\"index.php\">Home</a>";
		print "</p>\n";
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
