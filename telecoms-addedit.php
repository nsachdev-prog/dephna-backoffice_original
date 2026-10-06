<?php
$page = "Telecoms";
$subpage = "Extensions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Add/Edit Extension</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['id']) { $id = $_REQUEST['id']; 
			} 
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$site      = $_POST['site'];
				$extention = $_POST['extention'];
				$customer  = $_POST['customer'];
				$details   = $_POST['details'];
				$notes     = $_POST['notes'];

				list($siteid,$sitename) = explode("--",$site);
				if ($customer) {
					list($custref,$custname) = explode("--",$customer);
				}

				if ($extention) {
					if (!is_numeric($extention)) { $erextention = "Ext must be numeric"; $e++; }
					else {
						$sql = "SELECT ID FROM telecoms WHERE extention = '$extention'";
						$extresult = $dbcnx->query($sql);
						if (($extresult) && ($extresult->num_rows)) {
							if ($id) {
								$extrow = $extresult->fetch_assoc();
								$existing = $extrow['ID'];
								if ($id != $existing) {
									$erextention = "Extention already exists"; $e++;
								}
							} else {
								$erextention = "Extention already exists"; $e++;
							}
						}
					}
				}

				if (!$e) {

					$sitename = addslashes($sitename);
					$custname = addslashes($custname);
					$details  = addslashes($details);
					$notes    = addslashes($notes);
					$now = time();
					if ($id) {
						$sql = "UPDATE telecoms SET
						siteid='$siteid',
						sitename='$sitename',
						extention='$extention',
						custref='$custref',
						custname='$custname',
						details='$details',
						notes='$notes'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename Ext $extention','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO telecoms VALUES (
						null,
						'$siteid',
						'$sitename',
						'$extention',
						'$custref',
						'$custname',
						'$details',
						'$notes'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename Ext $extention','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
	  			$sql = "SELECT * FROM telecoms WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$extention = $row['extention'];
					$custref   = $row['custref'];
					$custname  = $row['custname'];
					$details   = $row['details'];
					$details   = stripslashes($details);
					$notes     = $row['notes'];
					$notes     = stripslashes($notes);
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

					print "<form name=\"addform\" method=\"post\" action=\"telecoms-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$thissiteid  = $sitesrow['ID'];
						$sitename    = $sitesrow['title'];
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
					print "	<td align=\"right\">Extension</td>\n";
					print "	<td><input name=\"extention\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$extention\" />";
					if ($erextention) { print "&nbsp;<span class=\"redtext\">$erextention</span>"; }
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
					print "	<td align=\"right\">Optional Details</td>\n";
					print "	<td><input name=\"details\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$details\" />";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Notes:<br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

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
		print "<p align=\"center\"><a href=\"telecoms-extensions.php\">Return</a>&nbsp;|&nbsp;<a href=\"telecoms-addedit.php\">Add New Extension</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
