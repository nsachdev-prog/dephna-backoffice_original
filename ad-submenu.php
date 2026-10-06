<?php
$subsections = array("Guide" => "ad-guide.php",
				"Debtors"    => "ad-list.php",
				"Payment Plans" => "pplans-list.php",
				"Settings"   => "ad-settings.php",
				"Post DDs"   => "ad-dds-list.php",
				"Bank Files" => "ad-bankfiles.php",
				"Managed"    => "ad-managed.php",
				"Snapshot"   => "ad-snapshot-detail.php"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
if ($accessRights[$page] > 2) {
	if ($subpage == "Reporting") {
		$menustr[] = "<a href=\"ad-reporting.php\"><span class=\"active\">Reporting</span></a>";
	} else {
		$menustr[] = "<a href=\"ad-reporting.php\">Reporting</a>";
	}
}
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
