<?php
$page = "SRF";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">Service Request Forms</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
            $custref = $_REQUEST['custref'];
            $id      = $_REQUEST['id'];
            $delete  = $_REQUEST['delete'];
			if ($custref AND $id) {
				
                // get srf
				$sql = "SELECT * FROM srf2 WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$authby  = $row['authby'];
					$unitname = $row['unitname'];
					$details = $row['details'];
					$agreementID = $row['agreementID'];
					$confirmed = $row['confirmed'];

					if ($agreementID) {
						include_once('bk-adobeSignStatus.php');
					}

					// get customer name and address
					$sql = "SELECT compname, addr1, addr2, addr3, addr4, email, aemails FROM users WHERE custref = '$custref'";
					$result = $dbcnx->query($sql);
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$compname = $row['compname'];
					}
                    
                    print "<p align=\"center\" class=\"larger\">Delete</p>\n";
                    print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
					
					if ($delete) {

                        if ($agreementID) {
                            if (!$ageementStatus == "SIGNED") {
                                include_once('srf2-adobeSignCancel.php');
                            }
                        }

                        $sql = "DELETE FROM srf2 WHERE ID='$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "SRF Deleted<br />\n";
                            $now = time();
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$custref $unitname','SRF','$now')");
                        } else {
                            $resultMessage .= "SRF Could NOT Be Deleted<br />\n";
                        }

                    } else {
                        if ($agreementID) {
                            if (!$ageementStatus == "SIGNED") {
                                print "<p align=\"center\"><b>AGREEMENT NOT SIGNED</b><br />The agreement will be cancelled if you delete the SRF.</p>";
                            }
                        }
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"srf2-delete.php?custref=$custref&amp;id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"srf2-list.php\">No DON'T Delete</a>";
                        print "</p>\n";
                    }
					
				} else {
					$resultMessage = "SRF Not Found!<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without ids<br />";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"srf2-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
