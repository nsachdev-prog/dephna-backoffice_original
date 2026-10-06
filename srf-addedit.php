<?php
$page = "SRF";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "UPDATE SRF";
			} else {
				$heading = "NEW SRF";
			}
			print "<p align =\"center\" class=\"heading\">$heading</p>\n";
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$imonth    = $_POST['imonth'];
				$iday      = $_POST['iday'];
				$iyear     = $_POST['iyear'];
				$site      = $_POST['site'];
				$customer  = $_POST['customer'];
				$details   = $_POST['details'];
				$amount    = $_POST['amount'];
				$vat       = $_POST['vat'];
				$notes     = $_POST['notes'];

				$details = str_replace(",","",$details);

				if (!checkdate($imonth,$iday,$iyear)) { $eridate = "Invalid Date"; $e++; }
				if (!$details) { $erdetails = "You did not add a description"; $e++; }
				if (!$amount) { $eramount = "You did not enter an amount"; $e++; }
				elseif (!is_numeric($amount)) { $eramount = "Amount must be numeric"; $e++; }
				if (!$customer) { $ercustomer = "You did not select a customer"; $e++; }
				else { list($custref,$custname) = explode("--",$customer); }
				if (!$site) { $ersite = "You did not select a site"; $e++; }
				else { list($siteid,$sitename) = explode("--",$site); }

				if (!$e) {
					$sitename = addslashes($sitename);
					$custname = addslashes($custname);
					$details  = addslashes($details);
					$details  = str_replace(",","&comma;",$details);
					$notes    = addslashes($notes);
					$now = time();
					$ayear  = date("Y"); // auto approve from 26-05-2020
					$amonth = date("m");
					$aday   = date("d");
					if ($id) {
						$sql = "UPDATE srf SET
						year='$iyear',
						month='$imonth',
						day='$iday',
						siteid='$siteid',
						sitename='$sitename',
						custref='$custref',
						custname='$custname',
						amount='$amount',
						vat='$vat',
						details='$details',
						notes='$notes'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref $amount','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO srf VALUES (
						null,
						'$iyear',
						'$imonth',
						'$iday',
						'$ayear',
						'$amonth',
						'$aday',
						'$siteid',
						'$sitename',
						'$custref',
						'$custname',
						'$amount',
						'$vat',
						'$details',
						'$notes',
						'{$_SESSION['username']}',
						'$now'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref $amount','$page','$now')");
							# email accounts
							$to   = "Dephna Accounts <accounts@dephna.com>";
							$from = "Dephna Accounts <accounts@dephna.com>";
							$subject = "New SRF";
							$thisdate = date("d/m/Y H:i");
							$headers = "From: $from\r\nReply-To: $from\r\nX-Mailer: PHP/" . phpversion();
							$message = "An SRF form has been submitted by {$_SESSION['username']} on $thisdate and requires your approval.";
							mail($to, $subject, $message, $headers);
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
	  			$sql = "SELECT * FROM srf WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$iyear     = $row['year'];
					$imonth    = $row['month'];
					$iday      = $row['day'];
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$custref   = $row['custref'];
					$custname  = $row['custname'];
					$amount    = $row['amount'];
					$vat       = $row['vat'];
					$details   = $row['details'];
					$createdby = $row['createdby'];
					$crdate    = $row['crdate'];
					$details   = stripslashes($details);
					$details   = str_replace("&comma;",",",$details);
					$notes     = $row['notes'];
					$notes     = stripslashes($notes);
					$crdate    = date("jS M Y H:i",$crdate);
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					# get sites and customers
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					$sql = "SELECT ID, custref, compname FROM users WHERE level='2' ORDER BY compname";
					$custsresult = $dbcnx->query($sql);
					if ((!$iday) && (!$imonth) && (!$iyear)) {
						# inv date not yet selected
						$iday = date("d");
						$imonth = date("m");
						$iyear = date("Y");
					}

					print "<p align=\"center\" class=\"smalltext\">Record originally created by $createdby on $crdate</p>\n";

					print "<form name=\"addform\" method=\"post\" action=\"srf-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

		  			print "<tr>\n";
					print "	<td align=\"right\">SRF Date:</td>\n";
					print "	<td>\n";
					print "	<select name=\"iday\" class=\"element_date\">\n";
					for ($o=1; $o <= 31; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($iday == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"imonth\" class=\"element_date\">\n";
					$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
					for ($o=1; $o <= 12; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($imonth == $check) { print "SELECTED "; }
						$op = $o-1;
						print "value=\"$check\">$options[$op]</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"iyear\" class=\"element_date\">\n";
					$options[0] = date("Y")-1;
					$options[1] = date("Y");
					$options[2] = date("Y")+1;
					for ($o=0; $o < 3; $o++) {
						print "		<option ";
						if ($iyear == $options[$o]) { print "SELECTED "; }
						print "value=\"$options[$o]\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					if ($eridate) { print "&nbsp;<span class=\"redtext\">$eridate</span>"; }
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					print "		<option></option>\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$thissiteid = $sitesrow['ID'];
						$sitename   = $sitesrow['title'];
						print "		<option value=\"$thissiteid--$sitename\"";
						if ($thissiteid == $siteid) {
							print " SELECTED";
						}
						print ">$sitename</option>\n";
					}
					print "	</select>";
					if ($ersite) { print "&nbsp;<span class=\"redtext\">$ersite</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Customer:</td>\n";
					print "	<td><select name=\"customer\" class=\"element\">\n";
					print "		<option></option>\n";
					while ($custsrow = $custsresult->fetch_assoc()) {
						$thiscustid   = $custsrow['ID'];
						$thiscustref  = $custsrow['custref'];
						$thiscustname = $custsrow['compname'];
						print "		<option value=\"$thiscustref--$thiscustname\"";
						if ($thiscustref == $custref) {
							print " SELECTED";
						}
						print ">$thiscustname</option>\n";
					}
					print "	</select>";
					if ($ercustomer) { print "&nbsp;<span class=\"redtext\">$ercustomer</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Description:</td>\n";
					print "	<td><input name=\"details\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$details\" />";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Amount:</td>\n";
					print "	<td><input name=\"amount\" type=\"text\" class=\"element_small\" value=\"$amount\" />";
					if ($eramount) { print "&nbsp;<span class=\"redtext\">$eramount</span>"; }
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">VAT:</td>\n";
					print "	<td><select name=\"vat\" class=\"element_tiny\">";
					print "		<option "; if ($vat == "Yes") { print "SELECTED "; } print "value=\"Yes\">Yes</option>";
					print "		<option "; if ($vat == "No")  { print "SELECTED "; } print "value=\"No\">No</option>";
					print "	</select>";
					print "	</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Notes:<br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"srf-view.php\">Return</a>&nbsp;|&nbsp;<a href=\"srf-addedit.php\">Add New SRF</a> (Without Signed Agreement)</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
