<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">Bookings</p>\n";
            print "<p align=\"center\" class=\"larger\">Bookings with End Dates</p>\n";
			print "<p align=\"center\">$menustr</p>\n";

			// get bookings
					
			$sql = "SELECT b.datefrom, b.dateto, s.title AS sitename, u.title AS unitname, c.compname
                    FROM bookings AS b
                    LEFT JOIN sites AS s ON b.siteid = s.ID
                    LEFT JOIN units AS u ON b.unitid = u.ID
                    LEFT JOIN users AS c ON b.custid = c.custref
                    WHERE b.dateto > '0'
                    ORDER BY dateto, compname
            ";
			$result = $dbcnx->query($sql);
            if ($result->num_rows > 0) {
                print "<table align=\"center\" width=\"90%\"cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Start Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>End Date</b></td>\n";
                print "</tr>\n";

                while ($row = $result->fetch_assoc()) {
                    $id       = $row['ID'];
                    $site     = $row['sitename'];
                    $unit     = $row['unitname'];
                    $compname = $row['compname'];
                    $datefrom = intval($row['datefrom']);
                    $dateto   = intval($row['dateto']);
                    if ($datefrom) { 
                        $datefrom = date("d-m-Y",$datefrom);
                    } else {
                        $datefrom = "<span style=\"color:#FF0000\">No Start Date</span>";
                    }
                    if ($dateto) { 
                        $dateto = date("d-m-Y",$dateto);
                    } else {
                        $dateto = "Ongoing";
                    }
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$site</td>\n";
                    print "<td>$unit</td>\n";
                    print "<td>$compname</td>\n";
                    print "<td>$datefrom</td>\n";
                    print "<td>$dateto</td>\n";
                    print "</tr>\n";

                }
                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
            } else {
                print "<p align=\"center\">No Bookings Found</p>\n";
            }		


			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">&nbsp;</p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
