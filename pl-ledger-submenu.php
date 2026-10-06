<?php
$subsections = array("Ledger" => "pl-ledger.php?id=$id&$qString",	
					"Details" => "suppliers-view.php?id=$id",
					"Add Payment" => "pl-payment-addedit.php?id=$id&$qString",
					"Add Refund" => "pl-refund-addedit.php?id=$id&$qString",
					"Allocations" => "pl-allocations.php?id=$id&$qString",
                    "New Allocation" => "pl-payment-allocate.php?id=$id&hid=0&$qString"
);
if ($from == "purchases") {
	$subsections["Return"] = "purchases-ledger.php?$qString";
} else {
	$subsections["Return"] = "pl-ledgers-list.php?$qString";
}
foreach ($subsections AS $key => $value) {
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;|&nbsp;", $menustr);
?>
