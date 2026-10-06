<?php
# cron-inv-post.php - Written by David Sherwood - www.davidsherwood.co.uk
# Client - Mat Blackmore/Dephna Impex.
# Called by cron-invoicing.php
#
# Creates invoices headers and copies invoice lines from the csv file to the database

# get csv file
$invarray  = array();
$custarray = array();
$newindexfile = $genfile;
if(file_exists($newindexfile)) {
	$newindex = file($newindexfile);
	$stat = stat($newindexfile);
	$generated = date("d/m/Y H:i",$stat['mtime']);

	for ($n=1; $n < count($newindex); $n++) {
		list(
			$invnumber,
			$date,
			$custref,
			$details1,
			$details2,
			$letfrom,
			$letto,
			$lineqty,
			$linerate,
			$linenet,
			$slaesledgertext,
			$vatledgertext,
			$vatrate,
			$vattotal,
			$linetotal,
			$location,
			$office,
			$customername
		) = explode(",",$newindex[$n]);
		$invnumber  = trim($invnumber);
		$invarray[] = $invnumber;
		$custref    = trim($custref);
		$location   = trim($location);
		$office     = trim($office);
		$officearray[$location][] = $office;

		# strip DP from customer reference
		$custref = str_replace("DP","",$custref);
		$custarray[] = $custref;

		# check customer exists
		$sql = "SELECT custref FROM users WHERE custref='$custref'";
		$result = $dbcnx->query($sql);
		if (($result) && ($result->num_rows)) {
			# exists
		} else {
			$errorMessage .= "Customer $custref Does not exist<br />";
		}
	}

	# check locations and offices exist
	foreach ($officearray AS $key => $value) {
		$officearray[$key] = array_unique($officearray[$key]);
	}
	foreach ($officearray AS $location => $offices) {
		$sql = "SELECT ID FROM sites WHERE title='$location'";
		$lresult = $dbcnx->query($sql);
		if (($lresult) && ($lresult->num_rows)) {
			$lrow = $lresult->fetch_assoc();
			$siteid = $lrow['ID'];
			foreach ($offices AS $key => $value) {
				if (($value != "Virtual Office") && ($value != "Car Parking")) {
					$sql = "SELECT ID FROM units WHERE siteid='$siteid' AND title='$value'";
					$oresult = $dbcnx->query($sql);
					if (($oresult) && ($oresult->num_rows)) {
						# exists
					} else {
						# maybe an ancillary
						$tresult = $dbcnx->query("SELECT title FROM ancillaries WHERE title='$value'");
						if (($tresult) && ($tresult->num_rows)) {
							# exists
						} else {
							$errorMessage .= "Unit or Ancillary $value at $location Does not exist<br />";
						}
					}
				}
			}
		} else {
			$errorMessage .= "Site $location Does not exist<br />";
		}
	}

	if (!$errorMessage) {

		$postResult  = "<p><b>3. Post Invoices</b></p>\n";
		$postResult .= "<table cellspacing=\"2\" cellpadding=\"2\">\n";

		# rename the csv file
		$csvfilename = "IR-".date("Y")."-".date("m")."-".date("d")."-".date("H")."-".date("i")."-".date("s")."-000.csv";
		$csvpathname = $csv_dir."/".$csvfilename;
		rename($newindexfile,$csvpathname);

		# delete invoices if already exist
		$invarray  = array_unique($invarray);
		$custarray = array_unique($custarray); # used in mailouts later
		foreach ($invarray AS $key => $value) {
			$sql = "SELECT ID FROM inv_headers WHERE invnumber='$value'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$hid = $row['ID'];
				$sql = "DELETE FROM inv_headers WHERE ID='$hid'";
				$hresult = $dbcnx->query($sql);
				$sql = "DELETE FROM inv_lines WHERE hid='$hid'";
				$lresult = $dbcnx->query($sql);
				if (($hresult) && ($lresult)) {
					$postResult .= "<tr><td>Duplicate Invoice</td><td>$value</td><td colspan=\"4\">Deleted</td></tr>\n";
				} else {
					$postResult .= "<tr><td>Duplicate Invoice</td><td>$value</td><td colspan=\"4\">NOT Deleted</td></tr>\n";
				}
			}
		}

		$grandTotal = 0;

		# get site ids
		$siteids = array();
		$sql = "SELECT ID, title FROM sites";
		$sresult = $dbcnx->query($sql);
		if (($sresult) && ($sresult->num_rows)) {
			while ($srow = $sresult->fetch_assoc()) {
				$siteid = $srow['ID'];
				$stitle = $srow['title'];
				$siteids[$stitle] = $siteid;
			}
		}

		for ($n=1; $n < count($newindex); $n++) {

			$linearray = explode(",",$newindex[$n]);
			foreach($linearray AS $key => $value) {
				$value = trim($value);
			}
			$thisline = implode(",",$linearray);

			list(
				$invnumber,
				$date,
				$custref,
				$details1,
				$details2,
				$letfrom,
				$letto,
				$lineqty,
				$linerate,
				$linenet,
				$slaesledgertext,
				$vatledgertext,
				$vatrate,
				$vattotal,
				$linetotal,
				$location,
				$office,
				$customername
			) = explode(",",$thisline);

			if ($invnumber) {

				list($pday,$pmonth,$pyear) = explode ("/",$date);
				$pyear = "20" . $pyear;

				# strip DP from customer reference
				$custref = str_replace("DP","",$custref);

				# get unit type
				if (($office == "Virtual Office") || ($office == "Car Parking")) {
					$unittype = $office;
				} else {
					$sql = "SELECT unittype FROM units WHERE siteid='{$siteids[$location]}' AND title='$office'";
					$tresult = $dbcnx->query($sql);
					if (($tresult) && ($tresult->num_rows)) {
						$trow = $tresult->fetch_assoc();
						$unittype = $trow['unittype'];
					} else {
						# must be an ancillary
						$tresult = $dbcnx->query("SELECT ancgroup FROM ancillaries WHERE title='$office'");
						if (($tresult) && ($tresult->num_rows)) {
							$trow = $tresult->fetch_assoc();
							$unittype = $trow['ancgroup'];
						} else {
							$unittype = "Unknown";
						}
					}
				}

				$details1 = addslashes($details1);
				$details2 = addslashes($details2);
				$now = time();
				$sql = "SELECT ID FROM inv_headers WHERE invnumber='$invnumber'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$hid = $row['ID'];
				} else {
					$sql = "INSERT INTO inv_headers VALUES (
						null,
						'I',
						'$invnumber',
						'$pyear',
						'$pmonth',
						'$pday',
						'$custref',
						'0',
						'0',
						'',
						'',
						'$now',
						'System'
					)";
					$result = $dbcnx->query($sql);
					if ($result) {
						$prinvtot = 0;
						$postResult .= "<tr><td>Invoice</td><td>$invnumber</td><td>DP$custref</td><td colspan=\"3\">Created</td></tr>\n";
						$hid = $dbcnx->insert_id;
						$doneheaders++;
					} else {
						$postResult = "<tr><td>Invoice</td><td>$invnumber</td><td>DP$custref</td><td colspan=\"3\">Could NOT create header</td></tr>\n";
					}
				}
				if ($hid) {
					$sql = "INSERT INTO inv_lines VALUES (
						null,
						'$hid',
						'$details1',
						'$details2',
						'$letfrom',
						'$letto',
						'$lineqty',
						'$linerate',
						'$linenet',
						'$vatrate',
						'$vattotal',
						'$linetotal',
						'$location',
						'$office',
						'$unittype'
					)";
					$result = $dbcnx->query($sql);
					if ($result) {
						$donelines++;
					} else {
						$postResult .= "<tr><td>Invoice</td><td>$invnumber</td><td colspan=\"4\">Could NOT add line</td></tr>\n";
					}
					$sql = "UPDATE inv_headers SET invtotal=invtotal+'$linetotal', outstanding=outstanding+'$linetotal' WHERE ID='$hid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						# cool, $postResult .= "<tr><td>Invoice</td><td>$invnumber</td><td colspan=\"4\">Total added to header</td></tr>\n";
					} else {
						$postResult .= "<tr><td>Invoice</td><td>$invnumber</td><td colspan=\"4\">Could NOT add total to header</td></tr>\n";
					}
				}
				$grandTotal += $linetotal;
			} else {
				$postResult .= "<tr><td>Line</td><td>$n</td><td colspan=\"4\">No invoice number - Line ignored</td></tr>\n";
			}
		}
		$postResult .= "</table>\n<p>$doneheaders invoices with $donelines lines added.<br />";
		$postResult .= "With total value of <b>&pound;". number_format($grandTotal,2)."</b></p>";
		$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Created','$doneheaders Invs - Value $grandTotal','Invoices','$now')");

		# Mark utilities as invoiced.
		$utlmonth = $pmonth - 1;
		if ($utlmonth == 0) {
			$utlmonth = 12;
			$utlyear  = $pyear - 1;
		} else {
			$utlyear = $pyear;
		}
		$utlmonth = sprintf("%02s",$utlmonth);
		$utlyear = sprintf("%02s",$utlyear);
		$sql = "UPDATE UtlUsedNew SET invoice = 'Yes' WHERE year = '$utlyear' AND month = '$utlmonth'";
		$utlresult = $dbcnx->query($sql);

		# Reset Pro-Rata Overrides
		list($pday,$pmonth,$pyear) = explode ("/",$date);
		$now = time();
		$sql = "UPDATE bookings SET prfrom='', prto='' WHERE prfrom < '$now'";
		$prresult = $dbcnx->query($sql);
		if ($prresult) {
			$postResult .= "<p>Unit Pro-Rata Overrides Reset</p>\n";
		} else {
			$postResult .= "<p>Could NOT Reset Unit Pro-Rata Overrides</p>\n";
		}
		$sql = "UPDATE vbookings SET prfrom='', prto='' WHERE prfrom < '$now'";
		$prvresult = $dbcnx->query($sql);
		if ($prvresult) {
			$postResult .= "<p>Virtuals Pro-Rata Overrides Reset</p>\n";
		} else {
			$postResult .= "<p>Could NOT Reset Unit Pro-Rata Overrides</p>\n";
		}
	}
} else {
	$errorMessage .= "Invoices file has not been generated or invoices already posted<br />";
}
?>
