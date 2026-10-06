<?php

$day   = date("d");
$month = date("m");
$year  = date("y");

$invdate  = "$day/$month/$year";

$lastmonth  = strtotime("-1 Month");

$callsmonth = date("m",$lastmonth);
$callsyear  = date("y",$lastmonth);

$datefrom   = "01/$month/$year";
$monthstart = strtotime("20".$year."-".$month."-01");
$monthend   = date("t");
$dateto     = "$monthend/$month/$year";

$callsdatefrom   = "01/$callsmonth/$callsyear";
$callsmonthstart = strtotime("01-".$callsmonth."-20".$callsyear);
$callsmonthend   = date("t",$lastmonth);
$callsdateto     = "$callsmonthend/$callsmonth/$callsyear";

$fipcheck = "20".$year.$month;

$sql = "SELECT ID, custref, compname FROM users WHERE (level = '2' AND custref != '20848')";
$custresult = $dbcnx->query($sql);
if (($custresult) && ($custresult->num_rows)) {
	$csvfile = "$csv_dir/invoices.csv";
	$handle  = fopen("$csvfile", "w");
	$newrecord = "Invoice No,Date of Invoice,RefNo,Type of Sales,Unit No,Let From,Let To,Qty,Rate,Amount,Sales Ledger,VAT Ledger,VAT,VAT Amt,Total,Location,rt,Customer Name,On Notice,New Booking,Not monthly\n";
	fwrite($handle, $newrecord);
	$summary = array();
	while ($crow = $custresult->fetch_assoc()) {
		$custid   = $crow['ID'];
		$custref  = $crow['custref'];
		$custname = $crow['compname'];
		$invoiced = 0;

		// get unit bookings
		$sql = "SELECT * FROM bookings WHERE custid='$custref' AND status='on'";
		$unitsresult = $dbcnx->query($sql);
		if (($unitsresult) && ($unitsresult->num_rows)) {
			while ($urow = $unitsresult->fetch_assoc()) {
				$bkid       = $urow['ID'];
				$siteid     = $urow['siteid'];
				$unitid     = $urow['unitid'];
				$newbooking = $urow['datefrom'];
				$onnotice   = $urow['dateto'];
				$net        = $urow['rate'];
				$per        = $urow['per'];
				$lead       = $urow['lead'];
				$prfrom     = $urow['prfrom'];
				$prto       = $urow['prto'];
				$fip        = $urow['fip'];
				if ($fip) { $fip = date("Ym",$fip); } else { $fip = 0; }

				if ($fip <= $fipcheck) { // if not prior to first invoicing period else skip

					if ($prfrom) { $pdayfr = date("d",$prfrom); $pmonth = date("m",$prfrom); $pyear  = date("Y",$prfrom); }
					if ($prto)   { $pdayto = date("d",$prto); }

					if (($newbooking < $monthstart) || ($newbooking > $monthend)) { $newbooking = ""; }
					else { $newbooking = date("d/m/y",$newbooking); }
					if ($onnotice) { $onnoticetxt = date("d/m/y",$onnotice); } else { $onnoticetxt = ""; }

					// is this booking monthly, yearly or quarterly?
					if ($per == "Yearly") {
						$intmonth = intval($month);
						if ($lead == $intmonth) {
							$processMonth = 1;
							$nextperiod = strtotime("+1 year -1 day",$monthstart);
							if (($onnotice) AND ($onnotice < $nextperiod) AND ($onnotice >= $monthstart)) {
								$onnoticeapplies = 1;
								$perday = $net/365;
								$thisdateto = date("d/m/y",$onnotice);
							} else {
								$onnoticeapplies = 0;
								$thisdateto = date("d/m/y",$nextperiod);
							}
							$thisdatefrom = $datefrom;
							$notmonthly = "Yearly $lead";
						} else {
							$processMonth = 0;
						}
					} elseif ($per == "Quarterly") {
						$intmonth = intval($month);
						if (($lead == $intmonth) OR ($lead+3 == $intmonth) OR ($lead+6 == $intmonth) OR ($lead+9 == $intmonth)) {
							$processMonth = 1;
							$nextperiod = strtotime("+3 months -1 day",$monthstart);
							if (($onnotice) AND ($onnotice < $nextperiod) AND ($onnotice >= $monthstart)) {
								$onnoticeapplies = 1;
								$perday = $net*4/365;
								$thisdateto = date("d/m/y",$onnotice);
							} else {
								$onnoticeapplies = 0;
								$thisdateto = date("d/m/y",$nextperiod);
							}
							$thisdatefrom = $datefrom;
							$notmonthly = "Quarterly $lead";
						} else {
							$processMonth = 0;
						}
					} elseif ($per == "Monthly") {
						$processMonth = 1;
						$chkyear = "20".$year;
						$nextperiod = strtotime("+1 month -1 day",$monthstart);
						if (($onnotice) AND ($onnotice < $nextperiod) AND ($onnotice >= $monthstart)) {
							$onnoticeapplies = 1;
							$perday = $net*12/365;
							$thisdateto = date("d/m/y",$onnotice);
						}
						elseif (($prfrom) && ($month == $pmonth) && ($chkyear == $pyear)) {
							$prapplies = 1;
							$onnoticeapplies = 0;
							$thisdateto = $pdayto."/".$pmonth."/".$year;
							$thisdatefrom = $pdayfr."/".$pmonth."/".$year;
							$perday = $net*12/365;
						} else {
							$prapplies = 0;
							$onnoticeapplies = 0;
							$notmonthly = "";
							$thisdateto = $dateto;
							$thisdatefrom = $datefrom;
						}
					} else {
						$processMonth = 0;
					}

					if ($processMonth) {

						// get site name
						$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
						$snresult = $dbcnx->query($sql);
						if (($snresult) && ($snresult->num_rows)) {
							$snrow = $snresult->fetch_assoc();
							$sitename = $snrow['title'];
						} else {
							$errorMessage .= "Site $siteid Not Found<br />\n";
						}

						// get unit name
						$sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
						$unresult = $dbcnx->query($sql);
						if (($unresult) && ($unresult->num_rows)) {
							$unrow = $unresult->fetch_assoc();
							$unitname = $unrow['title'];
							$unittype = $unrow['unittype'];
							if ($unittype == "Car Parking") {
								$cunitname = "Parking Bay";
							} else {
								$cunitname = $unitname;
							}
						} else {
							$errorMessage .= "Unit $unitid at Site $siteid Not Found<br />\n";
						}

						// get vat rate
						$sql = "SELECT vat FROM types WHERE title='$unittype'";
						$tvatresult = $dbcnx->query($sql);
						if (($tvatresult) && ($tvatresult->num_rows)) {
							$tvrow = $tvatresult->fetch_assoc();
							$tvat = $tvrow['vat'];
						} else {
							$tvat = "No";
						}

						$qty = 1;
						if ($onnoticeapplies) {
							$datediff = $onnotice - $monthstart;
							$prdays = floor($datediff/(60*60*24))+1;
							$nettotal = $perday*$prdays;
							$nettotal = round($nettotal, 2);
							$notmonthly = "Pro Rata $prdays Days";
						}
						elseif ($prapplies) {
							$prdays = $pdayto-$pdayfr+1;
							$nettotal = $perday*$prdays;
							$nettotal = round($nettotal, 2);
							$notmonthly = "Pro Rata $prdays Days";
						} else {
							$nettotal = $net;
						}
						if ($tvat == "Yes") {
							$vattotal = $nettotal/100*$vatRate;
							$vattotal = round($vattotal, 2);
							$vatrate = $vatRate;
						} else {
							$vattotal = 0;
							$vatrate = 0;
						}
						$linetotal = $nettotal + $vattotal;

						if (($unittype != "Cold Room") && ($unittype != "Storage")) {
							$unittype = "Licence";
						}

						$newrecord = "$invnum,$invdate,DP$custref,$sitename $unittype,$cunitname,$thisdatefrom,$thisdateto,$qty,$net,$nettotal,$sitename $unittype,Vat On Sales,$vatrate,$vattotal,$linetotal,$sitename,$unitname,$custname,$onnoticetxt,$newbooking,$notmonthly\n";
						fwrite($handle, $newrecord);
						$invoiced++;
						$summary[$sitename] += $linetotal;

						// get acillaries for this booking
						$anresult = $dbcnx->query("SELECT * FROM bookings_an WHERE bkid='$bkid' ORDER BY anctitle");
						if (($anresult) && ($anresult->num_rows)) {
							while ($anrow = $anresult->fetch_assoc()) {
								$ancid   = $anrow['ancid'];
								$ancname = $anrow['anctitle'];
								$net     = $anrow['rate'];
								$qty     = $anrow['qty'];
								$ancvat  = $anrow['vat'];
								$groupresult = $dbcnx->query("SELECT ancgroup FROM ancillaries WHERE ID='$ancid'");
								if (($groupresult) && ($groupresult->num_rows)) {
									$grow = $groupresult->fetch_assoc();
									$thisgroup = $grow['ancgroup'];
									if ($thisgroup == "Cleaning Charges") {
										$thisgroup = "Licence";
									}
								} else {
									$errorMessage .= "Ancillary group for ancillary id $ancid Not Found BKID $bkid Custref $custref<br />\n";
								}
								if (($prapplies) OR ($onnoticeapplies)) {
									if ($per == "Yearly") {
										$perday = $net/365;
									} elseif ($per == "Quarterly") {
										$perday = $net*4/365;
									} elseif ($per == "Monthly") {
										$perday = $net*12/365;
									}
									$nettotal = $perday*$prdays;
									$nettotal = $nettotal*$qty;
									$nettotal = round($nettotal, 2);
								} else {
									$nettotal = $net*$qty;
								}
								if ($ancvat == "Yes") {
									$vattotal = $nettotal/100*$vatRate;
									$vattotal = round($vattotal, 2);
									$thisvatrate = $vatRate;
								} else {
									$vattotal = 0;
									$thisvatrate = 0;
								}
								$linetotal = $nettotal+$vattotal;
								$newrecord = "$invnum,$invdate,DP$custref,$sitename $thisgroup,$ancname,$thisdatefrom,$thisdateto,$qty,$net,$nettotal,$sitename $thisgroup,Vat On Sales,$thisvatrate,$vattotal,$linetotal,$sitename,$ancname,$custname,$onnoticetxt,$newbooking,$notmonthly\n";
								fwrite($handle, $newrecord);
								$summary[$sitename] += $linetotal;
								$invoiced++;
							}
						}
					}
				}
			}
		}

		// virtual offices
		$sql = "SELECT * FROM vbookings WHERE custref='$custref'";
		$virtualsresult = $dbcnx->query($sql);
		if (($virtualsresult) && ($virtualsresult->num_rows)) {
			while ($vrow = $virtualsresult->fetch_assoc()) {
				$bkid       = $vrow['ID'];
				$sitename   = $vrow['sitename'];
				$packname   = $vrow['pkname'];
				$net        = $vrow['rate'];
				$vbvat      = $vrow['vat'];
				$newbooking = $vrow['datefrom'];
				$per        = $vrow['per'];
				$lead       = $vrow['lead'];
				$prfrom     = $vrow['prfrom'];
				$prto       = $vrow['prto'];
				$onnotice   = $vrow['dateto'];
				$fip        = $vrow['fip'];
				if ($fip) { $fip = date("Ym",$fip); } else { $fip = 0; }

				if ($fip <= $fipcheck) { // if not prior to first billing period else skip

					if (($newbooking < $monthstart) || ($newbooking > $monthend)) { $newbooking = ""; }
					else { $newbooking = date("d/m/y",$newbooking); }
					if ($onnotice) { $onnoticetxt = date("d/m/y",$onnotice); } else { $onnoticetxt = ""; }

					if ($prfrom) {   $pdayfr = date("d",$prfrom);   $pmonth = date("m",$prfrom);   $pyear  = date("Y",$prfrom); }
					if ($prto)   {   $pdayto = date("d",$prto); }

					// is this booking monthly, yearly or quarterly?
					if ($per == "Yearly") {
						$intmonth = intval($month);
						if ($lead == $intmonth) {
							$processMonth = 1;
							$nextperiod = strtotime("+1 year -1 day",$monthstart);
							if (($onnotice) AND ($onnotice < $nextperiod) AND ($onnotice >= $monthstart)) {
								$onnoticeapplies = 1;
								$perday = $net/365;
								$thisdateto = date("d/m/y",$onnotice);
							} else {
								$onnoticeapplies = 0;
								$thisdateto = date("d/m/y",$nextperiod);
							}
							$thisdatefrom = $datefrom;
							$notmonthly = "Yearly $lead";
						} else {
							$processMonth = 0;
						}
					} elseif ($per == "Quarterly") {
						$intmonth = intval($month);
						if (($lead == $intmonth) OR ($lead+3 == $intmonth) OR ($lead+6 == $intmonth) OR ($lead+9 == $intmonth)) {
							$processMonth = 1;
							$nextperiod = strtotime("+3 months -1 day",$monthstart);
							if (($onnotice) AND ($onnotice < $nextperiod) AND ($onnotice >= $monthstart)) {
								$onnoticeapplies = 1;
								$perday = $net*4/365;
								$thisdateto = date("d/m/y",$onnotice);
							} else {
								$onnoticeapplies = 0;
								$thisdateto = date("d/m/y",$nextperiod);
							}
							$thisdatefrom = $datefrom;
							$notmonthly = "Quarterly $lead";
						} else {
							$processMonth = 0;
						}
					} elseif ($per == "Monthly") {
						$processMonth = 1;
						$chkyear = "20".$year;
						$nextperiod = strtotime("+1 month -1 day",$monthstart);
						if (($onnotice) AND ($onnotice < $nextperiod) AND ($onnotice >= $monthstart)) {
							$onnoticeapplies = 1;
							$perday = $net*12/365;
							$thisdateto = date("d/m/y",$onnotice);
						}
						elseif (($prfrom) && ($month == $pmonth) && ($chkyear == $pyear)) {
							$prapplies = 1;
							$onnoticeapplies = 0;
							$thisdateto = $pdayto."/".$pmonth."/".$year;
							$thisdatefrom = $pdayfr."/".$pmonth."/".$year;
							$perday = $net*12/365;
						} else {
							$prapplies = 0;
							$onnoticeapplies = 0;
							$notmonthly = "";
							$thisdateto = $dateto;
							$thisdatefrom = $datefrom;
						}
					} else {
						$processMonth = 0;
					}

					if ($processMonth) {
						$qty = 1;
						if ($onnoticeapplies) {
							$datediff = $onnotice - $monthstart;
							$prdays = floor($datediff/(60*60*24))+1;
							$nettotal = $perday*$prdays;
							$nettotal = round($nettotal, 2);
							$notmonthly = "Pro Rata $prdays Days";
						}
						elseif ($prapplies) {
							$prdays = $pdayto-$pdayfr+1;
							$nettotal = $perday*$prdays;
							$nettotal = round($nettotal, 2);
							$notmonthly = "Pro Rata $prdays Days";
						} else {
							$nettotal = $net;
						}
						if ($vbvat == "Yes") {
							$vattotal = $nettotal/100*$vatRate;
							$vattotal = round($vattotal, 2);
							$thisvatrate = $vatRate;
							$sltext = "Virtual Office";
						} else {
							$vattotal = 0;
							$thisvatrate = 0;
							$sltext = "Virtual Office0";
						}
						$linetotal = $nettotal+$vattotal;
						$newrecord = "$invnum,$invdate,DP$custref,$sitename Licence,$sltext,$thisdatefrom,$thisdateto,$qty,$net,$nettotal,$sitename Licence,Vat On Sales,$thisvatrate,$vattotal,$linetotal,$sitename,Virtual Office,$custname,$onnoticetxt,$newbooking,$notmonthly\n";
						fwrite($handle, $newrecord);
						$invoiced++;
						$summary[$sitename] += $linetotal;

						// get acillaries for this booking
						$sql = "SELECT * FROM vbookings_an WHERE bkid='$bkid' ORDER BY anctitle";
						$anresult = $dbcnx->query($sql);
						if (($anresult) && ($anresult->num_rows)) {
							while ($anrow = $anresult->fetch_assoc()) {
								$ancid   = $anrow['ancid'];
								$ancname = $anrow['anctitle'];
								$net     = $anrow['rate'];
								$qty     = $anrow['qty'];
								$ancvat  = $anrow['vat'];

								$groupresult = $dbcnx->query("SELECT ancgroup FROM ancillaries WHERE ID='$ancid'");
								if (($groupresult) && ($groupresult->num_rows)) {
									$grow = $groupresult->fetch_assoc();
									$thisgroup = $grow['ancgroup'];
									if ($thisgroup == "Cleaning Charges") {
										$thisgroup = "Licence";
									}
								} else {
									$errorMessage .= "Ancillary group for acillary id $ancid Not Found<br />\n";
								}
								if (($prapplies) OR ($onnoticeapplies)) {
									if ($per == "Yearly") {
										$perday = $net/365;
									} elseif ($per == "Quarterly") {
										$perday = $net*4/365;
									} elseif ($per == "Monthly") {
										$perday = $net*12/365;
									}
									$nettotal = $perday*$prdays;
									$nettotal = $nettotal*$qty;
									$nettotal = round($nettotal, 2);
								} else {
									$nettotal = $net*$qty;
								}
								if ($ancvat == "Yes") {
									$vattotal = $nettotal/100*$vatRate;
									$vattotal = round($vattotal, 2);
									$thisvatrate = $vatRate;
								} else {
									$vattotal = 0;
									$thisvatrate = 0;
								}
								$linetotal = $nettotal+$vattotal;
								$newrecord = "$invnum,$invdate,DP$custref,$sitename $thisgroup,$ancname,$thisdatefrom,$thisdateto,$qty,$net,$nettotal,$sitename $thisgroup,Vat On Sales,$thisvatrate,$vattotal,$linetotal,$sitename,$ancname,$custname,$onnoticetxt,$newbooking,$notmonthly\n";
								fwrite($handle, $newrecord);
								$summary[$sitename] += $linetotal;
								$invoiced++;
							}
						}
					}
				}
			}
		}

		$fullyear = "20" . $callsyear;

		// utilities
		$comresult = $dbcnx->query("
			SELECT * FROM UtlUsedNew
			WHERE custref='$custref' AND year='$fullyear' AND month='$callsmonth'
			ORDER BY commodity
		");
		if (($comresult) && ($comresult->num_rows)) {
			while ($comrow = $comresult->fetch_assoc()) {

				$ufromday   = "";
				$ufrommonth = "";
				$ufromyear  = "";

				$sitename  = $comrow['sitename'];
				$commodity = $comrow['commodity'];
				$collector = $comrow['collector'];
				$counter   = $comrow['counter'];
				$nettotal  = $comrow['charge'];
				$meter     = $comrow['meter'];
				$prevdate  = $comrow['prevdate'];
				$previous  = $comrow['previous'];
				$used      = $comrow['used'];
				$rate      = $comrow['rate'];
				$utunit    = $comrow['unit'];
				$utoyear   = $comrow['year'];
				$utomonth  = $comrow['month'];
				$utoday    = $comrow['day'];
				
				$utoyear = $utoyear-2000;
				$utodate = "$utoday/$utomonth/$utoyear";

				$stchmn  = date("m/y");        // standing charge month
				$lastday = date("t");          // last day of current month
				$stchfr  = "01/$stchmn";       // standing charge from
				$stchto  = "$lastday/$stchmn"; // standing charge to

				if ($prevdate) {
					list($ufromyear,$ufrommonth,$ufromday) = explode("-",$prevdate);
					$ufromyear = $ufromyear - 2000;
					$ufromdate = "$ufromday/$ufrommonth/$ufromyear";
				} else {
					$ufromdate = "";
				}

				$vattotal  = $nettotal/100*$vatRate;
				$vattotal  = round($vattotal, 2);
				$thisvatrate = $vatRate;

				if (($commodity == "Hot") || ($commodity == "Cold")) {
					$sltext = "$commodity Water";
				} else {
					$sltext = "$commodity";
				}

				$linetotal = $nettotal+$vattotal;

				if ($linetotal > 0.00) {
					if ($custref != "20199") {
						// not dephna
						// add the standing charge for meter
						$scvattotal  = 20/100*$vatRate;
						$scvattotal  = round($scvattotal, 2);
						$sclinetotal = 20+$scvattotal;
						$newrecord = "$invnum,$invdate,DP$custref,$sitename Utilities,$sltext $utunit Standing Charge,,,1,20.00,20.00,$sitename Utilities,Vat On Sales,$thisvatrate,$scvattotal,$sclinetotal,$sitename,$sltext,$custname,,,\n";
						fwrite($handle, $newrecord);
						$summary[$sitename] += 24;
						$invoiced++;
					}
					// add the useage charges
					$newrecord = "$invnum,$invdate,DP$custref,$sitename Utilities,$sltext $utunit,$ufromdate,$utodate,$used,$rate,$nettotal,$sitename Utilities,Vat On Sales,$thisvatrate,$vattotal,$linetotal,$sitename,$sltext,$custname,,,\n";
					fwrite($handle, $newrecord);
					$summary[$sitename] += $linetotal;
					$invoiced++;
				}
			}
		}

		// Telephone Calls
		$telresult = $dbcnx->query("SELECT sitename, SUM(totalcharge) AS tcharge FROM TelCallData
			WHERE custref='$custref' AND callyear='$fullyear' AND callmonth='$callsmonth'
			GROUP BY sitename");
		if (($telresult) && ($telresult->num_rows)) {
			while ($telrow = $telresult->fetch_assoc()) {
				$sitename = $telrow['sitename'];
				$nettotal = $telrow['tcharge'];
				$vattotal = $nettotal/100*$vatRate;
				$vattotal  = round($vattotal, 2);
				$linetotal = $nettotal+$vattotal;
				$nettotal  = round($nettotal, 2);
				$linetotal = round($linetotal, 2);
				$thisvatrate = $vatRate;
				$qty = 1;
				$newrecord = "$invnum,$invdate,DP$custref,$sitename Telephone,Call Charges,$callsdatefrom,$callsdateto,$qty,$nettotal,$nettotal,$sitename Telephone,Vat On Sales,$thisvatrate,$vattotal,$linetotal,$sitename,Call Charges,$custname,,,\n";
				fwrite($handle, $newrecord);
				$summary[$sitename] += $linetotal;
				$invoiced++;
			}
		}

		// SRF
		$sql = "SELECT * FROM srf WHERE custref='$custref' AND ayear='$fullyear' AND amonth='$callsmonth'";
		$srfresult = $dbcnx->query($sql);
		if (($srfresult) && ($srfresult->num_rows)) {
			while ($srfrow = $srfresult->fetch_assoc()) {
				$sitename   = $srfrow['sitename'];
				$net        = $srfrow['amount'];
				$srfvat     = $srfrow['vat'];
				$srfdetails = $srfrow['details'];

				// remove any commas from the details
				$srfdetails = str_replace(","," ",$srfdetails);

				$qty = 1;
				$nettotal = $net;
				if ($srfvat == "Yes") {
					$vattotal = $nettotal/100*$vatRate;
					$vattotal = round($vattotal, 2);
					$thisvatrate = $vatRate;
					$sltext = "Service Request";
				} else {
					$vattotal = 0;
					$thisvatrate = 0;
					$sltext = "Service Request0";
				}
				$linetotal = $nettotal+$vattotal;
				$newrecord = "$invnum,$invdate,DP$custref,$sltext,$srfdetails,$callsdatefrom,$callsdateto,$qty,$net,$nettotal,$sitename Car Parking,Vat On Sales,$thisvatrate,$vattotal,$linetotal,$sitename,Service Request,$custname,,,\n";
				fwrite($handle, $newrecord);
				$summary[$sitename] += $linetotal;
				$invoiced++;
			}
		}

		if ($invoiced) {
			$invnum++;
		}
	}
	fclose($handle);
	// chmod($genfile,0666);

	$invSummary  = "<p><b>2. Generate Invoices</b></p>\n";
	$invSummary .= "<table cellspacing=\"2\" cellpadding=\"2\">\n";
	foreach($summary AS $key => $value) {
		$invSummary .= "<tr>\n";
		$invSummary .= "	<td align=\"right\">$key:</td>\n";
		$invSummary .= "	<td align=\"right\">" . number_format($value,2) . "</td>\n";
		$invSummary .= "</tr>\n";
		$total += $value;
	}
	$invSummary .= "<tr>\n";
	$invSummary .= "	<td colspan=\"2\">&nbsp;</td>\n";
	$invSummary .= "</tr>\n";
	$invSummary .= "<tr>\n";
	$invSummary .= "	<td align=\"right\"><b>Total:</b></td>\n";
	$invSummary .= "	<td align=\"right\"><b>" . number_format($total,2) . "</b></td>\n";
	$invSummary .= "</tr>\n";
	$invSummary .= "</table>\n";

} else {
	$errorMessage .= "No Customers Found<br />" . $dbcnx->error();
}
?>
