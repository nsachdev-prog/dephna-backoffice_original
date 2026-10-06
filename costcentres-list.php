<?php
$page = "Suppliers";
$subpage = "Cost Centres";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading
			print "<p align=\"center\" class=\"heading\">COST CENTRES</p>\n";
			include('suppliers-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			# get a page of results
			$sql = "SELECT *
					FROM costcentres
					ORDER BY ccgroup, title
			";
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {
				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cost Centre Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Group</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Notes</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40\">&nbsp;</td>\n";
				print "</tr>\n";

				$ccgroups = array(
					"1" => "Cost of Sales",
					"2" => "Administrative Expenses",
					"3" => "Statement Only Items",
					"4" => "One Off Expense"
				);

				while ($row = $result->fetch_assoc()) {
					$id = $row['ID'];
					if ($id > 0) {
						$title = $row['title'];
						$ccgroup = $row['ccgroup'];
						$notes = $row['notes'];
						if ($notes) {
							$notes = stripslashes($notes);
							if (strlen($notes) > 50) { $notes = substr($notes, 0, 50) . "..."; }
						} else {
							$notes = "&nbsp;";
						}
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td valign=\"top\">$title</td>\n";
						print "	<td valign=\"top\">{$ccgroups[$ccgroup]}</td>\n";
						print "	<td valign=\"top\">$notes</td>\n";
						print "	<td align=\"center\" valign=\"top\"><a href=\"costcentres-addedit.php?id=$id\">Edit</a></td>\n";
						print "	<td align=\"center\" valign=\"top\"><a href=\"costcentres-delete.php?id=$id\">Delete</a></td>\n";
						print "</tr>\n";
					}
				}
				print "<tr>\n";
				print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				$resultMessage = "<b>No Cost Centres Found!</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"costcentres-addedit.php\">Add New Cost Centre</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
