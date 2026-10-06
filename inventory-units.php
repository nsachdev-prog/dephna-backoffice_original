<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];
			$id      = $_REQUEST['id'];

			if ($custref AND $id) {

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

				// get inventory type
				$sql = "SELECT * FROM inventory WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$inout   = $row['inout'];
					$confirmed = $row['confirmed'];
				} else {
					$resultMessage .= "<span class=\"redtext\">Inventory Details Not Found!</span><br />" . $dbcnx->error . "<br />";
				}
				
				// get customer
				$sql = "SELECT u.compname, u.custref, s.ID AS siteid, s.title AS sitename
						FROM users AS u
						LEFT JOIN sites AS s ON s.ipaddress = u.location
						WHERE u.custref = '$custref'
				";
				$result = $dbcnx->query($sql);
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
				}
				
				print "<p align=\"center\" class=\"larger\">$compname Check $inout</p>\n";
				print "<p align=\"center\" class=\"larger\">Units</p>\n";

				// get current units
				$sql = "SELECT * FROM inventory_units WHERE iid = '$id' ORDER BY `join`, unitname";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {

					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit Type</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Joined with</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
					print "</tr>\n";

					$joind = array();

					while ($row = $result->fetch_assoc()) {
						$uid      = $row['ID'];
						$unitname = $row['unitname'];
						$unittype = $row['unittype'];
						$join     = $row['join'];

						if (!$join) {
							$joins[$uid] = $unitname;
						}

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$unitname</td>\n";
						print "	<td>$unittype</td>\n";
						if ($join) {
							print "	<td>{$joins[$join]}</td>\n";
						} else {
							print "	<td>&nbsp;</td>\n";
						}
						if ($confirmed) {
							print "	<td><i>Edit</i></td>\n";
							print "	<td><i>Images</i></td>\n";
							print "	<td><i>Meters</i></td>\n";
							print "	<td><i>Delete</i></td>\n";
						} else {
							if ($accessRights[$page] > 1) {
								print "	<td><a href=\"inventory-units-addedit.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Edit</a></td>\n";
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
							}
							print "	<td><a href=\"inventory-images.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Images</a></td>\n";
							print "	<td><a href=\"inventory-meters.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Meters</a></td>\n";
							if ($accessRights[$page] > 2) {
								print "	<td><a href=\"inventory-units-delete.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Delete</a></td>\n";
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
							}
						}
						print "</tr>\n";

					}
					print "<tr>\n";
					print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
					
				} else {
					print "<p align=\"center\">No Units Added Yet</p>\n";
				}
				if ($confirmed) {
					print "<p align=\"center\"><b>Confirmed</b></p>\n";
				} else {
					print "<p align=\"center\"><a href=\"inventory-units-addedit.php?id=$id&custref=$custref\">Add Unit</a></p>\n";
				}

			} else {
				$resultMessage .= "Page called witout custref or id!<br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br />" . $dbcnx->connect_error . "<br />";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"inventory-list.php\">Inventory</a> | ";
		print "<a href=\"index.php\">Home</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
