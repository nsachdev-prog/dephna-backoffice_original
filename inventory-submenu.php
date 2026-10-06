<?php
$subsections = array("Inventory" => "inventory-list.php",
					"Edit" => "inventory-addedit.php?id=$id&custref=$custref",
					"Units" => "inventory-units.php?id=$id&custref=$custref",
					"Summary" => "inventory-summary.php?id=$id&custref=$custref",
					"Meeting" => "inventory-meeting.php?id=$id&custref=$custref",
					"Confirm" => "inventory-confirm.php?id=$id&custref=$custref",
					"Delete" => "inventory-delete.php?id=$id&custref=$custref"
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
