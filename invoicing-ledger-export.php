<?php
$page = "Invoicing";
include('settings.php');
include('session.php');
$csv_dir = "../../dis2_db/csv";
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Sales Ledger - Export</title>
		<link href="style.css" rel="stylesheet" type="text/css" />
		<link rel="shortcut icon" href="http://www.dephna.net/backoffice/images/icon.png" />
	</head>
	<body>
	
<?php
print "<table align=\"center\" width=\"600px\">\n";
print "<tr>\n";
print "<td class=\"main\" valign=\"top\">\n";

print "<p>&nbsp;</p>\n";

if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"heading\">SALES LEDGER EXPORT</p>\n";
			$tx = $_POST['tx'];
			if ($tx) {
				$userno = sprintf("%03s",$_SESSION['user']);
				$filename = "LX-".date("Y")."-".date("m")."-".date("d")."-".date("H")."-".date("i")."-".date("s")."-".$userno.".csv";
				$csvfile = "$csv_dir/$filename";
				$handle  = fopen("$csvfile", "w");
				foreach ($tx AS $key => $invnumber) {
					# get tx header
					$sql = "SELECT inv_headers.*, users.compname
							FROM inv_headers
							JOIN users ON inv_headers.custref = users.custref
							WHERE invnumber='$invnumber'";
					$hresult = $dbcnx->query($sql);
					if ($hresult->num_rows) {
						$hrow = $hresult->fetch_assoc();
						$hid = $hrow['ID'];
						$y = $hrow['year'];
						$m = $hrow['month'];
						$d = $hrow['day'];
						$custref  = $hrow['custref'];
						$compname = $hrow['compname'];
						$invtotal = $hrow['invtotal'];
						$invdate  = "$d/$m/$y";
						
						# get tx lines
						$sql = "SELECT * FROM inv_lines WHERE hid='$hid'";
						$lresult = $dbcnx->query($sql);
						if ($lresult->num_rows) {
							while ($lrow = $lresult->fetch_assoc()) {
								$details1  = $lrow['details1'];
								$details2  = $lrow['details2'];
								$letfrom   = $lrow['letfrom'];
								$letto     = $lrow['letto'];
								$lineqty   = $lrow['lineqty'];
								$linerate  = $lrow['linerate'];
								$linenet   = $lrow['linenet'];
								$vatrate   = $lrow['vatrate'];
								$vattotal  = $lrow['vattotal'];
								$linetotal = $lrow['linetotal'];
								$location  = $lrow['location'];
								$office    = $lrow['office'];
								$unittype  = $lrow['unittype'];
								
								$newrecord = "$invnumber,$invdate,DP$custref,$details1,$details2,$letfrom,$letto,$lineqty,$linerate,$linenet,$location $unittype,Vat On Sales,$vatrate,$vattotal,$linetotal,$location,$office,$custname\n";
								fwrite($handle, $newrecord);
								$linesexp++;
							}
						}
						$headsexp++;
						$totvalue += $invtotal;
					} else {
						$resultMessage .= "<b>Could NOT get TX $invnumber</b><br>";
					}
				}
				fclose($handle);
				print "<p align=\"center\"><b>Transactions Exported: $headsexp Value: ".number_format($totvalue,2)."</b></p>";
				print "<p align=\"center\"><a href=\"invcsvdl.php?fl=$filename\">Download</a></p>";
			} else {
				$resultMessage = "<b>No Transactions Selected</b>";
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
} else {
	include ('loggedout.php');
}
print "<p align=\"center\"><a href=\"javascript:window.close()\"><b>Close Window</b></a></p>\n";
print "<p>&nbsp;</p>\n";
print "</td></tr></table>\n";
?>
</body>
</html>
