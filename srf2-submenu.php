<?php
$subsections = array("SRF"   => "srf2-list.php",
					"Edit"    => "srf2-addedit.php?id=$id&custref=$custref",
					"Summary" => "srf2-summary.php?id=$id&custref=$custref",
					"Confirm" => "srf2-confirm.php?id=$id&custref=$custref"
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
