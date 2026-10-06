<?php
	$anposted = $_POST['anposted'];
	$anupdate = $_POST['anupdate'];
	$andelete = $_GET['andelete'];
	$vid = $_REQUEST['vid'];

	# ancillary update section
	if ($anposted) {
		$ancid = $_POST['ancid'];
		# get vancillary details
		$sql = "SELECT * FROM ancillaries WHERE ID='$ancid'";
		$anresult = $dbcnx->query($sql);
		if (($anresult) && ($anresult->num_rows)) {
			$anrow = $anresult->fetch_assoc();
			$anctitle = $anrow['title'];
			$ancrate  = $anrow['rate'];
			$mvat     = $anrow['mvat'];
			$sql = "INSERT INTO bkm_vanc VALUES (null,'$bkid','$vid','$ancid','$anctitle','1','$ancrate','$mvat','Month')";
			$anresult = $dbcnx->query($sql);
			if (!$anresult) {
				$resultMessage = "<b>Could not add anc record</b><br />" . $dbcnx->error;
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
			$sql = "UPDATE bkm_vanc SET qty='$ancqty', rate='$ancrate' WHERE ID='$ancid'";
			$anresult = $dbcnx->query($sql);
			if (!$anresult) {
				$resultMessage = "<b>Could not update anc record</b><br />" . $dbcnx->error;
			}
		}
	} elseif ($andelete) {
		$ancid = $_GET['ancid'];
		$sql = "DELETE FROM bkm_vanc WHERE ID='$ancid'";
		$anresult = $dbcnx->query($sql);
		if (!$anresult) {
			$resultMessage = "<b>Could not delete anc record</b><br />" . $dbcnx->error;
		}
	}



	# print heading
	print "<p align=\"center\" class=\"heading\">$sitename - $compname</p>\n";

	if ($posted) {
		# error checking
		$package   = $_POST['package'];
		$bkrate    = strip_tags($_POST['bkrate']);
		$bkper     = strip_tags($_POST['bkper']);
		$leadmonth = strip_tags($_POST['leadmonth']);
		$bkvat     = $_POST['bkvat'];
		$notes     = strip_tags($_POST['notes']);
		$depnotes  = strip_tags($_POST['depnotes']);

		$depvirttotal = $_POST['depvirttotal']; if ($depvirttotal) { if (!is_numeric($depvirttotal)) { $erdepvirttotal = "Must be numeric"; $e++; } }
		$depcommtotal = $_POST['depcommtotal']; if ($depcommtotal) { if (!is_numeric($depcommtotal)) { $erdepcommtotal = "Must be numeric"; $e++; } }
		if (!$e) {
			$deptotal = $depvirttotal + $depcommtotal;
			if ($deptotal) {
				$depincmonths  = $_POST['depincmonths'];
				$depincamounts = $_POST['depincamounts'];
				if ($depincamounts) {
					$increments = array();
					foreach($depincmonths AS $key => $value) {
						$increments[] = $value. "-" . $depincamounts[$key];
						$inccheck += $depincamounts[$key];
					}
					if ($inccheck) {
						if ($inccheck != $deptotal) { $erdepincamounts = "Deposit increments do not equal total deposits"; $e++; }
					}
				}
			}
		}
		$depnotes  = $_POST['depnotes'];
		$bkcntrper = $_POST['bkcntrper'];
		$sday      = $_POST['sday'];
		$smonth    = $_POST['smonth'];
		$syear     = $_POST['syear'];
		if (($sday) || ($smonth) || ($syear)) {
			if (!checkdate($smonth,$sday,$syear)) {
				$ersdate = "Invalid Date"; $e++;
			} else {
				if ($bkper == "Monthly") {
					if ($sday > 1) {
						# calculate pro rata override
						$pdayfr = $sday;
						$pyear  = $syear;
						$pmonth = $smonth+1;
						if ($pmonth > 12) {
							$pmonth = "01";
							$pyear++;
						}
						$pdayto = date("t",strtotime("$pyear-$pmonth-$pdayfr"));
						$prfrom = $pyear."/".$pmonth."/".$pdayfr; $prfrom = strtotime("$prfrom");
						$prto   = $pyear."/".$pmonth."/".$pdayto; $prto   = strtotime("$prto");
					}
				}
			}
			$fim = $_POST['fim'];
			$fiy = $_POST['fiy'];
			if (($fim) && ($fiy)) {
				$fip = strtotime("$fiy/$fim/01");
			} else {
				# calculate first invoicing month
				$fiy = $syear;
				$fim = $smonth+1;
				if ($fim > 12) { $fim = "01"; $fiy++; }
				if ($sday > 1) { $fim++; if ($fim > 12) { $fim = "01"; $fiy++; }  }
				$fip = strtotime("$fiy/$fim/01");
			}
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
		}

		if (!$e) {
			# post to database
			list($pkid,$pkname) = explode("--",$package);
			$thistype   = addslashes($thistype);
			$notes      = addslashes($notes);
			$depnotes   = addslashes($depnotes);
			if ($increments) { $increments = implode("|",$increments); }

			if ($sday) {
				$datefrom = $syear."/".$smonth."/".$sday;
				$datefrom = strtotime("$datefrom");
			}
			if ($eday) {
				$dateto   = $eyear."/".$emonth."/".$eday;
				$dateto   = strtotime("$dateto");
			}

			if ($vid) {
				$sql = "UPDATE bkm_virtuals SET
				pkid='$pkid',
				pkname='$pkname',
				datefrom='$datefrom',
				dateto='$dateto',
				rate='$bkrate',
				per='$bkper',
				cntrper='$bkcntrper',
				lead='$leadmonth',
				vat='$bkvat',
				prfrom='$prfrom',
				prto='$prto',
				notes='$notes',
				depnotes='$depnotes',
				fip='$fip',
				deptotal='$deptotal',
				depvirttotal='$depvirttotal',
				depcommtotal='$depcommtotal',
				increments='$increments'
				WHERE ID='$vid'";
				$result = $dbcnx->query($sql);
				if ($result) {
					$resultMessage = "<b>Record successfully updated</b>";
				} else {
					$resultMessage = "<b>Could not update record</b><br />" . $dbcnx->error;
				}
			} else {
				$now = time();

				// create prov booking if not yet exists (must be first virtual in this booking)
				if (!$bkid) {
					$sql = "INSERT INTO bk_manager VALUES (
					null,
					'{$_SESSION['user']}',
					'{$_SESSION['username']}',
					'$now',
					'$custref',
					'$compname',
					'$siteid',
					'$sitename',
					'$bktype',
					'$newcust',
					'',
					'',
					''
					)";
					$result = $dbcnx->query($sql);
					$bkid = $dbcnx->insert_id;
					$_SESSION['siteid']   = "";
					$_SESSION['sitename'] = "";
					$_SESSION['bktype'] = "";
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname','$page','$now')");
				}

				$sql = "INSERT INTO bkm_virtuals VALUES (
				null,
				'$bkid',
				'$pkid',
				'$pkname',
				'$datefrom',
				'$dateto',
				'',
				'$bkrate',
				'$bkper',
				'$bkcntrper',
				'$leadmonth',
				'$bkvat',
				'$prfrom',
				'$prto',
				'{$_SESSION['user']}',
				'$now',
				'$notes',
				'$depnotes',
				'$fip',
				'$deptotal',
				'$depvirttotal',
				'$depcommtotal',
				'$increments'
				)";
				$result = $dbcnx->query($sql);
				if ($result) {
					$vid = $dbcnx->insert_id;
					$resultMessage = "<b>Virtual successfully added</b>";
				} else {
					$resultMessage = "<b>Could not add new record to database</b><br />" . $dbcnx->error;
				}
			}
		}
	}

	if ($vid) {
		# get existing booked virtual
		$sql = "SELECT * FROM bkm_virtuals WHERE ID = '$vid'";
		$result = $dbcnx->query($sql);
		if (($result) && ($result->num_rows)) {
			$row = $result->fetch_assoc();
			$pkid         = $row['pkid'];
			$pkname       = $row['pkname'];
			$datefrom     = $row['datefrom'];
			$dateto       = $row['dateto'];
			$bkrate       = $row['rate'];
			$bkcntrper    = $row['cntrper'];
			$bkper        = $row['per'];
			$leadmonth    = $row['lead'];
			$bkvat        = $row['vat'];
			$notes        = $row['notes'];
			$depnotes     = $row['depnotes'];
			$prfrom       = $row['prfrom'];
			$prto         = $row['prto'];
			$fip          = $row['fip'];
			$deptotal     = $row['deptotal'];
			$depvirttotal = $row['depvirttotal'];
			$depcommtotal = $row['depcommtotal'];
			$increments   = $row['increments'];
			if ($fip) { $fim = date("n",$fip); $fiy = date("Y",$fip); }
			if ($datefrom) { $sday   = date("d",$datefrom); $smonth = date("m",$datefrom); $syear = date("Y",$datefrom); }
			if ($dateto) {   $eday   = date("d",$dateto);   $emonth = date("m",$dateto);   $eyear = date("Y",$dateto); }
			if ($prfrom) {   $pdayfr = date("d",$prfrom);   $pmonth = date("m",$prfrom);   $pyear = date("Y",$prfrom); }
			if ($prto)   {   $pdayto = date("d",$prto); }
		} else {
			$resultMessage = "Booking not found!<br />" . $dbcnx->error;
		}
	}

	# report to screen
	if ($resultMessage) {
		print "<script	language=\"javascript\" type=\"text/javascript\">\n";
		print "	$(document).ready(function() {\n";
		print "		$(\".quick-alert\").delay(3000).fadeOut(\"slow\");\n";
		print "	});\n";
		print "</script>\n";
		print "<p align=\"center\"><span class=\"quick-alert\">&nbsp; $resultMessage &nbsp;</span></p>\n";
		$resultMessage = "";
	}

	if ($vid) {
		print "<p align=\"center\"><a href=\"#anc\">Add Ancillaries</a></p>\n";
	}

	# print the form
	print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
	print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
	print "<input type=\"hidden\" name=\"module\" value=\"virtuals\" />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />\n";
	print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
	print "<input type=\"hidden\" name=\"vid\" value=\"$vid\" />\n";
	print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

	# get packages
	$sql = "SELECT ID, title FROM virtuals ORDER BY ID";
	$vpsresult = $dbcnx->query($sql);

	print "<tr>\n";
	print "	<td align=\"right\">Package:</td>\n";
	print "	<td><select name=\"package\" class=\"element\">\n";
	while ($vpsrow = $vpsresult->fetch_assoc()) {
		$thispkid = $vpsrow['ID'];
		$pkname   = $vpsrow['title'];
		print "		<option value=\"$thispkid--$pkname\"";
		if ($thispkid == $pkid) {
			print " SELECTED";
		}
		print ">$pkname</option>\n";
	}
	print "	</select>";
	if ($erpackage) { print "&nbsp;<span class=\"redtext\">$erpackage</span>"; }
	print "</td>\n";
	print "</tr>\n";

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

	$cpoptions = array("Ongoing","Monthly","Quarterly","Yearly","None");
	print "<tr>\n";
	print "	<td align=\"right\">Contract Period:</td>\n";
	print "	<td>\n";
	print "		<select name=\"bkcntrper\" class=\"element\">\n";
	foreach($cpoptions AS $value) {
		print "			<option value=\"$value\""; if ($value == $bkcntrper) { print " SELECTED"; } print ">$value</option>\n";
	}
	print "		</select>\n";
	print "	</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td align=\"right\">Add VAT:</td>\n";
	print "	<td><select name=\"bkvat\" class=\"element\">";
	print "		<option "; if ($bkvat == "Yes") { print "SELECTED "; } print "value=\"Yes\">Yes</option>";
	print "		<option "; if ($bkvat == "No")  { print "SELECTED "; } print "value=\"No\">No</option>";
	print "	</select>";
	print "	</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td valign=\"top\" align=\"right\"><b>Deposit</b></td>\n";
	print "	<td>Enter the total value for the deposit. If it is to be<br />staggered over several months, please click<br />'Show/Hide' and fill in the increments you require.</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "  <td valign=\"middle\" align=\"right\">Virtual Total:</td>\n";
	print "  <td><input name=\"depvirttotal\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$depvirttotal\" />\n";
	if ($erdepvirttotal) { print "&nbsp;<span class=\"redtext\">$erdepvirttotal</span>"; }
	print "</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "  <td valign=\"middle\" align=\"right\">Comms Total:</td>\n";
	print "  <td><input name=\"depcommtotal\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$depcommtotal\" />\n";
	if ($erdepcommtotal) { print "&nbsp;<span class=\"redtext\">$erdepcommtotal</span>"; }
	print "</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td colspan=\"2\">Deposit Notes: "; if ($erdepnotes) { print "&nbsp;&nbsp;<span class=\"redtext\">$erdepnotes</span>"; } print "<br />";
	print "<textarea name=\"depnotes\" wrap=\"virtual\" cols=\"100\" rows=\"3\" class=\"element_box\">$depnotes</textarea></td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td align=\"right\" valign=\"top\">Increments:</td>\n";
	print "	<td>\n";
	print "		<script type=\"text/javascript\">\n";
	print "		$(document).ready(function(){\n";
	print "			$(\".slidingDiv\").hide();\n";
	print "			$(\".show_hide\").show();\n";
	print "			$('.show_hide').click(function(){\n";
	print "			$(\".slidingDiv\").slideToggle();\n";
	print "			});\n";
	print "		});\n";
	print "		</script>\n";
	print "		<a href=\"#\" class=\"show_hide\">Show/Hide</a>\n";
	print "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "	<td colspan=\"2\">\n";
	print "		<div class=\"slidingDiv\">\n";
	print "		<table width=\"80%\" align=\"right\" cellspacing=\"4\" cellpadding=\"0\">\n";
	print "		<tr><td>\n";
	if ($increments) {
		$increments = explode("|",$increments);
	} else {
		$increments = array();
		$year = date("Y");
		$month = date("m");
		$increments[] = "$year-$month-";
		for ($n = 1; $n < 12; $n++) {
			$newmonth = strtotime("+ $n months");
			$year  = date("Y",$newmonth);
			$month = date("m",$newmonth);
			$increments[] = "$year-$month-";
		}
	}
	$rowcount = 1;
	print "		  <table>\n";
	foreach ($increments AS $value) {
		list($year,$month,$amount) = explode("-",$value);
		print "		  <tr>\n";
		print "			<td width=\"22%\" align=\"right\"><b>$month $year</strong></b>\n";
		print "			<input type=\"hidden\" name=\"depincmonths[]\" value=\"$year-$month\" />\n";
		print "			<td width=\"29%\"><input type=\"text\" name=\"depincamounts[]\" class=\"element_tiny\" value=\"$amount\" /></td>\n";
		print "		  </tr>\n";
		$rowcount++;
		if ($rowcount == 7) {
			print "			</table>\n";
			print "			</td>\n";
			print "			<td>\n";
			print "			<table>\n";
		}
	}
	print "		  </table>\n";
	print "			</td>\n";
	print "			</tr>\n";
	print "		  </table>\n";
	print "		</div>\n";
	if ($erdepincamounts) { print "<br /><span class=\"redtext\" align=\"center\">$erdepincamounts</span>"; }
	print "</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
	print "</tr>\n";

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
	print "	<td>Monthly accounts only.</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td align=\"right\">Pro-Rata Period:</td>\n";
	print "	<td>$pdayfr-$pdayto/$pmonth/$pyear</td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td colspan=\"2\">Notes: "; if ($erNotes) { print "&nbsp;&nbsp;<span class=\"redtext\">$erNotes</span>"; } print "<br />";
	print "<textarea name=\"notes\" wrap=\"virtual\" cols=\"100\" rows=\"3\" class=\"element_box\">$notes</textarea></td>\n";
	print "</tr>\n";

	print "<tr>\n";
	print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" style=\"width:130px;\" /></td>\n";
	print "</tr>\n";

	print "</table>\n";
	print "</form>\n";

	if ($vid) {

		print "<a name=\"anc\"></a>\n";

		print "<p align=\"center\"><img src=\"images/blue.gif\" width=\"500px\" height=\"2px\" alt=\"\" /></p>\n";

		print "<p align=\"center\" class=\"heading\">Ancillary Charges</p>\n";

		# get current charges
		$sql = "SELECT * FROM bkm_vanc WHERE vid='$vid' ORDER BY anctitle";
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
				print "<form name=\"updancform\" method=\"post\" action=\"bk-manager.php\">";
				print "<input type=\"hidden\" name=\"module\" value=\"virtuals\" />";
				print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />";
				print "<input type=\"hidden\" name=\"anupdate\" value=\"1\" />";
				print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />";
				print "<input type=\"hidden\" name=\"vid\" value=\"$vid\" />";
				print "<input type=\"hidden\" name=\"ancid\" value=\"$ancid\" />";
				print "&nbsp;<input name=\"ancqty\" type=\"text\" class=\"element_nb2\" maxlength=\"4\" value=\"$ancqty\" />&nbsp;";
				print "&nbsp;<input name=\"ancrate\" type=\"text\" class=\"element_nb\" maxlength=\"10\" value=\"$ancrate\" />";
				print "<input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Update\" />";
				print "</form>\n";
				print "</td>\n";
				print "	<td class=\"colEnd\" align=\"center\"><a href=\"bk-manager.php?module=virtuals&amp;action=addedit&amp;bkid=$bkid&amp;vid=$vid&amp;ancid=$ancid&amp;andelete=1\">Delete</a></td>\n";
				print "</tr>\n";
			}
			print "</table>\n";
			print "<p align=\"center\">Don't forget to click Update if you make changes above</p>\n";
		} else {
			print "<p align=\"center\">None Found</p>\n";
		}

		print "<p align=\"center\"><img src=\"images/blue.gif\" width=\"500px\" height=\"2px\" alt=\"\" /></p>\n";

		# add ancillary charge
		$sql = "SELECT ID, title FROM ancillaries ORDER BY title";
		$anresult = $dbcnx->query($sql);
		if (($anresult) && ($anresult->num_rows)) {
			print "<form name=\"addancform\" method=\"post\" action=\"bk-manager.php\">\n";
			print "<input type=\"hidden\" name=\"anposted\" value=\"1\" />\n";
			print "<input type=\"hidden\" name=\"module\" value=\"virtuals\" />";
			print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />";
			print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />";
			print "<input type=\"hidden\" name=\"vid\" value=\"$vid\" />";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
			print "<tr>\n";
			print "	<td align=\"right\"><b>Add New Charge</b>:</td>\n";
			print "	<td><select name=\"ancid\" class=\"element\">\n";
			while ($anrow = $anresult->fetch_assoc()) {
				$ancid    = $anrow['ID'];
				$anctitle = $anrow['title'];
				print "		<option value=\"$ancid\">$anctitle</option>\n";
			}
			print "	</select>";
			print "</td>\n";
			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
		}
	}

	print "<p align=\"center\"><a href=\"bk-manager.php?module=virtuals&amp;action=select&amp;bkid=$bkid\">Select Another Virtual</a></p>\n";
?>
