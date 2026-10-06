<?php
$page = "Ancillaries";
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
				$heading = "UPDATE ANCILLARY CHARGE";
			} else {
				$heading = "NEW ANCILLARY CHARGE";
			}
			print "<p align =\"center\" class=\"heading\">$heading</p>\n";
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$title    = $_POST['title'];
				$group    = $_POST['group'];
				$rate     = $_POST['rate'];
				$mvat     = $_POST['mvat'];
				$notes    = $_POST['notes'];

				if (!$rate) { $errate = "Please give a rate"; $e++; }
				elseif (!is_numeric($rate)) { $errate = "Rate must be numeric"; $e++; }

				if (!$e) {

					$title = addslashes($title);
					$group = addslashes($group);
					$notes = addslashes($notes);
					$now = time();
					if ($id) {
						$sql = "UPDATE ancillaries SET 
						title='$title',
						ancgroup='$group',
						rate='$rate',
						mvat='$mvat',
						notes='$notes' 
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO ancillaries VALUES (
						null,
						'$title',
						'$group',
						'$rate',
						'$mvat',
						'$notes'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
				}
			}

			elseif ($id) {
	  			$sql = "SELECT * FROM ancillaries WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title = $row['title'];
					$group = $row['ancgroup'];
					$rate  = $row['rate'];
					$mvat  = $row['mvat'];
					$notes = $row['notes'];
					$notes = stripslashes($notes);
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"ancillaries-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Ancillary Name</td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$title\" />";
					if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					# get groups
					$groups = $dbcnx->query("SELECT title FROM ancgroups ORDER BY title");

					print "<tr>\n";
					print "	<td align=\"right\">Ancillary Group:</td>\n";
					print "	<td><select name=\"group\" class=\"element\">\n";
					while ($grow = $groups->fetch_assoc()) {
						$thisgroup = $grow['title'];
						print "		<option value=\"$thisgroup\"";
						if ($thisgroup == $group) {
							print " SELECTED";
						}
						print ">$thisgroup</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Rate Each:</td>\n";
					print "	<td><input name=\"rate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$rate\" />";
					if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Default VAT:</td>\n";
					print "	<td><select name=\"mvat\" class=\"element\">";
					print "		<option "; if ($mvat == "Yes") { print "SELECTED "; } print "value=\"Yes\">Yes</option>";
					print "		<option "; if ($mvat == "No")  { print "SELECTED "; } print "value=\"No\">No</option>";
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
		print "<p align=\"center\"><a href=\"ancillaries-view.php\">Return</a>&nbsp;|&nbsp;<a href=\"ancillaries-addedit.php\">Add New Acillary Charge</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
