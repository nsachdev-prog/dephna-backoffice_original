<?php
$subsections = array("Guide"           => "pl-guide.php",
					 "Purchases"       => "purchases-ledger.php",
					 "Ledgers"         => "pl-ledgers-list.php",
					 "Pending"         => "purchases-pending.php",
					 "Add Transaction" => "purchases-addedit.php",
					 "Add Statement"   => "pl-statement-addedit.php",
					 "Add Payment"     => "pl-payment-addedit.php",
					 "Add Refund"      => "pl-refund-addedit.php",
					 "Payment Run"     => "purchases-paymentrun.php",
					 "Bank Files"      => "purchases-bankandtally.php",
					 "Fixed Payments"  => "pl-fixed-payments-list.php"
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
