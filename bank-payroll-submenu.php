<?php
$subsections = array("Ledgers" => "bank-payroll-ledgers-list.php",
					"Payments" => "bank-payroll-payments-list.php",
					"Defaults" => "bank-payroll-defaults.php",
					"Payroll Run" => "bank-payroll-submit.php",
					"Ni and Paye" => "bank-paye-payments-list.php",
					"Return" => "bank-daybook.php"
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
