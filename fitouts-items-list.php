<?php
$page = "Fitouts";
$subpage = "Items";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
            
            print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		    include('fitouts-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Items</p>\n";

            $sql = "SELECT *
                    FROM fitouts_items
                    ORDER BY `type`, title
            ";
            
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                print "<table align=\"center\" width=\"80%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
                print "	    <td style=\"border-bottom:1px solid #666\"><b>Title</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amps</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Kws</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Upg</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Cost</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Imgs</b></td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" width=\"35\">&nbsp;</td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" width=\"30\">&nbsp;</td>\n";
                print "	    <td style=\"border-bottom:1px solid #666\" width=\"35\">&nbsp;</td>\n";
                print "	</tr>\n";

                $lasttype = "";

                while ($row = $result->fetch_assoc()) {
                    $id      = $row['ID'];
                    $title   = $row['title'];
                    $type    = $row['type'];
                    $power   = $row['power'];
                    $powerg  = $row['powerg'];
                    $upgrade = $row['upgrade'];
                    $amount  = $row['amount'];
                    $images  = $row['images'];

                    if ($upgrade) { $upgrade = "Yes"; } else { $upgrade = "&nbsp;"; }

                    // count images
                    $sql = "SELECT count(ID) AS images FROM fitouts_items_images WHERE itmid = $id";
                    if ($iresult = $dbcnx->query($sql) AND $iresult->num_rows) {
                        $irow = $iresult->fetch_assoc();
                        $images = $irow['images'];
                    } else {
                        $images = 0;
                    }

                    if ($type != $lasttype) {
                        print "<tr>\n";
                        print "	<td colspan=\"9\" style=\"background-color:#0c4173;font-size:14px;color:#eee\"><b>&nbsp;$type</b></td>\n";
                        print "</tr>\n";
                        $lasttype = $type;
                    }

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$title</td>\n";
                    print "<td align=\"right\">$power</td>\n";
                    print "<td align=\"right\">$powerg</td>\n";
                    print "<td align=\"right\">$upgrade</td>\n";
                    print "<td align=\"right\">".number_format($amount,2)."</td>\n";
                    print "<td align=\"right\">$images</td>\n";
                    print "<td><a href=\"fitouts-items-view.php?id=$id\">View</a></td>\n";
                    print "<td><a href=\"fitouts-items-addedit.php?id=$id\">Edit</a></td>\n";
                    print "<td><a href=\"fitouts-items-delete.php?id=$id\">Delete</a></td>\n";
                    print "</tr>\n";

                }

                $numrows = $result->num_rows;
                print "<tr>\n";
                print "	<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
                print "	<td colspan=\"9\" align=\"right\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";

                print "</table>\n";
            } else {
                $resultMessage = "<b>No Items Found!</b>";
            }
            
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"fitouts-items-addedit.php\">Add New Fitout Item</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
