<?php
$page = "Purchases";
$subpage = "Purchase Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        $txtypes = array("I"=>"Invoice","C"=>"Credit Note","R"=>"Refund","BP"=>"Bank Payment","BR"=>"Bank Receipt");
			
        if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
        if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
        if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
        if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; }
        if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
        if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
        $qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // suppid
        if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
        if ($_REQUEST['posted']) { $posted = $_REQUEST['posted']; } 

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$oldsuppname = $row['suppname'];

                    print "<p align=\"center\" class=\"larger\">Move Unallocated Invoice/Credit Note</p>\n";

					$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['dephnaref'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['total'];
						$lastupd  = $row['lastupd'];
						$updby    = $row['updby'];
						$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
                            
                        $txndate = "$d-$m-$y";
						$txntotal = number_format($txntotal,2);

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Ref</b></td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Date</b></td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Amount</b></td>\n";
						print "<td>$txntotal</td>\n";
						print "</tr>\n";

                        print "</table>\n";

                        if ($posted) {

                            $supplier = $_POST['supplier'];
                            $e = 0; $rdb = "";

                            if ($supplier != "Select") {
                                list($suppid,$suppname) = explode("--",$supplier);
                            } else {
                                $ersupplier = "Select Supplier"; $e++;
                            }

                            if (!$e) {

                                $sql = "UPDATE purchases SET suppid = '$suppid' WHERE ID = '$hid'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                    $resultMessage = "<b>Transaction Moved</b><br />From: $id - $oldsuppname<br />To: $suppid - $suppname";
                                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Moved','Transaction $txntotal $id > $suppid','$page','$now')");
                                } else {
                                    $resultMessage = "<span class=\"redtext\">Could NOT move transaction.</span><br />" . $dbcnx->error;
                                }

                                if ($rdb) {
                                    include_once("rdb-save.php");
                                    include_once("rdb-mail.php");
                                }

                            }

                        }

                        if (!$resultMessage OR $e) {

                            $sql = "SELECT ID, suppname FROM suppliers ORDER BY suppname";
                            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                                print "<p align=\"center\">From: $id - $oldsuppname</p>\n";

                                print "<form name=\"addform\" method=\"post\" action=\"purchases-move.php?$qString\">\n";
                                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                                print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                                print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";

                                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                                print "<tr>\n";
                                print "	<td align=\"right\"><b>To Supplier</b>:</td>\n";
                                print "	<td><select name=\"supplier\" class=\"element\">\n";
                                print " <option value=\"Select\">Select</option>\n";
                                while ($row = $result->fetch_assoc()) {
                                    $thissuppid   = $row['ID'];
                                    $thissuppname = $row['suppname'];
                                  print "	<option value=\"$thissuppid--$thissuppname\""; if ($suppid == $thissuppid) { print " SELECTED"; } print ">$thissuppname</option>\n";
                                }
                                print "	</select>";
                                if ($ersupplier) {
                                    print "&nbsp;<span class=\"redtext\">$ercustomer</span>";
                                }
                                print "</td>\n";
                                print "</tr>\n";

                                print "</table>\n";

                                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                                print "</form>\n";
                            }

                        }
 
					} else {
						$resultMessage = "Payment not found!";
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
