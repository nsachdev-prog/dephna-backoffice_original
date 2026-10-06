<?php

$sql = "SELECT txtype, year, month, day, amount, dephnaref, bankref, details
		FROM bank
        WHERE CONCAT(year,\"-\",month,\"-\",day) >= '$period_start'
			AND CONCAT(year,\"-\",month,\"-\",day) <= '$period_end'
            AND (BINARY txtype = 'CR' OR BINARY txtype = 'DR')
		    AND costcentre = '57'
		ORDER BY year, month, day
";
	
if ($result = $dbcnx->query($sql) AND $result->num_rows) {

	$csv = "";
	$total = 0;

	while ($row = $result->fetch_assoc()) {
        $txtype = $row['txtype'];
		$year   = $row['year'];
		$month  = $row['month'];
		$day    = $row['day'];
		$amount = $row['amount'];
		$dephnaref = $row['dephnaref'];
        $bankref = $row['bankref'];
        if ($txtype == "CR") {
            $amount *=-1;
        }
		$total += $amount;

		$csv .= "$day-$month-$year,";
		$csv .= "$dephnaref,";
		$csv .= "$bankref,";
		$csv .= "$amount\r\n";
		
	}
	$csv .= ",";
	$csv .= ",";
	$csv .= ",";
	$csv .= "$total\r\n";

	$zip->addFromString('vat.csv', $csv);

}

?>