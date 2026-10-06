<?php
$subsections = array("Stock"    => "cl-stock-list.php",
					 "Orders"   => "cl-porders-list.php",
					 "Products" => "cl-products-list.php"
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
