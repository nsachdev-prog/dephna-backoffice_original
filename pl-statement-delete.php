<?php
$page = "Purchases";
$subpage = "Purchases";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">PURCHASES</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
			if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
			if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
			if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
			if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
			$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";
			
			$id = $_REQUEST['id'];
			if ($id) {
				# get details
				$sql = "SELECT p.*, s.suppname
					FROM purchases p
					LEFT JOIN suppliers s ON p.suppid = s.ID
					WHERE p.ID = '$id'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $date = $row['day']."/".$row['month']."/".$row['year'];
                    $suppid    = $row['suppid'];
                    $suppname  = $row['suppname'];
                    $pdf       = $row['pdf'];
                    $year      = $row['year'];
                    $month     = $row['month'];
                    $day       = $row['day'];

                    print "<p align=\"center\" class=\"larger\">Delete Statement</p>\n";
                    print "<p align=\"center\" class=\"larger\">$suppname</p>\n";
                
                    $delete = $_REQUEST['delete'];

                    if ($delete) {
    
                        if ($pdf) {
                            if (is_file("../../dis2_db/pdf/pl/$pdf")) {
                                if (unlink("../../dis2_db/pdf/pl/$pdf")) {
                                    $resultMessage .= "Deleted Pdf<br>";
                                    $sql = "DELETE FROM purchases WHERE ID='$id'";
                                    $result = $dbcnx->query($sql);
                                    if ($result) {
                                        $rdb .= $sql.";\n";
                                        $resultMessage .= "Deleted Statement<br>";
                                        $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$dephnaref from $suppname','$page','$now')");
                                    } else {
                                        $resultMessage .= "<span class=\"redtext\"><b>$dephnaref from $suppname Could Not Be Deleted</b></span><br>" . $dbcnx->error . "<br>";
                                    }
                                } else {
                                    $resultMessage .= "Cound NOT Delete Pdf<br>";
                                }
                            }
                        }
                        
                        if ($rdb) {
                            include_once("rdb-save.php");
                            include_once("rdb-mail.php");
                        }
                            
                    } else {
                        print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
                        print "	<td align=\"right\">Statement Date:</td>\n";
                        print "	<td colspan=\"3\">$year-$month-$day</td>\n";
                        print "</tr>\n";
                        print "</table>\n";
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"pl-statement-delete.php?id=$id&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"pl-statement-details.php?id=$suppid&amp;hid=$id&amp;$qString\">No DON'T Delete</a>";
                        print "</p>\n";
                    }	
				} else {
					$resultMessage .= "<span class=\"redtext\">Statement Not Found." . $dbcnx->error . "<br>";
				}
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Page called without id</b></span><br>";
			}	
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"purchases-ledger.php?$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
