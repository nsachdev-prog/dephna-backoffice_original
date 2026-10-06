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
		$mid     = $_REQUEST['mid'];
		$uid     = $_REQUEST['uid'];
		$delete  = $_REQUEST['delete'];
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
				}
				
				// get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
				}

				// get unitname
				$sql = "SELECT unitname, unittype FROM inventory_units WHERE ID = '$uid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
                    $unitname = $row['unitname'];
                    $unittype = $row['unittype'];
				} else {
					$resultMessage .= "Could Get Unit Details<br />" . $dbcnx->error . "<br />";
				}

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				print "<p align=\"center\" class=\"larger\">$compname Check $inout</p>\n";
				print "<p align=\"center\" class=\"larger\">$unitname - $unittype</p>\n";

				if ($delete) {

					// get current meter
                    $sql = "SELECT commodity FROM inventory_meters WHERE ID = '$mid'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
						$commodity = $row['commodity'];
					} else {
                        $resultMessage .= "Could Not Get Meter Details<br />" . $dbcnx->error . "<br />";
					}
					print "<p align=\"center\" class=\"larger\">Delete Meter</p>\n";
					print "<p align=\"center\" class=\"larger\">$commodity</p>\n";

				}

				if ($delete == 1) {

					print "<p align=\"center\"><b>ARE YOU SURE?</b></p>\n";
					print "<p align=\"center\">";
					print "<a href=\"inventory-meters.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;mid=$mid&amp;delete=2\">Yes Delete</a> | ";
					print "<a href=\"inventory-meters.php?id=$id&amp;custref=$custref&amp;uid=$uid\">No DON'T Delete</a>";
					print "</p>\n";
				
				}

				elseif ($delete == 2) {

					// delete meter
                    $sql = "DELETE FROM `inventory_meters` WHERE `ID` = '$mid'";
                    $result = $dbcnx->query($sql);
                    if ($result) {
                        $resultMessage .= "Meter Deleted<br />\n";
						$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Deleted Meter $unitname $commodity')");
                    } else {
                        $resultMessage .= "Meter Could NOT Be Deleted<br />\n";
                    }

				}
				
				if (!$delete) {

					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";

					// get the check in meter data
					$sql = "SELECT * FROM `inventory_meters` WHERE `uid` = '$uid' AND `inout` = '$inout'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        print "<p align=\"center\" class=\"larger\">Meters</p>\n";
                        print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Commodity</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Prev Date</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Previous</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Reading</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Notes/Our Notes</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
                        print "</tr>\n";
                        $i = 0;
                        while ($row = $result->fetch_assoc()) {
						
							$mid       = $row['ID'];
							$commodity = $row['commodity'];
							$prevdate  = $row['prevdate'];
							$previous  = $row['previous'];
                            $meter     = $row['meter'];
                            $details   = $row['details'];
                            $notes     = $row['notes'];
                            
                            $alldetails = "";

							if ($details) {
								$details = str_replace('\n',"<br />",$details);
								$alldetails .= "<i>Customer Notes.....</i><br />$details<br />";
							}
							if ($notes) {
								$notes = str_replace('\n',"<br />",$notes);
								$alldetails .= "<i>Internal Notes.....</i><br />$notes<br />";
							}

                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td valign=\"top\">$commodity</td>\n";
							print "	<td valign=\"top\">$prevdate</td>\n";
                            print "	<td valign=\"top\">$previous</td>\n";
                            print "	<td valign=\"top\">$meter</td>\n";
                            print "	<td valign=\"top\">$alldetails</td>\n";
							if ($accessRights[$page] > 1) {
								print "	<td valign=\"top\"><a href=\"inventory-meters-addedit.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;mid=$mid&amp;code=$code&amp;io=in\">Edit</a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Edit</span></td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "	<td valign=\"top\"><a href=\"inventory-meters.php?id=$id&amp;custref=$custref&amp;uid=$uid&amp;mid=$mid&amp;delete=1\">Delete</a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Delete</span></td>\n";
							}
							print "</tr>\n";
                        }
                        print "<tr>\n";
                        print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                        print "</tr>\n";
                        print "</table>\n";
                    } else {
                        print "	<p align=\"center\">No Meters Added Yet</p>";
					}

					print "	<p align=\"center\"><a href=\"inventory-meters-addedit.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Add Meter</a></p>\n";
					
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
