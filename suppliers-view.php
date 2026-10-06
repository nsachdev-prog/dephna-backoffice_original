<?php
$page = "Suppliers";
$subpage = "Suppliers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">SUPPLIERS</p>\n";
		include('suppliers-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align = \"center\" class=\"heading\">Supplier Details</p>\n";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
			$qString ="se=$se&amp;az=$az&amp;pa=$pa";

			if ($id) {
		  		$sql = "SELECT *
		  				FROM suppliers
		  				WHERE ID = '$id'
		  		";
				$result = $dbcnx->query($sql);

				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];
					$suppcustref  = $row['suppcustref'];
					$addr1        = $row['addr1'];
					$addr2        = $row['addr2'];
					$addr3        = $row['addr3'];
					$addr4        = $row['addr4'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$email        = $row['email'];
					$aemails      = $row['aemails'];
					$termsdays    = $row['termsdays'];
					$costcentre   = $row['costcentre'];
					$vat          = $row['vat'];
					$contname     = $row['contname'];
					$bank         = $row['bank'];
					$bnkAccName   = $row['bnkAccName'];
					$bnkSort      = $row['bnkSort'];
					$bnkAccNum    = $row['bnkAccNum'];
					$erem         = $row['erem'];
					$site         = $row['site'];
					$utilitytype  = $row['utilitytype'];
					$notes        = $row['notes'];

					$email = str_replace(",", "<br />", $email);
					$aemails = str_replace(",", "<br />", $aemails);

					// get cost centre name
					$sql = "SELECT title FROM costcentres WHERE ID='$costcentre'";
					$ccresult = $dbcnx->query($sql);
					if ($ccresult->num_rows) {
						$ccrow = $ccresult->fetch_assoc();
						$costcentre = $ccrow['title'];
					}

					// get site name
					if ($site && $site != "0") {
						$sql = "SELECT title FROM sites WHERE ID='$site'";
						$siteresult = $dbcnx->query($sql);
						if ($siteresult->num_rows) {
							$siterow = $siteresult->fetch_assoc();
							$site = $siterow['title'];
						}
					} else {
						$site = "N/A";
					}

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier Name</b>:</td>\n";
					print "	<td>$suppname</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier Ref</b>:</td>\n";
					print "	<td>$suppcustref</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Dephna Ref</b>:</td>\n";
					print "	<td>SU$suppref</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Address</b>:</td>\n";
					print "	<td>$addr1</td>";
					print "</tr>\n";

					if ($addr2) {
						print "<tr>\n";
						print "	<td align=\"right\">&nbsp;</td>\n";
						print "	<td>$addr2</td>";
						print "</tr>\n";
					}

					if ($addr3) {
						print "<tr>\n";
						print "	<td align=\"right\">&nbsp;</td>\n";
						print "	<td>$addr3</td>";
						print "</tr>\n";
					}

					if ($addr4) {
						print "<tr>\n";
						print "	<td align=\"right\">&nbsp;</td>\n";
						print "	<td>$addr4</td>";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>Telephone 1</b>:</td>\n";
					print "	<td>$telephone</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Telephone 2</b>:</td>\n";
					print "	<td>$telephone2</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Sales email(s)</b>:</td>\n";
					print "	<td>$email</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Accounts email(s)</b>:</td>\n";
					print "	<td>$aemails</td>";
					print "</tr>\n";
						
					print "<tr>\n";
					print "	<td align=\"right\"><b>Contact Name</b>:</td>\n";
					print "	<td>$contname</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Terms in Days</b>:</td>\n";
					print "	<td>$termsdays</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>VAT</b>:</td>\n";
					print "	<td>$vat</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Cost Centre</b>:</td>\n";
					print "	<td>$costcentre</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank</b>:</td>\n";
					print "	<td>$bank</td>\n";
					print "</tr>\n";

					if ($bank == "Yes") {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Bank Acc Name</b>:</td>\n";
						print "	<td>$bnkAccName</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Bank Sort Code</b>:</td>\n";
						print "	<td>$bnkSort</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Bank Acc Number</b>:</td>\n";
						print "	<td>$bnkAccNum</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>Email Remittance</b>:</td>\n";
					print "	<td>$erem</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Site</b>:</td>\n";
					print "	<td>$site</td>\n";
					print "</tr>\n";

					if (!$utilitytype) { $utilitytype = "N/A"; }
					print "<tr>\n";
					print "	<td align=\"right\"><b>Utility</b>:</td>\n";
					print "	<td>$utilitytype</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" width=\"300px\"><b>Notes</b>:<br />";
					print "$notes</td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					
				} else {
					$resultMessage = "<b>Supplier Not Found</b><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<b>Page Called Without ID</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"suppliers-list.php?$qString\">Return</a></p>";
		print "<p align=\"center\">";
		if ($accessRights[$page] > 1) {
			print "<a href=\"suppliers-addedit.php?id=$id\">Edit</a>&nbsp;|&nbsp;";
			print "<a href=\"services-addedit.php?suppref=$suppref\">Add New Service</a>\n";
		} else {
			print "<span class=\"noAccess\">Edit</span>&nbsp;|&nbsp;";
			print "<span class=\"noAccess\">Add New Booking</span>\n";
		}
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
