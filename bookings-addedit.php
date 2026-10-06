<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
$PriceChangeAlertTo = "rajive@dephna.com,nimesh@dephna.com,brindha@dephna.com"; # Email address(es) or blank for no alert
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
$bkid     = $_REQUEST['bkid'];
$siteid   = $_REQUEST['siteid'];
$unitid   = $_REQUEST['unitid'];
$custref  = $_REQUEST['custref'];
$posted   = $_POST['posted'];
$anposted = $_POST['anposted'];
$anupdate = $_POST['anupdate'];
$andelete = $_GET['and'];
$usposted = $_POST['usposted'];
$ueposted = $_POST['ueposted'];
if ($bkid) { $thist = "Update"; } else { $thist = "New"; }
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {
		if (($siteid) && ($unitid) && ($custref)) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# ancillary update section
				if ($anposted) {
					$ancid = $_POST['ancid'];
					# get ancillary details
					$sql = "SELECT * FROM ancillaries WHERE ID='$ancid'";
					if (($anresult = $dbcnx->query($sql)) AND ($anresult->num_rows)) {
						$anrow = $anresult->fetch_assoc();
						$anctitle = $anrow['title'];
						$ancrate  = $anrow['rate'];
						$mvat     = $anrow['mvat'];
						$sql = "INSERT INTO bookings_an VALUES (null,'$bkid','$custref','$ancid','$anctitle','1','$ancrate','$mvat','Month')";
						$anresult = $dbcnx->query($sql);
						if (!$anresult) {
							$resultMessage = "Could not add record<br />" . $dbcnx->error;
						}
					}
				} elseif ($anupdate) {
					$ancid   = $_POST['ancid'];
					$ancqty  = $_POST['ancqty'];
					$ancrate = $_POST['ancrate'];
					if (!$ancqty) { $eranc = "Please give a qty"; $eanc++; }
					elseif (!is_numeric($ancqty)) { $eranc = "Qty must be numeric"; $eanc++; }
					if (!$ancrate) { $eranc = "Please give a rate"; $eanc++; }
					elseif (!is_numeric($ancrate)) { $eranc = "Rate must be numeric"; $eanc++; }
					else {
						$sql = "UPDATE bookings_an SET qty='$ancqty', rate='$ancrate' WHERE ID='$ancid'";
						$anresult = $dbcnx->query($sql);
						if (!$anresult) {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					}
				} elseif ($andelete) {
					$ancid = $_GET['ancid'];
					$sql = "DELETE FROM bookings_an WHERE ID='$ancid'";
					$anresult = $dbcnx->query($sql);
					if (!$anresult) {
						$resultMessage = "Could not delete record<br />" . $dbcnx->error;
					}
				}

				# utils update section
				if ($usposted) {
					$electricity = $_POST['electricity'];
					$gas         = $_POST['gas'];
					$hot         = $_POST['hot'];
					$cold        = $_POST['cold'];
					if ($electricity) { if (!is_numeric($electricity)) { $erelectricity = "Must be numeric"; $e++; } }
					if ($gas)         { if (!is_numeric($gas))         { $ergas = "Must be numeric"; $e++; } }
					if ($hot)         { if (!is_numeric($hot))         { $erhot = "Must be numeric"; $e++; } }
					if ($cold)        { if (!is_numeric($cold))        { $ercold = "Must be numeric"; $e++; } }
					if (!$e) {
						$utilLastUpd = time();
						$sql = "UPDATE bookings SET
								electricity='$electricity',
								gas='$gas',
								hot='$hot',
								cold='$cold',
								utilLastUpd='$utilLastUpd',
								utilLastUpdBy='{$_SESSION['username']}'
								WHERE ID='$bkid'";
						$result = $dbcnx->query($sql);
					}
				} elseif ($ueposted) {
					$electricityEnd = $_POST['electricityEnd'];
					$gasEnd         = $_POST['gasEnd'];
					$hotEnd         = $_POST['hotEnd'];
					$coldEnd        = $_POST['coldEnd'];
					if ($electricityEnd) { if (!is_numeric($electricityEnd)) { $erelectricityEnd = "Must be numeric"; $e++; } }
					if ($gasEnd)         { if (!is_numeric($gasEnd))   { $ergasEnd = "Must be numeric"; $e++; } }
					if ($hotEnd)         { if (!is_numeric($hotEnd))   { $erhotEnd = "Must be numeric"; $e++; } }
					if ($coldEnd)        { if (!is_numeric($coldEnd))  { $ercoldEnd = "Must be numeric"; $e++; } }
					if (!$e) {
						$utilEndLastUpd = time();
						$sql = "UPDATE bookings SET
								electricityEnd='$electricityEnd',
								gasEnd='$gasEnd',
								hotEnd='$hotEnd',
								coldEnd='$coldEnd',
								utilEndLastUpd='$utilEndLastUpd',
								utilEndLastUpdBy='{$_SESSION['username']}'
								WHERE ID='$bkid'";
						$result = $dbcnx->query($sql);
					}
				}

				# get site title
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				# get customer
				$sql = "SELECT ID, compname FROM users WHERE custref = '$custref'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$customer  = $row['compname'];
					$custID    = $row['ID'];
				}

				# get unit details
				$sql = "SELECT title, unittype, rate, per FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title    = $row['title'];
					$thistype = $row['unittype'];
					$bkrate   = $row['rate'];
					$bkper    = $row['per'];

					if (($unittype == "Office") OR
							($unittype == "Office V") OR
							($unittype == "Kitchen") OR
							($unittype == "Kitchen V")
						 ) {
						$hsriskApplies = 1;
					}

					# get booking status
					$sql = "SELECT custid FROM bookings WHERE siteid=$siteid AND unitid='$unitid' AND status='on'";
					if (($bresult = $dbcnx->query($sql)) AND ($bresult->num_rows)) {
						$brow = $bresult->fetch_assoc();
						$bookedcust = $brow['custid'];
						if (($custref) && ($bookedcust == $custref)) { $booked = 0; }
						else { $booked = 1; }
					}

					# print heading
					print "<p align=\"center\" class=\"heading\">$thist Unit Booking</p>\n";
					print "<p align=\"center\" class=\"heading\">$sitetitle - $title - $customer</p>\n";
					if ($links) { print "<p align=\"center\">Linked to $links</p>\n"; }

					if ($posted) {
						# error checking
						$thistype  = strip_tags($_POST['thistype']);
						$bkrate    = strip_tags($_POST['bkrate']);
						$oldbkrate = strip_tags($_POST['oldbkrate']);
						$bkper     = strip_tags($_POST['bkper']);
						$leadmonth = strip_tags($_POST['leadmonth']);
						$notes     = strip_tags($_POST['notes']);
						$otype     = strip_tags($_POST['otype']);
						$risk      = strip_tags($_POST['risk']);
						$prfrom = 0;
						$prto   = 0;
						$fip	= 0;

						if (!$otype) { $erotype = "Enter operation type"; $e++; }
						if (!$risk) { $errisk = "Enter operation risk"; $e++; }

						$sday      = $_POST['sday'];
						$smonth    = $_POST['smonth'];
						$syear     = $_POST['syear'];
						if (($sday) || ($smonth) || ($syear)) {
							if (!checkdate($smonth,$sday,intval($syear))) { $ersdate = "Invalid Date"; $e++; }
						}

						$eday      = $_POST['eday'];
						$emonth    = $_POST['emonth'];
						$eyear     = $_POST['eyear'];
						if (($eday) || ($emonth) || ($eyear)) {
							if (!checkdate($emonth,$eday,$eyear)) { $eredate = "Invalid Date"; $e++; }
						}

						if (!$bkrate) { $errate = "Please give a rate"; $e++; }
						elseif (!is_numeric($bkrate)) { $errate = "Rate must be numeric"; $e++; }

						if (($bkper == "Quarterly") || ($bkper == "Yearly")) {
							if ($leadmonth == 0) {
								$erleadmonth = "Select a lead month"; $e++;
							}
							elseif ($bkper == "Quarterly") {
								if ($leadmonth > 3) {
									$erleadmonth = "Quarterly is Jan, Feb or Mar"; $e++;
								}
							}
						} else {
							if ($_POST['pmonth']) {
								$pmonth = $_POST['pmonth'];
								$pyear  = $_POST['pyear'];
								$pdayfr = $_POST['pdayfr'];
								$pdayto = $_POST['pdayto'];
								if (!checkdate($pmonth,$pdayfr,$pyear)) { $erpdate = "Invalid Date"; $e++; }
								elseif (!checkdate($pmonth,$pdayto,$pyear)) { $erpdate = "Invalid Date"; $e++; }
								elseif ($pdayfr > $pdayto) { $erpdate = "From greater than To"; $e++; }
								else {
									$prfrom = $pyear."/".$pmonth."/".$pdayfr; $prfrom = strtotime("$prfrom");
									$prto   = $pyear."/".$pmonth."/".$pdayto; $prto   = strtotime("$prto");
								}
							}
						}

						$fim  = $_POST['fim'];
						$fiy  = $_POST['fiy'];
						if (($fim) && ($fiy)) {
							$fip = strtotime("$fiy/$fim/01");
						}

						if (!$e) {
							# post to database
							$thistype   = addslashes($thistype);
							$notes      = addslashes($notes);
							$otype      = addslashes($otype);

							if ($sday) {
								$datefrom = $syear."/".$smonth."/".$sday;
								$datefrom = strtotime("$datefrom");
							} else {
								$datefrom = 0;
							}
							if ($eday) {
								$dateto   = $eyear."/".$emonth."/".$eday;
								$dateto   = strtotime("$dateto");
							} else {
								$dateto = 0;
							}

							if ($bkid) {
								$sql = "UPDATE bookings SET
								datefrom='$datefrom',
								dateto='$dateto',
								rate='$bkrate',
								per='$bkper',
								lead='$leadmonth',
								prfrom='$prfrom',
								prto='$prto',
								notes='$notes',
								fip='$fip',
								TypeOfOpperation='$otype',
								Risk='$risk'
								WHERE ID='$bkid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage = "Record successfuly updated";
									$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitetitle - $title','Bookings','$now')");
									if (($PriceChangeAlertTo) AND ($bkrate != $oldbkrate)) {
										# notify rate change

										$subject = "Price Changed In Bookings";
										$from = "Robot <accounts@dephna.com>";
										$mime_boundary = md5(uniqid(time()));

										$headers  = "From: $from\r\n";
										$headers .= "Reply-To: $from\r\n";
										$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
										$headers .= "MIME-Version: 1.0\r\n";
										$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

										$message = "--".$mime_boundary."\r\n";
										$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
										$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

										$message .= "<p><b>Price Changed In Bookings</b></p>\n";
										$message .= "<table>\n";
										$message .= "<tr><td>Site: </td><td>$sitetitle</td></tr>\n";
										$message .= "<tr><td>Unit: </td><td>$title</td></tr>\n";
										$message .= "<tr><td>Customer: </td><td>$customer</td></tr>\n";
										$message .= "<tr><td>Price: </td><td>$oldbkrate</td></tr>\n";
										$message .= "<tr><td>Changed To: </td><td>$bkrate</td></tr>\n";
										$message .= "<tr><td>By: </td><td>{$_SESSION['username']}</td></tr>\n";
										$message .= "</table>\n";

										$message .= "\r\n\r\n";
										$message .= "--".$mime_boundary."--";
										mail($PriceChangeAlertTo, $subject, $message, $headers);

									}

								} else {
									$resultMessage = "Could not update record<br />" . $dbcnx->error;
								}
							} else {
								$now = time();
								$sql = "INSERT INTO bookings VALUES (
								null,
								'$siteid',
								'$unitid',
								'$thistype',
								'',
								'$custref',
								'0',
								'$datefrom',
								'$dateto',
								'',
								'$bkrate',
								'$bkper',
								'$leadmonth',
								'$prfrom',
								'$prto',
								'{$_SESSION['user']}',
								'$now',
								'$notes',
								'$fip',
								'$otype',
								'$risk',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'',
								'0',
								''
								)";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage = "Booking successfuly added";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitetitle - $title','Bookings','$now')");
								} else {
									$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
								}
							}
						}
					} elseif ($bkid) {
						# get existing booking details
						$sql = "SELECT * FROM bookings WHERE ID = '$bkid'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$datefrom  = $row['datefrom'];
							$dateto    = $row['dateto'];
							$bkrate    = $row['rate'];
							$bkper     = $row['per'];
							$leadmonth = $row['lead'];
							$notes     = $row['notes'];
							$prfrom    = $row['prfrom'];
							$prto      = $row['prto'];
							$fip       = $row['fip'];
							$otype     = $row['TypeOfOpperation'];
							$risk      = $row['Risk'];
							$electricity = $row['electricity'];
							$gas       = $row['gas'];
							$hot       = $row['hot'];
							$cold      = $row['cold'];
							$electricityEnd = $row['electricityEnd'];
							$gasEnd    = $row['gasEnd'];
							$hotEnd    = $row['hotEnd'];
							$coldEnd   = $row['coldEnd'];
							$utilLastUpd        = $row['utilLastUpd'];
							$utilLastUpdBy      = $row['utilLastUpdBy'];
							$utilEndLastUpd     = $row['utilEndLastUpd'];
							$utilEndLastUpdBy   = $row['utilEndLastUpdBy'];
							if ($fip) { $fim = date("n",$fip); $fiy = date("Y",$fip); }
							if ($datefrom) { $sday   = date("d",$datefrom); $smonth = date("m",$datefrom); $syear  = date("Y",$datefrom); }
							if ($dateto) {   $eday   = date("d",$dateto);   $emonth = date("m",$dateto);   $eyear  = date("Y",$dateto); }
							if ($prfrom) {   $pdayfr = date("d",$prfrom);   $pmonth = date("m",$prfrom);   $pyear  = date("Y",$prfrom); }
							if ($prto)   {   $pdayto = date("d",$prto); }
							if ($utilLastUpd) { $utilLastUpd = date("d/m/Y H:i",$utilLastUpd); }
							if ($utilEndLastUpd) { $utilEndLastUpd = date("d/m/Y H:i",$utilEndLastUpd); }
							$oldbkrate = $bkrate;
						} else {
							$resultMessage = "Booking not found!<br />" . $dbcnx->error;
						}
					}

					if ((!$resultMessage) || ($e)) {

						# print the form
						print "<form name=\"addform\" method=\"post\" action=\"bookings-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
						print "<input type=\"hidden\" name=\"thistype\" value=\"$thistype\" />\n";
						print "<input type=\"hidden\" name=\"oldbkrate\" value=\"$oldbkrate\" />\n";
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Contract Start Date</td>\n";
						print "	<td>\n";
						print "	<select name=\"sday\" class=\"element_date\">\n";
						print "		<option></option>\n";
						for ($o=1; $o <= 31; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($sday == $check) { print "SELECTED "; }
							print "value=\"$check\">$check</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"smonth\" class=\"element_date\">\n";
						print "		<option></option>\n";
						$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
						for ($o=1; $o <= 12; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($smonth == $check) { print "SELECTED "; }
							$op = $o-1;
							print "value=\"$check\">$options[$op]</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"syear\" class=\"element_date\">\n";
						print "		<option></option>\n";
						$options[0] = date("Y")-2;
						$options[1] = date("Y")-1;
						$options[2] = date("Y");
						$options[3] = date("Y")+1;
						for ($o=0; $o < 4; $o++) {
							print "		<option ";
							if ($syear == $options[$o]) { print "SELECTED "; }
							print "value=\"$options[$o]\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						if ($ersdate) { print "&nbsp;<span class=\"redtext\">$ersdate</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Contract End Date</td>\n";
						print "	<td>\n";
						print "	<select name=\"eday\" class=\"element_date\">\n";
						print "		<option></option>\n";
						for ($o=1; $o <= 31; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($eday == $check) { print "SELECTED "; }
							print "value=\"$check\">$check</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"emonth\" class=\"element_date\">\n";
						print "		<option></option>\n";
						$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
						for ($o=1; $o <= 12; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($emonth == $check) { print "SELECTED "; }
							$op = $o-1;
							print "value=\"$check\">$options[$op]</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"eyear\" class=\"element_date\">\n";
						print "		<option></option>\n";
						$options[0] = date("Y");
						$options[1] = date("Y")+1;
						for ($o=0; $o < 2; $o++) {
							print "		<option ";
							if ($eyear == $options[$o]) { print "SELECTED "; }
							print "value=\"$options[$o]\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						if ($eredate) { print "&nbsp;<span class=\"redtext\">$eredate</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Contract Rate:</td>\n";
						print "	<td><input name=\"bkrate\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$bkrate\" />";
						if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">First Invoicing Period:</td>\n";
						print "	<td>\n";
						print "	<select name=\"fim\" class=\"element_date\">\n";
						$options = array("n/a","January","February","March","April","May","June","July","August","September","October","November","December");
						print "		<option></option>\n";
						for ($o=1; $o <= 12; $o++) {
							print "		<option ";
							if ($fim == $o) { print "SELECTED "; }
							print "value=\"$o\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"fiy\" class=\"element_date\">\n";
						print "		<option></option>\n";
						$options[0] = date("Y");
						$options[1] = date("Y")+1;
						for ($o=0; $o < 2; $o++) {
							print "		<option ";
							if ($fiy == $options[$o]) { print "SELECTED "; }
							print "value=\"$options[$o]\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						if ($erfip) { print "&nbsp;<span class=\"redtext\">$erfip</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
						print "</tr>\n";

						# check for deposit increments
						$sql = "SELECT * FROM `deposits` WHERE `bkid`='$bkid' AND `custref`='$custref' ORDER BY `year`,`month`";
						$dresult = $dbcnx->query($sql);
						if (($dresult) && ($dresult->num_rows)) {
							print "<tr>\n";
							print "	<td>&nbsp;</td>\n";
							print "	<td><b>Deposit Increments</b></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td>&nbsp;</td>\n";
							print "	<td>\n";
							print "		<table>\n";
							while ($drow = $dresult->fetch_assoc()) {
								$thisyear   = $drow['year'];
								$thismonth  = $drow['month'];
								$thisamount = $drow['amount'];
								print "			<tr>\n";
								print "				<td>$thismonth/$thisyear</td>\n";
								print "				<td align=\"right\">$thisamount</td>\n";
								print "			</tr>\n";
							}
							print "		</table>\n";
							print "	</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td valign=\"top\" align=\"right\"><b>Billing Period</b></td>\n";
						print "	<td>If not monthly you must select a billing month.<br />If Quarterly, select the first of the 4 billing months.<br />If Yearly, select the only billing month of the year.</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Select Period:</td>\n";
						print "	<td><select name=\"bkper\" class=\"element\">\n";
						print "		<option value=\"Monthly\"";   if ($bkper == "Monthly")   { print " SELECTED"; } print ">Monthly</option>\n";
						print "		<option value=\"Quarterly\""; if ($bkper == "Quarterly") { print " SELECTED"; } print ">Quarterly</option>\n";
						print "		<option value=\"Yearly\"";    if ($bkper == "Yearly")    { print " SELECTED"; } print ">Yearly</option>\n";
						print "		<option value=\"0\"";         if ($bkper == "0")         { print " SELECTED"; } print ">None</option>\n";
						print "	</select>";
						if ($erper) { print "&nbsp;<span class=\"redtext\">$erper</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Billing Month:</td>\n";
						print "	<td>\n";
						print "	<select name=\"leadmonth\" class=\"element_date\">\n";
						$options = array("n/a","January","February","March","April","May","June","July","August","September","October","November","December");
						for ($o=0; $o <= 12; $o++) {
							print "		<option ";
							if ($leadmonth == $o) { print "SELECTED "; }
							print "value=\"$o\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						if ($erleadmonth) { print "&nbsp;<span class=\"redtext\">$erleadmonth</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td valign=\"top\" align=\"right\"><b>Pro-Rata Override</b></td>\n";
						print "	<td>Monthly accounts only.<br />Enter Month, Year, Day From &Amp; Day To.<br />The systen will calculate a pro-rata charge.<br />The charge will be applied to contract rate and ancillaries.<br />The override will be deleted after being invoiced.</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Pro-Rata Period:</td>\n";
						print "	<td>\n";
						print "	<select name=\"pmonth\" class=\"element_date\">\n";
						$options = array("n/a","January","February","March","April","May","June","July","August","September","October","November","December");
						print "		<option></option>\n";
						for ($o=1; $o <= 12; $o++) {
							print "		<option ";
							$oo = sprintf("%02s",$o);
							if ($pmonth == $oo) { print "SELECTED "; }
							print "value=\"$oo\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"pyear\" class=\"element_date\">\n";
						print "		<option></option>\n";
						$options[0] = date("Y");
						$options[1] = date("Y")+1;
						for ($o=0; $o < 2; $o++) {
							print "		<option ";
							if ($pyear == $options[$o]) { print "SELECTED "; }
							print "value=\"$options[$o]\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						print " Fr: \n";
						print "	<select name=\"pdayfr\" class=\"element_date\">\n";
						print "		<option></option>\n";
						for ($o=1; $o <= 31; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($pdayfr == $check) { print "SELECTED "; }
							print "value=\"$check\">$check</option>\n";
						}
						print "	</select>\n";
						print " To: \n";
						print "	<select name=\"pdayto\" class=\"element_date\">\n";
						print "		<option></option>\n";
						for ($o=1; $o <= 31; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($pdayto == $check) { print "SELECTED "; }
							print "value=\"$check\">$check</option>\n";
						}
						print "	</select>\n";
						if ($erpdate) { print "&nbsp;<span class=\"redtext\">$erpdate</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "  <td valign=\"middle\" align=\"right\">Type Of Operation:</td>\n";
						print "  <td><input name=\"otype\" type=\"text\" class=\"element\" size=\"50\" maxlength=\"200\" value=\"$otype\" />\n";
						if ($erotype) { print "&nbsp;<span class=\"redtext\">$erotype</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Risk:</td>\n";
						print "	<td><select name=\"risk\" class=\"element\" style=\"width:140px\">\n";
						print "		<option value=\"Low\"";       if ($risk == "Low")       { print " SELECTED"; } print ">Low</option>\n";
						print "		<option value=\"Medium\"";    if ($risk == "Medium")    { print " SELECTED"; } print ">Medium</option>\n";
						print "		<option value=\"High\"";      if ($risk == "High")      { print " SELECTED"; } print ">High</option>\n";
						print "		<option value=\"N/A\"";       if ($risk == "N/A")       { print " SELECTED"; } print ">N/A</option>\n";
						print "		<option value=\"No Change\""; if ($risk == "No Change") { print " SELECTED"; } print ">No Change</option>\n";
						print "	</select>";
						if ($errisk) { print "&nbsp;<span class=\"redtext\">$errisk</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">Notes: "; if ($erNotes) { print "&nbsp;&nbsp;<span class=\"redtext\">$erNotes</span>"; } print "<br />";
						print "<textarea name=\"notes\" wrap=\"virtual\" cols=\"100\" rows=\"3\" class=\"element_box\">$notes</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

						if ($bkid) {

							print "<p align=\"center\"><img src=\"images/blue.gif\" width=\"500px\" height=\"2px\" alt=\"\" /></p>\n";

							print "<p align=\"center\" class=\"heading\">Ancillary Charges</p>\n";

							# get current charges
							$sql = "SELECT * FROM bookings_an WHERE bkid='$bkid' ORDER BY anctitle";
							$anresult = $dbcnx->query($sql);
							if (($anresult) && ($anresult->num_rows)) {
								if ($eranc) {
									print "<p align=\"center\"><span class=\"redtext\">$eranc</span></p>\n";
								}
								print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
								Print "<tr>\n";
								Print "	<td class=\"colHead\">Charge</td>\n";
								Print "	<td class=\"colHead\" align=\"center\">Qty</td>\n";
								Print "	<td class=\"colHead\" align=\"center\">Each</td>\n";
								Print "	<td class=\"colHead\" align=\"center\">VAT</td>\n";
								Print "	<td class=\"colHead\" align=\"center\">Change Qty / Each</td>\n";
								Print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
								Print "</tr>\n";
								while ($anrow = $anresult->fetch_assoc()) {
									$ancid    = $anrow['ID'];
									$anctitle = $anrow['anctitle'];
									$ancqty   = $anrow['qty'];
									$ancrate  = $anrow['rate'];
									$ancvat   = $anrow['vat'];
									print "<tr>\n";
									print "	<td class=\"col\">$anctitle</td>\n";
									print "	<td class=\"col\">$ancqty</td>\n";
									print "	<td class=\"col\" align=\"right\">" . number_format($ancrate,2) . "</td>\n";
									print "	<td class=\"col\">$ancvat</td>\n";
									print " <td class=\"colElement\">";
									print "<form name=\"updancform\" method=\"post\" action=\"bookings-addedit.php\">";
									print "<input type=\"hidden\" name=\"anupdate\" value=\"1\" />";
									print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />";
									print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />";
									print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />";
									print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />";
									print "<input type=\"hidden\" name=\"ancid\" value=\"$ancid\" />";
									print "<input type=\"hidden\" name=\"thistype\" value=\"$thistype\" />\n";
									print "&nbsp;<input name=\"ancqty\" type=\"text\" class=\"element_nb2\" maxlength=\"4\" value=\"$ancqty\" />&nbsp;";
									print "&nbsp;<input name=\"ancrate\" type=\"text\" class=\"element_nb\" maxlength=\"10\" value=\"$ancrate\" />";
									print "<input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Update\" />";
									print "</form>\n";
									print "</td>\n";
									print "	<td class=\"colEnd\" align=\"center\"><a href=\"bookings-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;bkid=$bkid&amp;ancid=$ancid&amp;and=1\">Delete</a></td>\n";
									print "</tr>\n";
								}
								print "</table>\n";
								print "<p align=\"center\">Don't forget to click Update if you make changes above</p>\n";
							} else {
								print "<p align=\"center\">None Found</p>\n";
							}

							# add ancillary charge
							$sql = "SELECT ID, title FROM ancillaries ORDER BY title";
							$anresult = $dbcnx->query($sql);
							if (($anresult) && ($anresult->num_rows)) {
								print "<form name=\"addancform\" method=\"post\" action=\"bookings-addedit.php\">\n";
								print "<input type=\"hidden\" name=\"anposted\" value=\"1\" />\n";
								print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
								print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
								print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
								print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
								print "<input type=\"hidden\" name=\"thistype\" value=\"$thistype\" />\n";
								print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
								print "<tr>\n";
								print "	<td align=\"right\"><b>Add New Charge</b>:</td>\n";
								print "	<td><select name=\"ancid\" class=\"element\">\n";
								while ($anrow = $anresult->fetch_assoc()) {
									$ancid    = $anrow['ID'];
									$anctitle = $anrow['title'];
									if ($anctitle !== strstr($anctitle, 'Line Rental')) {
										print "		<option value=\"$ancid\">$anctitle</option>\n";
									}
								}
								print "	</select>";
								print "</td>\n";
								print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
								print "</tr>\n";
								print "</table>\n";
								print "</form>\n";
							}

							print "<p align=\"center\"><img src=\"images/blue.gif\" width=\"500px\" height=\"2px\" alt=\"\" /></p>\n";

							print "<p align=\"center\" class=\"heading\">Utilities</p>\n";

							print "<form name=\"utilstart\" method=\"post\" action=\"bookings-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"usposted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
							print "<input type=\"hidden\" name=\"thistype\" value=\"$thistype\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><b>Utilities Start</b></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Electricity Start:</td>\n";
							print "<td><input type=\"text\" name=\"electricity\" class=\"element_small\" value=\"$electricity\" />\n";
							if ($erelectricity) { print "&nbsp;<span class=\"redtext\">$erelectricity</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Gas Start:</td>\n";
							print "<td><input type=\"text\" name=\"gas\" class=\"element_small\" value=\"$gas\" />\n";
							if ($ergas) { print "&nbsp;<span class=\"redtext\">$ergas</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Hot Start:</td>\n";
							print "<td><input type=\"text\" name=\"hot\" class=\"element_small\" value=\"$hot\" />\n";
							if ($erhot) { print "&nbsp;<span class=\"redtext\">$erhot</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Cold Start:</td>\n";
							print "<td><input type=\"text\" name=\"cold\" class=\"element_small\" value=\"$cold\" />\n";
							if ($ercold) { print "&nbsp;<span class=\"redtext\">$ercold</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
							print "</tr>\n";

							if ($utilLastUpd) {
								print "<tr>\n";
								print "	<td colspan=\"2\" align=\"center\">Last Updated $utilLastUpd By $utilLastUpdBy</td>\n";
								print "</tr>\n";
							}

							print "</table>\n";
							print "</form>\n";

							print "<form name=\"utilend\" method=\"post\" action=\"bookings-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"ueposted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
							print "<input type=\"hidden\" name=\"thistype\" value=\"$thistype\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><b>Utilities End</b></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Electricity End:</td>\n";
							print "<td><input type=\"text\" name=\"electricityEnd\" class=\"element_small\" value=\"$electricityEnd\" />\n";
							if ($erelectricityEnd) { print "&nbsp;<span class=\"redtext\">$erelectricityEnd</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Gas End:</td>\n";
							print "<td><input type=\"text\" name=\"gasEnd\" class=\"element_small\" value=\"$gasEnd\" />\n";
							if ($ergasEnd) { print "&nbsp;<span class=\"redtext\">$ergasEnd</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Hot End:</td>\n";
							print "<td><input type=\"text\" name=\"hotEnd\" class=\"element_small\" value=\"$hotEnd\" />\n";
							if ($erhotEnd) { print "&nbsp;<span class=\"redtext\">$erhotEnd</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\">Cold End:</td>\n";
							print "<td><input type=\"text\" name=\"coldEnd\" class=\"element_small\" value=\"$coldEnd\" />\n";
							if ($ercoldEnd) { print "&nbsp;<span class=\"redtext\">$ercoldEnd</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
							print "</tr>\n";

							if ($utilEndLastUpd) {
								print "<tr>\n";
								print "	<td colspan=\"2\" align=\"center\">Last Updated $utilEndLastUpd By $utilEndLastUpdBy</td>\n";
								print "</tr>\n";
							}

							print "</table>\n";
							print "</form>\n";

						}
					}
				} else {
					print "<p align=\"center\">Unit not found!</p>\n";
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without customer, site or unit id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if (($siteid) && ($unitid)) {
			print "<p align=\"center\"><a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref\">Unit Details</a>&nbsp;|&nbsp;<a href=\"customer-view.php?id=$custID\">Customer</a></p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-units-view.php?siteid=$siteid&amp;custref=$custref\">Units</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in as an administrator</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
