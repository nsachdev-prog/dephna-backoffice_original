<?php
$subsections = array(
	"Support"   => "support-list.php?st=Open&amp;ty=Support",
	"Accounts"  => "support-list.php?st=Open&amp;ty=Accounts",
	"Camilla"   => "support-list.php?st=Open&amp;ty=Camilla",
	"Flats"     => "support-list.php?st=Open&amp;ty=Flats",
	"Closed"    => "support-closed-list.php",
	"Snapshot"  => "support-snapshot-detail.php",
	"Templates" => "support-templates-list.php",
	"Settings"  => "support-settings.php"
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
