<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Commodities";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";


			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "EDIT COMMODITY";
			} else {
				$heading = "NEW COMMODITY";
			}
			print "<p align =\"center\" class=\"heading\">$heading</p>\n";
			if ($_POST['posted']) {

				$e = 0;
				$site		= $_POST['site'];
				$custref	= $_POST['custref'];
				$commodity	= $_POST['commodity'];
				$rate		= $_POST['rate'];
				$threshold	= $_POST['threshold'];

				if (!$commodity) { $ercommodity = "Please enter a commodity"; $e++; }
				if ($rate) {
					if (!is_numeric($rate)) { $errate = "Must be numeric"; $e++; }
				} else {
					$errate = "Please enter a rate"; $e++;
				}
				if (!is_numeric($threshold)) { $erthreshold = "Must be numeric"; $e++; }
				if (!$e) {
					$sql = "SELECT ID FROM UtlCommodities WHERE sitename = '$site' AND custref = '$custref' AND commodity = '$commodity'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$ercommodity = "Commodity already exists"; $e++;
					}
				}

				if (!$e) {

					// sanitize inputs
					$site		= $dbcnx->real_escape_string($site);
					$custref	= $dbcnx->real_escape_string($custref);
					$commodity	= $dbcnx->real_escape_string($commodity);
					$rate		= $dbcnx->real_escape_string($rate);
					$threshold	= $dbcnx->real_escape_string($threshold);
					$now = time();

					if ($id) {
						$sql = "UPDATE UtlCommodities SET
						sitename	= '$site',
						custref		= '$custref',
						commodity	= '$commodity',
						rate		= '$rate',
						divider		= '1000',
						threshold	= '$threshold'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$site $custref $commodity','$subpage','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO UtlCommodities VALUES (
						null,
						'$site',
						'$custref',
						'$commodity',
						'$rate',
						'1000',
						'$threshold'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$site $custref $commodity','$subpage','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
				$sql = "SELECT * FROM UtlCommodities WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$site		= $row['sitename'];
					$custref	= $row['custref'];
					$commodity	= $row['commodity'];
					$rate		= $row['rate'];
					$threshold	= $row['threshold'];
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					# get sites and customers
					$sql = "SELECT title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					$sql = "SELECT custref, compname FROM users WHERE level='2' ORDER BY compname";
					$custsresult = $dbcnx->query($sql);

					print "<form name=\"addform\" method=\"post\" action=\"utilities-commodities-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$sitename = $sitesrow['title'];
						print "		<option value=\"$sitename\"";
						if ($sitename == $site) {
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
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print "		<option>Default</option>\n";
					while ($custsrow = $custsresult->fetch_assoc()) {
						$thiscustref  = $custsrow['custref'];
						$thiscustname = $custsrow['compname'];
						print "		<option value=\"$thiscustref\"";
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
					print "	<td align=\"right\">Commodity:</td>\n";
					print "	<td><select name=\"commodity\" class=\"element\">\n";
					print "		<option></option>\n";
					print "		<option value=\"Electricity\""; if ($commodity == "Electricity") { print " SELECTED"; } print ">Electricity</option>\n";
					print "		<option value=\"Hot\""; if ($commodity == "Hot") { print " SELECTED"; } print ">Hot</option>\n";
					print "		<option value=\"Cold\""; if ($commodity == "Cold") { print " SELECTED"; } print ">Cold</option>\n";
					print "		<option value=\"Gas\""; if ($commodity == "Gas") { print " SELECTED"; } print ">Gas</option>\n";
					print "	</select>";
					if ($ercommodity) { print "<br /><span class=\"redtext\">$ercommodity</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Rate</td>\n";
					print "	<td><input name=\"rate\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$rate\" />";
					if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Threshold +/-</td>\n";
					print "	<td><input name=\"threshold\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$threshold\" />";
					if ($erthreshold) { print "&nbsp;<span class=\"redtext\">$erthreshold</span>"; }
					print "</td>\n";
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
		print "<p align=\"center\"><a href=\"utilities-commodities-view.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
