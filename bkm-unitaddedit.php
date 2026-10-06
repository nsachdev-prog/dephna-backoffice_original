<?php
	$anposted = $_POST['anposted'];
	$anupdate = $_POST['anupdate'];
	$andelete = $_GET['andelete'];
	$uid = $_REQUEST['uid'];
	$hid = $_REQUEST['hid'];

	// ancillary update section
	if ($anposted) {
		$ancid = $_POST['ancid'];
		# get ancillary details
		$sql = "SELECT * FROM ancillaries WHERE ID='$ancid'";
		$anresult = $dbcnx->query($sql);
		if (($anresult) && ($anresult->num_rows)) {
			$anrow = $anresult->fetch_assoc();
			$anctitle = $anrow['title'];
			$ancrate  = $anrow['rate'];
			$mvat     = $anrow['mvat'];
			$sql = "INSERT INTO bkm_anc VALUES (null,'$bkid','$uid','$ancid','$anctitle','1','$ancrate','$mvat','Month')";
			$anresult = $dbcnx->query($sql);
			if (!$anresult) {
				$rdb .= $sql.";\n";
				$resultMessage .= "<b>Could not add anc record</b><br>" . $dbcnx->error;
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
			$sql = "UPDATE bkm_anc SET qty='$ancqty', rate='$ancrate' WHERE ID='$ancid'";
			$anresult = $dbcnx->query($sql);
			if (!$anresult) {
				$rdb .= $sql.";\n";
				$resultMessage .= "<b>Could not update anc record</b><br>" . $dbcnx->error;
			}
		}
	} elseif ($andelete) {
		$ancid = $_GET['ancid'];
		$sql = "DELETE FROM bkm_anc WHERE ID='$ancid'";
		$anresult = $dbcnx->query($sql);
		if (!$anresult) {
			$rdb .= $sql.";\n";
			$resultMessage .= "<b>Could not delete anc record</b><br>" . $dbcnx->error;
		}
	}

	// get unit details
	$sql = "SELECT title, unittype, rate, per FROM units WHERE ID = '$unitid'";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		$row = $result->fetch_assoc();
		$title    = $row['title'];
		$unittype = $row['unittype'];
		$bkrate   = $row['rate'];
		$bkper    = $row['per'];

		// print heading
		print "<p align=\"center\" class=\"heading\">$sitename - $title - $compname</p>\n";

		if ($posted) {
			// error checking
			if ($_POST['bkrate']) { $bkrate = strip_tags($_POST['bkrate']); }
			if ($_POST['bkper']) { $bkper = strip_tags($_POST['bkper']); }
			if ($_POST['leadmonth']) { $leadmonth = strip_tags($_POST['leadmonth']); }
			if ($_POST['notes']) { $notes = strip_tags($_POST['notes']); }
			if ($_POST['depnotes']) { $depnotes = strip_tags($_POST['depnotes']); }
			if ($_POST['otype']) { $otype = strip_tags($_POST['otype']); }
			if ($_POST['risk']) { $risk = strip_tags($_POST['risk']); }
			if ($_POST['CRPowerIgnore']) { $CRPowerIgnore = strip_tags($_POST['CRPowerIgnore']); }
			// $iptype    = strip_tags($_POST['iptype']);
			// $ipqty     = strip_tags($_POST['ipqty']);
			if ($_POST['access']) { $access = strip_tags($_POST['access']); }

			if (!$otype) { $erotype = "Enter operation type"; $e++; }
			if (!$risk) { $errisk = "Enter operation risk"; $e++; }

			$depunittotal = $_POST['depunittotal']; if ($depunittotal) { if (!is_numeric($depunittotal)) { $erdepunittotal = "Must be numeric"; $e++; } } else { $depunittotal = 0; }
			$depcommtotal = $_POST['depcommtotal']; if ($depcommtotal) { if (!is_numeric($depcommtotal)) { $erdepcommtotal = "Must be numeric"; $e++; } } else { $depcommtotal = 0; }
			if (!$e) {
				$deptotal = $depunittotal + $depcommtotal;
				if ($deptotal) {
					$depincmonths  = $_POST['depincmonths'];
					$depincamounts = $_POST['depincamounts'];
					// if there are any amounts in the depincammounts array.
					if (is_array($depincamounts) AND count($depincamounts)) {
						$increments = array();
						$inccheck = 0;
						foreach($depincmonths AS $key => $value) {
							if ($depincamounts[$key] AND $value) {
								$increments[] = $value. "-" . $depincamounts[$key];
								$inccheck += $depincamounts[$key];
							}
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
							// calculate pro rata override
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
						} else {
							$prfrom = 0;
							$prto   = 0;
						}
					}
				}
				$fim = $_POST['fim'];
				$fiy = $_POST['fiy'];
				if (($fim) && ($fiy)) {
					$fip = strtotime("$fiy/$fim/01");
				} else {
					// calculate first invoicing month
					$fiy = $syear;
					$fim = $smonth+1;
					if ($fim > 12) { $fim = "01"; $fiy++; }
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

			/* $electricity = $_POST['electricity'];
			$gas         = $_POST['gas'];
			$hot         = $_POST['hot'];
			$cold        = $_POST['cold'];
			if ($electricity) { if (!is_numeric($electricity)) { $erelectricity = "Must be numeric"; $e++; } }
			if ($gas)         { if (!is_numeric($gas))         { $ergas = "Must be numeric"; $e++; } }
			if ($hot)         { if (!is_numeric($hot))         { $erhot = "Must be numeric"; $e++; } }
			if ($cold)        { if (!is_numeric($cold))        { $ercold = "Must be numeric"; $e++; } } */

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
				// post to database
				$now = time();
				if ($thistype) { $thistype = addslashes($thistype); }
				if ($notes) { $notes = addslashes($notes); }
				if ($depnotes) { $depnotes = addslashes($depnotes); }
				if ($otype) { $otype = addslashes($otype); }
				// if increments is set, convert it to a string separated by "|", otherwise set it to an empty string
				if ($increments) { $increments = implode("|",$increments); } else { $increments = ""; }

				if ($sday) {
					$datefrom = $syear."/".$smonth."/".$sday;
					$datefrom = strtotime("$datefrom");
				}
				if ($eday) {
					$dateto   = $eyear."/".$emonth."/".$eday;
					$dateto   = strtotime("$dateto");
				} else {
					$dateto = 0;
				}

				if ($uid) {
					$sql = "UPDATE bkm_units SET 
						datefrom='$datefrom',
						dateto='$dateto',
						rate='$bkrate',
						per='$bkper',
						lead='$leadmonth',
						prfrom='$prfrom',
						prto='$prto',
						access='$access',
						notes='$notes',
						depnotes='$depnotes',
						fip='$fip',
						TypeOfOpperation='$otype',
						Risk='$risk',
						deptotal='$deptotal',
						depunittotal='$depunittotal',
						depcommtotal='$depcommtotal',
						increments='$increments',
						CRPowerIgnore='$CRPowerIgnore'
						WHERE ID='$uid'
					";
					$result = $dbcnx->query($sql);
					if ($result) {
						$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
						$resultMessage .= "<b>Unit Record Updated</b><br>";
					} else {
						$resultMessage .= "<b>Could NOT Update Record</b><br>" . $dbcnx->error;
					}
				} else {

					// create prov booking if not yet exists (must be first unit in this booking)
					if (!$bkid) {
						if (!$newcust) { $newcust = 0; }
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
						'',
						'',
						'Yes'
						)";
						if ($result = $dbcnx->query($sql)) {
							$bkid = $dbcnx->insert_id;
							$rdb .= "INSERT INTO bk_manager VALUES ('$bkid','{$_SESSION['user']}','{$_SESSION['username']}','$now','$custref','$compname','$siteid','$sitename','$bktype','$newcust','','','','');\n";
							$_SESSION['siteid']   = "";
							$_SESSION['sitename'] = "";
							$_SESSION['bktype']   = "";
							$_SESSION['newcust']  = "";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname','$page','$now')");
						} else {
							$resultMessage .= "<b>Could NOT add new booking record to database</b><br>" . $dbcnx->error;
						}
					}

					$sql = "INSERT INTO bkm_units VALUES (
					null,
					'$bkid',
					'$unitid',
					'$title',
					'$unittype',
					'$datefrom',
					'$dateto',
					'',
					'$bkrate',
					'$bkper',
					'$bkcntrper',
					'$leadmonth',
					'$prfrom',
					'$prto',
					'0',
					'',
					'$access',
					'{$_SESSION['user']}',
					'$now',
					'$notes',
					'$depnotes',
					'$fip',
					'$otype',
					'$risk',
					'$deptotal',
					'$depunittotal',
					'0',
					'$depcommtotal',
					'$increments',
					'0',
					'0',
					'0',
					'0',
					'$CRPowerIgnore'
					)";
					$result = $dbcnx->query($sql);
					if ($result) {
						$uid = $dbcnx->insert_id;
						$rdb .= "INSERT INTO bkm_units VALUES ('$uid','$bkid','$unitid','$title','$unittype','$datefrom','$dateto','','$bkrate','$bkper','$bkcntrper','$leadmonth','$prfrom','$prto','$ipqty','$iptype','$access','{$_SESSION['user']}','$now','$notes','$depnotes','$fip','$otype','$risk','$deptotal','$depunittotal','0','$depcommtotal','$increments','$electricity','$gas','$hot','$cold');\n";
						$resultMessage .= "<b>Unit Record Added</b><br>";
					} else {
						$resultMessage .= "<b>Could NOT add new record to database</b><br>" . $dbcnx->error;
					}
				}

				if ($unittype == "Kitchen" AND $custref) {

					if ($_POST['canopy'])     { $canopy = strip_tags($_POST['canopy']); }
					if ($_POST['clientcost']) { $clientcost = strip_tags($_POST['clientcost']); }
					if ($_POST['dephnacost']) { $dephnacost = strip_tags($_POST['dephnacost']); }
					if ($_POST['canopysize']) { $canopysize = strip_tags($_POST['canopysize']); }
					if ($_POST['fitout'])     { $fitout = strip_tags($_POST['fitout']); }
					if ($_POST['servicegas']) { $servicegas = strip_tags($_POST['servicegas']); }
					if ($_POST['gastfee'])    { $gastfee = strip_tags($_POST['gastfee']); }
					if ($_POST['servicewater']) { $servicewater = strip_tags($_POST['servicewater']); }
					if ($_POST['watertfee']) { $watertfee = strip_tags($_POST['watertfee']); }
					if ($_POST['serviceelect']) { $serviceelect = strip_tags($_POST['serviceelect']); }
					if ($_POST['electtfee']) { $electtfee = strip_tags($_POST['electtfee']); }
					if ($_POST['graceperiod']) { $graceperiod = strip_tags($_POST['graceperiod']); }
					if ($_POST['handovernotes']) { $handovernotes = strip_tags($_POST['handovernotes']); }
					if ($handovernotes) { $handovernotes = str_replace('\n',"<br>",$handovernotes); }

					if ($clientcost) { $clientcost    = addslashes($clientcost); }
					if ($dephnacost) { $dephnacost    = addslashes($dephnacost); }
					if ($canopysize) { $canopysize    = addslashes($canopysize); }
					if ($gastfee)    { $gastfee       = addslashes($gastfee); }
					if ($watertfee)  { $watertfee     = addslashes($watertfee); }
					if ($electtfee)  { $electtfee     = addslashes($electtfee); }
					if ($graceperiod) { $graceperiod   = addslashes($graceperiod); }
					if ($handovernotes) { $handovernotes = addslashes($handovernotes); }

					if ($hid) {
						$sql = "UPDATE handover SET
							`custref`       = '$custref',
							`datefrom`      = '$datefrom',
							`canopy`        = '$canopy',
							`clientcost`    = '$clientcost',
							`dephnacost`    = '$dephnacost',
							`canopysize`    = '$canopysize',
							`fitout`        = '$fitout',
							`servicegas`    = '$servicegas',
							`gastfee`       = '$gastfee',
							`servicewater`  = '$servicewater',
							`watertfee`     = '$watertfee',
							`serviceelect`  = '$serviceelect',
							`electtfee`     = '$electtfee',
							`graceperiod`   = '$graceperiod',
							`notes`         = '$handovernotes'
							WHERE ID = '$hid'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "<b>Handover Record Updated</b><br>";
						} else {
							$resultMessage .= "<b>Could NOT update handover record</b><br>" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO handover VALUES (
							null,
							'$bkid',
							'$uid',
							'$siteid',
							'$unitid',
							'$title',
							'$datefrom',
							'',
							'$custref',
							'$canopy',
							'$clientcost',
							'$dephnacost',
							'$canopysize',
							'$fitout',
							'$servicegas',
							'$gastfee',
							'$servicewater',
							'$watertfee',
							'$serviceelect',
							'$electtfee',
							'$graceperiod',
							'$handovernotes',
							'',
							'$now',
							'{$_SESSION['username']}'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$hid = $dbcnx->insert_id;
							$rdb .= "INSERT INTO bkm_units VALUES ('$hid','$bkid','$uid','$siteid','$unitid','$title','$datefrom','','$custref','$canopy','$clientcost','$dephnacost','$canopysize','$fitout','$servicegas','$gastfee','$servicewater','$watertfee','$serviceelect','$electtfee',, '$graceperiod','$handovernotes','','$now','{$_SESSION['username']}');\n";
							$resultMessage .= "<b>Handover Record Added</b><br>";
						} else {
							$resultMessage .= "<b>Could NOT add new handover record to database</b><br>" . $dbcnx->error;
						}
					}
				}
			}
		}

		if ($uid) {
			// get existing booked unit
			$sql = "SELECT * FROM bkm_units WHERE ID = '$uid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$datefrom     = $row['datefrom'];
				$dateto       = $row['dateto'];
				$bkrate       = $row['rate'];
				$bkcntrper    = $row['cntrper'];
				$bkper        = $row['per'];
				$leadmonth    = $row['lead'];
				$notes        = $row['notes'];
				$depnotes     = $row['depnotes'];
				$prfrom       = $row['prfrom'];
				$prto         = $row['prto'];
				$ipqty        = $row['ipqty'];
				$iptype       = $row['iptype'];
				$access       = $row['access'];
				$fip          = $row['fip'];
				$otype        = $row['TypeOfOpperation'];
				$risk         = $row['Risk'];
				$deptotal     = $row['deptotal'];
				$depunittotal = $row['depunittotal'];
				$depcommtotal = $row['depcommtotal'];
				$increments   = $row['increments'];
				$CRPowerIgnore = $row['CRPowerIgnore'];
				/* $electricity  = $row['electricity'];
				$gas          = $row['gas'];
				$hot          = $row['hot'];
				$cold         = $row['cold']; */
				if ($fip) { $fim = date("n",$fip); $fiy = date("Y",$fip); }
				if ($datefrom) { $sday   = date("d",$datefrom); $smonth = date("m",$datefrom); $syear = date("Y",$datefrom); }
				if ($dateto) {   $eday   = date("d",$dateto);   $emonth = date("m",$dateto);   $eyear = date("Y",$dateto); }
				if ($prfrom) {   $pdayfr = date("d",$prfrom);   $pmonth = date("m",$prfrom);   $pyear = date("Y",$prfrom); }
				if ($prto)   {   $pdayto = date("d",$prto); }

				if ($bkid) {
					// get handover details if they exist yet
					$gothandover = 0;
					$sql = "SELECT * FROM handover WHERE bkid = '$bkid' AND bkuid = '$uid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$gothandover = 1;
					} else {
						// get last kitchen handover details on this booking
						$sql = "SELECT * FROM handover WHERE bkid = '$bkid' ORDER BY lastupd DESC LIMIT 0,1";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$gothandover = 2;
						}
					}
					if ($gothandover) {
						$row = $result->fetch_assoc();
						if ($gothandover == 1) {
							$hid = $row['ID'];
						}
						$canopy        = $row['canopy'];
						$clientcost    = $row['clientcost'];
						$dephnacost    = $row['dephnacost'];
						$canopysize    = $row['canopysize'];
						$fitout        = $row['fitout'];
						$servicegas    = $row['servicegas'];
						$gastfee       = $row['gastfee'];
						$servicewater  = $row['servicewater'];
						$watertfee     = $row['watertfee'];
						$serviceelect  = $row['serviceelect'];
						$electtfee     = $row['electtfee'];
						$graceperiod   = $row['graceperiod'];
						$handovernotes = $row['notes'];
						$handovernotes = str_replace("<br>","\n",$handovernotes);
					}
				}
			}
		}

		// report to screen
		if ($resultMessage) {
			print "<script	language=\"javascript\" type=\"text/javascript\">\n";
			print "	$(document).ready(function() {\n";
			print "		$(\".quick-alert\").delay(3000).fadeOut(\"slow\");\n";
			print "	});\n";
			print "</script>\n";
			print "<p align=\"center\"><span class=\"quick-alert\">&nbsp; $resultMessage &nbsp;</span></p>\n";
			$resultMessage .= "";
		}

		if ($uid) {
			print "<p align=\"center\"><a href=\"#anc\">Add Ancillaries</a> | <a href=\"bk-manager.php?module=units&amp;action=select&amp;bkid=$bkid\">Select Another Unit</a></p>\n";
		}

		// print the form
		print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
		print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
		print "<input type=\"hidden\" name=\"module\" value=\"units\" />\n";
		print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />\n";
		print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
		print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";
		print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
		print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
		print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

		// if (!$ipqty)  { $ipqty = 3; }
		// if (!$iptype) { $iptype = "Months"; }
		if (!$access) { $access = "24Hours"; }

		print "<tr>\n";
		print "	<td align=\"right\">Contract Start Date:</td>\n";
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
		print "	<td align=\"right\">Contract End Date:</td>\n";
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
		print "&nbsp;<span style=\"color:red;\">AUTO-FILLED</span>\n";
		print "</td>\n";
		print "</tr>\n";

		$cpoptions = array("Ongoing","Monthly","Quarterly","Yearly","See Addendum","None");
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
		print "	<td align=\"right\">Initial Period</td>\n";
		print "	<td>Use Addendum";
		/* print "	<select name=\"ipqty\" class=\"element_date\">\n";
		print "		<option></option>\n";
		for ($o=1; $o <= 31; $o++) {
			print "		<option ";
			if ($ipqty == $o) { print "SELECTED "; }
			print "value=\"$o\">$o</option>\n";
		}
		print "	</select>\n";
		print "	<select name=\"iptype\" class=\"element_small\">\n";
		print "		<option "; if ($iptype == "Days") { print "SELECTED "; } print ">Days</option>\n";
		print "		<option "; if ($iptype == "Weeks") { print "SELECTED "; } print ">Weeks</option>\n";
		print "		<option "; if ($iptype == "Months") { print "SELECTED "; } print ">Months</option>\n";
		print "		<option "; if ($iptype == "Years") { print "SELECTED "; } print ">Years</option>\n";
		print "	</select>\n"; */
		print "	</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td align=\"right\">Access Hours</td>\n";
		print "	<td>\n";
		print "	<select name=\"access\" class=\"element_medium\">\n";
		print "		<option "; if ($access == "24hours") { print "SELECTED "; } print ">24hours</option>\n";
		print "		<option "; if ($access == "8am-5.30pm") { print "SELECTED "; } print ">8am-5.30pm</option>\n";
		print "	</select>\n";
		print "	</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
		print "</tr>\n";

		if ($unittype == "Cold Room") {
			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><span class=\"larger\">Cold Room Power Setting</span></td>\n";
			print "</tr>\n";
			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\">
				Normally Power will be turned off leaving the client to manage their own power setting.<br />
				If this is a contract change or name change for an existing client, please tick the box below.<br />
				Keep Existing Power Setting: <input type=\"checkbox\" name=\"CRPowerIgnore\" value=\"1\""; if ($CRPowerIgnore) { print " CHECKED"; } print " />
			</td>\n";
			print "</tr>\n";
		}

		print "<tr>\n";
		print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td valign=\"top\" align=\"right\"><b>Deposit</b></td>\n";
		print "	<td>Enter the total value for the deposit. If it is to be<br />staggered over several months, please click<br />'Show/Hide' and fill in the increments you require.</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "  <td valign=\"middle\" align=\"right\">Unit Total:</td>\n";
		print "  <td><input name=\"depunittotal\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$depunittotal\" />\n";
		if ($erdepunittotal) { print "&nbsp;<span class=\"redtext\">$erdepunittotal</span>"; }
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
		print "	<td><select name=\"bkper\" class=\"element\" style=\"width:140px\">\n";
		print "		<option value=\"Monthly\"";   if ($bkper == "Monthly")   { print " SELECTED"; } print ">Monthly</option>\n";
		print "		<option value=\"Quarterly\""; if ($bkper == "Quarterly") { print " SELECTED"; } print ">Quarterly</option>\n";
		print "		<option value=\"Yearly\"";    if ($bkper == "Yearly")    { print " SELECTED"; } print ">Yearly</option>\n";
		print "		<option value=\"0\"";         if ($bkper == "0")         { print " SELECTED"; } print ">None</option>\n";
		print "	</select>";
		if ($erper) { print "&nbsp;<span class=\"redtext\">$erper</span>"; }
		print "&nbsp; <span style=\"color:red;\">AUTO-FILLED</span>\n";
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
		print "&nbsp;<span style=\"color:red;\">AUTO-FILLED</span>\n";
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
		print "  <td valign=\"middle\" align=\"right\">Type Of Operation:</td>\n";
		print "  <td><input name=\"otype\" type=\"text\" class=\"element\" size=\"50\" maxlength=\"150\" value=\"$otype\" />\n";
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

		/* print "<tr>\n";
		print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td valign=\"top\" align=\"right\"><b>Utilities</b></td>\n";
		print "	<td>Enter the initial meter readings to calculate first utility bill.<br />Initial meter readings can be added at a later date <br />(initial readings must be before the invoice start date).</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "<td align=\"right\">Electricity:</td>\n";
		print "<td><input type=\"text\" name=\"electricity\" class=\"element_small\" value=\"$electricity\" />\n";
		if ($erelectricity) { print "&nbsp;<span class=\"redtext\">$erelectricity</span>"; }
		print "</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "<td align=\"right\">Gas:</td>\n";
		print "<td><input type=\"text\" name=\"gas\" class=\"element_small\" value=\"$gas\" />\n";
		if ($ergas) { print "&nbsp;<span class=\"redtext\">$ergas</span>"; }
		print "</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "<td align=\"right\">Hot:</td>\n";
		print "<td><input type=\"text\" name=\"hot\" class=\"element_small\" value=\"$hot\" />\n";
		if ($erhot) { print "&nbsp;<span class=\"redtext\">$erhot</span>"; }
		print "</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "<td align=\"right\">Cold:</td>\n";
		print "<td><input type=\"text\" name=\"cold\" class=\"element_small\" value=\"$cold\" />\n";
		if ($ercold) { print "&nbsp;<span class=\"redtext\">$ercold</span>"; }
		print "</td>\n";
		print "</tr>\n"; */

		if ($unittype == "Kitchen" AND $custref) {

			print "<tr>\n";
			print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><span class=\"larger\">Handover</span></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Canopy Required:</td>\n";
			print "	<td>";
			print "<input type=\"checkbox\" name=\"canopy\""; if ($canopy) { print " CHECKED"; } print " />&nbsp;";
			print "C-Cost: <input type=\"text\" name=\"clientcost\" class=\"element_small\" value=\"$clientcost\" />&nbsp;";
			print "D-Cost: <input type=\"text\" name=\"dephnacost\" class=\"element_small\" value=\"$dephnacost\" />&nbsp;";
			print "Size: <input type=\"text\" name=\"canopysize\" class=\"element_small\" value=\"$canopysize\" />";
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Fitout Required:</td>\n";
			print "	<td><input type=\"checkbox\" name=\"fitout\""; if ($fitout) { print " CHECKED"; } print " /> Service schedule will be emailed to client at confirmation.</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Grace Period From:</td>\n";
			print "	<td><input type=\"text\" name=\"graceperiod\" class=\"element_small\" value=\"$graceperiod\" /> If client can move equipment in before start date.</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><b>Services To Remain</b></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Gas:</td>\n";
			print "	<td>";
			print "<input type=\"checkbox\" name=\"servicegas\""; if ($servicegas) { print " CHECKED"; } print " />&nbsp;";
			print "Testing Fee: <input type=\"text\" name=\"gastfee\" class=\"element_small\" value=\"$gastfee\" />&nbsp;";
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Water:</td>\n";
			print "	<td>";
			print "<input type=\"checkbox\" name=\"servicewater\""; if ($servicewater) { print " CHECKED"; } print " />&nbsp;";
			print "Testing Fee: <input type=\"text\" name=\"watertfee\" class=\"element_small\" value=\"$watertfee\" />&nbsp;";
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Electricity:</td>\n";
			print "	<td>";
			print "<input type=\"checkbox\" name=\"serviceelect\""; if ($serviceelect) { print " CHECKED"; } print " />&nbsp;";
			print "Testing Fee: <input type=\"text\" name=\"electtfee\" class=\"element_small\" value=\"$electtfee\" />&nbsp;";
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\">Additional Notes for Handover:<br>";
			print "<textarea name=\"handovernotes\" wrap=\"virtual\" cols=\"100\" rows=\"4\" class=\"element_box\">$handovernotes</textarea></td>\n";
			print "</tr>\n";

		}

		print "<tr>\n";
		print "	<td colspan=\"2\"><hr noshade size=\"1px\"></td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td colspan=\"2\">BK General Notes:<br />";
		print "<textarea name=\"notes\" wrap=\"virtual\" cols=\"100\" rows=\"3\" class=\"element_box\">$notes</textarea></td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" style=\"width:130px;\" /></td>\n";
		print "</tr>\n";

		print "</table>\n";
		print "</form>\n";

		if ($uid) {

			print "<a name=\"anc\"></a>\n";

			print "<p align=\"center\"><img src=\"images/blue.gif\" width=\"500px\" height=\"2px\" alt=\"\" /></p>\n";

			print "<p align=\"center\" class=\"larger\">Ancillary Charges</p>\n";

			// get current charges
			$sql = "SELECT * FROM bkm_anc WHERE uid='$uid' ORDER BY anctitle";
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
					print "<input type=\"hidden\" name=\"module\" value=\"units\" />";
					print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />";
					print "<input type=\"hidden\" name=\"anupdate\" value=\"1\" />";
					print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />";
					print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />";
					print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />";
					print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />";
					print "<input type=\"hidden\" name=\"ancid\" value=\"$ancid\" />";
					print "&nbsp;<input name=\"ancqty\" type=\"text\" class=\"element_nb2\" maxlength=\"4\" value=\"$ancqty\" />&nbsp;";
					print "&nbsp;<input name=\"ancrate\" type=\"text\" class=\"element_nb\" maxlength=\"10\" value=\"$ancrate\" />";
					print "<input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Update\" />";
					print "</form>\n";
					print "</td>\n";
					print "	<td class=\"colEnd\" align=\"center\"><a href=\"bk-manager.php?module=units&amp;action=addedit&amp;bkid=$bkid&amp;uid=$uid&amp;ancid=$ancid&amp;unitid=$unitid&amp;andelete=1\">Delete</a></td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
				print "<p align=\"center\">Don't forget to click Update if you make changes above</p>\n";
			} else {
				print "<p align=\"center\">None Found</p>\n";
			}

			print "<p align=\"center\"><img src=\"images/blue.gif\" width=\"500px\" height=\"2px\" alt=\"\" /></p>\n";

			// add ancillary charge
			$sql = "SELECT ID, title FROM ancillaries ORDER BY title";
			$anresult = $dbcnx->query($sql);
			if (($anresult) && ($anresult->num_rows)) {
				print "<form name=\"addancform\" method=\"post\" action=\"bk-manager.php\">\n";
				print "<input type=\"hidden\" name=\"anposted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"module\" value=\"units\" />";
				print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />";
				print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />";
				print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />";
				print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />";
				print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />";
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
		}
	} else {
		print "<p align=\"center\">Unit not found!</p>\n";
	}

	print "<p align=\"center\"><a href=\"bk-manager.php?module=units&amp;action=select&amp;bkid=$bkid\">Select Another Unit</a></p>\n";
?>
