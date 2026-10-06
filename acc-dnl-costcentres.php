<?php

$sql = "SELECT l.ccid, c.title, c.ccgroup, c.tbgroup
		FROM purchases_lines AS l
		LEFT JOIN purchases AS p ON p.ID = l.hid
		LEFT JOIN costcentres AS c ON c.ID = l.ccid
		WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
			AND CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
		GROUP BY l.ccid
		ORDER BY l.ccid
";

if ($ccresult = $dbcnx->query($sql) AND $ccresult->num_rows) {

	$txtypes = array(
		"I"=>"Invoice",
		"C"=>"Credit Note",
		"P"=>"Payment",
		"R"=>"Receipt",
		"BP"=>"Payment",
		"BR"=>"Receipt"
	);

	$ccgroups = array(
		"1"=>"Cost of Sales",
		"2"=>"Administrative Expenses",
		"3"=>"Statement Only Items",
	);

	$tbgroups = array(
		"0"=>"Not Applicable",
		"1"=>"Direct Expenses",
		"2"=>"Indirect Expenses"
	);

	while ($ccrow = $ccresult->fetch_assoc()) {
		$ccid = $ccrow['ccid'];
		$cctitle = $ccrow['title'];
		$ccgroup = $ccrow['ccgroup'];
		$tbgroup = $ccrow['tbgroup'];

		$csv .= "$cctitle - {$ccgroups[$ccgroup]} - {$tbgroups[$tbgroup]},";
		$csv .= ",";
		$csv .= ",";
		$csv .= ",";
		$csv .= ",";
		$csv .= ",";
		$csv .= "\r\n";

		$sql = "SELECT l.net, l.vat, l.total, p.txtype,
						s.suppname, p.dephnaref, p.year, p.month, p.day
				FROM purchases_lines AS l
				LEFT JOIN purchases AS p ON p.ID = l.hid
				LEFT JOIN suppliers AS s ON s.ID = p.suppid
				WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
					AND CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
					AND l.ccid = '$ccid'
				ORDER BY p.year, p.month, p.day
		";
	
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			$totnet = 0;
			$totvat = 0;
			$tottot = 0;

			while ($row = $result->fetch_assoc()) {
				$lastsuppid = $suppid;
				$txtype = $row['txtype'];
				$suppid = $row['suppid'];
				$year   = $row['year'];
				$month  = $row['month'];
				$day    = $row['day'];
				$net    = $row['net'];
				$vat    = $row['vat'];
				$tot    = $row['total'];
				$dephnaref = $row['dephnaref'];
				$suppname = $row['suppname'];
				$totnet += $net;
				$totvat += $vat;
				$tottot += $tot;

				$csv .= "$day-$month-$year,";
				$csv .= "{$txtypes[$txtype]},";
				$csv .= "$dephnaref,";
				$csv .= "$suppname,";
				$csv .= "$net,";
				$csv .= "$vat,";
				$csv .= "$tot\r\n";
			
			}

			$csv .= ",";
			$csv .= ",";
			$csv .= ",";
			$csv .= ",";
			$csv .= "$totnet,";
			$csv .= "$totvat,";
			$csv .= "$tottot\r\n\r\n";

		}

	}

	$zip->addFromString('costcentres.csv', $csv);

}

?>