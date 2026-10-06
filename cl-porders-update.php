<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Cleaning";
$subpage = "Orders";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CLEANING</p>\n";
	if ($accessRights[$page]) {

		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$hid = $_REQUEST['hid'];
		$update = $_REQUEST['update'];

		if ($hid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$status = array(
					0 => "Entered",
					1 => "Approved",
					2 => "Ordered",
					3 => "Delivered",
					4 => "Invoiced",
					5 => "Completed"
				);
	
				$actions = array(
					0 => "Approve Order",
					1 => "Send Order",
					2 => "Log Delivery",
					3 => "Approve Invoice",
					4 => "Complete Order"
				);

				// get the users cladmin rights
				$sql = "SELECT cladmin FROM staff WHERE ID='{$_SESSION['user']}'";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$row = $uresult->fetch_assoc();
					$cladmin = $row['cladmin'];
				}

				if ($update) {
					// get current status
					$sql = "SELECT status, notes FROM cl_poheaders WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$thisstatus = $row['status'];
						$ordernum   = str_pad($hid, 5, "0", STR_PAD_LEFT);
						$notes	    = $row['notes'];
						$now = time();

						if ($thisstatus == 0) {
							// update status
							$sql = "UPDATE cl_poheaders SET status = '1', appdate = '$now', appby = '{$_SESSION['username']}' WHERE ID = '$hid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Order Approved to Send<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Approved CL-Order $hid','$page','$now')");
							} else {
								$resultMessage .= "Order Could Not Be Approved<br>" . $dbcnx->error;
							}
						} elseif ($thisstatus == 1) {
							// email order to supplier
							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							// get site address
							$sql = "SELECT siteaddress FROM sites WHERE title = (SELECT site FROM cl_poheaders WHERE ID = '$hid')";
							$result = $dbcnx->query($sql);
							if ($result->num_rows) {
								$row = $result->fetch_assoc();
								$siteaddress = $row['siteaddress'];
							} else {
								$resultMessage .= "Site Address Not Found<br>" . $dbcnx->error;
							}

							// supplier name and email
							$sql = "SELECT email, suppname FROM suppliers WHERE ID = (SELECT suppid FROM cl_poheaders WHERE ID = '$hid')";
							$result = $dbcnx->query($sql);
							if ($result->num_rows) {
								$row = $result->fetch_assoc();
								$emails = $row['email'];
								$suppname = $row['suppname'];

								$resultMessage .= "Supplier email(s): $emails<br>";

								// get order details
								$sql = "SELECT l.ordqty, l.prodid, p.prodname, p.suppref
										FROM cl_polines AS l
										LEFT JOIN cl_products AS p ON l.prodid = p.ID
										WHERE l.hid = '$hid'
								";
								$result = $dbcnx->query($sql);
								if ($result->num_rows) {
									$body = "<p>Dear $suppname,<br><br>Please supply the following items to the site below:<br><br>";
									$body .= "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\">\n";
									$body .= "<tr><th>Quantity</th><th>Product Ref</th><th>Product Name</th></tr>\n";
									while ($row = $result->fetch_assoc()) {
										$body .= "<tr><td>{$row['ordqty']}</td><td>{$row['suppref']}</td><td>{$row['prodname']}</td></td></tr>\n";
									}
									$body .= "</table><br>";
									$body .= "Site: Dephna Group Ltd, $siteaddress<br>";
									$body .= "Order Number: $ordernum<br>";
									$body .= "Notes: $notes<br><br>";

									$from     = "support@dephna.com";
									$fromname = "Dephna Support";
									$emails   = explode(",", $emails);

									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= $body . "\r\n";
									$message .= file_get_contents('email-footer.html') . "\r\n";

									$mail = new PHPMailer(true);

									try {
										
										include('../../cron/SMTP.php');
			
										$mail->CharSet = 'UTF-8';
										$mail->setFrom($from, $fromname);
										$mail->addReplyTo($from, $fromname);
										foreach ($emails as $email) {
											$mail->addAddress($email);
										}
										$mail->isHTML(true);
										$mail->Subject = "Dephna Cleaning Order $ordernum";
										$mail->Body    = $message;
										$mail->AltBody = "Please use an HTML compatible email viewer";
										$mail->send();

										$resultMessage .= "Order Sent to Supplier<br>";
										$sql = "UPDATE cl_poheaders SET status = '2', sentdate = '$now', sentby = '{$_SESSION['username']}' WHERE ID = '$hid'";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "Order Updated<br>";
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Sent CL-Order $hid','$page','$now')");
										} else {
											$resultMessage .= "Order Could Not Be Updated<br>" . $dbcnx->error;
										}
									} catch (Exception $e) {
										$resultMessage .= "Order Could Not Be Sent<br>" . $mail->ErrorInfo;
									}
								} else {
									$resultMessage .= "Order Details Not Found sending email to supplier<br>" . $dbcnx->error;
								}
								
							} else {
								$resultMessage .= "Supplier Email Not Found<br>" . $dbcnx->error;
							}

						} elseif ($thisstatus == 2) {
							// nothing to do

						} elseif ($thisstatus == 3) {
							// update status
							$sql = "UPDATE cl_poheaders SET status = '4', payappdate = '$now', payappby = '{$_SESSION['username']}' WHERE ID = '$hid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Order Invoice Approved<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Approved Invoice CL-Order $hid','$page','$now')");
							} else {
								$resultMessage .= "Order Could Not Be Approved<br>" . $dbcnx->error;
							}
						} elseif ($thisstatus == 4) {
							// update status
							$sql = "UPDATE cl_poheaders SET status = '5' WHERE ID = '$hid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Order Completed<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Completed CL-Order $hid','$page','$now')");
							} else {
								$resultMessage .= "Order Could Not Be Completed<br>" . $dbcnx->error;
							}
						}
					} else {
						$resultMessage .= "Order Not Found getting current status<br>" . $dbcnx->error;
					}
				} else {

					// get header
					$sql = "SELECT o.*, s.siteaddress, sup.suppname, sup.email
							FROM cl_poheaders AS o
							LEFT JOIN sites AS s ON o.site = s.title
							LEFT JOIN suppliers AS sup ON o.suppid = sup.ID
							WHERE o.ID='$hid'
					";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$hrow = $result->fetch_assoc();
						$thisstatus  = $hrow['status'];
						$siteaddress = $hrow['siteaddress'];
						$crdate      = date("d-m-Y",$hrow['crdate']);
						if ($hrow['appdate'])    { $appdate	     = date("d-m-Y",$hrow['appdate']); }
						if ($hrow['sentdate'])   { $sentdate	 = date("d-m-Y",$hrow['sentdate']); }
						if ($hrow['payappdate']) { $payappdate	 = date("d-m-Y",$hrow['payappdate']); }
						$site        = $hrow['site'];
						$ordernum    = str_pad($hrow['ID'], 5, "0", STR_PAD_LEFT);
						if ($hrow['deldate']) { $deldate = date("d-m-Y",$hrow['deldate']); } else { $deldate = "&nbsp;"; }

						print "<p align=\"center\" class=\"larger\">{$actions[$thisstatus]}</p>\n";

						print "<table align=\"center\" width=\"500px\" cellspacing=\"0\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Supplier:</b></td>\n";
						print "<td>{$hrow['suppname']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Supplier Email:</b></td>\n";
						print "<td>{$hrow['email']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Order Number:</b></td>\n";
						print "<td>$ordernum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Order Total</b></td>\n";
						print "	<td>{$hrow['ordertotal']}</td>\n";
						print "	<td>&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Site:</b></td>\n";
						print "<td>$site</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Delivery Addreess:</b></td>\n";
						print "<td>" . nl2br("$siteaddress") . "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Entered:</b></td>\n";
						print "<td>$crdate by {$hrow['crby']}</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Order Approved:</b></td>\n";
						if ($appdate) {
							print "<td>$appdate by {$hrow['appby']}</td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Order Sent:</b></td>\n";
						if ($sentdate) {
							print "<td>$sentdate by {$hrow['sentby']}</td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Payment Approved:</b></td>\n";
						if ($payappdate) {
							print "<td>$payappdate by {$hrow['payappby']}</td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Notes To Supplier:</b></td>\n";
						print "	<td>{$hrow['notes']}</td>\n";
						print "</tr>\n";

						print "</table>\n";

						print "<p>&nbsp;</p>\n";

						# get lines
						$sql = "SELECT * FROM cl_polines WHERE hid='$hid'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
							print "<tr>\n";
							print "	<td><i>Product</i></td>\n";
							print "	<td><i>Ref</i></td>\n";
							print "	<td width=\"10px\">&nbsp;</td>\n";
							print "	<td align=\"right\"><i>Qty</i></td>\n";
							print "	<td align=\"right\"><i>Each</i></td>\n";
							print "	<td align=\"right\"><i>Total</i></td>\n";
							print "	<td align=\"right\"><i>Received</i></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"7\" height=\"1px\"><img src=\"images/blue.gif\" width=\"100%\" height=\"1px\" alt=\"\" /></td>\n";
							print "</tr>\n";

							while ($lrow = $result->fetch_assoc()) {
								print "<tr>\n";
								print "	<td>{$lrow['prodname']}</td>\n";
								// added by Mat 27.01.14
								$sql = "SELECT suppref FROM cl_products WHERE ID='{$lrow['prodid']}'";
								$pidresult = $dbcnx->query($sql);
								if ($pidresult->num_rows) {
										$pidrow = $pidresult->fetch_assoc();
										$suppref = $pidrow['suppref'];
								}
								print "	<td>$suppref</td>\n";
								print "	<td width=\"10px\">&nbsp;</td>\n";
								print "	<td align=\"right\">{$lrow['ordqty']}</td>\n";
								print "	<td align=\"right\">{$lrow['ordprice']}</td>\n";
								print "	<td align=\"right\">{$lrow['ordlinetotal']}</td>\n";
								print "	<td align=\"right\">{$lrow['delqty']}</td>\n";
								print "</tr>\n";
							}

							print "<tr>\n";
							print "	<td colspan=\"7\" height=\"1px\"><img src=\"images/blue.gif\" width=\"100%\" height=\"1px\" alt=\"\" /></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"5\"><i>Order Total</i></td>\n";
							print "	<td align=\"right\"><b>{$hrow['ordertotal']}</b></td>\n";
							print "	<td>&nbsp;</td>\n";
							print "</tr>\n";

							print "</table>\n";

						} else {
							print "<p align=\"center\">No Order Lines Found!</p>\n";
						}

						print "<p align=\"center\">";
						if ($thisstatus == 2) {
							print "	<a href=\"cl-porders-delivery.php?hid=$hid&amp;update=1\">Yes {$actions[$thisstatus]}</a>&nbsp;|&nbsp;<a href=\"cl-porders-list.php\">No DON'T {$actions[$thisstatus]}</a>";
						} else {
							print "	<a href=\"cl-porders-update.php?hid=$hid&amp;update=1\">Yes {$actions[$thisstatus]}</a>&nbsp;|&nbsp;<a href=\"cl-porders-list.php\">No DON'T {$actions[$thisstatus]}</a>";
						}
						print "</p>\n";

					} else {
						print "<p align=\"center\">Order Not Found!<br>" . $dbcnx->error . "</p>\n";
					}
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without id!</b>\n";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-porders-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
