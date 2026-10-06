<?php
$subsections = array("Ledgers" => "bank-dividends-ledgers-list.php",
					"Dividends Run" => "bank-dividends-submit.php",
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
