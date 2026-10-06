<?php
$subsections = array(
	"Receipts" => "sl-daybook-daily.php",
	"Ledgers" => "sl-ledgers-list.php",
	"Pending" => "sl-pending.php",
	"Add Receipt" => "sl-receipt-addedit.php?$qString",
	"Add Payment" => "sl-payment-addedit.php?$qString",
	"Batch Receipts" => "sl-receipts-batch.php",
	"Bounced DDs" => "sl-bounceddds.php"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;|&nbsp;", $menustr);
?>
