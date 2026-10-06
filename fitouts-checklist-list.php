<?php
$page = "Fitouts";
$subpage = "Checklist";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		print "<p align = \"center\" class=\"heading\">Checklist</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_GET['move']) {
				$move = $_GET['move'];
				$pos  = $_GET['pos'];
                $id   = $_GET['id'];
                $sql = "SELECT commodity FROM fitouts_checklist WHERE ID = '$id'";
                $result = $dbcnx->query($sql);
                $row = $result->fetch_assoc();
                $commodity = $row['commodity'];
				if ($move == "up") {
					$moveto = $pos-1;
					$movethat = "pos=pos+1";
					$otherone = $pos-1;
					$moveback = "pos='$moveto'";
				} else {
					$moveto = $pos+1;
					$movethat = "pos=pos-1";
					$otherone = $pos+1;
					$moveback = "pos='$moveto'";
				}
				$sql = "UPDATE fitouts_checklist SET pos = '0' WHERE commodity = '$commodity' AND pos = '$pos'";
				$result = $dbcnx->query($sql);
				$sql = "UPDATE fitouts_checklist SET $movethat WHERE commodity = '$commodity' AND pos = '$otherone'";
				$result = $dbcnx->query($sql);
				$sql = "UPDATE fitouts_checklist SET $moveback WHERE commodity = '$commodity' AND pos='0'";
				$result = $dbcnx->query($sql);
			}
			
			// get some records
			$sql = "SELECT * FROM fitouts_checklist ORDER BY commodity, pos";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";

                // count the number of rows for each commodity
                $sql = "SELECT commodity, COUNT(*) AS count FROM fitouts_checklist GROUP BY commodity";
                $cresult = $dbcnx->query($sql);
                while ($crow = $cresult->fetch_assoc()) {
                    $commodity = $crow['commodity'];
                    $count = $crow['count'];
                    $commodities[$commodity] = $count;
                }
				
				$n = 0;
                $lastcommodity = "";
				while ($row = $result->fetch_assoc()) {
					
					$id        = $row['ID'];
					$commodity = $row['commodity'];
					$details   = $row['details'];
					$pos       = $row['pos'];

                    if ($lastcommodity != $commodity) {
                        $n = 0;
                        if ($lastcommodity) {
                            print "<tr>\n";
                            print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                            print "</tr>\n";
                        }
                        print "<tr>\n";
                        print "	<td colspan=\"5\" class=\"larger\">$commodity</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
                        Print "	<td align=\"center\" colspan=\"2\" style=\"border-bottom:1px solid #999999\" width=\"36px\"><b>Move</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
                        print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
                        print "</tr>\n";
                        $lastcommodity = $commodity;
                    }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\">$details</td>\n";
					if ($n > 0) {
						print "		<td valign=\"top\" align=\"center\" width=\"18px\"><a href=\"fitouts-checklist-list.php?id=$id&amp;move=up&amp;pos=$pos\"><b>&nbsp;&#9650&nbsp;</b></a></td>\n";
					} else {
						print "		<td valign=\"top\" align=\"center\" width=\"18px\">&nbsp;</td>\n";
					}
					if ($n < $commodities[$commodity]-1) {
						print "		<td valign=\"top\" align=\"center\" width=\"18px\"><a href=\"fitouts-checklist-list.php?id=$id&amp;move=dn&amp;pos=$pos\"><b>&nbsp;&#9660&nbsp;</b></a></td>\n";
					} else {
						print "		<td valign=\"top\" align=\"center\" width=\"18px\">&nbsp;</td>\n";
					}
					if ($accessRights[$page] > 1) {
						print "<td valign=\"top\" align=\"center\"><a href=\"fitouts-checklist-addedit.php?id=$id\">Edit</a></td>\n";
					} else {
						print "	<td valign=\"top\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "<td valign=\"top\" align=\"center\"><a href=\"fitouts-checklist-delete.php?id=$id\">Delete</a></td>\n";
					} else {
						print "	<td valign=\"top\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
					$n++;
				}
				print "<tr>\n";
				print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\"><b>No Items Found</b></p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-checklist-addedit.php\">Create New Item</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
