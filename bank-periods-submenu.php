<?php
$subsections = array(
	"Bank"      => "bank-periods.php",
	"Debtors"   => "sl-periods.php",
	"Creditors" => "pl-periods.php"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subsubpage) {
		$submenustr[] = "<span class=\"active\">$key</span>";
	} else {
		$submenustr[] = "<a href=\"$value\">$key</a>";
	}
}
$submenustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $submenustr);
?>
