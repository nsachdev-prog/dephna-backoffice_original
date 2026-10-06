<?php
$page = "Customers";
include('settings.php');
include('session.php');
include('header.php');
$custid = $_REQUEST['custid'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading and submenu
			print "<p align=\"center\" class=\"heading\">SEND H&amp;S DOCS</p>\n";
			
			$custref = $_REQUEST['custref'];
			$custid = $_REQUEST['custid'];
			
			if ($custref) {
				# get customer
				$sql = "SELECT `username`, `email`, `aemails`, `compname`, `location` FROM `users` WHERE `custref`='$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
					$custname = $crow['username'];
					$aemails  = $crow['aemails'];
					$email    = $crow['email'];
					$site     = $crow['location'];
					if (!$aemails) { $aemails = $email; }
					
					# get sitename
					$sql = "SELECT title FROM sites WHERE ipaddress='$site'";
					$sitesresult = $dbcnx->query($sql);
					if ($sitesresult->num_rows) {
						$srow = $sitesresult->fetch_assoc();
						$sitename = $srow['title'];
					}
					
					print "<p align=\"center\" class=\"heading\">$compname - $sitename</p>\n";

					if ($_POST['send']) {
						# process and send email
						$subject = strip_tags($_POST['subject']);
						$body    = strip_tags($_POST['body']);
						if (!$subject) { $ersubject = "No Subject"; $e++; }
						if (!$body)    { $erbody    = "No Message"; $e++; }
						if (!$e) {
							# send email to client
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

						    require('../../../tcpdf/config/lang/eng.php');
							require('../../../tcpdf/tcpdf.php');

							$fileattname = "HealthandSafetyPolicyStatement.pdf";
							$fileatt = file_get_contents("pdf/$sitename/$fileattname");
							$data    = chunk_split(base64_encode($fileatt));
							$fileatttype = "application/pdf";
							$message .= "--".$mime_boundary."\r\n";
							$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
							$message .= "Content-Transfer-Encoding: base64\r\n";
							$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
							$message .= $data;
							$message .= "\r\n\r\n";
							
						    $message .= "--".$mime_boundary."--";
						    $emails = explode(",",$aemails);
							foreach ($emails AS $to) {
						    	if (mail($to, $subject, $message, $headers)) {
									$resultMessage .= "Email Successfully Sent to $to<br />";
								} else {
									$resultMessage .= "Email FAILED to send to $to<br />";
								}
							}
						}
					}
					if ((!$resultMessage) OR ($e)) {
						print "<form name=\"emailform\" method=\"post\" action=\"customer-hsdocs.php\">\n";
						print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"custid\" value=\"$custid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";

						# get email template
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'HSDocs'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow  = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];

							$search  = array("[[custname]]","[[staffname]]");
							$replace = array($custname,$_SESSION['username']);
							$body    = str_replace($search,$replace,$body);

							print "<p align=\"center\"><b>To:</b> $aemails</p>\n";

							print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
							if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
							print "</p>\n";

							if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
							print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
							print "</form>\n";

						} else {
							$resultMessage = "<b>Email template HSDocs not found</b><br>" . $dbcnx->error;
						}
					}

				} else {
					$resultMessage = "<b>Customer not found!</b>";
				}
			} else {
				$resultMessage = "<b>Page called without custref!</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"customer-list.php?id=$custid\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
