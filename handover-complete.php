<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Handover";
$subpage = "Complete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">HANDOVER</p>\n";
	
	if ($accessRights[$page]) {

        $id = $_REQUEST['id'];
        if ($id) {

            if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
            if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
            $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;pa=$pa";

		    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		    if(!$dbcnx->connect_error) {

                $sql = "SELECT h.*, u.compname, s.title AS sitename
                        FROM handover AS h
                        LEFT JOIN sites AS s ON s.ID = h.siteid
                        LEFT JOIN users AS u ON u.custref = h.custref
                        WHERE h.ID = '$id'
                ";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['sitename'];
                    $unitname = $row['unitname'];
                    $compname = $row['compname'];
                    $datefrom = $row['datefrom'];
                    $datefrom = date("d-m-Y",$datefrom);

                    print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";
                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";
                    print "<p align=\"center\"><b>Mark As Complete</b></p>\n";
                
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Move In Date</b></td>\n";
                    print "	<td>$datefrom</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>BK Confirmed</b></td>\n";
                    print "	<td>$bkconfirmed</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    if ($_REQUEST['complete']) {

                        $today = date("Y-m-d");
                        $sql = "UPDATE handover SET confirmed = '$today' WHERE ID = '$id'";
                        if ($dbcnx->query($sql)) {
                            $resultMessage .= "Handover Updated if Kitchens<br />";
                        }

                    } else {
                        print "<p align=\"center\"><span class=\"redtext\">ARE YOU SURE?</span></p>\n";
                        print "<p align=\"center\"><a href=\"handover-complete.php?id=$id&amp;$qString\">Yes Complete</a> | <a href=\"handover-list.php?id=$id&amp;$qString\">No DON'T Complete</a></p>\n";
                    }

				}

			    $dbcnx->close();
            } else {
                $resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "Page called without ID<br>";
        }
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"handover-list.php?$qString\">Return To List</a> | ";
        print "<a href=\"handover-view.php?$qString\">Return To View</a>";
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
