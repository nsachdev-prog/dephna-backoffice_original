<?php
$page = "SRF";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">Service Request Forms</p>\n";
    
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $id = $_REQUEST['id'];
            $custref = $_REQUEST['custref'];

			if ($id AND $custref) {

                include('srf2-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";

				// get srf

                $sql = "SELECT * FROM srf2 WHERE ID = '$id'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $txndate = $row['txndate'];
                    $authby  = $row['authby'];
                    $email   = $row['email'];
                    $siteid  = $row['siteid'];
                    $unitid  = $row['unitid'];
                    $unitname = $row['unitname'];
                    $details = $row['details'];
                    $notes   = $row['notes'];
                    $adnotes = $row['adnotes'];
                    $charge  = $row['charge'];
                    $vat     = $row['vat'];
                    $group   = $row['group'];
                    $type    = $row['type'];
                    $lastupd = $row['lastupd'];
                    $updby   = $row['updby'];
                    $lastupd = date("D jS M Y \a\\t H:i",$lastupd);
                    $agreementID = $row['agreementID'];
                    $confirmed = $row['confirmed'];
                    if ($vat == "on") {
                        $vat = "Yes";
                    } else {
                        $vat = "No";
                    }

                    // get customer name
                    $sql = "SELECT compname FROM users WHERE custref = '$custref'";
                    $result = $dbcnx->query($sql);
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $compname = $row['compname'];
                    }

                    print "<p align=\"center\" class=\"larger\">Cancel Adobe Sign Agreement</p>\n";
                    print "<p class=\"larger\" align=\"center\">$compname - $unitname</p>\n";

                    $cancel = $_GET['cancel'];
                    $now = time();

                    if ($cancel) {

                        include_once('srf2-adobeSignCancel.php');

                        if ($rdb) {
                            include_once("rdb-save.php");
                            include_once("rdb-mail.php");
                        }

                    } else {

                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\"><a href=\"srf2-contract-cancel.php?id=$id&amp;custref=$custref&amp;cancel=1\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"srf2-summary.php?id=$id&amp;custref=$custref\">No DON'T Cancel</a></p>\n";
                        
                    }
                } else {
                    $resultMessage = "Could not get inventory from database<br />" . $dbcnx->error;
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
		print "<p align=\"center\"><a href=\"srf2-summary.php?id=$id&amp;custref=$custref\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
