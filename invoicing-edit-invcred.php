<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Invoicing";
$subpage = "Add Transaction";
include('settings.php');
include('session.php');
include('header.php');
require('../../tcpdf6.8/tcpdf.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">EDIT TRANSACTION</p>\n";
	include('invoicing-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$txtypes  = array("I"=>"Invoice","C"=>"Credit Note");
			$invhead  = $_SESSION['invhead'];
			$hid      = $invhead['hid'];
			$txtype   = $invhead['txtype'];
			$txref    = $invhead['txref'];
			$custref  = $invhead['custref'];
			$custname = $invhead['custname'];
			$compname = $invhead['compname'];
			$aemails  = $invhead['aemails'];
			$trndate  = $invhead['trndate'];
			$mailit   = $invhead['mailit'];
			$ddmonth  = $invhead['ddmonth'];
			$site     = $invhead['site'];
			$siteid   = $invhead['siteid'];

			$cancel  = $_GET['cancel'];
			$addhead = $_POST['addhead'];
			$addline = $_POST['addline'];
			$delline = $_GET['delline'];
			$confirm = $_GET['confirm'];

			if ($cancel) {
				// destroy session variables
				$_SESSION['invhead']  = "";
				$_SESSION['invlines'] = "";
				$resultMessage = "<b>Edit Cancelled</b>";
			}
			elseif ($addhead) {
				// get fields
				$hid      = $_POST['hid'];
				$txtype   = $_POST['txtype'];
				$txref    = $_POST['txref'];
				$customer = $_POST['customer'];
				$trndate  = $_POST['trndate'];
				$mailit   = $_POST['mailit'];
				$ddmonth  = $_POST['ddmonth'];
				$ddyear   = $_POST['ddyear'];
				if (!$dd) { $dd = "No"; }
				list($custref,$compname,$custname,$aemails,$site,$siteid) = explode("--",$customer);
				// error check
				if ($customer == "Select") {
					$site = ""; $ercustomer = "Select customer"; $e++;
				}
				if (!$trndate) {
					$ertrndate = "Select date"; $e++;
				} else {
					if ($trndate <= $VAT_Period_Lock) {
						$ertrndate = "VAT Period Closed"; $e++;
					}
				}
				if (!$txtype)  { $ertxtype = "Select type"; $e++; }

				// save header
				if (!$e) {
					if (($ddmonth) AND ($ddyear)) {
						$ddmonth = "$ddyear-$ddmonth";
					} else {
						$ddmonth = "";
					}
					$invhead = array();
					$invhead['hid']       = $hid;
					$invhead['txtype']    = $txtype;
					$invhead['txref']     = $txref;
					$invhead['custref']   = $custref;
					$invhead['custname']  = $custname;
					$invhead['compname']  = $compname;
					$invhead['aemails']   = $aemails;
					$invhead['site']      = $site;
					$invhead['siteid']    = $siteid;
					$invhead['trndate']   = $trndate;
					$invhead['mailit']    = $mailit;
					$invhead['ddmonth']   = $ddmonth;
					$_SESSION['invhead']  = $invhead;
				}
				
				// get the existing lines
				$sql = "SELECT * FROM `inv_lines` WHERE `hid`='$hid'";
				if ($result = $dbcnx->query($sql)) {

					$_SESSION['invlines'] = array();
					
					while ($row = $result->fetch_assoc()) {
						
						$datefr = $row['letfrom'];
						if ($datefr) {
							list($d,$m,$y) = explode("/",$datefr);
							$y += 2000; $datefr = "$y-$m-$d";
						} else {
							$datefr = "";
						}
						
						$dateto = $row['letto'];
						if ($dateto) {
							list($d,$m,$y) = explode("/",$dateto);
							$y += 2000; $dateto = "$y-$m-$d";
						} else {
							$dateto = "";
						}

						$newline['details1'] = $row['details1'];
						$newline['details2'] = $row['details2'];
						$newline['datefr']   = $datefr;
						$newline['dateto']   = $dateto;
						$newline['lineqty']  = $row['lineqty'];
						$newline['linerate'] = $row['linerate'];
						$newline['linenet']  = $row['linenet'];
						$newline['vatrate']  = $row['vatrate'];
						$newline['vattotal'] = $row['vattotal'];
						$newline['linetotal'] = $row['linetotal'];
						$newline['office']   = $row['office'];
						$newline['repgrp']   = $row['unittype'];
						$_SESSION['invlines'][] = $newline;

					}
					$datefr   = "";
					$dateto   = "";
				} else {
					$resultMessage = "<b>Could not get transaction lines</b><br>" . $dbcnx->error;
				}
			}
			elseif ($addline) {
				// get fields
				$repgrp   = $_POST['repgrp'];
				$office   = $_POST['office'];
				$details  = $_POST['details'];
				$datefr   = $_POST['datefr'];
				$dateto   = $_POST['dateto'];
				$qty      = $_POST['qty'];
				$each     = $_POST['each'];
				$vat      = $_POST['vat'];
				
				// error check
				if ($datefr) {
					list($y,$m,$d) = explode("-",$datefr);
					if (!checkdate($m,$d,$y)) { $erdatefr = "Invalid"; $le++; }
				}

				if ($dateto) {
					list($y,$m,$d) = explode("-",$dateto);
					if (!checkdate($m,$d,$y)) { $erdateto = "Invalid"; $le++; }
				}

				if (!$qty) { $erqty = "Required"; $le++; }
				elseif (!is_numeric($qty)) { $erqty = "Not Numeric"; $le++; }
				else { $qty = intval($qty); }

				if (!$each) { $erqty = "Required"; $le++; }
				elseif (!is_numeric($each)) { $erqty = "Not Numeric"; $le++; }
				else { $each = round($each, 2); }
				
				if (!$repgrp) { $errepgrp = "Required"; $le++; }

				if (!$le) {

					$linenet = $qty*$each;
					if ($vat == "Yes") {
						$linevat = $linenet/100*$vatRate;
						$linevat = round($linevat, 2);
						$linetotal = $linenet + $linevat;
						$thisvat = $vatRate;
					} else {
						$linevat = 0;
						$linetotal = $linenet;
						$thisvat = 0;
					}
					$trntotal += $linetotal;

					list($ancillary,$repgrp) = explode("--",$repgrp);
					if ($office == "None") { $office = $ancillary; $details = $ancillary . " " . $details; }
					else { $details = $office . " " . $ancillary . " " . $details; }
					$details = trim($details);

					// save line
					$newline = array();

					$newline['details1']  = "$site $repgrp";
					$newline['details2']  = $details;
					$newline['datefr']    = $datefr;
					$newline['dateto']    = $dateto;
					$newline['lineqty']   = $qty;
					$newline['linerate']  = $each;
					$newline['linenet']   = $linenet;
					$newline['vatrate']   = $thisvat;
					$newline['vattotal']  = $linevat;
					$newline['linetotal'] = $linetotal;
					$newline['office']    = $office;
					$newline['repgrp']    = $ancillary;

					$_SESSION['invlines'][] = $newline;

					$repgrp   = "";
					$details  = "";
					$datefr   = "";
					$dateto   = "";
					$qty      = "";
					$each     = "";
					$vat      = "";
				}
			}
			elseif ($delline) {
				$line = $_GET['line'];
				unset($_SESSION['invlines'][$line]);
			}
			
			elseif ($confirm == "Yes") {	
				$now = time();
				list($y,$m,$d) = explode("-",$trndate);
				if (!$ddmonth) { $ddmonth = "No"; }

				$sql = "UPDATE inv_headers SET 
					`year`='$y',
					`month`='$m',
					`day`='$d',
					`custref`='$custref',
					`ddmonth`='$ddmonth',
					`crdate`='$now',
					`crby`='{$_SESSION['username']}' 
					WHERE `ID`='$hid'";
				$result = $dbcnx->query($sql);
				if ($result) {

					if ($_SESSION['invlines']) {

						// delete original lines
						$sql = "DELETE FROM `inv_lines` WHERE `hid`='$hid'";
						$result = $dbcnx->query($sql);

						// post new lines
						$trntotal = 0;
						foreach ($_SESSION['invlines'] AS $key => $value) {
							$details1  = $value['details1'];
							$details2  = $value['details2'];
							$lineqty   = $value['lineqty'];
							$linerate  = $value['linerate'];
							$linenet   = $value['linenet'];
							$thisvat   = $value['vatrate'];
							$linevat   = $value['vattotal'];
							$linetotal = $value['linetotal'];
							$office    = $value['office'];
							$repgrp    = $value['repgrp'];
							$datefr	   = $value['datefr'];
							$dateto	   = $value['dateto'];

							$trntotal += $linetotal;
							
							if ($datefr) {
								list($y,$m,$d) = explode("-",$value['datefr']);
								$y -= 2000;
								$datefr = "$d/$m/$y";
							}

							if ($dateto) {
								list($y,$m,$d) = explode("-",$value['dateto']);
								$y -= 2000;
								$dateto = "$d/$m/$y";
							}
							
							if ($txtype == "C") {
								// credit note change figures to minus
								$linenet *= -1;
								$linevat *= -1;
								$linetotal *= -1;
							}
							
							$sql = "INSERT INTO inv_lines VALUES (
								null,
								'$hid',
								'$details1',
								'$details2',
								'$datefr',
								'$dateto',
								'$lineqty',
								'$linerate',
								'$linenet',
								'$thisvat',
								'$linevat',
								'$linetotal',
								'$site',
								'$office',
								'$repgrp'
							)";
							$result = $dbcnx->query($sql);
							if (!$result) {
								$resultMessage = "<b>Could not post transaction lines</b><br>" . $dbcnx->error . "<br>";
							}
						}
						if ($txtype == "C") {
							$trntotal *= -1;
						}
						$sql = "UPDATE inv_headers SET invtotal='$trntotal', outstanding='$trntotal' WHERE ID='$hid'";
						$result = $dbcnx->query($sql);
						if (!$result) {
							$resultMessage = "<b>Could not update transaction total</b><br>" . $dbcnx->error . "<br>";
						}
					}
						
					if ($mailit) {
						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";
						// get email template
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'InvManTx'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow  = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];

							// replace fields in template with data
							$search  = array("[[custname]]","[[txtype]]","[[invnumber]]","[[staffname]]");
							$replace = array($custname,$txtypes[$txtype],$invnumber,$_SESSION['username']);
							$body    = str_replace($search,$replace,$body);
							
							// prepare email headers
							$from = "accounts@dephna.com";
							$fromname = "Dephna Accounts";
							
							$message  = file_get_contents('email-header.html') . "\r\n";
							$message .= nl2br($body);
							$message .= file_get_contents('email-footer.html') . "\r\n";

							$emails = explode(",",$aemails);

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								foreach ($emails AS $email) {
									$mail->addAddress($email);
								}
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$sql = "SELECT * FROM `inv_headers` WHERE `invnumber`='$txref'";
								$invresult = $dbcnx->query($sql);
								$invrow = $invresult->fetch_assoc();
								$inv    = $invrow['invnumber'];
								$id     = $invrow['ID'];
								$iyear  = $invrow['year'];
								$imonth = $invrow['month'];
								$iday   = $invrow['day'];
								$invtotal = $invrow['invtotal'];
								include("mailouts-pdf-make-inv.php");
								if ($fileatt) {
									$mail->addStringAttachment($fileatt, "Invoice $inv.pdf", 'base64', 'application/pdf');
								}

								$mail->send();

								$resultMessage .= "<b>Email sent to $aemails</b><br>";

							} catch (Exception $e) {
								$resultMessage .= "<b>Email Error:</b><br>" . $mail->ErrorInfo . "<br>";
							}
						} else {
							$resultMessage .= "<b>Email accept template InvManTx not found.</b><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$resultMessage .= "<b>Email client not selected.</b><br>";
					}
					
					$_SESSION['invhead']  = "";
					$_SESSION['invlines'] = "";
					$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','{$txtypes[$txtype]} $invnumber to DP$custref','$page','$now')");
					$resultMessage .= "<b>Transaction Successfully Posted</b>";

				} else {
					$resultMessage = "<b>Could NOT post transaction header</b><br>" . $dbcnx->error;
				}
			} else {
				if (!$invhead) {
					// get transaction from database
					$hid = $_GET['hid'];
					if ($hid) {
						$sql = "SELECT * FROM `inv_headers` WHERE `ID`='$hid'";
						if ($result = $dbcnx->query($sql)) {
							$row = $result->fetch_assoc();
							
							$txtype   = $row['txtype'];
							$txref    = $row['invnumber'];
							$custref  = $row['custref'];
							$ddmonth  = $row['ddmonth'];
							$y        = $row['year'];
							$m        = $row['month'];
							$d        = $row['day'];
							$trndate  = "$y-$m-$d";
							$invtotal = $row['invtotal'];
							$outstanding = $row['outstanding'];

							if ($trndate <= $VAT_Period_Lock) {
								$resultMessage .= "<b>You Cannot Edit This Transaction</b><br />Transaction Date: $trndate<br />VAT Period Closed<br />";
							}

							elseif ($outstanding <> $invtotal) {
								// get custid
								$sql = "SELECT ID FROM users WHERE custref = '$custref'";
								if ($result = $dbcnx->query($sql) AND $result->num_rows) {
									$row = $result->fetch_assoc();
									$custid = $row['ID'];
								}
								$resultMessage .= "<b>Transaction Is Allocated</b><br />Please undo the allocation first<br /><a href=\"sl-allocations.php?id=$custid\">Allocations</a><br />";
							}

							if ($ddmonth == "No") { $ddmonth = ""; }
							if ($ddmonth) { list($ddyear,$ddmonth) = explode("-",$ddmonth); }
							
						} else {
							$resultMessage = "<b>Could not get transaction header.</b><br>" . $dbcnx->error;
						}
					} else {
						$resultMessage .= "<b>Page called without transaction id</b>";
					}
				}
			}
			
			if (!$resultMessage) {
				if (!$site) {
					# print header form
					print "<p align=\"center\"><b>Edit Transaction Header</b></p>\n";
					print "<form name=\"invform\" method=\"post\" action=\"invoicing-edit-invcred.php\">\n";
					print "<input type=\"hidden\" name=\"addhead\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
					print "<input type=\"hidden\" name=\"txtype\" value=\"$txtype\" />\n";
					print "<input type=\"hidden\" name=\"txref\" value=\"$txref\" />\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Transaction Type:</b></td>\n";
					print "	<td>\n";
					if ($txtype == "I") { print "Invoice"; }
					elseif ($txtype == "C") { print "Credit Note"; }
					else { print "Unknown"; }
					print "</td>\n";
					print "</tr>\n";
					
	
					if (!$trndate) {
						$trndate = date("Y-m-d");
					}
					print "<tr>\n";
					print "	<td align=\"right\"><b>Transaction Date:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"trndate\" value=\"$trndate\" class=\"element\" />";
					if ($ertrndate) { print "&nbsp;<span class=\"redtext\">$ertrndate</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					// get all clients
					$sql = "SELECT users.username, users.compname, users.custref, users.aemails, users.level, users.location, sites.title, sites.ID, sites.ipaddress FROM `users`
							LEFT JOIN `sites` ON `location`=`ipaddress`
							WHERE `level`='2'
							ORDER BY `compname`";
					if ($result = $dbcnx->query($sql)) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Customer</b>:</td>\n";
						print "	<td><select name=\"customer\" class=\"element\">\n";
						print "		<option>Select</option>\n";
						while ($row = $result->fetch_assoc()) {
							$thiscustname  = $row['username'];
							$thiscompname  = $row['compname'];
							$thiscustref   = $row['custref'];
							$thisaemails   = $row['aemails'];
							$thissitename  = $row['title'];
							$thissiteid    = $row['ID'];
							$customer = "$thiscustref--$thiscompname--$thiscustname--$thisaemails--$thissitename--$thissiteid";
							print "		<option value=\"$customer\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$thiscompname</option>\n";
						}
						print "	</select>";
						if ($ercustomer) { print "&nbsp;<span class=\"redtext\">$ercustomer</span>"; }
						print "</td>\n";
						print "</tr>\n";
					} else {
						$resultMessage = "<b>Customers not found!</b><br>" . $dbcnx->error;
					}
					print "<tr>\n";
					print "	<td align=\"right\"><b>Email to Customer</b>:</td>\n";
					print "	<td><input type=\"checkbox\" name=\"mailit\" value=\"Yes\" /></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\">Select the month in which the DD should be collected<br>or leave blank if not to be included in DD run.</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>DD Month</b>:</td>\n";
					print "	<td><select name=\"ddmonth\" class=\"element_date\">\n";
					print "		<option></option>\n";
					$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
					for ($o=1; $o <= 12; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($ddmonth == $check) { print "SELECTED "; }
						$op = $o-1;
						print "value=\"$check\">$options[$op]</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"ddyear\" class=\"element_date\">\n";
					print "		<option></option>\n";
					$options[0] = date("Y");
					$options[1] = date("Y")+1;
					for ($o=0; $o < 2; $o++) {
						print "		<option ";
						if ($ddyear == $options[$o]) { print "SELECTED "; }
						print "value=\"$options[$o]\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					if ($erdddate) { print "&nbsp;<span class=\"redtext\">$erdddate</span>"; }
					print "	</td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></p>\n";
					print "</form>\n";
				} else {
					// print header details and lines
					print "<p align=\"center\"><b>Transaction Details</b></p>\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td valign=\"top\">\n";
	
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Type:</b></td>\n";
					print "	<td style=\"font-size:14px\">{$txtypes[$txtype]}</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Number:</b></td>\n";
					print "	<td style=\"font-size:14px\">$txref</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Date:</b></td>\n";
					print "	<td style=\"font-size:14px\">$trndate</td>\n";
					print "</tr>\n";
					print "</table>\n";
	
					print "</td>\n";
					print "<td valign=\"top\">\n";
	
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>To:</b></td>\n";
					print "	<td style=\"font-size:14px\">$compname</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Site:</b></td>\n";
					print "	<td style=\"font-size:14px\">$site</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Email:</b></td>\n";
					if ($mailit == "Yes") {
						print "	<td style=\"font-size:14px\">Yes</td>\n";
					} else {
						print "	<td style=\"font-size:14px\">No</td>\n";
					}
					print "</tr>\n";
					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>DD:</b></td>\n";
					if ($ddmonth) {
						print "	<td style=\"font-size:14px\">$ddmonth</td>\n";
					} else {
						print "	<td style=\"font-size:14px\">No</td>\n";
					}
					print "</tr>\n";
					print "</table>\n";
	
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";
	
					// print lines
					$invlines = $_SESSION['invlines'];
					print "<p align=\"center\"><b>Transaction Lines</b></p>\n";
					if ($invlines) {
						print "<table align=\"center\" cellspacing=\"4px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td><b>Type of Sale</b></td>\n";
						print "<td><b>Details</b></td>\n";
						print "<td><b>From</b></td>\n";
						print "<td><b>To</b></td>\n";
						print "<td align=\"right\"><b>Qty</b></td>\n";
						print "<td align=\"right\"><b>Each</b></td>\n";
						print "<td align=\"right\"><b>Net</b></td>\n";
						print "<td align=\"right\"><b>Vat</b></td>\n";
						print "<td align=\"right\"><b>Total</b></td>\n";
						print "<td align=\"right\">&nbsp;</td>\n";
						print "</tr>\n";
						$nettotal  = 0;
						$vattotal  = 0;
						$trntotal  = 0;
						foreach ($invlines AS $key => $line) {
							print "<tr>\n";
							print "<td>{$line['details1']}</td>\n";
							print "<td>{$line['details2']}</td>\n";
							print "<td>{$line['datefr']}</td>\n";
							print "<td>{$line['dateto']}</td>\n";
							print "<td align=\"right\">{$line['lineqty']}</td>\n";
							print "<td align=\"right\">{$line['linerate']}</td>\n";
							$linenet = $line['linenet'];
							$nettotal += $linenet;
							print "<td align=\"right\">".number_format($linenet,2)."</td>\n";
							$linevat   = $line['vattotal'];
							$linevat   = number_format($linevat,2);
							$vattotal += $linevat;
							print "<td align=\"right\">$linevat</td>\n";
							$linetotal = $line['linetotal'];
							$trntotal += $linetotal;
							$linetotal = number_format($linetotal,2);
							print "<td align=\"right\">$linetotal</td>\n";
							print "<td>[<a href=\"invoicing-edit-invcred.php?delline=1&amp;line=$key\">Del</a>]</td>\n";
							print "</tr>\n";
						}
						$nettotal = number_format($nettotal,2);
						$vattotal = number_format($vattotal,2);
						$trntotal = number_format($trntotal,2);
						print "<tr>\n";
						print "<td colspan=\"6\" align=\"right\"><b>Totals</b></td>\n";
						print "<td align=\"right\">$nettotal</td>\n";
						print "<td align=\"right\">$vattotal</td>\n";
						print "<td align=\"right\">$trntotal</td>\n";
						print "<td>&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Lines Added Yet</p>\n";
					}
					if (!$confirm) {
						// print new line form
						print "<p align=\"center\"><b>Add New Line</b></p>\n";
						print "<form name=\"lineform\" method=\"post\" action=\"invoicing-edit-invcred.php\">\n";
						print "<input type=\"hidden\" name=\"addline\" value=\"1\" />\n";
		
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
		
						// prepare types array
						$sql = "SELECT title, ancgroup FROM ancillaries";
						if ($result = $dbcnx->query($sql)) {
							while ($row = $result->fetch_assoc()) {
								$ancillary  = $row['title'];
								$ancgroup   = $row['ancgroup'];
								$reptypes[] = "$ancillary--$ancgroup";
							}
						}
						$sql = "SELECT title FROM types";
						if ($result = $dbcnx->query($sql)) {
							while ($row = $result->fetch_assoc()) {
								$reptypes[] = "{$row['title']}--Licence";
							}
						}
						$reptypes[] = "Car Parking--Licence";
						sort($reptypes);
		
						print "<tr>\n";
						print "	<td align=\"right\"><b>Type of Sale:</b></td>\n";
						print "	<td colspan=\"5\"><select name=\"repgrp\" class=\"element\">\n";
						foreach ($reptypes AS $key => $value) {
							list($thisancillary,$thisancgroup) = explode("--",$value);
							print "		<option value=\"$value\"";
							if ($value == $repgrp) {
								print " SELECTED";
							}
							print ">$thisancillary</option>\n";
						}
						print "	</select>";
						if ($errepgrp) { print "&nbsp;<span class=\"redtext\">$errepgrp</span>"; }
						print "</td>\n";
						print "</tr>\n";
						
						// get offices
						print "<tr>\n";
						print "	<td align=\"right\"><b>At Unit:</b></td>\n";
						print "	<td colspan=\"5\"><select name=\"office\" class=\"element\">\n";
						print "		<option value=\"None\">None</option>\n";
						$sql = "SELECT title FROM units WHERE siteid='$siteid'";
						if ($result = $dbcnx->query($sql)) {
							while ($row = $result->fetch_assoc()) {
								$thisunit = $row['title'];
								print "		<option value=\"$thisunit\"";
								if ($offfice == $thisunit) {
									print " SELECTED";
								}
								print ">$thisunit</option>\n";
							}
						}
						print "	</select>";
						if ($eroffice) { print "&nbsp;<span class=\"redtext\">$eroffice</span>"; }
						print "</td>\n";
						print "</tr>\n";
		
						print "	<tr>\n";
						print "		<td align=\"right\"><b>Details:</b></td>\n";
						print "		<td colspan=\"5\"><input type=\"text\" name=\"details\" value=\"$details\" maxlength=\"100\" class=\"element\" />";
						if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
						print "</td>\n";
						print "	</tr>\n";
		
						print "<tr>\n";
						print "	<td align=\"right\"><b>Date From:</b></td>\n";
						print "	<td colspan=\"5\"><input type=\"text\" id=\"datepicker\" name=\"datefr\" value=\"$datefr\" class=\"element\" /></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Date To:</b></td>\n";
						print "	<td colspan=\"5\"><input type=\"text\" id=\"datepicker2\" name=\"dateto\" value=\"$dateto\" class=\"element\" /></td>\n";
						print "</tr>\n";
		
						print "	<tr>\n";
						print "		<td align=\"right\"><b>Qty:</b></td>\n";
						print "		<td><input type=\"text\" name=\"qty\" value=\"$qty\" class=\"element_tiny\" /></td>\n";
		
						print "		<td align=\"right\"><b>Each:</b></td>\n";
						print "		<td><input type=\"text\" name=\"each\" value=\"$each\" class=\"element_tiny\" /></td>\n";
		
						print "		<td align=\"right\"><b>VAT:</b></td>\n";
						print "		<td><select name=\"vat\" class=\"element_tiny\">\n";
						print "			<option value=\"Yes\"";  if ($vat == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
						print "			<option value=\"No\"";   if ($vat == "No")  { print " SELECTED"; } print ">No</option>\n";
						print "		</select>";
						if ($erqty) { print "&nbsp;&nbsp;<span class=\"redtext\">$erqty</span>"; }
						print "	</td>\n";
						print "	</tr>\n";
		
						print "</table>\n";
						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Add Line\" /></p>\n";
						print "</form>\n";
		
						if ($invlines) {
							print "<p align=\"center\"><a href=\"invoicing-edit-invcred.php?confirm=1\">Confirm</a></p>\n";
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>\n";
						print "<p align=\"center\"><a href=\"invoicing-edit-invcred.php?confirm=Yes\">Yes Confirm</a> | <a href=\"invoicing-edit-invcred.php\">No DON'T Confirm</a></p>\n";
					}
					print "<p align=\"center\"><a href=\"invoicing-edit-invcred.php?cancel=1\">Cancel Edit</a></p>\n";
				}
	
				// print jquery date picker
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
				print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
				print "	});\n";
				print "</script>\n";
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
		print "<p align=\"center\">";
		print "<a href=\"invoicing-ledger.php\">Return</a> | ";
		print "<a href=\"invoicing-add-invcred.php\">Add Transaction</a>";
		print "</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
