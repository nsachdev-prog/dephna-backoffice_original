<?php
	$subsections = array(
		"Imported" => "utilities-itemised-rayleigh.php",
		"Meters"   => "utilities-meters-list.php",
		"Types"    => "utilities-types-list.php",
		"Gateways" => "utilities-gateways-list.php",
		"Rates"    => "utilities-commodities-view.php",
		"Failures" => "utilities-meters-failures.php",
		"Test Meters" => "utilities-testmeters-list.php"
	);
	// "Previous Charges" => "utilities-rayleigh-charges-last.php",
	foreach ($subsections AS $key => $value) {
		if ($key == $subpage) {
			$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
		} else {
			$menustr[] = "<a href=\"$value\">$key</a>";
		}
	}
	$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);

	// "Report"   => "utilities-reports-used.php"
?>
