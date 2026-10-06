<?php
$page = "Fitouts";
$subpage = "Send";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

        $qid = $_REQUEST['qid'];
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
        print "<p align=\"center\">$menustr</p>\n";

        print "<p align=\"center\" class=\"larger\">Quote</p>\n";

        if ($qid) {
		
		    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		    if (!$dbcnx->connect_error) {

                $sql = "SELECT q.*, u.compname
                    FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
                    WHERE q.ID = '$qid'
                ";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $year      = $row['year'];
                    $month     = $row['month'];
                    $day       = $row['day'];
                    $compname  = $row['compname'];

                    print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";
                }
					
			    $dbcnx->close();
            } else {
                $resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "Page called witout id";
        }
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
