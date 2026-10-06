<?php
$page = "Cleaning";
$subpage = "Products";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING - PRODUCTS</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT p.ID, p.prodname, p.suppref, p.unitprice, s.suppname
					FROM cl_products AS p
					JOIN suppliers AS s ON p.suppid = s.ID
					ORDER BY p.prodname
				";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"70%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Product Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Supplier</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Supplier Code</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit Price</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>{$row['prodname']}</td>\n";
					print "	<td>{$row['suppname']}</td>\n";
					print "	<td>{$row['suppref']}</td>\n";
					print "	<td>{$row['unitprice']}</td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"cl-products-addedit.php?prodid={$row['ID']}\">Edit</a></td>\n";
						print "	<td align=\"center\"><a href=\"cl-products-delete.php?prodid={$row['ID']}\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"6\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Products Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($accessRights[$page] > 2) {
			print "<p align=\"center\"><a href=\"cl-products-addedit.php\">Add New Product</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
