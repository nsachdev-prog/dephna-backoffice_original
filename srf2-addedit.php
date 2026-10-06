<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "SRF";
$subpage = "Edit";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">Service Request Form</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];

			if ($_REQUEST['custref']) {
				
				if ($_REQUEST['id']) {
					$id  = $_REQUEST['id']; 
					$heading = "Update SRF";
					include('srf2-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
				} else {
					$heading = "New SRF";
				}

				print "<p align=\"center\" class=\"larger\">$heading</p>\n";
				
				// get customer
				$sql = "SELECT compname, email, aemails, username
						FROM users
						WHERE custref = '$custref'
				";
				$result = $dbcnx->query($sql);
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
                    $username = $row['username'];
					$aemails  = $row['aemails'];
					if ($aemails) {
						$aemails  = explode(",",$aemails);
                        $defemail = $aemails[0];
                    } else {
                        $defemail = $row['email'];
                    }
				}
				
				print "<p align=\"center\" class=\"larger\">$compname</p>\n";
			
				if ($_POST['posted']) {

                    $txndate = strip_tags($_POST['txndate']);
                    $details = strip_tags($_POST['details']);
                    $authby  = strip_tags($_POST['authby']);
                    $email   = strip_tags($_POST['email']);
                    $notes   = strip_tags($_POST['notes']);
                    $adnotes = strip_tags($_POST['adnotes']);
                    $unit    = strip_tags($_POST['unit']);
                    $charge  = strip_tags($_POST['charge']);
					$vat     = strip_tags($_POST['vat']);
					$grty    = strip_tags($_POST['grty']);

					if ($grty == "Select") { $ergrty = "Select Group/Type"; $e++; }
					else { list($group,$type) = explode("--",$grty); }

                    if ($unit) {
                        list($siteid,$unitid,$unitname,$unittype) = explode("--",$unit);
                        $unittype = str_replace(" V","",$unittype);
                    } else {
                        $erunit = "No unit selected"; $e++;
                    }

					if ($txndate) {
						if (!validateDate($txndate)) {
							$ertxndate = "Invalid Date"; $e++;
						}
					}

					if ($email) {	
						$email = trim($email);
						if (!validate_email($email)) { $eremail = "Invalid email address"; $e++; }
					} else {
						$eremail = "Enter an email address"; $e++;
                    }
                    
                    if (!$details) { $erdetails = "Enter SRF details"; $e++; }
                    if (!$authby)  { $erauthby  = "Enter authorised by name"; $e++; }
                    if ($charge)  {
                        if (!is_numeric($charge)) {
                            $ercharge = "Charge must br numeric"; $e++;
                        }
                    } else {
                        $ercharge = "Enter charge"; $e++;
                    }
					
					if (!$e) {
						
                        $notes   = addslashes($notes);
                        $adnotes = addslashes($adnotes);
						$details = addslashes($details);
						$details = str_replace(",","&comma;",$details);
						$now = time();
			
						if ($id) {
							$sql = "UPDATE `srf2` SET
                                `txndate`='$txndate',
                                `authby`='$authby',
                                `email`='$email',
                                `siteid`='$siteid',
                                `unitid`='$unitid',
                                `unitname`='$unitname',
								`details`='$details',
								`notes`='$notes',
                                `adnotes`='$adnotes',
                                `charge`='$charge',
                                `vat`='$vat',
								`group`='$group',
								`type`='$type',
                                `lastupd`='$now',
								`updby`='{$_SESSION['username']}' 
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Details Updated<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref','$page','$now')");
							} else {
								$resultMessage .= "Could NOT Update Details<br>" . $dbcnx->error . "<br>";
							}
						} else {
							$sql = "INSERT INTO srf2 VALUES (
								null,
                                '$txndate',
								'$custref',
                                '$authby',
								'$email',
								'$siteid',
                                '$unitid',
                                '$unitname',
								'$details',
								'$notes',
                                '$adnotes',
                                '$charge',
                                '$vat',
								'$group',
								'$type',
								'',
								'',
								'',
								'',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "New SRF Created for DP$custref<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$custref','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
							}
						}
					}
				}

				elseif ($id) {
					$sql = "SELECT * FROM srf2 WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
                        $row = $result->fetch_assoc();
                        $txndate = $row['txndate'];
                        $authby  = $row['authby'];
						$email   = $row['email'];
						$unitid  = $row['unitid'];
						$details = $row['details'];
                        $notes   = $row['notes'];
                        $adnotes = $row['adnotes'];
                        $charge  = $row['charge'];
						$vat     = $row['vat'];
						$group   = $row['group'];
						$type    = $row['type'];
						$agreementID = $row['agreementID'];
						$lastupd = $row['lastupd'];
						$confirmed = $row['confirmed'];
						$updby   = $row['updby'];
						$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
						$grty  = "$group--$type";
						$details = str_replace("&comma;",",",$details);
					} else {
						$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
					}
				} else {
                    $vat = "on";
                }

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
						if (!$agreementID AND !$confirmed) {
							
							print "<form name=\"addform\" method=\"post\" action=\"srf2-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Date</b></td>\n";
							print "	<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element\" />\n";
							if ($ertxndate) { print "<br /><span class=\"redtext\">$ertxndate</span>"; }
							print "</td>\n";
							print "</tr>\n";
                        
							print "<tr>\n";
							print "	<td align=\"right\"><b>Unit</b></td>\n";
							print "	<td><select name=\"unit\" class=\"element\">\n";
							// get units
							$sql = "SELECT b.unitid, u.title AS unitname, b.unittype, b.siteid AS usiteid
									FROM bookings b
									LEFT JOIN units u ON u.ID = b.unitid
									WHERE b.custid = '$custref'
							";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								while ($row = $result->fetch_assoc()) {
									$thisunitid   = $row['unitid'];
									$thissiteid   = $row['usiteid'];
									$thisunittype = $row['unittype'];
									$thisunitname = $row['unitname'];
									print "		<option value=\"$thissiteid--$thisunitid--$thisunitname--$thisunittype\"";
									if ($thisunitid == $unitid) { print " SELECTED"; }
									print ">$thisunitname - $thisunittype</option>\n";
								}
							}
							print "	</select>";
							if ($erunits) { print "<br />$erunit"; }
							print "</td>\n";
							print "</tr>\n";
							
							print "<tr>\n";
							print "	<td align=\"right\"><b>Group-Type</b></td>\n";
							print "	<td><select name=\"grty\" class=\"element\">\n";
							print "		<option>Select</option>\n";
							$sql = "SELECT t.ID AS typeid, g.ID AS groupid, t.title AS typetitle, g.title AS grouptitle
								FROM mnt_types AS t
								LEFT JOIN mnt_groups AS g ON t.gid = g.ID
								ORDER BY g.title, t.title
							";
							if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
								while ($row = $result->fetch_assoc()) {
									$thisgid = $row['groupid'];
									$thistid = $row['typeid'];
									$gtitle  = $row['grouptitle'];
									$ttitle  = $row['typetitle'];
									$thisgrty = "$thisgid--$thistid";
									print "		<option value=\"$thisgrty\""; if ($thisgrty == $grty) { print " SELECTED"; } print ">$gtitle - $ttitle</option>\n";
								}
							}
							print "		</select>";
							if ($ergrty) { print "<br /><span class=\"redtext\">$ergrty</span>"; }
							print "</td>\n";
							print "</tr>\n";
							
							print "<tr>\n";
							print "	<td align=\"right\"><b>Details</b></td>\n";
							print "	<td><input name=\"details\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$details\" />";
							if ($erdetails) { print "<br /><span class=\"redtext\">$erdetails</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Charge (Before Vat)</b></td>\n";
							print "	<td><input name=\"charge\" type=\"text\" class=\"element\" maxlength=\"15\" value=\"$charge\" />";
							if ($ercharge) { print "<br /><span class=\"redtext\">$ercharge</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Apply Vat</b></td>\n";
							print "	<td><input type=\"checkbox\" name=\"vat\""; if ($vat) { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";

							if (!$authby) { $authby = $username; }
							
							print "<tr>\n";
							print "	<td align=\"right\"><b>Authorised By</b></td>\n";
							print "	<td><input name=\"authby\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$authby\" />";
							if ($erauthby) { print "<br /><span class=\"redtext\">$erauthby</span>"; }
							print "</td>\n";
							print "</tr>\n";
                        
							if (!$email) { $email = $defemail; }

							print "<tr>\n";
							print "	<td align=\"right\"><b>Signatory Email</b></td>\n";
							print "	<td><input name=\"email\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$email\" />";
							if ($eremail) { print "<br /><span class=\"redtext\">$eremail</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Customer Notes</b></td>";
							print "	<td><textarea name=\"adnotes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$adnotes</textarea></td>\n";
							print "</tr>\n";
						
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Our Notes</b></td>";
							print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
							print "</tr>\n";

							print "</table>\n";
							print "</form>\n";

							if ($id) {
								print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
							}

							// jquery date picker
							print "<script type=\"text/javascript\">\n";
							print "	$( document ).ready(function() {\n";
							print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
							print "	});\n";
							print "</script>\n";
						
						} else {
							if ($confirmed) {
								print "<p align=\"center\"><b>Cannot Update</b><br />Agreement Already Confirmed.</p>\n";
							} else {
								print "<p align=\"center\"><b>Cannot Update</b><br />Agreement Already Sent<br />To update you need to cancel the Adobe Sign agreement first.</p>\n";
							}
						}

					}
				}

			} else {
			
				print "<p align=\"center\" class=\"larger\">Select Customer</p>\n";
				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				print "<form name=\"form1\" method=\"post\" action=\"srf2-addedit.php\">\n";
				$sql = "SELECT custref, compname FROM users ORDER BY compname";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer</b>:</td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$custref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$custref\">$compname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "<tr><td colspan=\"2\" align=\"center\"><b>OR</b></td></tr>\n";
				print "<form name=\"form2\" method=\"post\" action=\"srf2-addedit.php\">\n";
				$sql = "SELECT custref, compname FROM users ORDER BY custref";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Custref</b>:</td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print " <option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$custref  = $row['custref'];
						$compname = $row['compname'];
						print "	<option value=\"$custref\">DP$custref - $compname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "</table>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"srf2-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
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
