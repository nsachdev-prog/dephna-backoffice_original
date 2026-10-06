<?php
$page = "Maintenance";
$subpage = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_REQUEST['tid']) {
	$tid = $_REQUEST['tid'];
	$heading = "MAINTENANCE EDIT TYPE";
} else {
	$heading = "MAINTENANCE NEW TYPE";
}
print "<p align =\"center\" class=\"heading\">$heading</p>\n";
include('maintenance-submenu.php');
print "<p align=\"center\">$menustr</p>\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {
		$gid = $_REQUEST['gid'];
		if ($gid) {
			
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get group name
				$sql = "SELECT title FROM mnt_groups WHERE ID='$gid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$grtitle = $row['title'];
					if ($_POST['posted']) {
						$title = $_POST['title'];
						if (!$title) { $ertitle = "Enter a type name"; $e++; }
						if (!$e) {
							$title = addslashes($title);
							$now = time();
							if ($tid) {
								$sql = "UPDATE mnt_types SET
								title = '$title'
								WHERE ID='$tid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage = "Record successfully updated";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Group $grtitle Type $title','$subpage','$now')");
								} else {
									$resultMessage = "Could not update record<br />" . $dbcnx->error;
								}
							} else {
								$sql = "INSERT INTO mnt_types VALUES (
								null,
								'$gid',
								'$title'
								)";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage .= "<b>Record successfully added</b><br />";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Group $grtitle Type $title','$subpage','$now')");
								} else {
									$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
								}
							}
						}
					}
					elseif ($tid) {
						$sql = "SELECT * FROM mnt_types WHERE ID='$tid'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$title = $row['title'];
						} else {
							$resultMessage = "Record not found<br />" . $dbcnx->error;
						}
					}
					if (!$resultMessage) {
						if ((!$posted) || ($e)) {
							print "<form name=\"addform\" method=\"post\" action=\"maintenance-types-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"gid\" value=\"$gid\" />\n";
							print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\">Group</td>\n";
							print "	<td>$grtitle</td>\n";

							print "<tr>\n";
							print "	<td align=\"right\">Type Name</td>\n";
							print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$title\" />";
							if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
							print "</tr>\n";

							print "</table>\n";
							print "</form>\n";
						}
					}
				} else {
					$resultMessage = "Could not get group name<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
			if ($resultMessage) {
				print "<p align=\"center\">$resultMessage</p>\n";
			}
			print "<p align=\"center\"><a href=\"maintenance-groups-view.php\">Return</a></p>\n";
		} else {
			print "<p align=\"center\"><b>Page called without group id</b></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}

print "</td>\n";
include('footer.php');
?>
