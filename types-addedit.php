<?php
$page = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$subheading = "Update Unit Type";
	} else {
		$subheading = "New Unit Type";
	}
	print "<p align=\"center\" class=\"heading\">UNIT TYPES</p>\n";
	print "<p align=\"center\" class=\"larger\">$subheading</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				$title = strip_tags($_POST['title']);
				$vat   = $_POST['vat'];

				if (!$title) { $ertitle = "Please give a site name"; $e++; }

				if (!$e) {

					$title = addslashes($title);

					if ($id) {
						$sql = "UPDATE types SET
						title='$title',
						vat='$vat'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfuly updated";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','Unit Types','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO types VALUES (
						null,
						'$title',
						'$vat'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfuly added";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','Unit Types','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {

				$sql = "SELECT title, vat FROM types WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title = $row['title'];
					$vat   = $row['vat'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

			}
			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"types-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					if ($id) {
						print "<input type=\"hidden\" name=\"title\" value=\"$title\" />\n";
					}
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Unit Type Name</b></td>\n";
					if ($id) {
						print "	<td>$title";
					} else  {
						print "	<td><input name=\"title\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"30\" value=\"$title\" />";
						if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					}
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>VAT</b></td>\n";
					print "	<td><select name=\"vat\" class=\"element_small\">";
					print "		<option "; if ($vat == "Yes") { print "SELECTED "; } print "value=\"Yes\">Yes</option>";
					print "		<option "; if ($vat == "No")  { print "SELECTED "; } print "value=\"No\">No</option>";
					print "	</select>";
					print "	</td>\n";
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
		print "<p align=\"center\"><a href=\"types-view.php\">Unit Types</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
