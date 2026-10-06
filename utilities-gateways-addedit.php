<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Gateways";
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
				$heading = "Edit Gateway";
			} else {
				$heading = "New Gateway";
			}
			print "<p align =\"center\" class=\"heading\">UTILITIES</p>\n";
			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align =\"center\" class=\"larger\">$heading</p>\n";

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$siteid     = $_POST['siteid'];
				$title      = $_POST['title'];
				$ipaddress  = $_POST['ipaddress'];
				$port       = $_POST['port'];
				$mac        = $_POST['mac'];
				$location   = $_POST['location'];
				$notes      = $_POST['notes'];

				if (!$siteid) {
					$ersiteid = "Please select site"; $e++;
				}
				if ($ipaddress) {
					if (filter_var($ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						//
					} else {
						$eripaddress = "Not a valid IP address $var"; $e++;
					}
				} else {
					$eripaddress = "Please enter an IP address"; $e++;
				}
				if ($port) {
					if (!is_numeric($port)) { $erport = "Must be numeric"; $e++; }
				} else {
					$erport = "Please enter port number"; $e++;
				}
				if (!$title) {
					$ertitle = "Please give a server title"; $e++;
				}

				if (!$e) {

					$now = time();
					$title = addslashes($title);
					$notes = addslashes($notes);
					$mac   = addslashes($mac);
					$location = addslashes($location);

					if ($id) {
						$sql = "UPDATE `UtlGateways` SET
							`siteid`    = '$siteid',
							`title`     = '$title',
							`ipaddress` = '$ipaddress',
							`port`      = '$port',
							`mac`       = '$mac',
							`location`  = '$location',
							`notes`     = '$notes',
							`lastupd`   = '$now',
							`updby`     = '{$_SERVER['username']}'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Gateway Updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title $ipaddress:$port','$subpage','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO UtlGateways VALUES (
							null,
							'$siteid',
							'$title',
							'$ipaddress',
							'$port',
							'$mac',
							'$location',
							'$notes',
							'$now',
							'{$_SERVER['username']}'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Gateway Added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title $ipaddress:$port','$subpage','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
				$sql = "SELECT * FROM UtlGateways WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$siteid    = $row['siteid'];
					$title     = $row['title'];
					$ipaddress = $row['ipaddress'];
					$port      = $row['port'];
					$mac       = $row['mac'];
					$location  = $row['location'];
					$notes     = $row['notes'];
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					// get sites
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);

					print "<form name=\"addform\" method=\"post\" action=\"utilities-gateways-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td><select name=\"siteid\" class=\"element\">\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$thissiteid = $sitesrow['ID'];
						$sitename   = $sitesrow['title'];
						print "		<option value=\"$thissiteid\"";
						if ($siteid == $thissiteid) {
							print " SELECTED";
						}
						print ">$sitename</option>\n";
					}
					print "	</select>";
					if ($ersiteid) { print "&nbsp;<span class=\"redtext\">$ersiteid</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Our Name:</b></td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$title\" />";
					if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>IP Address:</b></td>\n";
					print "	<td><input name=\"ipaddress\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$ipaddress\" />";
					if ($eripaddress) { print "<br /><span class=\"redtext\">$eripaddress</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Port:</b></td>\n";
					print "	<td><input name=\"port\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$port\" />";
					if ($erport) { print "<br /><span class=\"redtext\">$erport</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Mac Addr:</b></td>\n";
					print "	<td><input name=\"mac\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$mac\" />";
					if ($erpmac) { print "<br /><span class=\"redtext\">$ermac</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Location:</b></td>\n";
					print "	<td><input name=\"location\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$location\" />";
					if ($erlocation) { print "<br /><span class=\"redtext\">$erlocation</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
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
		print "<p align=\"center\"><a href=\"utilities-gateways-list.php\">Return</a></p>\n";
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
