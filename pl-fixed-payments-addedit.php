<?php
$page = "Purchases";
$subpage = "Fixed Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
		include('pl-fixed-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update";
			} else {
				$heading = "New";
			}
			print "<p align=\"center\" class=\"larger\">$heading Fixed Payment</p>\n";
			
			if ($_POST['posted']) {

                $day      = strip_tags($_POST['day']);
                $supplier = strip_tags($_POST['supplier']);
                $details  = strip_tags($_POST['details']);
                $total    = strip_tags($_POST['total']);
                $addvat   = strip_tags($_POST['addvat']);
                $make_inv = strip_tags($_POST['make_inv']);

                if ($day =="Select") { $erday = "Please select a day"; $e++; }
                if (!$total) { $ertotal   = "Please enter a total"; $e++; }
                elseif (!is_numeric($total)) { $ertotal = "Not Numeric"; $e++; }
				if ($supplier == "Select")  { $ersupplier = "Please select a supplier"; $e++; }
				else {
					list($suppid,$suppref,$suppname,$ccid,$defaultvat) = explode("--",$supplier);
					if ($addvat == "Default") {
						$vat = $defaultvat;
					} else {
						$vat = $addvat;
					}
					if ($vat > 0) {
						$net = $total/(1+$vat/100);
						$net = number_format($net,2,'.','');
						$vatamount = $total - $net;
						$vatamount = number_format($vatamount,2,'.','');
					} else {
						$net = $total;
						$vatamount = 0;
					}
				}

				if (!$e) {
					
                    $details = addslashes($details);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE `fixedpmts` SET 
                            `day`      = '$day',
							`suppid`   = '$suppid',
							`ccid`     = '$ccid',
                            `details`  = '$details',
							`net`      = '$net',
							`addvat`   = '$addvat',
                            `vat`      = '$vatamount',
                            `total`    = '$total',
							`make_inv` = '$make_inv',
							`lastupd`  = '$now',
							`updby`    = '{$_SESSION['username']}' 
							WHERE `ID` = '$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "<b>Fixed Payment Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','FP $ledger $total','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO `fixedpmts` VALUES (
							null,
                            '$day',
							'$suppid',
							'$ccid',
                            '$details',
							'$net',
							'$addvat',
                            '$vatamount',
                            '$total',
							'$make_inv',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "<b>Fixed Payment Successfully Added</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','FP $ledger $total','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
				if ($rdb) {
					include_once("rdb-save.php");
					include_once("rdb-mail.php");
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM fixedpmts WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
                    $day      = $row['day'];
					$suppid   = $row['suppid'];
                    $ccid     = $row['ccid'];
                    $details  = $row['details'];
					$net      = $row['net'];
					$addvat   = $row['addvat'];
                    $vat      = $row['vat'];
                    $total    = $row['total'];
					$make_inv = $row['make_inv'];
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fixed Payment Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
                $make_inv = "on";
            }

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"pl-fixed-payments-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier</b></td>\n";
					print "	<td><select name=\"supplier\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					$sql = "SELECT ID, suppname, suppref, costcentre, vat FROM suppliers ORDER BY suppname";
					if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
						while ($srow = $sresult->fetch_assoc()) {
                            $tsuppid      = $srow['ID'];
                            $tsuppref     = $srow['suppref'];
							$tsuppname    = $srow['suppname'];
							$tdefaultccid = $srow['costcentre'];
							$tdefaultvat  = $srow['vat'];
							$supplier = "$tsuppid--$tsuppref--$tsuppname--$tdefaultccid--$tdefaultvat";
							print "		<option value=\"$supplier\""; if ($tsuppid == $suppid) { print " SELECTED"; } print ">$tsuppname</option>\n";
						}
					}
					print "	</select>";
					if ($ersupplier) { print "&nbsp;<span class=\"redtext\">$ersupplier</span>"; }
					print "</td>\n";
					print "</tr>\n";

                    print "	<td align=\"right\"><b>Day Of Month</b></td>\n";
                    print "	<td>\n";
                    print "	<select name=\"day\" class=\"element_date\">\n";
                    print "		<option>Select</option>\n";
                    for ($o=1; $o <= 28; $o++) {
                        print "		<option ";
                        if ($o < 10) { $check = "0".$o; } else { $check = $o; }
                        if ($day == $check) { print "SELECTED "; }
                        print "value=\"$check\">$check</option>\n";
                    }
                    print "	</select>\n";
                    if ($erday) { print "&nbsp;<span class=\"redtext\">$erday</span>"; }
					print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>Default Amount (Total)</b></td>\n";
					print "	<td><input name=\"total\" type=\"text\" class=\"element\" value=\"$total\" />";
					if ($ertotal) { print "&nbsp;<span class=\"redtext\">$ertotal</span>"; }
					print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>VAT%</b></td>\n";
                    print "	<td><select name=\"addvat\" class=\"element\">\n";
                    print "		<option "; if ($addvat == "Default") { print "SELECTED "; } print "value=\"Default\">Supplier Default</option>\n";
                    print "		<option "; if ($addvat == $vatRate) { print "SELECTED "; } print "value=\"$vatRate\">$vatRate%</option>\n";
                    print "		<option "; if ($addvat == "0")  { print "SELECTED "; } print "value=\"0\">0%</option>\n";
                    print "	</select>\n";
                    print "	</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>Default Details</b></td>\n";
					print "	<td><input name=\"details\" type=\"text\" class=\"element\" value=\"$details\" />";
					if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
					print "</td>\n";
                    print "</tr>\n";
                    
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Make Invoice</b></td>\n";
                    print "	<td><input type=\"checkbox\" name=\"make_inv\""; if ($make_inv) { print " CHECKED"; } print " /></td>\n";
                    print "</tr>\n";

					print "</table>\n";
                    
                    print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                    print "</form>\n";
					
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"pl-fixed-payments-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
