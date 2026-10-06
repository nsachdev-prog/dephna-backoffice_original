<?php
$page = "Cleaning";
$subpage = "Products";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		# print page heading and submenu
		if ($_REQUEST['prodid']) {
			$prodid  = $_REQUEST['prodid'];
			$heading = "CLEANING - UPDATE PRODUCT";
		} else {
			$heading = "CLEANING - NEW PRODUCT";
		}
		print "<p align =\"center\" class=\"heading\">$heading</p>\n";
		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$prodname  = $_POST['prodname'];
				$suppid    = $_POST['suppid'];
				$suppref   = $_POST['suppref'];
				$unitprice = $_POST['unitprice'];

				if (!$prodname) { $erprodname = "Please enter a name"; $e++; }
				if (!$suppref) { $ersuppref = "Please enter supplier ref"; $e++; }
				if (!$unitprice) { $erunitprice = "Please give a price"; $e++; }
				elseif (!is_numeric($unitprice)) { $erunitprice = "Price must be numeric"; $e++; }

				if (!$e) {

					$prodname = addslashes($prodname);
					$now = time();
					if ($prodid) {
						$sql = "UPDATE cl_products SET
							prodname='$prodname',
							suppid='$suppid',
							suppref='$suppref',
							unitprice='$unitprice',
							lastupd='$now',
							updby='{$_SESSION['username']}'
						WHERE ID='$prodid'";
						if ($dbcnx->query($sql)) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Product: $prodname','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO cl_products VALUES (
							null,
							'$suppid',
							'',
							'$suppref',
							'$prodname',
							'$unitprice',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Product: $prodname','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($prodid) {
  			$sql = "SELECT * FROM cl_products WHERE ID = '$prodid'";
  			$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$prodname  = $row['prodname'];
					$suppid	   = $row['suppid'];
					$suppref   = $row['suppref'];
					$unitprice = $row['unitprice'];
					$lastupd   = $row['lastupd'];
					$updby     = $row['updby'];
					$lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
				} else {
					$resultMessage = "Record not found<br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"cl-products-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"prodid\" value=\"$prodid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Product Name</b>:</td>\n";
					print "	<td><input name=\"prodname\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$prodname\" />";
					if ($erprodname) { print "&nbsp;<span class=\"redtext\">$erprodname</span>"; }
					print "</td>\n";
					print "</tr>\n";

					// select cleaning supplier from dropdown
					print "<tr><td></td><td>Cleaning suppliers with sales email</td></tr>";

					$sql = "SELECT ID, suppname FROM suppliers WHERE costcentre = '4' AND email > '' ORDER BY suppname";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Supplier</b>:</td>\n";
						print "	<td><select name=\"suppid\" class=\"element\">\n";
						while ($row = $result->fetch_assoc()) {
							if ($row['ID'] == $suppid) { $sel = " selected"; } else { $sel = ""; }
							print "		<option value=\"{$row['ID']}\"$sel>{$row['suppname']}</option>\n";
						}
						print "	</select></td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier Product Code</b>:</td>\n";
					print "	<td><input name=\"suppref\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$suppref\" />";
					if ($ersuppref) { print "&nbsp;<span class=\"redtext\">$ersuppref</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Unit Price</b>:</td>\n";
					print "	<td><input name=\"unitprice\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$unitprice\" />";
					if ($erunitprice) { print "&nbsp;<span class=\"redtext\">$erunitprice</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if ($prodid) {
						print "<tr>\n";
						print "	<td colspan=\"2\"align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby<br>&nbsp;</td>\n";
						print "</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-products-list.php\">Return</a>&nbsp;|&nbsp;<a href=\"cl-products-addedit.php\">Add New Product</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
