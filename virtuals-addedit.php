<?php
$page = "Virtuals";
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
				$heading = "UPDATE VIRTUAL OFFICE";
			} else {
				$heading = "NEW VIRTUAL OFFICE";
			}

			print "<p align =\"center\" class=\"heading\">$heading</p>\n";

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$title    = $_POST['title'];
				$rate     = $_POST['rate'];
				$notes    = $_POST['notes'];

				if (!$rate) { $errate = "Please give a rate"; $e++; }
				elseif (!is_numeric($rate)) { $errate = "Rate must be numeric"; $e++; }

				if (!$e) {

					$title = addslashes($title);
					$notes    = addslashes($notes);
					$now = time();
					if ($id) {
						$sql = "UPDATE virtuals SET
							title='$title',
							rate='$rate',
							notes='$notes'
							WHERE ID='$id'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO virtuals VALUES (
							null,
							'$title',
							'$rate',
							'$notes'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
	  			$sql = "SELECT * FROM virtuals WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title  = $row['title'];
					$rate   = $row['rate'];
					$notes  = $row['notes'];
					$notes  = stripslashes($notes);
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					print "<form name=\"addform\" method=\"post\" action=\"virtuals-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Virtual Office Package</td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$title\" />";
					if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Rate per Month:</td>\n";
					print "	<td><input name=\"rate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$rate\" />";
					if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
					print "</td>\n";
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
		print "<p align=\"center\"><a href=\"virtuals-view.php\">Return</a>&nbsp;|&nbsp;<a href=\"virtuals-addedit.php\">Add New Virtual Office</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
