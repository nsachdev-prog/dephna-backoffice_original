<?php
$page = "Ancillaries";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MAINTAIN ANCILLARY CHARGES</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['newgroup']) {
				$newgroup = $_POST['newgroup'];
				$newgroup = addslashes($newgroup);
				$dbcnx->query("INSERT INTO ancgroups VALUES (null,'$newgroup')");
			}

			elseif ($_GET['delgroup']) {
				$gid = $_GET['gid'];
				$dbcnx->query("DELETE FROM ancgroups WHERE ID='$gid'");
			}

			$sql = "SELECT * FROM ancillaries ORDER BY title";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				Print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				Print "<tr>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Title</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\"><b>Group</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Std Rate</b></td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" align=\"center\"><b>Notes</b> (First 50 Chars)</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"50px\">&nbsp;</td>\n";
				Print "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$id       = $row['ID'];
					$title    = $row['title'];
					$group    = $row['ancgroup'];
					$rate     = $row['rate'];
					$notes    = $row['notes'];

					$groupsinuse[$group]++;

					if ($notes) {
						$notes = stripslashes($notes);
						if (strlen($notes) > 50) { $notes = substr($notes, 0, 50) . "..."; }
					} else {
						$notes = "";
					}
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$group</td>\n";
					print "	<td align=\"right\">" . number_format($rate,2) . "</td>\n";
					print "	<td>$notes</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"ancillaries-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"ancillaries-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"6\">&nbsp;</td>\n";
                print "</tr>\n";

				print "</table>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"ancillaries-addedit.php\">Add New Ancillary Charge</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Ancillary Charge</p>\n";
			}

			if ($accessRights[$page] > 1) {
				print "<p align=\"center\">&nbsp;</p>\n";
				print "<p align=\"center\" class=\"heading\">ANCILLARY GROUPS</p>\n";
				# get groups
				$groups = $dbcnx->query("SELECT ID, title FROM ancgroups ORDER BY title");
				if (($groups) AND ($groups->num_rows)) {
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #666\"><b>Group Title</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #666\" width=\"60px\">&nbsp;</td>\n";
					print "</tr>\n";
					$i = 0;
					while ($grow = $groups->fetch_assoc()) {
						$gid    = $grow['ID'];
						$gtitle = $grow['title'];
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$gtitle</td>\n";
						if ($groupsinuse[$gtitle]) {
							print "	<td align=\"center\">In Use</td>\n";
						} else {
							print "	<td align=\"center\"><a href=\"ancillaries-view.php?gid=$gid&amp;delgroup=1\">Delete</td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
                	print " <td style=\"border-top:1px solid #666\" colspan=\"2\">&nbsp;</td>\n";
                	print "</tr>\n";
					print "</table>\n";

				} else {
					print "<p align=\"center\">Nothing Found!</p>\n";
				}
				# add new group
				print "<form name=\"addgroup\" method=\"post\" action=\"ancillaries-view.php\">\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td align=\"right\"><b>Add New Group</b>:</td>\n";
				print "	<td><input type=\"text\" name=\"newgroup\" class=\"element_small\" maxlength=\"30\" /></td>\n";
				print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "</form>\n";
				print "<p align=\"center\">&nbsp;</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
