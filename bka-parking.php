<?php
$page = "BKManager";
$subpage = "Parking";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Additional Services</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['bkaid']) {

				$bkaid = $_REQUEST['bkaid'];

				include('bka-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				$sql = "SELECT b.*, u.title AS unitname, c.compname, c.ID AS custid
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
                        LEFT JOIN users AS c ON c.custref = b.custref
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custid   = $row['custid'];
					$custref  = $row['custref'];
					$siteid   = $row['siteid'];
					$unitid   = $row['unitid'];
                    $unitname = $row['unitname'];
                    $compname = $row['compname'];
					$lastupd  = $row['lastupd'];
                    $updby    = $row['updby'];
			
                    print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";

					// list bays in this prov booking
					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "	<tr>\n";
					print "		<td colspan=\"6\"><b>Parking Bays Already Added</b></td>\n";
					print "	</tr>\n";
					$sql = "SELECT * FROM bka_parking WHERE bkaid='$bkaid' ORDER BY bay";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<tr>\n";
						print "	<td class=\"colHead\">Bay</td>\n";
						print "	<td class=\"colHead\">Rate</td>\n";
						print "	<td class=\"colHead\">VAT</td>\n";
						print "	<td class=\"colHead\">Notes</td>\n";
						print "	<td class=\"colHead\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
						print "	<td class=\"colHeadEnd\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
						print "</tr>\n";
						$baysbooked = array();
						while ($row = $result->fetch_assoc()) {
							$pid    = $row['ID'];
							$bayid  = $row['bayid'];
							$bay    = $row['bay'];
							$bkrate = $row['bkrate'];
							$vat    = $row['vat'];
							$notes  = $row['notes'];
							$notes  = substr($notes,0,50);
							$baysbooked[$bayid] = $bay;
							print "<tr>\n";
							print "	<td class=\"col\">$bay</td>\n";
							print "	<td class=\"col\">$bkrate</td>\n";
							print "	<td class=\"col\">$vat</td>\n";
							print "	<td class=\"col\">$notes</td>\n";
							print "	<td class=\"col\"><a href=\"bka-parking-addedit.php?bkaid=$bkaid&amp;pid=$pid&amp;bayid=$bayid\">Edit</a></td>\n";
							print "	<td class=\"colEnd\"><a href=\"bka-parking-delete.php?bkaid=$bkaid&amp;pid=$pid&amp;bayid=$bayid\">Delete</a></td>\n";
							print "</tr>\n";
						}
					} else {
						print "<tr>\n";
						print "	<td colspan=\"6\">No Bays Added Yet.</td>\n";
						print "</tr>\n";
					}
					print "</table>\n";

					print "<p>&nbsp;</p>\n";

					// get available bays
					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "	<tr>\n";
					print "		<td colspan=\"8\"><b>Parking Bays Available at $sitename</b></td>\n";
					print "	</tr>\n";
					$sql = "SELECT * FROM parking WHERE siteid='$siteid' AND NOT custid ORDER BY bay";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td class=\"colHead\">Bay</td>\n";
						print "	<td class=\"colHead\">Rate</td>\n";
						print "	<td class=\"colHead\">Notes</td>\n";
						print "	<td class=\"colHeadEnd\" width=\"60px\">&nbsp;</td>\n";
						print "</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$bayid = $row['ID'];
							if (!$baysbooked[$bayid]) {
								$bay    = $row['bay'];
								$rate   = $row['rate'];
								$notes  = $row['notes'];
								$notes  = substr($notes,0,50);
								$colclass = "colYellow"; $colendclass="colEndYellow"; $custname = "&nbsp;";
								print "<tr>\n";
								print "	<td class=\"$colclass\">$bay</td>\n";
								print "	<td class=\"$colclass\">$rate</td>\n";
								print "	<td class=\"$colclass\">$notes</td>\n";
								print "	<td align=\"center\" class=\"$colendclass\"><a href=\"bka-parking-addedit.php?bkaid=$bkaid&amp;bayid=$bayid\">Add Bay</a></td>";
								print "</tr>\n";
								$found++;
							}
						}
					}
					if (!$found) {
						print "	<tr>\n";
						print "		<td colspan=\"8\">No Available Parking Bays</td>\n";
						print "	</tr>\n";
					}
					print "</table>\n";
			
                } else {
                    $resultMessage .= "<span class=\"redtext\"><b>Additional Services Not Found!</b></span><br>" . $dbcnx->error;
				}
				
            } else {
                $resultMessage .= "Page called without id";
			}	
				
			$dbcnx->close();
		} else {
			$resultMessage .= "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bka-contract.php?bkaid=$bkaid\">Next</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
