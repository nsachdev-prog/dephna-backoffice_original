<?php
$page = "Fitouts";
$subpage = "Quotes";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Delete Quote</p>\n";
			
            $qid   = $_REQUEST['qid'];
            $reset = $_REQUEST['reset'];
            $type  = $_REQUEST['type'];
			if ($qid) {
				
                $sql = "SELECT q.*, u.compname
                    FROM fitouts_quotes AS q
                    LEFT JOIN users AS u ON q.custid = u.ID
                    WHERE q.ID = '$qid'
                ";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {
                    $row = $result->fetch_assoc();
                    $year      = $row['year'];
                    $month     = $row['month'];
                    $day       = $row['day'];
                    $compname  = $row['compname'];

                    print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";
					
					if ($reset) {

                        if ($type == "acc") {
                            // reset accepted
                            $sql = "UPDATE fitouts_quotes SET accepted = '0' WHERE ID = '$qid'";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $resultMessage .= "Accepted Reset<br>\n";
                            } else {
                                $resultMessage .= "Accepted Could NOT be Reset<br>" . $dbcnx->error . "<br>\n";
                            }
                        } elseif ($type == "ufp") {
                            // reset ufpaid
                            $sql = "UPDATE fitouts_quotes SET ufpaid = '0' WHERE ID = '$qid'";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $resultMessage .= "UF Paid Reset<br>\n";
                            } else {
                                $resultMessage .= "UF Paid Could NOT be Reset<br>" . $dbcnx->error . "<br>\n";
                            }
                        }
                        
                    } else {
                        if ($type == "acc") {
                            $typetxt = "Accepted";
                        } elseif ($type == "ufp") {
                            $typetxt = "Up Front";
                        }
                        print "<p align=\"center\"><b>Reset $typetxt</b></p>";
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"fitouts-quotes-reset.php?qid=$qid&amp;type=$type&amp;reset=1\">Yes Reset</a>&nbsp;|&nbsp;<a href=\"fitouts-quotes-complete.php?qid=$qid\">No DON'T Reset</a>";
                        print "</p>\n";
                    }

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Quote Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-complete.php?qid=$qid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
