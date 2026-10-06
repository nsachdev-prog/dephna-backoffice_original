<?php
$page = "Purchase_Orders";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PURCHASE ORDERS</p>\n";
		if ($accessRights[$page] > 1) {
			print "<br /><p align=\"center\"><a href=\"porder-addedit.php\">New Purchase Order</a></p>\n";
		} else {
			print "<br /><p align=\"center\" class=\"noAccess\">Add New Purchase Order</p>\n";
		}
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM porders $search ORDER BY podate DESC";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows > 0)) {

				print "<table align=\"center\" width=\"96%\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\">\n";
				print "	<tr>\n";
				print "		<td style=\"border-bottom:1px solid #999999\"><div style=\"width:60px;\"><b>P.O. No.</b></div></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\"><div style=\"width:60px;\"><b>Date</b></div></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\"><b>Requested by</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"12%\"><b>Inputted by</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"12%\"><b>Company</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"60\"><b>Amount</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"15%\"><b>Reason or Client Name</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"23%\"><b>Description</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "		<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "	</tr>\n";

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id       = $row['ID'];
					$ponumber = $row['ponumber'];
					$podate   = $row['podate'];
					$poreq    = $row['poreq'];
					$createdby= $row['createdby'];
					$pocompany= $row['pocompany'];
					$poprice  = $row['poprice'];
					$poreason = $row['poreason'];
					$podesc   = $row['podesc'];
					if ($podate) { $podate = date("d/m/y",$podate); }

					// Reason/Desc preview
					$poreasonLite = $poreason;
					$podescLite = $podesc;
					if ($poreasonLite) {
						$poreasonLite = stripslashes($poreasonLite);
						if (strlen($poreasonLite) > 20) { $poreasonLite = "<a href=\"#\" title=\"$poreason\">" . substr($poreasonLite, 0, 20) . "...</a>"; }
					} else {
						$poreasonLite = $poreason;
					}
					if ($podescLite) {
						$podescLite = stripslashes($podescLite);
						if (strlen($podescLite) > 30) { $podescLite = "<a href=\"#\" title=\"$podesc\">" . substr($podescLite, 0, 30) . "...</a>"; }
					} else {
						$podescLite = $podesc;
					}

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td><a href=\"porder-addedit.php?id=$id\">DEP$ponumber</a></td>\n";
					print "	<td>$podate</td>\n";
					print "	<td>$poreq</td>\n";
					print "	<td>$createdby</td>\n";
					print "	<td><div class=\"clamped\">$pocompany<a href=\"#\" title=\"$pocompany\"><span class=\"moretext\">&#8250;&#8250;</a></span><span class=\"fill\"></span></span></div></td>\n";
					print "	<td>&pound;$poprice</td>\n";
					print "	<td><div class=\"clamped\">$poreason<a href=\"#\" title=\"$poreason\"><span class=\"moretext\">&#8250;&#8250;</a></span><span class=\"fill\"></span></span></div></td>\n";
					print "	<td><div class=\"clamped\">$podesc<a href=\"#\" title=\"$podesc\"><span class=\"moretext\">&#8250;&#8250;</a></span><span class=\"fill\"></span></span></div></td>\n";

					if ($accessRights[$page] > 1) {
						print "	<td align=\"right\"><a href=\"porder-addedit.php?id=$id\" onmouseout=\"MM_swapImgRestore()\" onmouseover=\"MM_swapImage('editIcon','','images/bt-edit_dn.png',1)\"><img src=\"images/bt-edit.png\" alt=\"Edit\" name=\"editIcon\" width=\"20\" height=\"20\" border=\"0\" id=\"editIcon\" /></a></td>\n";
					} else {
						print "	<td align=\"right\"><img src=\"images/bt-edit_no.png\" alt=\"Edit\" width=\"20\" height=\"20\" border=\"0\" /></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"right\"><a href=\"porder-delete.php?id=$id\" onmouseout=\"MM_swapImgRestore()\" onmouseover=\"MM_swapImage('deleteIcon','','images/bt-delete_dn.png',1)\"><img src=\"images/bt-delete.png\" alt=\"Delete\" name=\"deleteIcon\" width=\"20\" height=\"20\" border=\"0\" id=\"deleteIcon\" /></a></td>\n";
					} else {
						print "	<td align=\"right\"><img src=\"images/bt-delete_no.png\" alt=\"Delete\" width=\"20\" height=\"20\" border=\"0\" /></td>\n";
					}
					print "	</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"10\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				$resultMessage = "<b>No Purchase Orders Found</b>";
			}


			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"porder-addedit.php\">New Purchase Order</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Purchase Order</p>\n";
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
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
