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
                    $sitename = $row['sitename'];
					$unitid   = $row['unitid'];
                    $unitname = $row['unitname'];
                    $compname = $row['compname'];
					$lastupd  = $row['lastupd'];
                    $updby    = $row['updby'];
			
                    print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
                    
                    print "<p align=\"center\"><b>Delete Parking Bay</b></p>\n";

                    $pid = $_GET['pid'];
                    $delete = $_GET['delete'];
                    $sql = "SELECT bay FROM bka_parking WHERE ID = '$pid'";
                    $result = $dbcnx->query($sql);
                    if (($result) && ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $bay = $row['bay'];

                        if ($delete) {

                            $sql = "DELETE FROM bka_parking WHERE ID = '$pid'";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $rdb .= $sql.";\n";
                                $resultMessage = "Parking Bay $bay Deleted<br />";
                                $success++;
                            } else {
                                $resultMessage .= "Could not delete Parking Bay $bay<br />" . $dbcnx->error;
                            }
                            if ($rdb) {
                                include_once("rdb-save.php");
                                include_once("rdb-mail.php");
                            }

                            print "<p align=\"center\">$resultMessage</p>\n";
                            $resultMessage = "";

                        } else {
                            print "<p align=\"center\"><b>Delete Bay $bay<br>Are You Sure</b></p>\n";
                            print "<p align=\"center\"><a href=\"bka-parking-delete.php?bkaid=$bkaid&amp;pid=$pid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bka-parking.php?bkaid=$bkaid\">No DON'T Delete</a></p>\n";
                        }
                    } else {
                        $resultMessage = "Could not get parking bay";
                    }
			
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
		print "<p align=\"center\">";
        print "<a href=\"bka-parking.php?bkaid=$bkaid\">Return</a> | ";
        print "<a href=\"bka-contract.php?bkaid=$bkaid\">Next</a>";
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
