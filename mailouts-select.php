<?php
$page = "Mailouts";
$subpage = "Notices";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SELECT MAILOUT RECIPIENTS</p>\n";
	if ($accessRights[$page] > 1) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		$id = $_REQUEST['id'];

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				// get subject
				$sql = "SELECT subject FROM mailouts WHERE ID='$id'";
				$sresult = $dbcnx->query($sql);
				if (($sresult) && ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$subject = $srow['subject'];

					$site = $_POST['site'];
					$unittype = $_POST['unittype'];
					if (!$site) { $resultMessage .= "Select a site or all<br />"; }
					elseif ($site != "All") { $search[] = "b.siteid='$site'"; }
					if (!$unittype) { $resultMessage .= "Select a unit type or all<br />"; }
					elseif ($unittype != "All") { $search[] = "b.unittype='$unittype'"; }

					# get sites
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					while ($siterow = $sitesresult->fetch_assoc()) {
						$thissiteid = $siterow['ID'];
						$thissitenm = $siterow['title'];
						$sites[$thissiteid] = $thissitenm;
					}

					# get unit types
					$sql = "SELECT title FROM types ORDER BY title";
					$typesresult = $dbcnx->query($sql);

					# filter form for sites and unittypes
					print "<form name=\"filterform\" method=\"post\" action=\"mailouts-select.php\">\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table class=\"filterBlock\" align=\"center\">\n";
					print "	<tr>\n";
					print "		<td><b>Filters:</b></td>\n";
					print "		<td align=\"right\"><b>Site</b></td>\n";
					print "		<td><select name=\"site\" class=\"element_small\">\n";
					print "		<option value=\"\"></option>\n";
					print "		<option value=\"All\""; if ($site == "All") { print " SELECTED"; } print ">All</option>\n";
					$sitesresult->data_seek(0);
					while ($siterow = $sitesresult->fetch_assoc()) {
						$thissiteid = $siterow['ID'];
						$thissitenm = $siterow['title'];
						print "		<option value=\"$thissiteid\"";
						if ($thissiteid == $site) {
							print " SELECTED";
						}
						print ">$thissitenm</option>\n";
					}
					print "		</select>";
					print "		</td>\n";

					print "		<td align=\"right\"><b>Unit Type</b></td>\n";
					print "		<td><select name=\"unittype\" class=\"element_small\">\n";
					print "		<option value=\"\"></option>\n";
					print "		<option value=\"All\""; if ($unittype == "All") { print " SELECTED"; } print ">All</option>\n";
					while ($typerow = $typesresult->fetch_assoc()) {
						$thisunittype = $typerow['title'];
						print "		<option value=\"$thisunittype\"";
						if ($thisunittype == $unittype) {
							print " SELECTED";
						}
						print ">$thisunittype</option>\n";
					}
					print "		</select>";
					print "		</td>\n";

					print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

					print "	</tr>\n";
					print "</table>\n";
					print "</form>\n";
				} else {
					$resultMessage = "Could not find email to send<br />\n";
				}
			} else {
				$resultMessage = "Page called without mailout ID<br />\n";
			}

			if (!$resultMessage) {

				if ($search) { $search = "WHERE " . implode(" AND ",$search); }

				# print select all script
				print "<script type=\"text/javascript\">\n";
				print "$(document).ready(function()\n";
				print "{\n";
				print "	$(\"#custref_all\").click(function()	\n";
				print "	{\n";
				print "		var checked_status = this.checked;\n";
				print "		$(\"input[id=custref]\").each(function()\n";
				print "		{\n";
				print "			this.checked = checked_status;\n";
				print "		});\n";
				print "	});\n";
				print "});\n";
				print "</script>\n";

				# select all matching customers
				$sql = "SELECT b.custid, b.siteid, b.unittype, u.compname, u.email, u.aemails
						FROM bookings AS b
						LEFT JOIN users AS u ON u.custref = b.custid
						$search
						GROUP BY b.custid
						ORDER BY u.compname";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					# print list

					print "<form name=\"mailoutform\" method=\"post\" action=\"mailouts-send.php\">\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Subject:</b></td>\n";
					print "	<td><b>$subject</b></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>From:</b></td>\n";
					print "	<td><input name=\"from\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$supplierEmail\" />";
					print "</tr>\n";
					print "</table>\n";

					print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\"><input type=\"checkbox\" id=\"custref_all\" /></td>\n";
					print "	<td class=\"colHead\">Customer</td>\n";
					print "	<td class=\"colHead\">Email</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHeadEnd\">Unit Type</td>\n";
					print "</tr>\n";
					while ($row = $result->fetch_assoc()) {
						$thiscustref  = $row['custid'];
						$thissiteid   = $row['siteid'];
						$thiscustname = $row['compname'];
						if ($row['aemails']) {
							$thisemail = $row['aemails'];
							$thisemail = str_replace(",","<br />",$thisemail);
						} else {
							$thisemail = $row['email'];
						}
						$thisunittype = $row['unittype'];
						print "<tr>\n";
						print "	<td class=\"col\" valign=\"top\"><input type=\"checkbox\" id=\"custref\" name=\"custref[]\" value=\"$thiscustref\" /></td>\n";
						print "	<td class=\"col\" valign=\"top\">$thiscustname</td>\n";
						print "	<td class=\"col\" valign=\"top\">$thisemail</td>\n";
						print "	<td class=\"col\" valign=\"top\">$sites[$thissiteid]</td>\n";
						print "	<td class=\"colEnd\" valign=\"top\">$thisunittype</td>\n";
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td colspan=\"5\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Emails\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

				} else {
					$resultMessage .= "Nothing found";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage<p>\n";
		}
		print "<p align=\"center\"><a href=\"mailouts-view.php\">Mailouts</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
