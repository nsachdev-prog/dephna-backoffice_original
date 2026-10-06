<?php
$page = "Inventory";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">INVENTORY</p>\n";
    
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $id = $_REQUEST['id'];
            $custref = $_REQUEST['custref'];

			if ($id AND $custref) {

                include('inventory-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";

				// get customer
				$sql = "SELECT u.username, u.compname, u.custref, s.ID AS siteid, s.title AS sitename, u.email, u.aemails
                FROM users AS u
                LEFT JOIN sites AS s ON s.ipaddress = u.location
                WHERE u.custref = '$custref'
                ";
                $result = $dbcnx->query($sql);
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();

                    $compname = $row['compname'];
                    $contact  = $row['username'];
                    $siteid   = $row['siteid'];
                    $sitename = $row['sitename'];
                    $memail   = $row['email'];
                    $aemails  = $row['aemails'];
                    if (!$aemails) { $aemails = $memail; }

                    print "<p align=\"center\" class=\"larger\">Cancel Adobe Sign Agreement</p>\n";

                    // get inventory

                    $sql = "SELECT `inout`, `agreementID` FROM `inventory` WHERE `ID` = '$id'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $agreementID = $row['agreementID'];
                        $inout       = $row['inout'];
                        $cancel = $_GET['cancel'];
                        $now = time();

                        print "<p class=\"larger\" align=\"center\">$compname Check $inout</p>\n";

                        if ($cancel) {

                            include_once('inventory-adobeSignCancel.php');

                            if ($rdb) {
                                include_once("rdb-save.php");
                                include_once("rdb-mail.php");
                            }

                        } else {

                            print "<p align=\"center\"><b>Are You Sure?</b></p>";
                            print "<p align=\"center\"><a href=\"inventory-contract-cancel.php?id=$id&amp;custref=$custref&amp;cancel=1\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"inventory-summary.php?id=$id&amp;custref=$custref\">No DON'T Cancel</a></p>\n";
                            
                        }
                    } else {
                        $resultMessage = "Could not get inventory from database<br />" . $dbcnx->error;
                    }
				} else {
					$resultMessage = "Could not get customer from database<br />" . $dbcnx->error;
                }
            } else {
                $resultMessage = "Page called without ids";
            }
            $dbcnx->close();
        } else {
            $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
        }
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-summary.php?id=$id&amp;custref=$custref\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
