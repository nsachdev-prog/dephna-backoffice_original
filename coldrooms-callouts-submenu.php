<?php
$subsections = array(
	"Ongoing"  => "coldrooms-xweb-callouts.php?ty=O",
    "Cleared"  => "coldrooms-xweb-callouts.php?ty=C"
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
