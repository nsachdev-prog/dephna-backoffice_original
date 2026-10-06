<?php
$subsections = array(
	"Ledger" => "sl-ledger.php?id=$id&$qString",	
	"Details" => "sl-customer-view.php?id=$id",
	"Notes"   => "#",
	"Inv/Cred" => "invoicing-add-invcred.php?custref=$custref",
	"Receipt" => "sl-receipt-addedit.php?id=$id&$qString",
	"Payment" => "sl-payment-addedit.php?id=$id&$qString",
	"Allocations" => "sl-allocations.php?id=$id&$qString",
    "New Allocation" => "sl-receipt-allocate.php?id=$id&hid=0&$qString",
	"Analysis" => "sl-analysis.php?id=$id&$qString"
);
if ($from == "daily") {
	$subsections["Return"] = "sl-daybook-daily.php?$qString";
} elseif ($from == "monthly") {
	$subsections["Return"] = "sl-daybook-monthly.php?$qString";
} else {
	$subsections["Return"] = "sl-ledgers-list.php?$qString";
}
foreach ($subsections AS $key => $value) {
	if ($key == "Notes") {
		$menustr[] = "<a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id=$id','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">Notes</a>";
	}
	elseif ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
