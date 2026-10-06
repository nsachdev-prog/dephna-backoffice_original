<?php
$subsections = array("Bookings" => "bk-manager.php",
					"Services" => "bka-addedit.php?bkaid=$bkaid",
					"Contract" => "bka-contract.php?bkaid=$bkaid",
					"Summary" => "bka-summary.php?bkaid=$bkaid"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
