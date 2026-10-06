<?php
$subsections = array("Details" => "units-addedit.php?id=$id&amp;unitid=$unitid",
							"Images" => "units-images.php?id=$id&amp;unitid=$unitid",
							"Return" => "units-view.php?id=$id"
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
