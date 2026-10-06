<?php
$subsections = array("Ledger" => "sl-ledger.php?id=$id&$qString",
					 "Allocations" => "sl-allocations.php?id=$id&$qString",
					 "New Allocation" => "sl-receipt-allocate.php?id=$id&$qString"
					);
foreach ($subsections AS $key => $value) {
	if ($key == $subsubpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;|&nbsp;", $menustr);
?>
