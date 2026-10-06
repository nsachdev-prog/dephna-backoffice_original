<?php
$page = "Purchases";
$subpage = "Add Refund";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 2) {

        // get filtering
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        $qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa";

        if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // suppid
        if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
        if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT suppref, suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];

                    print "<p align =\"center\" class=\"larger\">$suppname</p>\n";
                    print "<p align =\"center\"><b>Delete Refund</b></p>\n";

                    $sql = "SELECT * FROM purchases WHERE ID = '$hid'";
                    $result = $dbcnx->query($sql);
                    if (($result) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $txnnum   = $row['dephnaref'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
                        $txntotal = $row['total'];
                        $tally_guid = $row['tally_guid'];
                        $txndate = "$d-$m-$y";
                        $txntotal *=-1;

                        print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td>Refund</td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Date</td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Amount</td>\n";
						print "<td>$txntotal</td>\n";
						print "</tr>\n";

                        print "</table>\n";

                        if ($delete) {

                            // delete refund
                            $sql = "DELETE FROM purchases WHERE ID = '$hid'";
                            if ($dbcnx->query($sql)) {
                                $rdb .= $sql.";\n";
                                $resultMessage .= "Refund Deleted.<br>";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$suppname Refund $txnnum','$page','$now')");
                            } else {
                                $resultMessage .= "<span class=\"redtext\">Could NOT delete refund.</span><br>" . $dbcnx->error;
                            }
                            if ($rdb) {
                                include_once("rdb-save.php");
                                include_once("rdb-mail.php");
                            }

                        } else {
                            print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                            print "<p class=\"centered\">";
                            print "	<a href=\"pl-refund-delete.php?id=$id&amp;hid=$hid&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"pl-refund-details.php?id=$id&amp;hid=$hid&amp;$qString\">No DON'T Delete</a>";
                            print "</p>\n";
                        }

                    } else {
                        $resultMessage = "Refund Not Found<br />" . $dbcnx->error;
                    }

                } else {
                    $resultMessage = "Supplier Not Found!";
                }

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without supplier / header ID!";
			}
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
    print "<p align=\"center\"><a href=\"pl-ledger.php?id=$id&$qString\">Return</a></p>\n";
}
include('footer.php');

?>
