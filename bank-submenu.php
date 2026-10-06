<?php
$subsections = array("Daybook" => "bank-daybook.php",
					"Statement" => "bank-statement.php",
					"Payments" => "bank-payments.php",
					"Ledgers" => "bank-ledgers-list.php",
					"Pending" => "bank-pending.php",
					"Add Transaction" => "bank-addedit.php",
					"Payroll" => "bank-payroll-ledgers-list.php",
					"Dividends" => "bank-dividends-ledgers-list.php",
					"Periods" => "bank-periods.php"
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
