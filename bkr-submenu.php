<?php
$subsections = array("Bookings" => "bk-manager.php",
					"Agreement" => "bkr-addedit.php?bkrid=$bkrid",
					"Summary" => "bkr-summary.php?bkrid=$bkrid"
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
