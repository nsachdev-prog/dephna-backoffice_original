<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Handover";
$subpage = "View";
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
                    $sitename      = $row['sitename'];
                    $unitname      = $row['unitname'];
                    $compname      = $row['compname'];
                    $datefrom      = $row['datefrom'];
                    $canopy        = $row['canopy'];
                    $clientcost    = $row['clientcost'];
                    $dephnacost    = $row['dephnacost'];
                    $canopysize    = $row['canopysize'];
                    $fitout        = $row['fitout'];
                    $servicegas    = $row['servicegas'];
                    $gastfee       = $row['gastfee'];
                    $servicewater  = $row['servicewater'];
                    $watertfee     = $row['watertfee'];
                    $serviceelect  = $row['serviceelect'];
                    $electtfee     = $row['electtfee'];
                    $graceperiod   = $row['graceperiod'];
                    $handovernotes = $row['notes'];
                    $bkconfirmed   = $row['bkconfirmed'];
                    $confirmed     = $row['confirmed'];
                    $updby         = $row['updby'];
                    $lastupd       = $row['lastupd'];
                    $lastupd = date("D jS M Y \a\\t H:i",$lastupd);
                    $datefrom = date("d-m-Y",$datefrom);
                    if ($canopy == "on") { $canopy = "Yes"; } else { $canopy = "No"; }
					if ($fitout == "on") { $fitout = "Yes"; } else { $fitout = "No"; }
                    if ($servicegas == "on") { $servicegas = "Yes"; } else { $servicegas = "No"; }
                    if ($servicewater == "on") { $servicewater = "Yes"; } else { $servicewater = "No"; }
                    if ($serviceelect == "on") { $serviceelect = "Yes"; } else { $serviceelect = "No"; }

                    print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";
                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";
                
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Move In Date</b></td>\n";
                    print "	<td>$datefrom</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Grace Period</b></td>\n";
                    print "	<td>$graceperiod</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>BK Confirmed</b></td>\n";
                    print "	<td>$bkconfirmed</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>HO Completed</b></td>\n";
                    print "	<td>$confirmed</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Canopy</b></td>\n";
                    print "	<td>$canopy</td>\n";
                    print "</tr>\n";

                    if ($canopy == "Yes") {
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Client Cost</b></td>\n";
                        print "	<td>$clientcost</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Dephna Cost</b></td>\n";
                        print "	<td>$dephnacost</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Canopy Size</b></td>\n";
                        print "	<td>$canopysize</td>\n";
                        print "</tr>\n";
                    }

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Fitout</b></td>\n";
                    print "	<td>$fitout</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
			        print "	<td colspan=\"2\" align=\"center\"><b>Services To Remain</b></td>\n";
			        print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Gas</b></td>\n";
                    print "	<td>";
                    print "$servicegas";
                    if ($servicegas == "Yes") {
                        print "&nbsp;&nbsp;Testing Fee: $gastfee";
                    }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Water</b></td>\n";
                    print "	<td>";
                    print "$servicewater";
                    if ($servicewater == "Yes") {
                        print "&nbsp;&nbsp;Testing Fee: $watertfee";
                    }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Electricity</b></td>\n";
                    print "	<td>";
                    print "$serviceelect";
                    if ($serviceelect == "Yes") {
                        print "&nbsp;&nbsp;Testing Fee: $electtfee";
                    }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td colspan=\"2\" width=\"400px\"><b>Additional Notes</b><br>";
                    print "$handovernotes";
                    print "</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    print "<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";
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
		print "<a href=\"handover-list.php?$qString\">Return</a>";
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
