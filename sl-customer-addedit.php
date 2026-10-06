<?php
$page = "Sales_Ledger";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

            if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
            if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
            if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
            if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
            if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
            if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
            $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";
		
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Customer";
			} else {
				$heading = "New Customer";
			}
			print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
			print "<p align = \"center\" class=\"larger\">$heading</p>\n";
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
					$_POST[$key] = str_replace("'","&#39;",$_POST[$key]);
				}
				$username     = $_POST['username'];
				$email        = $_POST['email'];
				$thispassword = $_POST['thispassword'];
				$custref      = $_POST['custref'];
				$compname     = $_POST['compname'];
				$addr1        = $_POST['addr1'];
				$addr2        = $_POST['addr2'];
				$addr3        = $_POST['addr3'];
				$addr4        = $_POST['addr4'];
				$telephone    = $_POST['telephone'];
				$telephone2   = $_POST['telephone2'];
				$aemails      = $_POST['aemails'];
				$pdfinvs      = $_POST['pdfinvs'];
				$site         = $_POST['site'];
				$billing      = $_POST['billing'];
				$FireMarshall = $_POST['FireMarshall'];
				$FirstAider   = $_POST['FirstAider'];
				$bank         = $_POST['bank'];
				$bnkAccName   = $_POST['bnkAccName'];
				$bnkSort      = $_POST['bnkSort'];
				$csdate       = $_POST['csdate'];
				$bnkAccNum    = $_POST['bnkAccNum'];
				$details      = $_POST['details'];
				$ColdRoomContact = $_POST['ColdRoomContact'];
				$ColdRoomPhone   = $_POST['ColdRoomPhone'];
				$ColdRoomEmail   = $_POST['ColdRoomEmail'];
	
				# strip DP from customer reference
				$custref = str_replace("DP","",$custref);
	
				# strip commas from bank account name.
				$bnkAccName = str_replace(",","",$bnkAccName);

				if (!$username) { $erUsername = "Please give a full name"; $e++; }
				if (!$email) { $erEmail = "Please give an email address"; $e++; }
				elseif (!validate_email($email)) { $erEmail = "Not a valid email address"; $e++; }
				if (!$id) {
					if (!$thispassword) { $erThispassword = "Please give a password"; $e++; }
					if (!$custref) {
						$erCustref = "Please give a customer reference";
						$e++;
					} elseif (!is_numeric($custref)) {
						$erCustref = "Customer Reference is not numeric";
						$e++;
					} else {
					  	$sql = "SELECT custref FROM users WHERE custref='$custref'";
						if ($result = $dbcnx->query($sql)) {
							if ($result->num_rows) {
								$erCustref = "Customer Reference already exists";
								$e++;
							}
						}
					}
				}

				if ($thispassword) {
					if(strlen($thispassword) < 8)              { $erThispassword .= "Password must be at least 8 chatacters long!<br>"; $e++; }		
					if(strlen($thispassword) > 20)             { $erThispassword .= "Password cannot be more than 20 characters long!<br>"; $e++; }
					if(!preg_match("#[0-9]+#", $thispassword)) { $erThispassword .= "Password must include at least one number!<br>"; $e++; }
					if(!preg_match("#[a-z]+#", $thispassword)) { $erThispassword .= "Password must include at least one lower case letter!<br>"; $e++; }
					if(!preg_match("#[A-Z]+#", $thispassword)) { $erThispassword .= "Password must include at least one upper case letter!<br>"; $e++; }
					if(!preg_match("#\W+#", $thispassword))    { $erThispassword .= "Password must include at least one symbol!<br>"; $e++; }
				}
				
				if (!$compname) { 
					$erCompname = "Please give a company name"; $e++;
				} else {
					// check to see if compname is all uppercase or all lowercase
					if (strtolower($compname) == $compname OR strtoupper($compname) == $compname) {
						// change to Title Case
						$compname = ucwords(strtolower($compname));
					}
				}
				if (!$addr1) { $erAddr1 = "Please enter an address"; $e++; }
				if (!$addr4) { $erAddr4 = "Please enter the postcode"; $e++; }
				if ($site == "Select") { $ersite = "Please select a site"; $e++; }
				if ($billing == "Select") { $erBilling = "Please select a billing period"; $e++; }
				if ($bank == "Yes") {
					if (!$bnkAccName) { $erBnkAccName = "Please enter Bank Acc Name"; $e++; }
					if (!$bnkSort) { $erBnkSort = "Please enter Bank Sort Code"; $e++; }
					if (!$bnkAccNum) { $erBnkAccNum = "Please enter Bank Acc Number"; $e++; }
				}

				if ($aemails) {
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
				} else {
					$aemails = $email;
				}

				if ($ColdRoomEmail) {
					if (!validate_email($ColdRoomEmail)) {
						$erColdRoomEmail = "Invalid email address"; $e++;
					}
				}

				if ($pdfinvs) {
					if (!$aemails) {
						$eraemails = "Email for Pdfs not given"; $e++;
					}
				}

				if (!$e) {
					$username     = addslashes($username);
					$email        = addslashes($email);
					$custref      = addslashes($custref);
					$compname     = addslashes($compname);
					$addr1        = addslashes($addr1);
					$addr2        = addslashes($addr2);
					$addr3        = addslashes($addr3);
					$addr4        = addslashes($addr4);
					$telephone    = addslashes($telephone);
					$telephone2   = addslashes($telephone2);
					$aemails      = addslashes($aemails);
					$site         = addslashes($site);
					$billing      = addslashes($billing);
					$FireMarshall = addslashes($FireMarshall);
					$FirstAider   = addslashes($FirstAider);
					$bnkAccName   = addslashes($bnkAccName);
					$bnkSort      = addslashes($bnkSort);
					$bnkAccNum    = addslashes($bnkAccNum);
					$details      = addslashes($details);
					$ColdRoomContact = addslashes($ColdRoomContact);
					$ColdRoomPhone   = addslashes($ColdRoomPhone);
					$ColdRoomEmail   = addslashes($ColdRoomEmail);
					$now = time();

					if ($thispassword) {
						$thispassword = $dbcnx->real_escape_string($thispassword);
						$Salt = generateRandomString();
						$Algo = '6';
						$Rounds = '5000';
						$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
						$hashed_password = crypt($thispassword, $CryptSalt);
						$expires = strtotime("+$changePasswordMonths months");
					}
					if ($id) {
						if ($thispassword) {
							$sql = "UPDATE users SET
								username='$username',
								email='$email',
								password='$hashed_password',
								validfrom='0',
								expires='$expires',
								compname='$compname',
								addr1='$addr1',
								addr2='$addr2',
								addr3='$addr3',
								addr4='$addr4',
								telephone='$telephone',
								telephone2='$telephone2',
								aemails='$aemails',
								pdfinvs='$pdfinvs',
								location='$site',
								billing='$billing',
								FireMarshall='$FireMarshall',
								FirstAider='$FirstAider',
								bank='$bank',
								bnkAccName='$bnkAccName',
								bnkSort='$bnkSort',
								bnkAccNum='$bnkAccNum',
								details='$details',
								ColdRoomContact='$ColdRoomContact',
								ColdRoomPhone='$ColdRoomPhone',
								ColdRoomEmail='$ColdRoomEmail'
							WHERE ID='$id'";
						} else {
							$sql = "UPDATE users SET
								username='$username',
								email='$email',
								compname='$compname',
								addr1='$addr1',
								addr2='$addr2',
								addr3='$addr3',
								addr4='$addr4',
								telephone='$telephone',
								telephone2='$telephone2',
								aemails='$aemails',
								pdfinvs='$pdfinvs',
								location='$site',
								billing='$billing',
								FireMarshall='$FireMarshall',
								FirstAider='$FirstAider',
								bank='$bank',
								bnkAccName='$bnkAccName',
								bnkSort='$bnkSort',
								bnkAccNum='$bnkAccNum',
								details='$details',
								ColdRoomContact='$ColdRoomContact',
								ColdRoomPhone='$ColdRoomPhone',
								ColdRoomEmail='$ColdRoomEmail'
							WHERE ID='$id'";
						}
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfuly updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$compname','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$expires = strtotime("+$changePasswordMonths months");
						$sql = "INSERT INTO users VALUES (
						null,
						'$username',
						'$email',
						'$hashed_password',
						'0',
						'$expires',
						'2',
						'$custref',
						'$compname',
						'$addr1',
						'$addr2',
						'$addr3',
						'$addr4',
						'$telephone',
						'$telephone2',
						'$aemails',
						'$pdfinvs',
						'$site',
						'$billing',
						'$FireMarshall',
						'$FirstAider',
						'',
						'0',
						'',
						'',
						'$bank',
						'',
						'',
						'',
						'',
						'$bnkAccName',
						'$bnkSort',
						'$bnkAccNum',
						'$details',
						'',
						'',
						'',
						'No',
						'',
						'$ColdRoomContact',
						'$ColdRoomPhone',
						'$ColdRoomEmail',
						'',
						'',
						''
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$id = $dbcnx->insert_id;
							$resultMessage = "<b>Customer successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
	  		$sql = "SELECT * FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$username     = $row['username'];
					$email        = $row['email'];
					$custref      = $row['custref'];
					$compname     = $row['compname'];
					$addr1        = $row['addr1'];
					$addr2        = $row['addr2'];
					$addr3        = $row['addr3'];
					$addr4        = $row['addr4'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$aemails      = $row['aemails'];
					$pdfinvs      = $row['pdfinvs'];
					$site         = $row['location'];
					$billing      = $row['billing'];
					$FireMarshall = $row['FireMarshall'];
					$FirstAider   = $row['FirstAider'];
					$bank         = $row['bank'];
					$bnkAccName   = $row['bnkAccName'];
					$bnkSort      = $row['bnkSort'];
					$bnkAccNum    = $row['bnkAccNum'];
					$details      = $row['details'];
					$ColdRoomContact = $row['ColdRoomContact'];
					$ColdRoomPhone   = $row['ColdRoomPhone'];
					$ColdRoomEmail   = $row['ColdRoomEmai'];
				} else {
					$resultMessage = "Customer Not Found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					# get sites
					$sql = "SELECT * FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					if (!$custref) {
						# get the next available customer ref
	  				$sql = "SELECT custref FROM users ORDER BY custref DESC LIMIT 0,1";
						$cresult = $dbcnx->query($sql);
						if ($cresult->num_rows) {
							$crow = $cresult->fetch_assoc();
							$custref = $crow['custref'];
							if ($custref) {
								$custref++;
								$autoref++;
							}
						}
					}

					print "<p align=\"center\">";
					print "<span class=\"redtext\">Please don't use block capitals.<br />Use Title Case Like This.</span>";
					print "</p>";

					print "<form name=\"addform\" method=\"post\" action=\"sl-customer-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Company Name:</b></td>\n";
					print "	<td><input name=\"compname\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$compname\" />";
					if ($erCompname) { print "<br><span class=\"redtext\">$erCompname</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"><i>Main login email must be unique. Two accounts cannot use the same login email address.</i></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Main Login email:</b></td>\n";
					print "	<td><input name=\"email\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$email\" />";
					if ($erEmail) { print "<br><span class=\"redtext\">$erEmail</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"><i>Passwords must be 8 - 20 characters with at lease 1 lower, 1 upper, 1 number &amp; 1 symbol.</i></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Password:</b></td>\n";
					print "	<td><input name=\"thispassword\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$thispassword\" />";
					if ($erThispassword) { print "<br><span class=\"redtext\">$erThispassword</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if ($id) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Cust Ref:</b></td>\n";
						print "	<td>DP$custref</td>\n";
						print "</tr>\n";
						print "<input type=\"hidden\" name=\"thislevel\" value=\"$thislevel\" />\n";
					} else {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Cust Ref:</b></td>\n";
						print "	<td><input name=\"custref\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"DP$custref\" />";
						if ($erCustref) { print "<br><span class=\"redtext\">$erCustref</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if ($autoref) {
							print "<tr>\n";
							print "	<td></td>\n";
							print "	<td>Next available Cust Ref auto generated.</td>\n";
							print "</tr>\n";
						}
					}

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Contact Name:</b></td>\n";
					print "	<td><input name=\"username\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$username\" />";
					if ($erUsername) { print "<br><span class=\"redtext\">$erUsername</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Address:</b></td>\n";
					print "	<td><input name=\"addr1\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$addr1\" />";
					if ($erAddr1) { print "<br><span class=\"redtext\">$erAddr1</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>&nbsp;</td>\n";
					print "	<td><input name=\"addr2\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$addr2\" />";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>&nbsp;</td>\n";
					print "	<td><input name=\"addr3\" type=\"text\" class=\"element\" maxlength=\"30\"  value=\"$addr3\" />";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Postcode:</b></td>\n";
					print "	<td><input name=\"addr4\" type=\"text\" class=\"element\" maxlength=\"12\" value=\"$addr4\" />";
					if ($erAddr4) { print "<br><span class=\"redtext\">$erAddr4</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Personal Telephone:</b></td>\n";
					print "	<td><input name=\"telephone\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$telephone\" />";
					if ($erTelephone) { print "<br><span class=\"redtext\">$erTelephone</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Work Telephone:</b></td>\n";
					print "	<td><input name=\"telephone2\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$telephone2\" />";
					if ($erTelephone2) { print "<br><span class=\"redtext\">$erTelephone2</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Email Pdf Invoices:</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"pdfinvs\""; if ($pdfinvs) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Accounts email(s) (1,2,3...):</b></td>\n";
					print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"300\" value=\"$aemails\" />";
					if ($eraemails) { print "<br><span class=\"redtext\">$eraemails</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Login At:</b></td>\n";
					print "	<td><select name=\"site\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";

					while ($srow = $sitesresult->fetch_assoc()) {
						$title     = $srow['title'];
						$ipaddress = $srow['ipaddress'];
						print "		<option value=\"$ipaddress\"";
						if ($ipaddress == $site) {
							print " SELECTED";
						}
						print ">$title</option>\n";
					}
					print "	</select>";
					if ($ersite) { print "<br><span class=\"redtext\">$ersite</span>"; }
					print "</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Billing Period:</b></td>\n";
					print "	<td><select name=\"billing\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					print "		<option value=\"weekly\""; if ($billing == "weekly") { print " SELECTED"; } print ">weekly</option>\n";
					print "		<option value=\"monthly\""; if ($billing == "monthly") { print " SELECTED"; } print ">monthly</option>\n";
					print "		<option value=\"quarterly\""; if ($billing == "quarterly") { print " SELECTED"; } print ">quarterly</option>\n";
					print "		<option value=\"6 monthly\""; if ($billing == "6 monthly") { print " SELECTED"; } print ">6 monthly</option>\n";
					print "		<option value=\"annually\""; if ($billing == "annually") { print " SELECTED"; } print ">annually</option>\n";
					print "		<option value=\"cheque\""; if ($billing == "cheque") { print " SELECTED"; } print ">cheque</option>\n";
					print "	</select>";
					if ($erBilling) { print "<br><span class=\"redtext\">$erBilling</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><b>H&amp;S Personnel</b><br>Optional. The client will normally maintain this.<br>Only applicable where client has office and kitchen units.</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Fire Marshall</td>\n";
					print "	<td><input name=\"FireMarshall\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"200\" value=\"$FireMarshall\" />";
					if ($erFireMarshall) { print "<br><span class=\"redtext\">$erFireMarshall</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>First Aider</td>\n";
					print "	<td><input name=\"FirstAider\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"200\" value=\"$FirstAider\" />";
					if ($erFirstAider) { print "<br><span class=\"redtext\">$erFirstAider</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><b>Bank Details</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Bank:</b></td>\n";
					print "	<td><select name=\"bank\" class=\"element\">\n";
					print "		<option></option>\n";
					print "		<option value=\"Yes\""; if ($bank == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
					print "		<option value=\"No\""; if ($bank == "No") { print " SELECTED"; } print ">No</option>\n";
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><b>If Bank yes, fill in the bank details</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Bank Acc Name</td>\n";
					print "	<td><input name=\"bnkAccName\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"50\" value=\"$bnkAccName\" />";
					if ($erBnkAccName) { print "<br><span class=\"redtext\">$erBnkAccName</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Bank Sort Code</td>\n";
					print "	<td><input name=\"bnkSort\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"6\" value=\"$bnkSort\" />";
					if ($erBnkSort) { print "<br><span class=\"redtext\">$erBnkSort</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Bank Acc Number</td>\n";
					print "	<td><input name=\"bnkAccNum\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"8\" value=\"$bnkAccNum\" />";
					if ($erBnkAccNum) { print "<br><span class=\"redtext\">$erBnkAccNum</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><b>Cold Room Contact Details</b><br>Normally the client will add and update this if they have cold rooms.</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Cold Room Contact Name</td>\n";
					print "	<td><input name=\"ColdRoomContact\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$ColdRoomContact\" />";
					if ($erColdRoomContact) { print "<br><span class=\"redtext\">$erColdRoomContact</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Cold Room Contact Phone</td>\n";
					print "	<td><input name=\"ColdRoomPhone\" type=\"text\" class=\"element\" maxlength=\"40\" value=\"$ColdRoomPhone\" />";
					if ($erColdRoomPhone) { print "<br><span class=\"redtext\">$erColdRoomPhone</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Cold Room Contact Email</td>\n";
					print "	<td><input name=\"ColdRoomEmail\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$ColdRoomEmail\" />";
					if ($erColdRoomEmail) { print "<br><span class=\"redtext\">$erColdRoomEmail</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Notes:<br />";
					print "<textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
				}
			}
			if ($id) {
				print "<p align=\"center\"><a href=\"sl-customer-view.php?id=$id&amp;$qString\">View Customer</a> | <a href=\"sl-ledgers-list.php?$qString\">Return</a></p>\n";
			} else {
				print "<p align=\"center\"><a href=\"sl-ledgers-list.php?$qString\">Return</a></p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
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

function generateRandomString($length = 16) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

?>
