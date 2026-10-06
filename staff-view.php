<?php
$page = "Staff";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MAINTAIN STAFF</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM staff $search ORDER BY username ASC";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows > 0)) {
				Print "<table align=\"center\" width=\"95%\" cellspacing=\"0\" cellpadding=\"4\">\n";
				Print "<tr>\n";
				Print "	<td style=\"border-bottom:1px solid #666\">Name</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\">Email</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\">Alt. Email</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\">Last Login</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
				Print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$username  = $row['username'];
					$email     = $row['email'];
					$lastlogin = $row['lastlogin'];
					$pemail    = $row['pemail'];
					if ($lastlogin) { $lastlogin = date("d/m/y H:i",$lastlogin); }
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$username</td>\n";
					print "	<td>$email</td>\n";
					print "	<td>$pemail</td>\n";
					print "	<td>$lastlogin</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"staff-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"staff-delete.php?id=$id\">Delete</a></td>\n";
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
				$resultMessage = "<b>No staff Found</b>";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"staff-addedit.php?action=addedit\">Add New Staff</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New User</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
