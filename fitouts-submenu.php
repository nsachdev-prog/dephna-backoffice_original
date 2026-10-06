<?php
$subsections = array("Quotes" => "fitouts-quotes-list.php",
					"Items" => "fitouts-items-list.php",
					"Archive" => "fitouts-quotes-archive-list.php",
					"Templates" => "fitouts-templates-list.php",
					"Checklist" => "fitouts-checklist-list.php",
					"Calculator" => "fitouts-calculator.php"
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
