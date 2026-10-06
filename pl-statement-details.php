<?php
$page = "Purchases";
$subpage = "Statement";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        $qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
        if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppname     = $row['suppname'];

                    print "<p align =\"center\" class=\"larger\">Statement</p>\n";
                    print "<p align =\"center\" class=\"larger\">$suppname</p>\n";

					$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['dephnaref'];
						$y        = $row['year'];
						$m        = $row['month'];
                        $d        = $row['day'];
                        $pdf      = $row['pdf'];
						$lastupd  = $row['lastupd'];
						$updby    = $row['updby'];
						$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
                            
                        $txndate = "$d-$m-$y";
                        $txntotal *=-1;
						$txntotal = number_format($txntotal,2);

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Date</b></td>\n";
						print "<td>$txndate</td>\n";
                        print "</tr>\n";
                        
                        if ($pdf) {
							print "<tr>\n";
							print "	<td align=\"right\"><b>Pdf:</b></td>\n";
							print "	<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">$pdf</a></td>\n";
							print "</tr>\n";
						}

                        print "</table>\n";

                        print "<p align=\"center\">";
                        print "<a href=\"pl-statement-addedit.php?id=$hid&$qString\">Edit</a> | ";
                        print "<a href=\"pl-statement-delete.php?id=$hid&$qString\">Delete</a>";
                        print "</p>\n";
                        
                        print "<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";
                        
					} else {
						$resultMessage = "Statement not found!";
					}

				} else {
                    $resultMessage = "Supplier Not Found<br>";
                }

			} else {
				$resultMessage = "Page called without supplier or header ID!<br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"pl-ledger.php?id=$id&$qString\">Return</a>\n";
}
include('footer.php');

?>
