<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
</style>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">Users &amp; Key Fobs</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$custid  = $_SESSION['user'];
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$posted = $_POST['posted'];

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			if ($posted) {

				$firstName  = $_POST['firstName'];
				$middleName = $_POST['middleName'];
				$lastName   = $_POST['lastName'];
				$telephone  = $_POST['telephone'];
				$accept     = $_POST['accept'];
				$sites      = $_POST['sites'];
                $cls        = $_POST['cls'];
				$notes      = $_POST['notes'];

				if (!$sites) {
					$ersites = "Please select at lease one site"; $e++;
				}

				if (!$firstName) { $erfirstName = "Please enter at least a first name"; $e++; }
				if (!$telephone) { $ertelephone = "Please enter a phone number"; $e++; }

				if (!$accept) {
					$eraccept .= "Please Accept the charge<br>"; $e++;
				}

				if ($_FILES['image']['tmp_name']) {
					if (exif_imagetype($_FILES['image']['tmp_name']) != IMAGETYPE_JPEG) {
						$erimage = "The image is not a jpg"; $e++;
					}
				} else {
					$erimage = "Please upload a jpg image of the user"; $e++;
				}

                $sql = "SELECT COUNT(*) AS numrows FROM training_checklist";
				if ($clresult = $dbcnx->query($sql) AND $clresult->num_rows) {
                    $clrow = $clresult->fetch_assoc();
                    $numrows = $clrow['numrows'];
                    if ($numrows > count($cls)) {
                        $ercls = "You must confirm all the training points"; $e++;
                    }
                }

				if (!$e) {

					$imagename = date("YmdHis") . "-$custref.jpg";
					copy($_FILES['image']['tmp_name'], "../../dis2_db/img/pax/$imagename");
					chmod("../../dis2_db/img/pax/$imagename", 0666);
					list($width, $height, $type, $attr) = getimagesize("../../dis2_db/img/pax/$imagename");
					if (($width > 800) OR ($height > 800)) {
						// too big
						if ($width > $height) {
							$scale = $width/800;
							$new_width = 800;
							$new_height = $height/$scale;
						} else {
							$scale = $height/800;
							$new_height = 800;
							$new_width = $width/$scale;
						}
						$temp_im = imageCreateFromJPEG("../../dis2_db/img/pax/$imagename");
						$new_im  = imagecreatetruecolor($new_width,$new_height);
						imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
						imagejpeg($new_im, "../../dis2_db/img/pax/$imagename", 80);
					}
					
					$firstName  = addslashes($firstName);
					$middleName = addslashes($middleName);
					$lastName   = addslashes($lastName);
					$telephone  = addslashes($telephone);
					$notes      = addslashes($notes);
					foreach ($cls AS $key => $value) {
						$clticks[] = "$key:$value";
					}
					$clticks   = implode("--",$clticks);
					$xdatetime = date("Y-m-d H:i:s");

					foreach ($sites AS $siteid) {

						$sql = "INSERT INTO pax_user_requests VALUES (
							null,
							'$xdatetime',
							'$siteid',
							'$custid',
							'$custref',
							'0',
							'$firstName',
							'$middleName',
							'$lastName',
							'$telephone',
							'$imagename',
							'$notes',
							'{$_SESSION['username']}',
                            '$clticks',
							'0',
							''
						)";
						if ($dbcnx->query($sql)) {
							$added++;
						}

					}

					if ($added) {

						// email client and bcc dephna support
						$sql = "SELECT email, aemails FROM users WHERE custref = '$custref'";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{

							require "../../PHPMailer/src/SMTP.php";
                            require "../../PHPMailer/src/PHPMailer.php";
                            require "../../PHPMailer/src/Exception.php";

							$row = $result->fetch_assoc();
							$aemails  = $row['aemails'];
							$email    = $row['email'];
							$custname = $_SESSION['username'];
							if (!$aemails) { $aemails = $email; }
							$aemails = explode(",",$aemails);

							$subject = "Dephna - Key Fob Request";
							$from = "support@dephna.com";				
							$fromname = "Dephna Support";

							$message  = file_get_contents('../backoffice/email-header.html') . "\r\n";
							$message .= "<p>Dear $custname,</p>\r\n";
							$message .= "<p>You have requested a key fob for $firstName $lastName and accepted our standard charge for key fobs.<br>A charge of &pound;10 + VAT per fob will be included on your next invoice.</p>\r\n";
							$message .= "<p>Kindly arrange collection from 2 Portal Way, London W3 6RT, Monday - Friday, 08.00 - 17.30.</p>\r\n";
							$message .= "<p>If you did not make this request or if the details are wrong please contact support straight away.</p>\r\n";
							$message .= "<p>Kind Regards<br>Dephna Support</p>\r\n";
							$message .= file_get_contents('../backoffice/email-footer.html') . "\r\n";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');
								$mail->setFrom($from, $fromname);
								$mail->addReplyTo($from, $fromname);
								foreach ($aemails AS $aemail) {
									$mail->addAddress($aemail);
								}
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;
								$mail->send();

								$resultMessage .= "<b>Request Received</b><br>Support will prepare your new key fob soon.";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Requested','Key Fob $firstName $lastName','Client','$now')");

							} catch (Exception $e) {
								$resultMessage .= "<b>Request FAILED</b><br>Please contact support<br>" . $mail->ErrorInfo . "<br>";
							}

						}

					} else {
						$resultMessage = "<b>Request FAILED</b><br>Please contact support<br>" . $dbcnx->error;
					}

				}

			}

			if (!$resultMessage OR $e) {

				$sql = "SELECT b.siteid, s.title AS sitename
					FROM bookings AS b
					LEFT JOIN sites AS s ON s.ID = b.siteid
					WHERE b.custid = '$custref'
					GROUP BY b.siteid
				";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {

					print "<p align=\"center\"><b>Request New User &amp; Key Fob</b></p>\n";
					print "<p align=\"center\">You must enter at least a first name and a telephone number.</p>";

					print "<form name=\"addform\" enctype=\"multipart/form-data\" method=\"post\" action=\"keyfobs-users-request.php\">\n";

					print "<fieldset style=\"border:none\">\n";

					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>First Name</b></td>\n";
					print "	<td><input name=\"firstName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$firstName\" />";
					if ($erfirstName) { print "<br><span style=\"color:red\">$erfirstName</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>Middle Name</b></td>\n";
					print "	<td><input name=\"middleName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$middleName\" />";
					if ($ermiddleName) { print "<br><span style=\"color:red\">$ermiddleName</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>Last Name</b></td>\n";
					print "	<td><input name=\"lastName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$lastName\" />";
					if ($erlastName) { print "<br><span style=\"color:red\">$erlastName</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>Telephone</b></td>\n";
					print "	<td><input name=\"telephone\" type=\"text\" class=\"element dis-field\" maxlength=\"50\" value=\"$telephone\" />";
					if ($ertelephone) { print "<br><span  style=\"color:red\">$ertelephone</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Photograph (jpg)</b></td>\n";
					print "	<td><input type=\"file\" name=\"image\" class=\"element dis-field\" />";
					if ($erimage) { print "<br><span  style=\"color:red\">$erimage</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>";
					print "<td colspan=\"2\"><b>Notes</b><br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element dis-field\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "</table>\n";

					if ($sresult->num_rows > 1) {
						$n=0;
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						while ($srow = $sresult->fetch_assoc()) {
							$thissiteid   = $srow['siteid'];
							$thissitename = $srow['sitename'];
							print "<tr>";
							print "<td align=\"right\"><b>$thissitename</b></td>\n";
							print "<td align=\"left\"><input type=\"checkbox\" name=\"sites[$n]\""; if ($sites[$n] == $thissiteid) { print " CHECKED "; } print "value=\"$thissiteid\" /></td>";
							print "</tr>";
							$n++;
						}
						if ($ersites) {
							print "<tr>";
							print "<td colspan=\"2\"><span style=\"color:red\">$ersites</span></td>";
							print "</tr>";
						}
						print "</table>\n";
					} else {
						$srow = $sresult->fetch_assoc();
						$thissiteid = $srow['siteid'];
						print "<input type=\"hidden\" name=\"sites[]\" value=\"$thissiteid\" />\n";
					}

					print "<p align=\"center\"><br><b>Training</b></p>\n";
            		print "<p align=\"center\">You must give the new key fob holder the following training.<br>Please click each checkbox to confirm that the relevent training has been given and understood.</p>\n";

                    if ($ercls) {
                        print "<p align=\"center\" style=\"color:red\">$ercls</p>\n";
                    }

					$sql = "SELECT * FROM training_checklist ORDER BY pos";
					if ($clresult = $dbcnx->query($sql) AND $clresult->num_rows) {
						print "<fieldset style=\"border:none\">\n";
						print "<table width=\"60%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "	<td colspan=\"2\" style=\"border-top:1px solid #999999\" align=\"right\"><i>Check All</i>&nbsp;&nbsp;<input type=\"checkbox\" class=\"checkall\"></td>\n";
						while ($clrow  = $clresult->fetch_assoc()) {
							$clid    = $clrow['ID'];
							$title   = $clrow['title'];
							$details = $clrow['details'];
							print "<tr>\n";
							print "<td align=\"left\" style=\"border-top:1px solid grey;\" valign=\"top\"><b>$title</b><br>$details</td>\n";
							print "<td style=\"border-top:1px solid grey;\" valign=\"top\"><input type=\"checkbox\" name=\"cls[$clid]\""; if ($cls[$clid]) { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "<td colspan=\"3\" style=\"border-top:1px solid grey;\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</fieldset>\n";
					}

					print "<p align=\"center\"><b>Charge</b></p>\n";
					print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>";
					print "<td align=\"left\" style=\"border-top:1px solid grey;\" valign=\"top\"><b>Accept The Charge</b><br>I understand that there is a charge for key fobs and that the charge will be included on my next invoice.";
					if ($eraccept) { print "<br><span style=\"color:red\">$eraccept</span>"; }
					print "</td>";
					print "<td style=\"border-top:1px solid grey;\" valign=\"top\"><input type=\"checkbox\" name=\"accept\""; if ($accept) { print " CHECKED"; } print " /></td>";
					print "</tr>\n";
					print "<tr>\n";
					print "<td colspan=\"3\" style=\"border-top:1px solid grey;\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "<span class=\"notclicked\">\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button btn btn-primary\" value=\"Submit\"></p>\n";
					print "</span>\n";

					print "<span class=\"uploading\">\n";
					print "<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "</span>\n";

					print "</form>\n";

					// ajax loader
					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";

					// select all toggle
					print "<script type=\"text/javascript\">\n";
					print "$(function () {\n";
					print "    $('.checkall').on('click', function () {\n";
					print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
					print "    });\n";
					print "});\n";
					print "</script>\n";

				} else {
					print "<p align=\"center\">You Have No Bookings</p>\n";
				}
			}

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage)	{
			print "<p align=\"center\">$resultMessage</p>\n";
			print "<p align=\"center\"><a href=\"keyfobs-users-request.php\">Request Another New User &amp; Fob</a></p>";
		}
		print "<p align=\"center\"><a href=\"keyfobs.php\">Return</a></p>";
		print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";

	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>