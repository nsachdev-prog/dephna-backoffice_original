<?php

$sql = "SELECT p.txtype, p.suppid, p.net, p.vat, p.total, p.outstanding,
				s.suppname, p.dephnaref, p.year, p.month, p.day
		FROM purchases AS p
		LEFT JOIN suppliers AS s ON s.ID = p.suppid
		WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
			AND CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
		ORDER BY s.suppname, p.year, p.month, p.day
";
	
if ($result = $dbcnx->query($sql) AND $result->num_rows) {

	$csv = "Date,TXType,Our Ref,Supplier,Net,Vat,Total,P/R,Outstanding\r\n";
	$total = 0;

	$txtypes = array(
		"I"=>"Invoice",
		"C"=>"Credit Note",
		"P"=>"Payment",
		"R"=>"Receipt",
		"BP"=>"Payment",
		"BR"=>"Receipt"
	);

	while ($row = $result->fetch_assoc()) {
		$lastsuppid = $suppid;
		$txtype = $row['txtype'];
		$suppid = $row['suppid'];
		$year   = $row['year'];
		$month  = $row['month'];
		$day    = $row['day'];
		$net = $row['net'];
		$vat = $row['vat'];
		$tot = $row['total'];
		$dephnaref = $row['dephnaref'];
		$suppname = $row['suppname'];
		$outstanding = $row['outstanding'];

		if ($suppid != $lastsuppid) {
			$csv .= ",,,,";
			$csv .= "$totnet,";
			$csv .= "$totvat,";
			$csv .= "$tottot,";
			$csv .= "$paytot,";
			$csv .= "$outtot\r\n\r\n";
			$totnet = 0;
			$totvat = 0;
			$tottot = 0;
			$paytot = 0;
			$outtot = 0;
		}

		$csv .= "$day-$month-$year,";
		$csv .= "{$txtypes[$txtype]},";
		$csv .= "$dephnaref,";
		$csv .= "$suppname,";
		if ($txtype == "P" OR $txtype == "R") {
			$csv .= ",";
			$csv .= ",";
			$csv .= ",";
			$csv .= "$tot,";
			$paytot += $tot;
		} else {
			$csv .= "$net,";
			$csv .= "$vat,";
			$csv .= "$tot,";
			$csv .= ",";
			$totnet += $net;
			$totvat += $vat;
			$tottot += $tot;
		}
		$outtot += $outstanding;
		$csv .= "$outstanding\r\n";
		
	}
	$csv .= ",,,,";
	$csv .= "$totnet,";
	$csv .= "$totvat,";
	$csv .= "$tottot,";
	$csv .= "$paytot,";
	$csv .= "$outtot\r\n\r\n";
	$totnet = 0;
	$totvat = 0;
	$tottot = 0;
	$paytot = 0;
	$outtot = 0;

	$zip->addFromString('supplierledgers.csv', $csv);

}

?>