<?php
$page = "Maintenance";
$subpage = "Tickets";
include('settings.php');
include('session.php');
include('header.php');
$mnt_image_path = "../../dis2_db/img/mnt";
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	$tid = $_REQUEST['tid'];
	$site = $_REQUEST['site'];
	if ($site) { list($siteid,$sitename) = explode("-",$site); }

	# print heading
	if ($tid) { $heading = "Update Ticket"; $ticket = "M" . sprintf("%06s",$tid); } else { $heading = "Add Ticket"; }
	print "<p align=\"center\" class=\"heading\">MAINTENANCE</p>\n";
	print "<p align=\"center\" class=\"larger\">$heading</p>\n";

	if ($accessRights[$page]) {

		# print submenu
		include('maintenance-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($site) OR ($tid)) {

				$statuses = array(1=>"Low",2=>"Medium",3=>"High",6=>"Completed",7=>"Closed");

				if ($_POST['close']) {
					$now = time();
					$sql = "UPDATE `mnt_tickets` SET `status`='7', `closed`='$now' WHERE ID='$tid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$ticket','$subpage','$now')");
						$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','<b>CLOSED</b>')");
					}
				}

				elseif ($_POST['posted']) {

					$unit = $_POST['unit'];
					$area = $_POST['area'];
					if (($unit == "Select") && (!$area)) { $erunit = "Select Unit or Area"; $e++; }
					$customer = $_POST['customer'];
					if ($customer == "Select") { $ercustomer = "Select Customer"; $e++; }
					else { list($custref,$custname) = explode("-",$customer); }
					$grty = $_POST['grty'];
					if ($grty == "Select") { $ergrty = "Select Group/Type"; $e++; }
					else { list($group,$type) = explode("-",$grty); }
					$notetype    = $_POST['notetype'];
					if ($notetype == "MEmail") {
						$emailBody = $_POST['emailBody'];
						if (!$emailBody) {
							$eremailBody = "You didn't type a message"; $e++;
							if (!$temails) {
								$ertemails = "Please give an email address"; $e++;
							}
						}
					} else {
						$emailBody = "";
					}
					$notedetails = $_POST['notedetails'];
					$status = $_POST['status'];
					if ($status == "Select") { $erstatus = "Select Status"; $e++; }
					if ($_POST['allocated'] != "Select") { $allocated = $_POST['allocated']; }

					$temails = $_POST['temails'];
					if (!$temails) {
						$ertemails = "Please give an email address"; $e++;
					} else {
						$emails = explode(",",$temails);
						foreach ($emails AS $email) {
							if (!validate_email($email)) { $ertemails = "Not a valid email address(es)"; $e++; }
						}
					}

					if (!$e) {

						$now = time();
						if ($tid) {
							// it's an update
							// get current fields and compare
							$sql = "SELECT * FROM mnt_tickets WHERE ID = '$tid'";
							$tresult = $dbcnx->query($sql);
							$trow = $tresult->fetch_assoc();
							$old_status     =  $trow['status'];
							$old_allocated  =  $trow['allocated'];
							$old_details    =  $trow['details'];
							if (!$old_allocated) { $old_allocated = "Nobody"; }
							$notedetails = addslashes($notedetails);
							$details = $old_details;

							// get the allocated staff email
							if ($allocated) {
								$sql = "SELECT `email` FROM `staff` WHERE `username`='$allocated'";
								$sresult = $dbcnx->query($sql);
								if (($sresult) && ($sresult->num_rows)) {
									$srow = $sresult->fetch_assoc();
									$bcc  = $srow['email'];
								}
							}

							// set up emails
							$mime_boundary = md5(uniqid(time()));
							$from = "support@dephna.com";
							$returnaddress = "-f" . $from;
							$subject = "Dephna Support Ticket:$ticket";
							$headers = "From: $from\r\n";
							$headers .= "Reply-To: $from\r\n";
							if ($bcc) { $headers .= "Bcc: $bcc\r\n"; }
							$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
							$headers .= "MIME-Version: 1.0\r\n";
							$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
							$message = "--".$mime_boundary."\r\n";
							$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
							$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
							$message .= "\r\n\r\n";

							if ($notetype == "details") {
								$details .= "<br /><b>".date("d/m/y H:i")." {$_SESSION['username']}:</b><br/>".$notedetails;
								$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','Changed <b>DETAILS</b>')");
							}
							elseif ($notetype == "note") {
								if ($notedetails != "") {
									$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','<b>NOTE</b>: $notedetails')");
								}
							}
							elseif ($notetype == "chargeable") {
								$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','<b>CHARGEABLE</b>: $notedetails')");
							}
							elseif ($notetype == "MPhone") {
								$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','<b>PHONED</b>: $notedetails')");
							}
							elseif ($notetype == "MEmail") {

								$thismessage = "Ticket number: $ticket<br />Location: $unit $sitename<br />Customer: $custname<br /><br />\r\n";
								$thismessage .= nl2br($emailBody);

								include("email-header.php");
								$message .= $thismessage;
								include("email-footer.php");

								$message .= "--".$mime_boundary."--";
								$emails = explode(",",$temails);
								foreach ($emails AS $to) {
									mail($to, $subject, $message, $headers, $returnaddress);
									$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','<b>EMAILED</b>: $to<br />$notedetails<br /><b>MESSAGE</b>:<br />$thismessage')");
								}
							}
							if (($allocated) && ($allocated != $old_allocated)) {
								$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','Changed <b>ALLOCATED</b> From $old_allocated to $allocated')");
							}
							if (($status) && ($status != $old_status)) {
								if ($status == 6) {

									$thismessage = "Ticket number: $ticket<br />Location: $unit $sitename<br />Customer: $custname<br /><br />\r\n";
									$thismessage .= "This ticket has been marked as completed and will now be closed.";

									include("email-header.php");
									$message .= $thismessage;
									include("email-footer.php");
									$message .= "--".$mime_boundary."--";

									$emails = explode(",",$temails);
									foreach ($emails AS $to) {
										mail($to, $subject, $message, $headers, $returnaddress);
										$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','<b>EMAILED</b>: $to<br />$notedetails<br /><b>MESSAGE</b>:<br />$thismessage')");
									}
								}
								$dbcnx->query("INSERT INTO mnt_notes VALUES (null,'$tid','$now','{$_SESSION['username']}','Changed <b>STATUS</b> from $statuses[$old_status] to $statuses[$status]')");
							}
							$details = addslashes($details);
							$sql = "UPDATE mnt_tickets SET
							status		= '$status',
							allocated	= '$allocated',
							details		= '$details',
							temails     = '$temails'
							WHERE ID='$tid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$ticket','$subpage','$now')");
							}
						} else {
							// it's a new ticket
							$notedetails = addslashes($notedetails);
							$area = addslashes($area);
							$sql = "INSERT INTO mnt_tickets VALUES (
							null,
							'$status',
							'$now',
							'{$_SESSION['username']}',
							'$siteid',
							'$sitename',
							'$unit',
							'$area',
							'$group',
							'$type',
							'$custref',
							'$custname',
							'$temails',
							'$allocated',
							'',
							'$notedetails'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$tid = $dbcnx->insert_id;
								$ticket = "M" . sprintf("%06s",$tid);
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$ticket','$subpage','$now')");
							}

							# update tickets to high status if medium + low in this group > $tk_stat_threshold.
							$sql = "SELECT * FROM mnt_tickets WHERE `group`='$group' AND `status`<'3'";
							$stresult = $dbcnx->query($sql);
							if (($stresult) AND ($stresult->num_rows > $tk_stat_threshold)) {
								$sql = "UPDATE mnt_tickets SET status = '3' WHERE `group`='$group' AND `status`<'3'";
								$stresult = $dbcnx->query($sql);
							}
						}
						$notedetails = "";
					}
				}

				if ($_REQUEST['images']) {


					if ($_FILES['image']['tmp_name']) {
						$allowed = array("jpg","jpeg","gif","png");
						$ext = strtolower(substr($_FILES['image']['name'],strrpos($_FILES['image']['name'],'.')+1));
						if (in_array($ext,$allowed) === FALSE) { $erimage = "The file is not an image"; $e++; }
						elseif (!getimagesize($_FILES['image']['tmp_name'])) { $erimage = "The file is not an image"; $e++; }
						if (!$e) {
							$filename = date(YmdHis) . "." . $ext;
							if (!copy($_FILES['image']['tmp_name'], "$mnt_image_path/$filename")) {
								$resultMessage = "Uploaded image could not be saved<br />\n";
							} else {
								list($width, $height, $type, $attr) = getimagesize($mnt_image_path."/".$filename);
								if (($width > 800) OR ($height > 800)) {
									if ($width > $height) {
										$scale = $width/800;
										$new_width = 800;
										$new_height = $height/$scale;
									} else {
										$scale = $height/800;
										$new_height = 800;
										$new_width = $width/$scale;
									}
									$temp_im = imageCreateFromJPEG($mnt_image_path."/".$filename);
									$new_im = imagecreatetruecolor($new_width,$new_height);
									imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
									imagejpeg($new_im, $mnt_image_path."/".$filename, 60);
									imageDestroy($temp_im);
									imageDestroy($new_im);
								}

								$now = time();
								$sql = "INSERT INTO mnt_images VALUES (null,'$tid','$filename','$now','{$_SESSION['username']}')";
								$iresult = $dbcnx->query($sql);
								if ($iresult) {
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Image on Ticket $ticket','$subpage','$now')");
								}
							}
						}
					}

					elseif ($_GET['delete']) {
						$iid = $_GET['iid'];
						$sql = "SELECT image FROM mnt_images WHERE ID='$iid'";
						$iresult = $dbcnx->query($sql);
						if (($iresult) && ($iresult->num_rows)) {
							$irow = $iresult->fetch_assoc();
							$image = $irow['image'];
							$filename = "$mnt_image_path/$image";
							if (!unlink($filename)) {
								$resultMessage = "Image could not be deleted\n";
							} else {
								$sql = "DELETE FROM mnt_images WHERE ID='$iid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$now = time();
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Image on Ticket $ticket','$subpage','$now')");
								}
							}
						}
					}
				}

				if ($tid) {

					$sql = "SELECT * FROM mnt_tickets WHERE ID='$tid'";
					$tresult = $dbcnx->query($sql);
					$trow = $tresult->fetch_assoc();
					$status     =  $trow['status'];
					$crdate     =  $trow['crdate'];
					$crby       =  $trow['crby'];
					$siteid     =  $trow['siteid'];
					$sitename   =  $trow['sitename'];
					$unit       =  $trow['unit'];
					$area       =  $trow['area'];
					$group      =  $trow['group'];
					$type       =  $trow['type'];
					$custref    =  $trow['custref'];
					$custname   =  $trow['custname'];
					$temails    =  $trow['temails'];
					$allocated  =  $trow['allocated'];
					$details    =  $trow['details'];
					$details    = stripslashes($details);

					$grty  = "$group-$type";

					$gres  = $dbcnx->query("SELECT title FROM mnt_groups WHERE ID='$group'");
					$grow  = $gres->fetch_assoc();
					$group = $grow['title'];

					$tres  = $dbcnx->query("SELECT title FROM mnt_types WHERE ID='$type'");
					$trrow = $tres->fetch_assoc();
					$type  = $trrow['title'];

					$crdate   = date("d/m/y H:i",$crdate);
					$customer = "$custref-$custname";
					$site     = "$siteid-$sitename";
					if ($images) { $images = explode("|",$images); }

					print "<table align=\"center\" cellpadding=\"0px\" cellspacing=\"20px\">\n";
					print "	<tr>\n";
					print "		<td valign=\"top\">\n"; # left pane
					$submittext = "Submit";
				} else {
					$submittext = "Create Ticket";
				}

				print "<form name=\"form1\" method=\"post\" action=\"maintenance-tickets-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
				print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";

				print "<table align=\"center\" cellpadding=\"2px\" cellspacing=\"10px\">\n";

				if ($tid) {
					print "	<tr>\n";
					print "		<td align=\"right\" class=\"larger\">Ticket:</td>\n";
					print "		<td colspan=\"3\" class=\"larger\">$ticket</td>\n";
					print "	</tr>\n";
				}

				print "	<tr>\n";
				print "		<td align=\"right\">Site:</td>\n";
				print "		<td colspan=\"3\">$sitename</td>\n";
				print "	</tr>\n";

				if ($tid) {
					print "<input type=\"hidden\" name=\"unit\" value=\"$unit\" />\n";
					print "<input type=\"hidden\" name=\"area\" value=\"$area\" />\n";
					print "	<tr>\n";
					print "		<td align=\"right\">Unit/Area:</td>\n";
					print "		<td colspan=\"3\">";
					if ($unit != "Select") { print "$unit "; }
					if ($area) { print $area; }
					print "</td>\n";
					print "	</tr>\n";
				} else {
					$uresult = $dbcnx->query("
						SELECT title FROM units
						WHERE siteid='$siteid'
						ORDER BY title
					");
					if (($uresult) && ($uresult->num_rows)) {
						print "<tr>\n";
						print "	<td align=\"right\">Unit:</td>\n";
						print "	<td><select name=\"unit\" class=\"element_smaller\">\n";
						print "		<option>Select</option>\n";
						while ($urow = $uresult->fetch_assoc()) {
							$thisunit = $urow['title'];
							print "		<option value=\"$thisunit\""; if ($thisunit == $unit) { print " SELECTED"; } print ">$thisunit</option>\n";
						}
						print "	</select>";
						
						print "&nbsp;&nbsp;And/Or Area: <input name=\"area\" class=\"element_small\" maxlength=\"30\" type=\"text\" value=\"$area\" />\n";
						print "	</td>\n";
						print "</tr>\n";
						if ($erunit) {
							print "	<tr>\n";
							print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
							print "		<td colspan=\"3\"><span class=\"redtext\">$erunit</span></td>\n";
							print "	</tr>\n";
						}
					}
				}

				if ($tid) {
					print "	<tr>\n";
					print "		<td align=\"right\">Created By:</td>\n";
					print "		<td colspan=\"3\">$crby</td>\n";
					print "	</tr>\n";

					print "	<tr>\n";
					print "		<td align=\"right\">Date:</td>\n";
					print "		<td colspan=\"3\">$crdate</td>\n";
					print "	</tr>\n";

					print "<input type=\"hidden\" name=\"customer\" value=\"$customer\" />\n";
					print "	<tr>\n";
					print "		<td align=\"right\">Customer:</td>\n";
					print "		<td colspan=\"3\">$custname</td>\n";
					print "	</tr>\n";
				} else {
					$sql = "SELECT custref, compname FROM users WHERE level='2' ORDER BY compname";
					$custsresult = $dbcnx->query($sql);
					print "<tr>\n";
					print "	<td align=\"right\">Customer:</td>\n";
					print "	<td colspan=\"3\"><select name=\"customer\" class=\"element\">\n";
					print "		<option>Select</option>\n";
					while ($crow = $custsresult->fetch_assoc()) {
						$thiscustref  = $crow['custref'];
						$thiscustname = $crow['compname'];
						if ($thiscustname != "(Customer Not Yet Selected)") {
							$thiscustomer = "$thiscustref-$thiscustname";
							print "		<option value=\"$thiscustomer\""; if ($thiscustomer == $customer) { print " SELECTED"; } print ">$thiscustname</option>\n";
						}
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";
					if ($ercustomer) {
						print "	<tr>\n";
						print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
						print "		<td colspan=\"3\"><span class=\"redtext\">$ercustomer</span></td>\n";
						print "	</tr>\n";
					}

				}

				print "<tr>\n";
				print "	<td align=\"right\">Email(s) (1,2,3..):</td>\n";
				print "	<td><input type=\"text\" name=\"temails\" class=\"element\" maxlength=\"150\" value=\"$temails\" />";
				print "</tr>\n";
				if ($ertemails) {
					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
					print "		<td colspan=\"3\"><span class=\"redtext\">$ertemails</span></td>\n";
					print "	</tr>\n";
				}

				$result = $dbcnx->query("
					SELECT t.ID AS typeid, g.ID AS groupid, t.title AS typetitle, g.title AS grouptitle FROM mnt_types AS t
					LEFT JOIN mnt_groups AS g ON t.gid = g.ID
					ORDER BY g.title, t.title
				");
				if (($result) && ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\">Group-Type:</td>\n";
					print "	<td colspan=\"3\"><select name=\"grty\" class=\"element\">\n";
					print "		<option>Select</option>\n";
					while ($row = $result->fetch_assoc()) {
						$thisgid = $row['groupid'];
						$thistid = $row['typeid'];
						$gtitle  = $row['grouptitle'];
						$ttitle  = $row['typetitle'];
						$thisgrty = "$thisgid-$thistid";
						print "		<option value=\"$thisgrty\""; if ($thisgrty == $grty) { print " SELECTED"; } print ">$gtitle - $ttitle</option>\n";
					}
					print "		</select>";
					print "		</td>\n";
					print "</tr>\n";
				}
				if ($ergrty) {
					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
					print "		<td colspan=\"3\"><span class=\"redtext\">$ergrty</span></td>\n";
					print "	</tr>\n";
				}

				if ($tid) {
					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">Details:</td>\n";
					print "		<td colspan=\"3\">$details</td>\n";
					print "	</tr>\n";
				}

				print "	<tr>\n";
				print "		<td align=\"right\">Status:</td>\n";
				if ($status < 7) {
					print "		<td><select name=\"status\" class=\"element_small\">\n";
					print "			<option>Select</option>\n";
					print "			<option value=\"3\""; if ($status == 3) { print " SELECTED"; } print ">High</option>\n";
					print "			<option value=\"2\""; if ($status == 2) { print " SELECTED"; } print ">Medium</option>\n";
					print "			<option value=\"1\""; if ($status == 1) { print " SELECTED"; } print ">Low</option>\n";
					if ($tid) { print "			<option value=\"6\""; if ($status == 6) { print " SELECTED"; } print ">Completed</option>\n"; }
					print "			</select>\n";
					print "		</td>\n";
					print "		<td><div class=\"redtext\">".strtoupper($statuses[$status])."</div></td>\n";
				} else {
					print "	<td>CLOSED</td>\n";
				}
				print "</tr>\n";
				if ($erstatus) {
					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
					print "		<td colspan=\"3\"><span class=\"redtext\">$erstatus</span></td>\n";
					print "	</tr>\n";
				}

				if ($accessRights[$page] > 1) {
					print "<tr>\n";
					print "	<td align=\"right\">Allocated:</td>\n";
					if ($status < 7) {
						print "	<td colspan=\"3\"><select name=\"allocated\" class=\"element\">\n";
						print "		<option>Select</option>\n";
						$sql = "SELECT username FROM staff WHERE onsupport = 'on' ORDER BY username";
						$staffresult = $dbcnx->query($sql);
						while ($staffrow = $staffresult->fetch_assoc()) {
							$thisstaffname = $staffrow['username'];
							print "		<option value=\"$thisstaffname\""; if ($thisstaffname == $allocated) { print " SELECTED"; } print ">$thisstaffname</option>\n";
						}
						print "	</select>";
						print "</td>\n";
					} else {
						print "	<td>$allocated</td>\n";
					}
					print "</tr>\n";
					if ($erallocated) {
						print "	<tr>\n";
						print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
						print "		<td colspan=\"3\"><span class=\"redtext\">$erallocated</span></td>\n";
						print "	</tr>\n";
					}
				} else {
					print "<input type=\"hidden\" name=\"allocated\" value=\"$allocated\" />\n";
				}

				if ($status < 7) {
					print "<tr>\n";
					if ($tid) {

						print "	<tr>";
						print "		<td>&nbsp;</td>\n";
						print "		<td colspan=\"2\" align=\"left\">";
						print "<a data-role=\"button\" data-val=\"MPhone\"  class=\"select-change\" id=\"btnPhone\"><img src=\"images/call_icon.jpg\" width=\"32px\" height=\"32px\" alt=\"Call\" /></a>";
						print "<a data-role=\"button\" data-val=\"MEmail\"  class=\"select-change\" id=\"btnEmail\"><img src=\"images/email_icon.jpg\" width=\"32px\" height=\"32px\" alt=\"Email\" style=\"padding-left:10px\" /></a>";
						print "<a data-role=\"button\" data-val=\"chargeable\" class=\"select-change\" id=\"btnCharge\"><img src=\"images/charge_icon.jpg\" width=\"32px\" height=\"32px\" alt=\"Charge\" style=\"padding-left:10px\" /></a>";
						print "<a data-role=\"button\" data-val=\"note\"  class=\"select-change\" id=\"btnNotes\"><img src=\"images/notes_icon.jpg\" width=\"32px\" height=\"32px\" alt=\"Note\" style=\"padding-left:10px\" /></a>";
						print "		</td>";
						print "	</tr>";

						print "<td align=\"right\" valign=\"top\">\n";
						print "	<select name=\"notetype\" id=\"MChoice\" class=\"element_small\">\n";
						print "		<option value=\"note\">Note</option>\n";
						print "		<option value=\"chargeable\">Chargeable</option>\n";
						print "		<option value=\"details\">Details</option>\n";
						print "		<option value=\"MPhone\">Phone</option>\n";
						print "		<option value=\"MEmail\">Email</option>\n";
						print "	</select>\n";
						print "<br />Internal Notes:";

						print "<script type=\"text/javascript\">\n";
						print "$('.select-change').click(function(){\n";
						print "	$('#MChoice').val($(this).data('val')).trigger('change');\n";
						print "})\n";
						print "</script>\n";

						if ($ernotetype) { print "<br /><span class=\"redtext\">$ernotetype</span>"; }
						print "</td>\n";
					} else {
						print "	<td align=\"right\" valign=\"top\">Details:</td>\n";
					}
					print "	<td colspan=\"3\"><textarea name=\"notedetails\" wrap=\"virtual\" cols=\"45\" rows=\"4\" class=\"element\">$notedetails</textarea></td>\n";
					print "</tr>\n";
					if ($ernotedetails) {
						print "	<tr>\n";
						print "		<td align=\"right\" valign=\"top\">&nbsp;</td>\n";
						print "		<td colspan=\"3\"><span class=\"redtext\">$ernotedetails</span></td>\n";
						print "	</tr>\n";
					}
					print "</tr>\n";

					print "<tr class=\"emailBody\">\n";
					print "<td align=\"right\" valign=\"top\">Email Message:</td>\n";
					print "	<td colspan=\"3\"><textarea name=\"emailBody\" wrap=\"virtual\" cols=\"45\" rows=\"4\" class=\"element\">$emailBody</textarea></td>\n";
					print "</tr>\n";
					print "</table>\n";

					?>
					<script>
					$(document).ready(function(){
						$('.emailBody').hide();
						$('#MChoice').on('change',function(){
							var optionText = $("#MChoice option:selected").text();
							if (optionText == "Email") {
								$('.emailBody').show();
							}
							else {
								$('.emailBody').hide();
							}
						});
					});
					</script>
					<?php

					print "<table align=\"center\" cellpadding=\"2px\" cellspacing=\"10px\">\n";
					print "	<tr>\n";
					print "		<td>\n";
					print "			<input name=\"submit\" type=\"submit\" class=\"button\" value=\"$submittext\" /></form>\n";
					print "		</td>\n";
					if ($accessRights[$page] > 2) {
						if ($status == 6) {
							print "<form name=\"form1\" method=\"post\" action=\"maintenance-tickets-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"close\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
							print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
							print "		<td>\n";
							print "			<input name=\"submit\" type=\"submit\" class=\"button\" value=\"Close Ticket\" /></form>\n";
							print "		</td>\n";
						}
					}
					print "	</tr>\n";
				}
				print "</table>\n";

				if (($accessRights[$page] > 2) AND ($tid)) {
					print "<p align=\"center\"><a href=\"maintenance-tickets-delete.php?tid=$tid\">Delete Ticket</a></p>\n";
				}

				if ($tid) {
					print "		</td>\n"; # end left pane
					print "		<td valign=\"top\">\n";  # right pane for images

					$sql = "SELECT * FROM mnt_images WHERE tid='$tid'";
					$iresult = $dbcnx->query($sql);
					if (($iresult) && ($iresult->num_rows)) {
						print "<table cellspacing=\"5\" align=\"center\">\n";
						while ($irow = $iresult->fetch_assoc()) {
							$iid   = $irow['ID'];
							$image = $irow['image'];
							$idate = $irow['crdate'];
							$iby   = $irow['crby'];
							$idate = date("d/m/y H:i",$idate);
							if (($i) AND ($i % 2 == 0)) {
								print "	</tr>\n";
								print "	<tr>\n";
							}
							print "<tr><td align=\"left\"><a href=\"getimage.php?dr=mnt&amp;im=$image\" target=\"_blank\"><img src=\"getimage.php?dr=mnt&amp;im=$image\" width=\"100px\" border=\"0px\" /></a><br />\n";
							print "By $iby<br />$idate<br /><a href=\"maintenance-tickets-addedit.php?images=1&amp;tid=$tid&amp;iid=$iid&amp;delete=1\">Delete</a></td></tr>\n";
						}
						print "	</table>\n";
					} else {
						print "<p class=\"bodytext\" align=\"center\">No images have been uploaded</p>\n";
					}
					if ($status < 7) {
						print "<p>&nbsp;</p>\n";
						print "<p align=\"center\"><b>Add New Image</b></p>\n";
						print "<p align=\"center\">Your image must be jpg, jpeg, gif or png.</p>\n";
						print "<form method=\"post\" enctype=\"multipart/form-data\" action=\"maintenance-tickets-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"tid\" value=\"$tid\">\n";
						print "<input type=\"hidden\" name=\"images\" value=\"1\">\n";
						print "<table align=\"center\" cellspacing=\"2\">\n";
						print "<tr>\n";
						print "	<td><input name=\"image\" type=\"file\" size=\"25\">";
						if ($erimage) { print "&nbsp;<span class=\"redtext\">$erimage</span>"; }
						print "</td>\n";
						print "	</tr>\n";
						print "	<tr>\n";
						print "		<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Upload\"></td>\n";
						print "	</tr>\n";
						print "</table>\n";
						print "</form>\n";
					}

					print "		</td>\n"; # end right pane
					print "	</tr>\n";
					print "</table>\n";

					// notes pane
					print "<p align=\"center\" class=\"larger\">Ticket History</p>\n";
					$sql = "SELECT * FROM mnt_notes WHERE tid='$tid' ORDER BY crdate";
					$nresult = $dbcnx->query($sql);
					if (($nresult) && ($nresult->num_rows)) {
						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "	<tr>\n";
						print "		<td style=\"border-bottom:1px solid #666\" valign=\"top\"><b>Date/Time</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\" valign=\"top\"><b>Added by</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\" valign=\"top\"><b>Details</b></td>\n";
						print "	</tr>\n";

						// row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						while ($nrow = $nresult->fetch_assoc()) {
							$crdate  = $nrow['crdate'];
							$crby    = $nrow['crby'];
							$details = $nrow['details'];
							$crdate  = date("d/m/y H:i",$crdate);
							$details = stripslashes($details);

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "		<td valign=\"top\">$crdate</td>\n";
							print "		<td valign=\"top\">$crby</td>\n";
							print "		<td valign=\"top\">$details</td>\n";
							print "	</tr>\n";
						}
						print "<tr>\n";
                		print " <td style=\"border-top:1px solid #666\" colspan=\"3\">&nbsp;</td>\n";
                		print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No History Found</p>\n";
					}
					// end notes pane
				}

			} else {
				$sql = "SELECT ID, title FROM sites ORDER BY title";
				$sitesresult = $dbcnx->query($sql);

				print "<form name=\"siteform\" method=\"post\" action=\"maintenance-tickets-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"sitepost\" value=\"1\" />\n";

				print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				if (($sitesresult) && ($sitesresult->num_rows)) {
					print "		<td align=\"right\">Site:</td>\n";
					print "		<td><select name=\"site\" class=\"element\">\n";
					while ($srow = $sitesresult->fetch_assoc()) {
						$thissiteid    = $srow['ID'];
						$thissitename  = $srow['title'];
						$thissite = "$thissiteid-$thissitename";
						print "		<option value=\"$thissite\""; if ($thissite == $site) { print " SELECTED"; } print ">$thissitename</option>\n";
					}
					print "		</select>";
					print "		</td>\n";
				}
				print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "</form>\n";
			}
			print "<p align=\"center\"><a href=\"maintenance-tickets-view.php\">Return</a></p>\n";
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
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
?>
