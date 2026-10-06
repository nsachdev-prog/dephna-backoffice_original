<?php
$page = "Purchases";
$subpage = "Cancel";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">PURCHASES</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$txtypes = array("I"=>"Invoice","C"=>"Credit Note","BP"=>"Bank Payment","BR"=>"Bank Receipt");
			
			if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
			if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
			if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
			if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
			if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
			$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";
			
            $id = $_REQUEST['id'];
            $hid = $_REQUEST['hid'];
            $cancel = $_REQUEST['cancel'];
			if ($id) {
				# get details
				$sql = "SELECT p.txtype, p.dephnaref, p.year, p.month, p.day, p.suppid, p.total, s.suppname
					    FROM purchases p
					    LEFT JOIN suppliers s ON p.suppid=s.ID
					    WHERE p.ID='$hid'
				";
				if ($result = $dbcnx->query($sql)) {
					if ($result->num_rows) {
                        $row = $result->fetch_assoc();
                        
                        $date       = $row['day']."-".$row['month']."-".$row['year'];
                        $trndate    = $row['year']."-".$row['month']."-".$row['day'];
						$suppname   = $row['suppname'];
						$dephnaref  = $row['dephnaref'];
						$total      = $row['total'];
                        $txtype     = $row['txtype'];
						$heading    = $txtypes[$txtype];

                        print "<p align=\"center\" class=\"heading\">Cancel $heading</p>\n";

                        if ($trndate > $VAT_Period_Lock) {

                            if ($cancel) {

                                $sql = "UPDATE purchases_lines SET 
                                            net='0.00',
                                            vat='0.00',
                                            total='0.00' 
                                        WHERE hid='$hid'
                                ";
                                $result = $dbcnx->query($sql);
                                if ($result) {
                                    $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                                    $resultMessage .= "Cancelled Costcentre Records<br>";
                                    $sql = "UPDATE purchases SET 
                                                net='0.00',
                                                vat='0.00',
                                                total='0.00',
                                                outstanding='0.00' 
                                            WHERE ID='$hid'
                                    ";
                                    $result = $dbcnx->query($sql);
                                    if ($result) {
                                        $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                                        $resultMessage .= "Cancelled Purchase Header<br>";
                                        $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cancelled','$dephnaref from $suppname','$page','$now')");
                                    } else {
                                        $resultMessage .= "<span class=\"redtext\"><b>$dephnaref from $suppname Could Not Be Cancelled</b></span><br>" . $dbcnx->error . "<br>";
                                    }
                                } else {
                                    $resultMessage .= "<span class=\"redtext\"><b>CAM Costcentre Records Could Not Be Cancelled</b></span><br>" . $dbcnx->error . "<br>";
                                }
                                if ($rdb) {
                                    include_once("rdb-save.php");
                                    include_once("rdb-mail.php");
                                }   

                            } else {
                                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
                                print "<tr>\n";
                                print "	<td align=\"right\">Dephna Ref:</td>\n";
                                print "	<td colspan=\"3\">$dephnaref</td>\n";
                                print "</tr>\n";
                                print "<tr>\n";
                                print "	<td align=\"right\">Supplier Name:</td>\n";
                                print "	<td colspan=\"3\">$suppname</td>\n";
                                print "</tr>\n";
                                print "<tr>\n";
                                print "	<td align=\"right\">Total:</td>\n";
                                print "	<td colspan=\"3\">$total</td>\n";
                                print "</tr>\n";
                                print "</table>\n";
                                print "<p align=\"center\"><b>Are You Sure?</b></p>";
                                print "<p align=\"center\">";
                                print "	<a href=\"purchases-cancel.php?id=$id&amp;hid=$hid&amp;cancel=1&amp;$qString\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"purchases-view.php?id=$id&amp;hid=$hid&amp;$qString\">No DON'T Cancel</a>";
                                print "</p>\n";
                            }

                        } else {
                            $resultMessage .= "<b>You Cannot Cancel This Transaction</b><br />Transaction Date: $trndate<br />VAT Period Closed<br />";
                        }
                        
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Transaction Not found!</b></span><br>";
					}
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Supplier Not Found!</b></span><br>" . $dbcnx->error . "<br>";
				}
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Page called without id</b></span><br>";
			}	
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	if ($tallyResultMessage) {
		print"<p align=\"center\">$tallyResultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"purchases-ledger.php?$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
