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
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";

		$custref = $_REQUEST['custref'];
		$id      = $_REQUEST['id'];
		$iid     = $_REQUEST['iid'];
		$uid     = $_REQUEST['uid'];
		$delete  = $_REQUEST['delete'];
		// $importimages = $_REQUEST['importimages'];
		$now = time();
		
		if ($custref AND $id AND $uid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				// get inventory type
				$sql = "SELECT * FROM inventory WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$inout   = $row['inout'];
				} else {
					$resultMessage .= "<span class=\"redtext\">Inventory Details Not Found!</span><br />" . $dbcnx->error . "<br />";
				}

				// get unitname and ID
				$sql = "SELECT `unitname`, `unitid`, `unittype`, `join` FROM `inventory_units` WHERE `ID` = '$uid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
                    $unitname = $row['unitname'];
                    $unittype = $row['unittype'];
					$unitid   = $row['unitid'];
					$join     = $row['join'];
				} else {
					$resultMessage .= "Could Get Unit Details<br />" . $dbcnx->error . "<br />";
				}

				// load image requirements
				$imagesIndex = array();
				$lines = file('inventory-images.csv', FILE_IGNORE_NEW_LINES);
				foreach ($lines as $line) {
					list(
						$tunittype,
						$tcode,
						$ttitle,
						$checkIn,
						$checkOut,
						$joined
					) = explode(",",$line);
					$imagesIndex[$tunittype][$tcode]['title'] = $ttitle;
					if ($join) {
						$imagesIndex[$tunittype][$tcode]['required'] = $joined;
					} else {
						if ($inout == "in") {
							$imagesIndex[$tunittype][$tcode]['required'] = $checkIn;
						} elseif ($inout == "out") {
							if ($checkOut == "on") {
								$imagesIndex[$tunittype][$tcode]['required'] = $checkOut;
							} else {
								$imagesIndex[$tunittype][$tcode]['required'] = $checkIn;
							}
						}
					}
				}
				
				// get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
				}

				/* if ($importimages) {
					$sql = "SELECT i.*
							FROM inventory_images AS i
							LEFT JOIN inventory_units AS u ON u.ID = i.uid
							WHERE u.unitid = '$unitid' AND i.inout = 'out'
					";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$code    = $row['code'];
							$title   = $row['title'];
							$image   = $row['image'];
							$details = $row['details'];
							$filename = date("YmdHis-") . rand(1000,9999) . "-$custref" . "-$id-00" . ".jpg";
							if (copy("../../dis2_db/img/inv/$image", "../../dis2_db/img/inv/$filename")) {
								$resultMessage .= "Previous check out image copied to $filename<br />";
								$sql = "INSERT INTO inventory_images VALUES (
									null,
									'$id',
									'$uid',
									'in',
									'$code',
									'$title',
									'$filename',
									'$details',
									'',
									'$now',
									'Imported Manually'
								)";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Image record added<br />";
								} else {
									$resultMessage .= "Image record could NOT be added<br />";
								}
							} else {
								$resultMessage .= "File $image could NOT be copied to $filename.<br />";
							}
							// $resultMessage .= "Previous check out image $image Found<br />";
						}
					}
				} */

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				print "<p align=\"center\" class=\"larger\">$compname Check $inout</p>\n";
				print "<p align=\"center\" class=\"larger\">$unitname - $unittype</p>\n";

				if ($delete) {

					// get current image
                    $sql = "SELECT `title`, `no_damage`, `image`, `inout` FROM `inventory_images` WHERE `ID` = '$iid'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
						$oldimage = $row['image'];
						$title    = $row['title'];
						$nodamage = $row['no_damage'];
						$io       = $row['inout'];
						if ($io == "in") {
							$check = "Check In";
						} elseif ($io == "out") {
							$check = "Check Out";
						}
					} else {
                        $resultMessage .= "Could Get Unit Details<br />" . $dbcnx->error . "<br />";
					}
					print "<p align=\"center\" class=\"larger\">Delete Image</p>\n";
					print "<p align=\"center\" class=\"larger\">$title - $check</p>\n";

				}

				if ($delete == 1) {

					print "<p align=\"center\"><b>ARE YOU SURE?</b></p>\n";
					print "<p align=\"center\">";
					print "<a href=\"inventory-images.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;iid=$iid&amp;delete=2\">Yes Delete</a> | ";
					print "<a href=\"inventory-images.php?id=$id&amp;custref=$custref&amp;uid=$uid\">No DON'T Delete</a>";
					print "</p>\n";
				
				}

				elseif ($delete == 2) {

					if ($oldimage) {
						if (is_file("../../dis2_db/img/inv/$oldimage")) {
							if (unlink("../../dis2_db/img/inv/$oldimage")) {
								$sql = "DELETE FROM inventory_images WHERE ID = '$iid'";
								if ($result = $dbcnx->query($sql)) {
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref IID$id $unitname Image','$page','$now')");
									$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Deleted Image $unitname $title')");
								} else {
									$resultMessage .= "Could NOT Delete Image<br />" . $dbcnx->error . "<br />";
								}
							} else {
								$resultMessage .= "Could NOT Unlink Image<br />";
							}
						}  else {
							$resultMessage .= "File Not Found<br />";
						}
					}
					elseif ($nodamage) {
						$sql = "DELETE FROM inventory_images WHERE ID = '$iid'";
						if ($result = $dbcnx->query($sql)) {
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref IID$id $unitname No Damage','$page','$now')");
							$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Deleted No Damage $unitname $title')");
						} else {
							$resultMessage .= "Could NOT Update Image<br />" . $dbcnx->error . "<br />";
						}
					} else {
						$resultMessage .= "Image Not Found<br />";
					}
					$delete = 0;
					$iid = 0;
					$io = "";
					$oldimage = "";
				}
				
				if (!$delete) {

					// get the check in/out images
					print "<p align=\"center\" class=\"larger\">Images</p>\n";
					print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Image</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Title/Cust Notes/Our Notes</b></td>\n";
					if ($inout == "out") {
						print "	<td style=\"border-bottom:1px solid #999999\"><b>No Damage</b></td>\n";
						$colspan = 5;
					} else {
						$colspan = 4;
					}
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
					print "</tr>\n";

					foreach($imagesIndex[$unittype] AS $code => $items) {
						$sql = "SELECT * FROM `inventory_images` WHERE `uid` = '$uid' AND `code` = '$code' AND `inout` = '$inout'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$alldetails = "";
							$iid     = $row['ID'];
							$image   = $row['image'];
							$title   = $row['title'];
							$nodamage = $row['no_damage'];
							$details = $row['details'];
							$notes   = $row['notes'];
							$imagecount++;

							if ($title) {
								$alldetails .= "<b>$title</b><br />";
							}
							if ($details) {
								$details = str_replace('\n',"<br />",$details);
								$alldetails .= "<i>Customer Notes.....</i><br />$details<br />";
							}
							if ($notes) {
								$notes = str_replace('\n',"<br />",$notes);
								$alldetails .= "<i>Internal Notes.....</i><br />$notes<br />";
							}

							if ($nodamage == "on") {
								$nodamage = "Ticked";
							}

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td valign=\"top\"><a href=\"getimage.php?dr=inv&amp;im=$image\" target=\"_blank\"><img src=\"getimage.php?dr=inv&amp;im=$image\" width=\"100\" alt=\"\" /></a></td>\n";
							print "	<td valign=\"top\">$alldetails</td>\n";
							if ($inout == "out") {
								print "	<td valign=\"top\" align=\"center\">$nodamage</td>\n";
							}
							if ($accessRights[$page] > 1) {
								print "	<td valign=\"top\"><a href=\"inventory-images-addedit.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;iid=$iid&amp;code=$code&amp;io=in\">Edit</a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Edit</span></td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "	<td valign=\"top\"><a href=\"inventory-images.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;iid=$iid&amp;delete=1\">Delete</a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Delete</span></td>\n";
							}
							print "</tr>\n";
						} else {
							if ($inout == "out") {
								$uptext = "Not Uploaded/Ticked Yet";
							} else {
								$uptext = "Not Uploaded Yet";
							}
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td valign=\"top\">{$items['title']}</td>\n";
							print "	<td valign=\"top\">$uptext</td>\n";
							if ($inout == "out") {
								print "	<td valign=\"top\">&nbsp;</td>\n";
							}
							if ($items['required']) {
								print "	<td valign=\"top\"><span class=\"redtext\">Required</span></td>\n";
							} else {
								print "	<td valign=\"top\">&nbsp;</td>\n";
							}
							if ($accessRights[$page] > 1) {
								print "	<td valign=\"top\"><a href=\"inventory-images-addedit.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;code=$code&amp;io=in\">Upload</a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Upload</span></td>\n";
							}
							print "</tr>\n";
						}
                    }
                    print "<tr>\n";
				    print "	<td colspan=\"$colspan\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				    print "</tr>\n";
                    print "</table>\n";

					/* if (!$imagecount AND $inout == "in") {
						$imagecount = 0;
						// see if there are previous check out images
						$sql = "SELECT i.ID AS imagecount
								FROM inventory_images AS i
								LEFT JOIN inventory_units AS u ON u.ID = i.uid
								WHERE u.unitid = '$unitid' AND i.inout = 'out'
						";
						if ($result = $dbcnx->query($sql)) {
							while ($row = $result->fetch_assoc()) {
								$imagecount ++;
							}
						} else {
							$imagecount = 0;
						}
					} else {
						$imagecount = 0;
					}
					
					if ($imagecount) {
						print "<p align=\"center\"><a href=\"inventory-images.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;importimages=1\">Import Checkout Images</a></p>\n";
					} */
				}
				$dbcnx->close();
			} else {
				$resultMessage = "Could Not Connect To Database!<br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without ids";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-units.php?id=$id&amp;custref=$custref&ampuid=$uid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
