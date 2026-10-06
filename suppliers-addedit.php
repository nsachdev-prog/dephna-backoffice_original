<?php
$page = "Suppliers";
$subpage = "Suppliers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">SUPPLIERS</p>\n";
		include('suppliers-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
			$qString ="se=$se&amp;az=$az&amp;pa=$pa";
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Supplier";
			} else {
				$heading = "New Supplier";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$suppref      = $_POST['suppref'];
				$suppname     = $_POST['suppname'];
				$suppcustref  = $_POST['suppcustref'];
				$addr1        = $_POST['addr1'];
				$addr2        = $_POST['addr2'];
				$addr3        = $_POST['addr3'];
				$addr4        = $_POST['addr4'];
				$telephone    = $_POST['telephone'];
				$telephone2   = $_POST['telephone2'];
				$email        = $_POST['email'];
				$aemails      = $_POST['aemails'];
				$termsdays    = $_POST['termsdays'];
				$costcentre   = $_POST['costcentre'];
				$vat          = $_POST['vat'];
				$contname     = $_POST['contname'];
				$bank         = $_POST['bank'];
				$bnkAccName   = $_POST['bnkAccName'];
				$bnkSort      = $_POST['bnkSort'];
				$bnkAccNum    = $_POST['bnkAccNum'];
				$erem         = $_POST['erem'];
				$site         = $_POST['site'];
				$utilitytype  = $_POST['utilitytype'];
				$notes        = $_POST['notes'];

				# strip SU from supplier reference
				$suppref = str_replace("SU","",$suppref);

				# strip commas from bank account name.
				$bnkAccName = str_replace(",","",$bnkAccName);
	
				if (!$suppname) { $ersuppname = "Please give a name"; $e++; }
				if (!$suppcustref) { $ersuppcustref = "Please give suppliers cust ref"; $e++; }
				if ($email) {
					$ee = 0;
					$trimmedemailarray = array();
					$emailarray = explode(",",$email);
					foreach ($emailarray AS $thisemail) {
						$thisemail = trim($thisemail);
						$trimmedemailarray[] = $thisemail;
						if (!validate_email($thisemail)) { $ee++; }
					}
					if ($ee) {
						$eremail = "Contains invalid email address"; $e++;
					} else {
						$email = implode(",",$trimmedemailarray);
					}
				}

				if ($aemails) {
					$ee = 0;
					$trimmedemailarray = array();
					$emailarray = explode(",",$aemails);
					foreach ($emailarray AS $thisemail) {
						$thisemail = trim($thisemail);
						$trimmedemailarray[] = $thisemail;
						if (!validate_email($thisemail)) { $ee++; }
					}
					if ($ee) {
						$eraemails = "Contains invalid email address"; $e++;
					} else {
						$aemails = implode(",",$trimmedemailarray);
					}
				}

				if (!$termsdays) {
					$ertermsdays = "Please enter the terms"; $e++;
				} elseif (!is_numeric($termsdays)) {
					$ertermsdays = "Must be numeric"; $e++;
				} else {
					$termsdays = intval($termsdays);
				}

				if (!$vat) {
					$ervat = "Please enter the vat%"; $e++;
				} elseif (!is_numeric($vat)) {
					$ervat = "Must be numeric"; $e++;
				}
				
				if ($costcentre == "Select") { $ercostcentre = "Please select a cost centre"; $e++; }

				if ($bank == "Yes") {
					if (!$bnkAccName) { $erBnkAccName = "Please enter Bank Acc Name"; $e++; }
					if (!$bnkSort) { $erBnkSort = "Please enter Bank Sort Code"; $e++; }
					if (!$bnkAccNum) {
						$erBnkAccNum = "Please enter Bank Acc Number"; $e++;
					} else {
						# strip commas from bank account name.
						$bnkAccName = str_replace(",","",$bnkAccName);
					}
				}

				if (!$e) {
					$suppref      = addslashes($suppref);
					$suppname     = addslashes($suppname);
					$suppcustref  = addslashes($suppcustref);
					$addr1        = addslashes($addr1);
					$addr2        = addslashes($addr2);
					$addr3        = addslashes($addr3);
					$addr4        = addslashes($addr4);
					$telephone    = addslashes($telephone);
					$telephone2   = addslashes($telephone2);
					$email        = addslashes($email);
					$contname     = addslashes($contname);
					$bnkAccName   = addslashes($bnkAccName);
					$bnkSort      = addslashes($bnkSort);
					$bnkAccNum    = addslashes($bnkAccNum);
					$erem         = addslashes($erem);
					$site         = addslashes($site);
					$utilitytype  = addslashes($utilitytype);
					$notes        = addslashes($notes);
					
					$now = time();
					
					if ($id) {
						$sql = "UPDATE suppliers SET
							suppname='$suppname',
							suppcustref='$suppcustref',
							addr1='$addr1',
							addr2='$addr2',
							addr3='$addr3',
							addr4='$addr4',
							telephone='$telephone',
							telephone2='$telephone2',
							email='$email',
							aemails='$aemails',
							termsdays='$termsdays',
							costcentre='$costcentre',
							vat='$vat',
							contname='$contname',
							bank='$bank',
							bnkAccName='$bnkAccName',
							bnkSort='$bnkSort',
							bnkAccNum='$bnkAccNum',
							erem='$erem',
							site='$site',
							utilitytype='$utilitytype',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Supplier Successfully Updated</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$suppname','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Update Record</b><br>" . $dbcnx->error;
						}
					} else {
						
						# NEW > Get next supplier ref
						$sql = "SELECT `suppref` FROM `suppliers` ORDER BY `suppref` DESC LIMIT 0, 1";
						if ($srresult = $dbcnx->query($sql)) {
							if ($srresult->num_rows) {
								$srrow = $srresult->fetch_assoc();
								$suppref = $srrow['suppref'];
								$suppref++;	
							} else {
								$suppref = 1001;
							}
						}
						
						if ($suppref) {
							$sql = "INSERT INTO suppliers VALUES (
								null,
								'$suppref',
								'$suppname',
								'$suppcustref',
								'$addr1',
								'$addr2',
								'$addr3',
								'$addr4',
								'$telephone',
								'$telephone2',
								'$email',
								'$aemails',
								'$termsdays',
								'$costcentre',
								'$vat',
								'$contname',
								'$bank',
								'$bnkAccName',
								'$bnkSort',
								'$bnkAccNum',
								'$erem',
								'$site',
								'$utilitytype',
								'$notes',
								'$now',
								'{$_SESSION['username']}'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>Supplier Successfully Added</b>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$suppname','$page','$now')");
							} else {
								$resultMessage = "<b>Could NOT Add New Record To Database</b><br>" . $dbcnx->error;
							}
						} else {
							$resultMessage = "<b>Could Get Next Supplier Ref</b>";
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];
					$suppcustref  = $row['suppcustref'];
					$addr1        = $row['addr1'];
					$addr2        = $row['addr2'];
					$addr3        = $row['addr3'];
					$addr4        = $row['addr4'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$email        = $row['email'];
					$aemails      = $row['aemails'];
					$termsdays    = $row['termsdays'];
					$costcentre   = $row['costcentre'];
					$vat          = $row['vat'];
					$contname     = $row['contname'];
					$bank         = $row['bank'];
					$bnkAccName   = $row['bnkAccName'];
					$bnkSort      = $row['bnkSort'];
					$bnkAccNum    = $row['bnkAccNum'];
					$erem    	  = $row['erem'];
					$site         = $row['site'];
					$utilitytype  = $row['utilitytype'];
					if (!$utilitytype) { $utilitytype = "N/A"; }
					$notes        = $row['notes'];
					$lastupd      = $row['lastupd'];
					$updby        = $row['updby'];
					$lastupd      = date("D jS M Y \a\\t H:i",$lastupd);
				} else {
					$resultMessage = "<b>Supplier Not Found</b><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"suppliers-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier Company Name</b>:</td>\n";
					print "	<td><input name=\"suppname\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$suppname\" />";
					if ($ersuppname) { print "&nbsp;<span class=\"redtext\">$ersuppname</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier Ref</b>:</td>\n";
					print "	<td><input name=\"suppcustref\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$suppcustref\" />";
					if ($ersuppcustref) { print "&nbsp;<span class=\"redtext\">$ersuppcustref</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if ($id) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Dephna Ref</b>:</td>\n";
						print "	<td>SU$suppref</td>\n";
						print "</tr>\n";
					}
						
					print "<tr>\n";
					print "	<td align=\"right\"><b>Address</b>:</td>\n";
					print "	<td><input name=\"addr1\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$addr1\" />";
					if ($eraddr1) { print "&nbsp;<span class=\"redtext\">$eraddr1</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">&nbsp;</td>\n";
					print "	<td><input name=\"addr2\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$addr2\" />";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">&nbsp;</td>\n";
					print "	<td><input name=\"addr3\" type=\"text\" class=\"element\" maxlength=\"30\"  value=\"$addr3\" />";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">&nbsp;</td>\n";
					print "	<td><input name=\"addr4\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$addr4\" />";
					print "</td>\n";
					print "</tr>\n";
						
					print "<tr>\n";
					print "	<td align=\"right\"><b>Telephone 1</b>:</td>\n";
					print "	<td><input name=\"telephone\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$telephone\" />";
					if ($ertelephone) { print "&nbsp;<span class=\"redtext\">$ertelephone</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Telephone 2</b>:</td>\n";
					print "	<td><input name=\"telephone2\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$telephone2\" />";
					if ($ertelephone2) { print "&nbsp;<span class=\"redtext\">$ertelephone2</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Sales email(s) (1,2,3...)</b>:</td>\n";
					print "	<td><input name=\"email\" type=\"text\" class=\"element\" maxlength=\"300\" value=\"$email\" />";
					if ($eremail) { print "&nbsp;<span class=\"redtext\">$eremail</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Accounts email(s) (1,2,3...)</b>:</td>\n";
					print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"300\" value=\"$aemails\" />";
					if ($eraemails) { print "&nbsp;<span class=\"redtext\">$eraemails</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Terms in Days</b>:</td>\n";
					print "	<td><input name=\"termsdays\" type=\"text\" class=\"element_tiny\" maxlength=\"3\" value=\"$termsdays\" />";
					if ($ertermsdays) { print "&nbsp;<span class=\"redtext\">$ertermsdays</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Contact Name</b>:</td>\n";
					print "	<td><input name=\"contname\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$contname\" />";
					if ($ercontname) { print "&nbsp;<span class=\"redtext\">$ercontname</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if (!$vat) { $vat = $vatRate; }
					print "<tr>\n";
					print "	<td align=\"right\"><b>VAT %</b>:</td>\n";
					print "	<td><input name=\"vat\" type=\"text\" class=\"element\" maxlength=\"5\" value=\"$vat\" />";
					if ($ervat) { print "&nbsp;<span class=\"redtext\">$ervat</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Cost Centre</b>:</td>\n";
					print "	<td><select name=\"costcentre\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					$sql = "SELECT * FROM costcentres ORDER BY title";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						while ($row = $result->fetch_assoc()) {
							$ccid  = $row['ID'];
							$title = $row['title'];
							print "		<option value=\"$ccid\"";
							if ($costcentre == $ccid) {
								print " SELECTED";
							}
							print ">$title</option>\n";
						}
					}
					print "	</select>";
					if ($ercostcentre) { print "&nbsp;<span class=\"redtext\">$ercostcentre</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank</b>:</td>\n";
					print "	<td><select name=\"bank\" class=\"element\">\n";
					print "		<option value=\"Yes\""; if ($bank == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
					print "		<option value=\"No\""; if ($bank == "No") { print " SELECTED"; } print ">No</option>\n";
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><b>If Bank yes, fill in the bank details</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Name</b>:</td>\n";
					print "	<td><input name=\"bnkAccName\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$bnkAccName\" />";
					if ($erBnkAccName) { print "&nbsp;<span class=\"redtext\">$erBnkAccName</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Sort Code</b>:</td>\n";
					print "	<td><input name=\"bnkSort\" type=\"text\" class=\"element\" maxlength=\"6\" value=\"$bnkSort\" />";
					if ($erBnkSort) { print "&nbsp;<span class=\"redtext\">$erBnkSort</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Number</b>:</td>\n";
					print "	<td><input name=\"bnkAccNum\" type=\"text\" class=\"element\" maxlength=\"8\" value=\"$bnkAccNum\" />";
					if ($erBnkAccNum) { print "&nbsp;<span class=\"redtext\">$erBnkAccNum</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Email Remittance</b>:</td>\n";
					print "	<td><select name=\"erem\" class=\"element\">\n";
					print "		<option value=\"Yes\""; if ($erem == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
					print "		<option value=\"No\""; if ($erem == "No") { print " SELECTED"; } print ">No</option>\n";
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					// site dropdown
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site (If Applicable)</b>:</td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					print "		<option value=\"0\">N/A</option>\n";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						while ($row = $result->fetch_assoc()) {
							$sid  = $row['ID'];
							$title = $row['title'];
							print "		<option value=\"$sid\"";
							if ($site == $sid) {
								print " SELECTED";
							}
							print ">$title</option>\n";
						}
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					// utility dropdown N/A, Electricity, Gas, Water, Sewerage, Telecoms 
					$utilitytypes = array("N/A", "Electricity", "Gas", "Water", "Sewerage", "Telecoms");
					print "<tr>\n";
					print "	<td align=\"right\"><b>Utility (If Applicable)</b>:</td>\n";
					print "	<td><select name=\"utilitytype\" class=\"element\">\n";
					foreach ($utilitytypes AS $utype) {
						print "		<option value=\"$utype\"";
						if ($utilitytype == $utype) {
							print " SELECTED";
						}
						print ">$utype</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Notes</b>:<br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					if ($id) {
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					}
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($id) {
			print "<p align=\"center\"><a href=\"suppliers-view.php?id=$id&amp;$qString\">View Supplier</a> | <a href=\"suppliers-list.php?$qString\">Return</a></p>\n";
		} else {
			print "<p align=\"center\"><a href=\"suppliers-list.php?$qString\">Return</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}

?>
