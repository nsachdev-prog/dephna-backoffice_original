<?php
$page = "Bookings";
$virtuals = 1;
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
$bkid    = $_REQUEST['bkid'];
$posted   = $_POST['posted'];
$anposted  = $_POST['anposted'];
$anupdate  = $_POST['anupdate'];
$andelete  = $_GET['and'];
if ($bkid) { $thist = "Update"; } else { $thist = "New"; }
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# ancillary update section
			if ($anposted) {
				$ancid   = $_POST['ancid'];
				$custref = $_POST['custref'];
				# get ancillary details
				$sql = "SELECT * FROM ancillaries WHERE ID='$ancid'";
				$anresult = $dbcnx->query($sql);
				if (($anresult) && ($anresult->num_rows)) {
					$anrow = $anresult->fetch_assoc();
					$anctitle = $anrow['title'];
					$ancrate  = $anrow['rate'];
					$mvat     = $anrow['mvat'];
					$sql = "INSERT INTO vbookings_an VALUES (null,'$bkid','$custref','$ancid','$anctitle','1','$ancrate','$mvat','Month')";
					$anresult = $dbcnx->query($sql);
				}
			} elseif ($anupdate) {
				$ancid  = $_POST['ancid'];
				$ancqty = $_POST['ancqty'];
				$ancrate = $_POST['ancrate'];
				if (!$ancqty) { $eranc = "Please give a qty"; $eanc++; }
				elseif (!is_numeric($ancqty)) { $eranc = "Qty must be numeric"; $eanc++; }
				if (!$ancrate) { $eranc = "Please give a rate"; $eanc++; }
				elseif (!is_numeric($ancrate)) { $eranc = "Rate must be numeric"; $eanc++; }
				else {
					$sql = "UPDATE vbookings_an SET qty='$ancqty', rate='$ancrate' WHERE ID='$ancid'";
					$anresult = $dbcnx->query($sql);
				}
			} elseif ($andelete) {
				$ancid = $_GET['ancid'];
				$sql = "DELETE FROM vbookings_an WHERE ID='$ancid'";
				$anresult = $dbcnx->query($sql);
			}

			# print heading
			print "<p align=\"center\" class=\"heading\">$thist Virtual Office</p>\n";

			if ($posted) {
				# error checking
				$bkrate    = strip_tags($_POST['bkrate']);
				$bkper     = $_POST['bkper'];
				$leadmonth = $_POST['leadmonth'];
				$notes     = strip_tags($_POST['notes']);
				$bkvat     = $_POST['bkvat'];

				$package   = $_POST['package'];
				$custref   = $_POST['custref'];
				$site      = $_POST['site'];

				$sday      = $_POST['sday'];
				$smonth    = $_POST['smonth'];
				$syear     = $_POST['syear'];
				if (($sday) || ($smonth) || ($syear)) {
					if (!checkdate($smonth,$sday,$syear)) { $ersdate = "Invalid Date"; $e++; }
				}

				$eday      = $_POST['eday'];
				$emonth    = $_POST['emonth'];
				$eyear     = $_POST['eyear'];
				if (($eday) || ($emonth) || ($eyear)) {
					if (!checkdate($emonth,$eday,$eyear)) { $eredate = "Invalid Date"; $e++; }
				}

				if (!$bkrate) { $errate = "Please give a rate"; $e++; }
				elseif (!is_numeric($bkrate)) { $errate = "Price must be numeric"; $e++; }

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

				$fim       = $_POST['fim'];
				$fiy       = $_POST['fiy'];
				if (($fim) && ($fiy)) {
					$fip = strtotime("$fiy/$fim/01");
				}

				if (!$e) {
					# post to database
					$notes = addslashes($notes);

					list($siteid,$sitename) = explode("--",$site);
					list($pkid,$pkname)     = explode("--",$package);

					if ($sday) {
						$datefrom = $syear."/".$smonth."/".$sday;
						$datefrom = strtotime("$datefrom");
					}
					if ($eday) {
						$dateto   = $eyear."/".$emonth."/".$eday;
						$dateto   = strtotime("$dateto");
					}

					if ($bkid) {
						$sql = "UPDATE vbookings SET
						pkid='$pkid',
						pkname='$pkname',
						siteid='$siteid',
						sitename='$sitename',
						custref='$custref',
						datefrom='$datefrom',
						dateto='$dateto',
						rate='$bkrate',
						per='$bkper',
						lead='$leadmonth',
						vat='$bkvat',
						prfrom='$prfrom',
						prto='$prto',
						notes='$notes',
						fip='$fip'
						WHERE ID='$bkid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename - $pkname','Bookings','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$now = time();
						$sql = "INSERT INTO vbookings VALUES (
						null,
						'$pkid',
						'$pkname',
						'$siteid',
						'$sitename',
						'$custref',
						'$datefrom',
						'$dateto',
						'$bkrate',
						'$bkper',
						'$leadmonth',
						'$bkvat',
						'$prfrom',
						'$prto',
						'$notes',
						'$fip'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Booking successfully added";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename - $pkname','Bookings','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			} elseif ($bkid) {
				# get existing booking details
				$sql = "SELECT * FROM vbookings WHERE ID = '$bkid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$pkid     = $row['pkid'];
					$pkname   = $row['pkname'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
					$custref  = $row['custref'];
					$datefrom = $row['datefrom'];
					$dateto   = $row['dateto'];
					$bkrate   = $row['rate'];
					$bkper    = $row['per'];
					$leadmonth = $row['lead'];
					$bkvat    = $row['vat'];
					$notes    = $row['notes'];
					$prfrom   = $row['prfrom'];
					$prto     = $row['prto'];
					$fip      = $row['fip'];
					if ($fip) { $fim = date("n",$fip); $fiy = date("Y",$fip); }
					if ($datefrom) { $sday   = date("d",$datefrom); $smonth = date("m",$datefrom); $syear  = date("Y",$datefrom); }
					if ($dateto) { $eday   = date("d",$dateto); $emonth = date("m",$dateto); $eyear  = date("Y",$dateto); }
					if ($prfrom) {   $pdayfr = date("d",$prfrom);   $pmonth = date("m",$prfrom);   $pyear  = date("Y",$prfrom); }
					if ($prto)   {   $pdayto = date("d",$prto); }
				} else {
					$resultMessage = "Booking not found!<br />" . $dbcnx->error;
				}
			}

			if ((!$resultMessage) || ($e)) {

				# print the form
				print "<form name=\"addform\" method=\"post\" action=\"bookings-virtuals-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
				print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

				# get packages
				$sql = "SELECT ID, title FROM virtuals ORDER BY ID";
				$vpsresult = $dbcnx->query($sql);

				# get sites
				$sql = "SELECT ID, title FROM sites ORDER BY title";
				$sitesresult = $dbcnx->query($sql);

				print "<tr>\n";
				print "	<td align=\"right\">Site:</td>\n";
				print "	<td><select name=\"site\" class=\"element\">\n";
				while ($srow = $sitesresult->fetch_assoc()) {
					$thissiteid = $srow['ID'];
					$sitename   = $srow['title'];
					print "		<option value=\"$thissiteid--$sitename\"";
					if ($thissiteid == $siteid) {
						print " SELECTED";
					}
					print ">$sitename</option>\n";
				}
				print "	</select>";
				if ($ersite) { print "&nbsp;<span class=\"redtext\">$ersite</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\">Package:</td>\n";
				print "	<td><select name=\"package\" class=\"element\">\n";
				while ($prow = $vpsresult->fetch_assoc()) {
					$thispkid = $prow['ID'];
					$pkname   = $prow['title'];
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

				if ($bkid) {
					$sql = "SELECT compname FROM users WHERE custref='$custref'";
					$custsresult = $dbcnx->query($sql);
					if (($custsresult) && ($custsresult->num_rows)) {
						$crow = $custsresult->fetch_assoc();
						$custname = $crow['compname'];
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						print "<tr>\n";
						print "	<td align=\"right\">Customer:</td>\n";
						print "	<td>$custname</td>\n";
						print "</tr>\n";
					}
				} else {
					$sql = "SELECT custref, compname FROM users WHERE level='2' ORDER BY compname";
					$custsresult = $dbcnx->query($sql);
					print "<tr>\n";
					print "	<td align=\"right\">Customer:</td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					while ($crow = $custsresult->fetch_assoc()) {
						$thiscustref = $crow['custref'];
						$custname    = $crow['compname'];
						print "		<option value=\"$thiscustref\"";
						if ($thiscustref == $custref) {
							print " SELECTED";
						}
						print ">$custname</option>\n";
					}
					print "	</select>";
					if ($ercustomer) { print "&nbsp;<span class=\"redtext\">$ercustomer</span>"; }
					print "</td>\n";
					print "</tr>\n";
				}

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

				$sql = "SELECT title, rate FROM virtuals ORDER BY ID";
				$ratesresult = $dbcnx->query($sql);
				if (($ratesresult) && ($ratesresult->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\">Standard Rates:</td>\n";
					print "	<td>\n";
					print "	<table>\n";
					while ($rrow = $ratesresult->fetch_assoc()) {
						$thistitle  = $rrow['title'];
						$thisrate   = $rrow['rate'];
						print "	<tr>\n";
						print "	<td align=\"right\">$thistitle</td>\n";
						print "	<td align=\"right\">" . number_format($thisrate,2) . "</td>\n";
						print "	<td align=\"right\">Per Month</td>\n";
						print "	</tr>\n";
					}
					print "</table>\n";
					print "	<td>\n";
					print "</tr>\n";
				}

				print "<tr>\n";
				print "	<td align=\"right\">Booking Rate:</td>\n";
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
				print "	<td valign=\"top\" align=\"right\"><b>Billing Period</b></td>\n";
				print "	<td>If not monthly you must select a billing month.<br />If Quarterly, select the first of the 4 billing months.<br />If Yearly, select the only billing month of the year.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\">Billing Period:</td>\n";
				print "	<td><select name=\"bkper\" class=\"element\">\n";
				print "		<option value=\"Monthly\"";   if ($bkper == "Month")     { print " SELECTED"; } print ">Monthly</option>\n";
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

					print "<p align=\"center\" class=\"heading\">Ancillary Charges</p>\n";

					# get current charges
					$sql = "SELECT * FROM vbookings_an WHERE bkid='$bkid' ORDER BY anctitle";
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
						while ($rrow = $anresult->fetch_assoc()) {
							$ancid    = $rrow['ID'];
							$anctitle = $rrow['anctitle'];
							$ancqty   = $rrow['qty'];
							$ancrate  = $rrow['rate'];
							$ancvat   = $rrow['vat'];
							print "<tr>\n";
							print "	<td class=\"col\">$anctitle</td>\n";
							print "	<td class=\"col\">$ancqty</td>\n";
							print "	<td class=\"col\" align=\"right\">" . number_format($ancrate,2) . "</td>\n";
							print "	<td class=\"col\">$ancvat</td>\n";
							print " <td class=\"colElement\">";
							print "<form name=\"updancform\" method=\"post\" action=\"bookings-virtuals-addedit.php\">";
							print "<input type=\"hidden\" name=\"anupdate\" value=\"1\" />";
							print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />";
							print "<input type=\"hidden\" name=\"ancid\" value=\"$ancid\" />";
							print "&nbsp;<input name=\"ancqty\" type=\"text\" class=\"element_nb2\" maxlength=\"4\" value=\"$ancqty\" />&nbsp;&nbsp;<input name=\"ancrate\" type=\"text\" class=\"element_nb\" maxlength=\"10\" value=\"$ancrate\" /><input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Submit\" />";
							print "</form>\n";
							print "</td>\n";
							print "	<td class=\"colEnd\" align=\"center\"><a href=\"bookings-virtuals-addedit.php?bkid=$bkid&amp;ancid=$ancid&amp;and=1\">Delete</a></td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
						print "<br />&nbsp;\n";
					} else {
						print "<p align=\"center\">None Found</p>\n";
					}

					# add ancillary charge
					$sql = "SELECT ID, title FROM ancillaries ORDER BY title";
					$anresult = $dbcnx->query($sql);
					if (($anresult) && ($anresult->num_rows)) {
						print "<form name=\"addancform\" method=\"post\" action=\"bookings-virtuals-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"anposted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
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
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-virtuals-view.php?custref=$custref\">Virtual Offices</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
