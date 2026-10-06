<?php
$subsections = array("Notices" => "mailouts-view.php",
					"Pdf Invoices" => "mailouts-pdf-invs-select.php"
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
