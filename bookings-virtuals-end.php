<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$bkid   = $_REQUEST['bkid'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">Bookings Virtual - Contract End</p>\n";
			
			if ($bkid) {
				# get the variables we need out of the tables	
				# get booking details
				$sql = "SELECT custref, siteid, pkname, dateto FROM vbookings WHERE ID = '$bkid'";
				if ($bkresult = $dbcnx->query($sql)) {
					if ($bkresult->num_rows) {
						$bkrow = $bkresult->fetch_assoc();
						$custref = $bkrow['custref'];
						$siteid  = $bkrow['siteid'];
						$pkname  = $bkrow['pkname'];
						$cedate  = $bkrow['dateto'];
						if ($cedate > 0) { $cedate = date("Y-m-d",$cedate); } else { $cedate = ""; }
						
						# get site name
						$sql = "SELECT title FROM sites WHERE ID='$siteid'";
						if ($siteresult = $dbcnx->query($sql)) {
							if ($siteresult->num_rows) {
								$siterow  = $siteresult->fetch_assoc();
								$sitename = $siterow['title'];
							}
						}
				
						# get customer details
						$sql = "SELECT username, compname, email, aemails, addr1, addr2, addr3, addr4, telephone FROM users WHERE custref = '$custref'";
						if ($custresult = $dbcnx->query($sql)) {
							if ($custresult->num_rows) {
								$custrow = $custresult->fetch_assoc();
								$compname  = $custrow['compname'];
								$custname  = $custrow['username'];
								$email     = $custrow['email'];
								$aemails   = $custrow['aemails'];
								$addr1     = $custrow['addr1'];
								$addr2     = $custrow['addr2'];
								$addr3     = $custrow['addr3'];
								$addr4     = $custrow['addr4'];
								$telephone = $custrow['telephone'];
								if (!$aemails) { $aemails = $email; }
							} else {
								$resultMessage = "<b>Could not get customer details from users</b>"; $e++;
							}
						} else {
							$resultMessage = "<b>Could not get anything from users database</b><br>" . $dbcnx->error; $e++;
						}
						
					} else {
						$resultMessage = "<b>Could not get custid from bookings database</b>"; $e++;
					}
				} else {
					$resultMessage = "<b>Could not get anything from bookings database</b><br>" . $dbcnx->error; $e++;
				}
				
				if (!$e) {
					# successfully got variables
					
					# display booking and customer header
					print "<p align=\"center\" class=\"heading\">$sitename - $pkname - $compname</p>\n";
					
					if ($_POST['posted']) { # if form id posted
					
						# error check contract end date
						$cedate = $_POST['cedate'];
						$unixcedate = strtotime($cedate);
						if ($unixcedate < time()) {
							$ercedate = "Cannot be in the past"; $e++;
						}
						
						# error check email template if being used
						$mailit = $_POST['mailit'];
						if ($mailit) {
							$subject  = strip_tags($_POST['subject']);
							$body     = strip_tags($_POST['body']);
							if (!$subject) { $ersubject = "No Subject"; $e++; }
							if (!$body)    { $erbody    = "No Message"; $e++; }
						}
						
						if (!$e) {
							$body = str_replace("[[enddate]]",$cedate,$body);
							# if no errors post to database
							$sql = "UPDATE vbookings SET dateto='$unixcedate' WHERE ID='$bkid'";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage = "Record successfuly updated<br>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $pkname DP$custref Contract End Date','$page','$now')");
								if ($mailit) {
									# if mail to customer selected
									$from = "accounts@dephna.com";
									$mime_boundary = md5(uniqid(time()));
									$headers = "From: $from\r\n";
									$headers .= "Reply-To: $from\r\n";
									$headers .= "Bcc: accounts@dephna.com\r\n";
									$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
									$headers .= "MIME-Version: 1.0\r\n";
									$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
								    $message = "--".$mime_boundary."\r\n";
								    $message .= "Content-type:text/html; charset=iso-8859-1\r\n";
								    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
								    include("email-header.php");
								    $message .= nl2br($body);
								    include("email-footer.php");
								    $message .= "--".$mime_boundary."--";
								    # $aemails = "david@davidsherwood.co.uk,mail@matthewblackmore.com";
								    $emails = explode(",",$aemails);
									foreach ($emails AS $to) {
								    	if (mail($to, $subject, $message, $headers)) {
											$resultMessage .= "Email Successfully Sent to $to<br />";
										} else {
											$resultMessage .= "Email FAILED to send to $to<br />";
										}
									}
								} else {
									$resultMessage .= "Email customer not selected";
								}
							} else {
								$resultMessage = "<b>Could not update bookings database</b><br>" . $dbcnx->error; $e++;
							}
						}
					} else {
						# get email template
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'ContractEndVirtuals'";
						if ($tresult = $dbcnx->query($sql)) {
							if ($tresult->num_rows) {
								$trow = $tresult->fetch_assoc();
								$subject = $trow['subject'];
								$body    = $trow['html'];
								# replace fields in template with data
								$search  = array("[[custname]]","[[staffname]]","[[type]]");
								$replace = array($custname,$_SESSION['username'],$pkname);
								$body    = str_replace($search,$replace,$body);
							} else {
								$resultMessage = "<b>Could not get email template ContractEndVirtuals.</b>\n";
							}
						} else {
							$resultMessage = "<b>Could not get anything from templates.</b>\n";
						}
						$mailit = "on";
					}
					
					if ((!$_POST['posted']) OR ($e)) {
						# print form
						print "<form name=\"invform\" method=\"post\" action=\"bookings-virtuals-end.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Contract End Date:</b></td>\n";
						print "	<td><input type=\"text\" id=\"datepicker\" name=\"cedate\" value=\"$cedate\" class=\"element\" />";
						if ($ercedate) { print "&nbsp;<span class=\"redtext\">$ercedate</span>"; }
						print "</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Email Customer:</b></td>\n";
						print "	<td><input type=\"checkbox\" name=\"mailit\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Subject:</b></td>\n";
						print "	<td><input type=\"text\" name=\"subject\" value=\"$subject\" class=\"element\" />";
						if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
						print "</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\">Do not change [[enddate]] below.<br>This will be replaced with your selection above.</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td colspan=\"2\"><b>Message:</b>"; if ($erbody) { print "&nbsp;<span class=\"redtext\">$erbody</span>"; } print "<br />";
						print "<textarea name=\"body\" wrap=\"virtual\" rows=\"15\" class=\"element_box_large\">$body</textarea></td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"Submit\" style=\"width:100px;\" /></td>\n";
						print "</tr>\n";
						
						print "</table>\n";
						print "</form>\n";
						
						# print jquery date picker
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
						print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
						print "	});\n";
						print "</script>\n";
					}
				}
			} else {
				$resultMessage = "<b>Page called without bkid</b>";
			}			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "<p align=\"center\"><a href=\"bookings-virtuals-view.php\">Return</a></p>\n";
	print "<p align=\"center\">&nbsp;</p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include ('footer.php');
?>
