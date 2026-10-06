<?php
$page = "Customers";
$subpage = "Details";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align =\"center\" class=\"heading\">CUSTOMERS</p>\n";
    print "<p align=\"center\" class=\"larger\">Customer Details</p>\n";
	if ($accessRights[$page]) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
            if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
            if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
            if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
            if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
            $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;pa=$pa";

			$id = $_REQUEST['id'];
			if ($id) {

	  			$sql = "SELECT * FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$username     = $row['username'];
					$email        = $row['email'];
					$custref      = $row['custref'];
					$compname     = $row['compname'];
					$addr1        = $row['addr1'];
					$addr2        = $row['addr2'];
					$addr3        = $row['addr3'];
					$addr4        = $row['addr4'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$aemails      = $row['aemails'];
					$pdfinvs      = $row['pdfinvs'];
					$site         = $row['location'];
					$billing      = $row['billing'];
					$FireMarshall = $row['FireMarshall'];
					$FirstAider   = $row['FirstAider'];
					$tally        = $row['tally'];
					$bank         = $row['bank'];
					$tim          = $row['tim'];
					$details      = $row['details'];
					$aemails = str_replace(",","<br />",$aemails);
					$details = str_replace('\n',"<br>",$details);

					if ($pdfinvs == "on") { $pdfinvs = "Yes"; } else { $pdfinvs = "No"; }

					$sql = "SELECT title FROM sites WHERE ipaddress='$site'";
					$sitesresult = $dbcnx->query($sql);
					if (($sitesresult) && ($sitesresult->num_rows)) {
						$srow = $sitesresult->fetch_assoc();
						$site = $srow['title'];
					}

					print "<p class=\"larger\" align=\"center\">$compname</p>\n";

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\">Cust Ref:</td>\n";
					print "	<td>DP$custref</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Contact Name:</td>\n";
					print "	<td>$username</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Address:</td>\n";
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
					print "	<td align=\"right\">Personal Telephone:</td>\n";
					print "	<td>$telephone</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Work Telephone:</td>\n";
					print "	<td>$telephone2</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Main email:</td>\n";
					print "	<td>$email</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Email Pdf Invoices:</td>\n";
					print "	<td>$pdfinvs</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\">Accounts email(s):</td>\n";
					print "	<td>$aemails</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Login At:</td>\n";
					print "	<td>$site</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Billing Period:</td>\n";
					print "	<td>$billing</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Fire Marshall:</td>\n";
					print "	<td>$FireMarshall</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">First Aider:</td>\n";
					print "	<td>$FirstAider</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Notes:<br />";
					print "$details</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";

				/* print "<table align=\"center\" cellspacing=\"4px\">";
				print "	<tr>\n";
				print "		<td>Direct Debit Form:</td>\n";
				print "		<td><a href=\"dd-print.php?id=$id\" target=\"_blank\">Print</a></td>\n";
				print "		<td><a href=\"ad-dd.php?custref=$custref&amp;custid=$id\">Send</a></td>";
				print "	</tr>\n";
				print "</table>\n"; */

				print "<p align=\"center\" class=\"larger\">Bookings</p>\n";

				$sql = "SELECT * FROM bookings WHERE custid='$custref' ORDER BY status DESC";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">Site - Unit</td>\n";
					print "	<td class=\"colHead\">Unittype</td>\n";
					print "	<td class=\"colHead\">On Notice</td>\n";
					print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
					print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
					print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
					print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
					print "</tr>\n";
					while ($row = $result->fetch_assoc()) {
						$bkid   = $row['ID'];
						$status = $row['status'];
						$siteid = $row['siteid'];
						$unitid = $row['unitid'];
						$dateto = $row['dateto'];
						$unittype = $row['unittype'];
						if ($status) {
							if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; }
							else { $dateto = "&nbsp;"; $colclass = "colGreen"; $colendclass="colEndGreen"; }
						} else {
							$dateto = "&nbsp;"; $colclass = "colYellow"; $colendclass="colEndYellow";
						}

						# get site title
						$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
						$siteresult = $dbcnx->query($sql);
						if (($siteresult) && ($siteresult->num_rows)) {
							$siterow = $siteresult->fetch_assoc();
							$sitetitle = $siterow['title'];
						} else {
							$resultMessage = "Site Not Found<br />" . $dbcnx->error;
						}

						# get unit details
						$sql = "SELECT title, master FROM units WHERE ID = '$unitid'";
						$unitresult = $dbcnx->query($sql);
						$links  = "";
						if (($unitresult) && ($unitresult->num_rows)) {
							$unitrow = $unitresult->fetch_assoc();
							$unittitle = $unitrow['title'];
						} else {
							$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
						}

						print "<tr>\n";
						print "	<td class=\"$colclass\"><a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;bkid=$bkid\">$sitetitle - $unittitle</a></td>\n";
						print "	<td class=\"$colclass\">$unittype</td>\n";
						print "	<td class=\"$colclass\">$dateto</td>\n";
						if ($accessRights["Bookings"] > 1) {
							print "	<td class=\"$colclass\" align=\"center\"><a href=\"bookings-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;bkid=$bkid\">Edit</a></td>\n";
							print "	<td class=\"$colclass\" align=\"center\"><a href=\"bookings-end.php?bkid=$bkid&amp;custid=$id\">End</a></td>\n";
						} else {
							print "	<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
							print "	<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">End</span></td>\n";
						}
						if ($accessRights["Bookings"] > 2) {
							print "	<td class=\"$colendclass\" align=\"center\"><a href=\"bookings-units-move.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;bkid=$bkid\">Move</a></td>\n";
							print "	<td class=\"$colendclass\" align=\"center\"><a href=\"bookings-delete.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;bkid=$bkid\">Delete</a></td>\n";
						} else {
							print "	<td class=\"$colendclass\" align=\"center\"><span class=\"noAccess\">Move</span></td>\n";
							print "	<td class=\"$colendclass\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						}
						print "</tr>\n";
					}
					print "</table>\n";
				} else {
					print "<p align=\"center\">No bookings</p>\n";
				}

				print "<p align=\"center\" class=\"larger\">Files</p>\n";

				// get some records
				$sql = "SELECT * FROM bookings_files WHERE custref = '$custref' ORDER BY lastupd";
				$result = $dbcnx->query ( $sql );
				if ($result->num_rows) {
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Title</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Pdf</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Notes</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
					print "</tr>\n";
					
					while ( $row = $result->fetch_assoc () ) {
						
						$fid     = $row ['ID'];
						$bkgid   = $row ['bkgid'];
						$title   = $row ['title'];
						$pdf     = $row ['pdf'];
						$file    = $row ['file'];
						$details = $row ['details'];
						$lastupd = $row ['lastupd'];
						if (strlen ( $title ) > 30) {
							$title = substr ( $title, 0, 27 ) . "...";
						}
						if (strlen ( $details ) > 30) {
							$details = substr ( $details, 0, 27 ) . "...";
						}

						$thisdate = date("Y-m-d", $lastupd);
						
						print "<tr class=\"r" . ($i ++ % 2) . "\">\n";
						print "	<td valign=\"top\">$thisdate</td>\n";
						print "	<td valign=\"top\">$title</td>\n";
						print "	<td valign=\"top\">$pdf</td>\n";
						print "	<td valign=\"top\">$details</td>\n";
						print "<td valign=\"top\" align=\"center\"><a href=\"getpdf.php?pdf=$pdf&file=$file\" target=\"_blank\">View</a></td>\n";
						if ($accessRights [$page] > 1) {
							print "<td valign=\"top\" align=\"center\"><a href=\"bookings-files-addedit.php?ret=$id&custref=$custref&id=$fid&bkgid=$bkgid\">Edit</a></td>\n";
						} else {
							print "	<td valign=\"top\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						}
						if ($_SESSION ['user'] == 6) {
							print "<td valign=\"top\" align=\"center\"><a href=\"bookings-files-delete.php?ret=$id&custref=$custref&id=$fid&bkgid=$bkgid\">Delete</a></td>\n";
						} else {
							print "	<td valign=\"top\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				} else {
					print "<p align=\"center\">No Files</p>\n";
				}

				print "<p align=\"center\" class=\"larger\">Parking</p>\n";
				$result = $dbcnx->query("SELECT * FROM parking WHERE custid='$id' ORDER BY sitename, bay");
				if (($result) && ($result->num_rows)) {
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					Print "<tr>\n";
					Print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					Print "	<td style=\"border-bottom:1px solid #999999\"><b>Bay</b></td>\n";
					Print "	<td style=\"border-bottom:1px solid #999999\"><b>Reg</b></td>\n";
					Print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
					Print "</tr>\n";
					while ($row = $result->fetch_assoc()) {
						$pid      = $row['ID'];
						$sitename = $row['sitename'];
						$bay      = $row['bay'];
						$reg      = $row['reg'];
						print "<tr class=\"r" . ($i ++ % 2) . "\">\n";
						print "	<td>$sitename</td>\n";
						print "	<td>$bay</td>\n";
						print "	<td>$reg</td>\n";
						if ($accessRights["Bookings"] > 1) {
							print "	<td align=\"center\"><a href=\"bookings-parking-addedit.php?pid=$pid\">Update</a></td>";
						} else {
							print "	<td align=\"center\"><span class=\"noAccess\">Update</span></td>\n";
						}
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				} else {
					print "<p align=\"center\">No Parking Bays</p>\n";
				}
				
			} else {
				$resultMessage = "Could not find customer<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"customers-list.php?$qString\">Return</a>";
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
