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
			
			print "<p align = \"center\" class=\"heading\">Up Front Payment Received</p>\n";
			
            $qid  = $_REQUEST['qid'];
            $paid = $_REQUEST['paid'];

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
					
					if ($paid) {

                        $now = time();

                        $sql = "UPDATE fitouts_quotes SET
                                    ufpaid = '$now'
                                WHERE ID = '$qid'
                        ";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Qupote Updated.<br>";
                        } else {
                            $resultMessage .= "<b>Quote Could NOT Be Updated</b><br>" . $dbcnx->error;
                        }
                    } else {
                        print "<p align=\"center\"><b>Up Front Payment Received And Posted</b></p>";
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"fitouts-quotes-ufpaid.php?qid=$qid&amp;paid=1\">Yes Paid</a>&nbsp;|&nbsp;<a href=\"fitouts-quotes-complete.php?qid=$qid\">No NOT Paid</a>";
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
